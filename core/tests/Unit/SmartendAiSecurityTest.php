<?php

use App\Services\Ai\AiHtmlSanitizer;
use App\Services\Ai\HtmlTranslationService;
use App\Services\Ai\PromptLoader;
use App\Services\Ai\SafeImageValidator;
use Tests\TestCase;

uses(TestCase::class);

test('AI HTML sanitizer removes executable and unsafe markup while preserving allowlisted semantics', function () {
    $sanitizer = app(AiHtmlSanitizer::class);
    $clean = $sanitizer->sanitize('<section class="container evil" onclick="run()"><h2>Heading</h2><script>alert(1)</script><style>body{display:none}</style><iframe src="https://evil.test"></iframe><form><input></form><img src="https://evil.test/a.png"><a href="javascript:alert(1)" data-x="1">Bad</a><a href="https://example.com" target="_blank">Good</a></section>');

    expect($clean)->toContain('<section class="container">')
        ->and($clean)->toContain('<h2>Heading</h2>')
        ->and($clean)->toContain('href="https://example.com"')
        ->and($clean)->toContain('rel="noopener noreferrer"')
        ->and($clean)->not->toContain('<script')
        ->and($clean)->not->toContain('<style')
        ->and($clean)->not->toContain('<iframe')
        ->and($clean)->not->toContain('<form')
        ->and($clean)->not->toContain('<img')
        ->and($clean)->not->toContain('javascript:')
        ->and($clean)->not->toContain('onclick')
        ->and($clean)->not->toContain('data-x')
        ->and($clean)->not->toContain(' evil');

    $designed = $sanitizer->sanitize('<section class="container py-5 bg-light"><div class="row gap-3"><div class="col-lg-4 card shadow-sm h-100"><a class="btn btn-primary" href="/contact">Contact</a></div></div></section>');
    expect($designed)
        ->toContain('class="container py-5 bg-light"')
        ->toContain('class="row gap-3"')
        ->toContain('class="col-lg-4 card shadow-sm h-100"')
        ->toContain('class="btn btn-primary"');
});

test('prompt loader JSON encodes values literally and rejects unresolved placeholders', function () {
    $loader = app(PromptLoader::class);
    $prompt = $loader->load('write-topic', [
        'target' => 'details',
        'format' => 'plain',
        'tone' => 'professional',
        'content_length' => 'short',
        'context' => ['$1', '\\path', '{{ignored}}'],
        'content' => ['value' => 'Hello'],
    ]);

    expect($prompt)->toContain('"$1"')
        ->and($prompt)->toContain('"\\\\path"')
        ->and($prompt)->toContain('"{{ignored}}"');

    expect(fn () => $loader->load('write-topic', []))->toThrow(RuntimeException::class, 'Unresolved AI prompt placeholder');
});

test('HTML translation reconstructs only text nodes and preserves sanitized structure', function () {
    $service = app(HtmlTranslationService::class);
    $source = '<section class="row evil"><p id="remove" onclick="run()">Hello <strong>world</strong>.</p><a href="https://example.com/path" title="Keep">Read more</a></section>';
    $segments = $service->segments('details', $source, true);
    $translated = collect($segments)->mapWithKeys(fn (array $segment) => [
        $segment['id'] => match (trim($segment['text'])) {
            'Hello' => 'Bonjour ',
            'world' => 'monde',
            '.' => '.',
            'Read more' => 'Lire la suite',
            default => $segment['text'],
        },
    ])->all();
    $result = $service->reconstruct('details', $source, $translated, true);

    expect($result)->toContain('<section class="row">')
        ->and($result)->toContain('<strong>monde</strong>')
        ->and($result)->toContain('href="https://example.com/path"')
        ->and($result)->toContain('title="Keep"')
        ->and($result)->toContain('Lire la suite')
        ->and($result)->not->toContain('onclick')
        ->and($result)->not->toContain('id="remove"')
        ->and($result)->not->toContain('evil');
});

test('generated image validation accepts supported image containers and rejects appended polyglots', function () {
    $validator = app(SafeImageValidator::class);
    $formats = [
        'image/png' => IMG_PNG,
        'image/jpeg' => IMG_JPG,
    ];
    if (function_exists('imagewebp')) {
        $formats['image/webp'] = IMG_WEBP;
    }

    foreach ($formats as $mime => $type) {
        $image = imagecreatetruecolor(2, 2);
        imagefill($image, 0, 0, imagecolorallocate($image, 20, 80, 120));
        ob_start();
        match ($type) {
            IMG_PNG => imagepng($image),
            IMG_JPG => imagejpeg($image),
            IMG_WEBP => imagewebp($image),
        };
        $bytes = (string) ob_get_clean();
        imagedestroy($image);

        expect($validator->validate($bytes, $mime))->toBe([$mime, 2, 2]);
        expect(fn () => $validator->validate($bytes.'<?php echo 1; ?>', $mime))->toThrow(RuntimeException::class);
    }
});

test('Topic photo ALT uses localized text then localized title and finally the default title', function () {
    $topic = (object) [
        'photo_alt_fr' => '<b>Portrait de l’équipe</b>',
        'title_fr' => 'Équipe',
        'title_en' => 'Team',
    ];
    expect(Helper::topicPhotoAlt($topic, 'fr'))->toBe('Portrait de l’équipe');

    $topic->photo_alt_fr = '';
    expect(Helper::topicPhotoAlt($topic, 'fr'))->toBe('Équipe');

    $topic->title_fr = '';
    expect(Helper::topicPhotoAlt($topic, 'fr'))->toBe('Team');
});
