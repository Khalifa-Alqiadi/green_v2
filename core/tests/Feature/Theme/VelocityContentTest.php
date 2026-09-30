<?php

use App\Models\Topic;
use App\Services\ThemeService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Tests\Support\VelocityContentSeeder;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    config(['smartend.theme' => 'velocity']);
    ThemeService::flushRuntimeCache();
});

afterEach(function () {
    ThemeService::flushRuntimeCache();
});

test('velocity homepage renders every CMS row with empty data safely', function () {
    $html = view('themes.velocity.home', [
        'PageTitle' => 'Velocity home',
        'Topic' => null,
        'Popup' => null,
        'page_type' => 'home',
        'page_id' => 0,
    ])->render();

    expect($html)
        ->toContain('data-theme="velocity"')
        ->toContain('velocity-home');
});

test('velocity landing page keeps custom hooks when there are no visible blocks', function () {
    $topic = (new Topic)->forceFill([
        'css_code' => '.landing-test { color: red; }',
        'js_code' => '<script>window.velocityLanding = true;</script>',
        'body_code' => '<div data-landing-body>Body hook</div>',
    ]);
    $topic->setRelation('topicBlocks', collect());

    $html = view('themes.velocity.landing.page', [
        'PageTitle' => 'Velocity landing',
        'Topic' => $topic,
        'WebmasterSection' => null,
        'Popup' => null,
        'page_type' => 'topic',
        'page_id' => 1,
    ])->render();

    expect($html)
        ->toContain('velocity-landing-page')
        ->toContain('velocity-page-heading--gradient')
        ->toContain('<h1>Velocity landing</h1>')
        ->toContain('data-velocity-landing-css')
        ->toContain('window.velocityLanding')
        ->toContain('data-landing-body');
});

test('velocity landing pages add a normal cover unless their first block is a slider', function () {
    $landing = File::get(resource_path('views/themes/velocity/landing/page.blade.php'));

    expect($landing)
        ->toContain('$velocityFirstBlock = $velocityBlocks->first();')
        ->toContain('$velocityLandingStartsWithSlider')
        ->toContain("\$velocityFirstBannerStyle === 'slider'")
        ->toContain('@unless($velocityLandingStartsWithSlider)')
        ->toContain("'variant' => \$velocityLandingCoverMedia ? 'media' : 'gradient'")
        ->toContain('$Topic->photo_file ?: ($WebmasterSection->photo ?? null)');
});

test('velocity landing pages do not add a cover before a first slider block', function () {
    $topic = (new Topic)->forceFill([
        'title_en' => 'Slider landing',
    ]);
    $topic->setRelation('topicBlocks', collect([
        (object) [
            'status' => 1,
            'type' => 2,
            'content' => json_encode([
                'banner_area_id' => 999999,
                'banner_style' => 'Slider',
            ]),
        ],
    ]));

    $html = view('themes.velocity.landing.page', [
        'PageTitle' => 'Slider landing',
        'Topic' => $topic,
        'WebmasterSection' => null,
        'Popup' => null,
        'page_type' => 'topic',
        'page_id' => 1,
    ])->render();

    expect($html)
        ->toContain('velocity-landing-page')
        ->not->toContain('velocity-page-heading');
});

test('velocity desktop card and testimonial carousels fit three complete slides', function () {
    $styles = File::get(resource_path('themes/velocity/css/components.css'));

    expect($styles)
        ->toContain('@media (min-width: 64rem)')
        ->toContain(".velocity-carousel--cards,\n        .velocity-carousel--testimonials")
        ->toContain('grid-auto-columns: calc((100% - 3rem) / 3);')
        ->toContain('.velocity-carousel--testimonials > .velocity-quote')
        ->toContain('overflow-wrap: anywhere;');
});

test('velocity hero carousel has no visible gap between slides', function () {
    $styles = File::get(resource_path('themes/velocity/css/components.css'));

    expect($styles)
        ->toContain('[data-theme="velocity"] .velocity-carousel--hero {')
        ->toContain(".velocity-carousel--hero {\n        gap: 0;");
});

test('velocity homepage block actions move beside headings only on large screens', function () {
    $section = File::get(resource_path('views/themes/velocity/components/landing-section.blade.php'));
    $carousel = File::get(resource_path('views/themes/velocity/landing/carousel.blade.php'));
    $styles = File::get(resource_path('themes/velocity/css/components.css'));

    expect($section)
        ->toContain('velocity-section-layout')
        ->toContain('velocity-section-actions')
        ->toContain('{{ $actions }}')
        ->and($carousel)
        ->toContain("@slot('actions')")
        ->toContain("components.carousel-controls")
        ->toContain("components.more-link")
        ->and($styles)
        ->toContain('@media (min-width: 64rem)')
        ->toContain('.velocity-section-actions {')
        ->toContain('grid-template-columns: minmax(0, 1fr) min(34rem, 44%);')
        ->toContain('.velocity-section-actions--carousel')
        ->toContain('justify-content: flex-end;');
});

test('velocity text banner icons use a bounded theme glyph instead of legacy size utilities', function () {
    $banners = File::get(resource_path('views/themes/velocity/layouts/banners.blade.php'));
    $styles = File::get(resource_path('themes/velocity/css/components.css'));

    expect($banners)
        ->toContain('{{ $velocityBanner->icon }} velocity-feature__glyph')
        ->not->toContain('fa-3x')
        ->and($styles)
        ->toContain('.velocity-feature__glyph')
        ->toContain('font-size: 2rem;')
        ->toContain('max-width: 100%;');
});

test('velocity renders the populated landing block inventory', function () {
    $this->seed(VelocityContentSeeder::class);

    $topic = Topic::query()
        ->with('topicBlocks')
        ->where('seo_url_slug_en', 'velocity-test-topic-10-primary')
        ->firstOrFail();

    $html = view('themes.velocity.landing.page', [
        'PageTitle' => 'Velocity populated landing',
        'Topic' => $topic,
        'WebmasterSection' => $topic->webmasterSection,
        'Popup' => null,
        'page_type' => 'topic',
        'page_id' => $topic->id,
    ])->render();

    expect($html)
        ->toContain('data-velocity-landing-block')
        ->toContain('velocity-test-section')
        ->toContain('data-velocity-test="code"')
        ->toContain('data-velocity-native-form')
        ->toContain('velocity-carousel');
});

test('velocity landing news presents a featured story and compact editorial list', function () {
    $news = File::get(resource_path('views/themes/velocity/landing/news.blade.php'));
    $styles = File::get(resource_path('themes/velocity/css/components.css'));

    expect($news)
        ->toContain('velocity-news-layout__lead')
        ->toContain('velocity-news-layout__item')
        ->toContain('velocity-news-layout__list" role="list"')
        ->toContain("'variant' => 'news'")
        ->toContain("'showMeta' => true")
        ->toContain('role="listitem"')
        ->not->toContain("'compact' => true")
        ->and($styles)
        ->toContain('.velocity-news-layout__lead .velocity-topic-card')
        ->toContain('grid-auto-rows: minmax(8.25rem, 1fr);')
        ->toContain('.velocity-news-layout__item .velocity-topic-card')
        ->toContain(':not(:has(.velocity-topic-card__media))');

    expect(File::get(resource_path('views/themes/velocity/components/topic-card.blade.php')))
        ->toContain("'featured', 'news'");
});

test('native forms preserve unenhanced phone values', function () {
    $this->from('/contact')->post(route('contactPageSubmit'), [
        'contact_name' => 'Velocity Contact',
        'contact_phone' => '+20 100 000 0000',
        'contact_email' => 'velocity-contact@example.test',
        'contact_subject' => 'Velocity native contact',
        'contact_message' => 'Native contact form submission.',
    ])->assertRedirect('/contact')
        ->assertSessionHas('frontendFormFeedback.status', 'success');

    $this->assertDatabaseHas('webmails', [
        'from_email' => 'velocity-contact@example.test',
        'from_phone' => '20 100 000 0000',
    ]);

    $this->seed(VelocityContentSeeder::class);
    $formModuleId = (int) DB::table('webmaster_sections')
        ->where('seo_url_slug_en', 'velocity-test-module-6')
        ->value('id');
    $phoneFieldId = (int) DB::table('webmaster_section_fields')
        ->where('webmaster_id', $formModuleId)
        ->where('type', 15)
        ->value('id');

    $this->from('/public-form')->post(route('formSubmit'), [
        'WebmasterSectionId' => encrypt($formModuleId),
        'date' => date('Y-m-d'),
        'section_id' => 0,
        'customField_'.$phoneFieldId => '+20 111 111 1111',
    ])->assertRedirect('/public-form')
        ->assertSessionHas('doneMessage');

    $this->assertDatabaseHas('topic_fields', [
        'field_id' => $phoneFieldId,
        'field_value' => '20 111 111 1111',
    ]);
});

test('velocity landing allowlist logs and skips unknown stored styles', function () {
    Log::spy();

    $topic = (new Topic)->forceFill([]);
    $topic->setRelation('topicBlocks', collect([(object) [
        'status' => 1,
        'type' => 3,
        'content' => json_encode(['view_style' => '../../dashboard'], JSON_THROW_ON_ERROR),
    ]]));

    $html = view('themes.velocity.landing.page', [
        'PageTitle' => 'Velocity landing allowlist',
        'Topic' => $topic,
        'WebmasterSection' => null,
        'Popup' => null,
        'page_type' => 'topic',
        'page_id' => 1,
    ])->render();

    expect($html)->not->toContain('../../dashboard');
    Log::shouldHaveReceived('warning')->once();
});

test('velocity source includes all homepage and landing views without dynamic include paths', function () {
    $required = [
        'home.blade.php',
        'layouts/slider.blade.php',
        'layouts/banners.blade.php',
        ...array_map(fn (int $row): string => "homepage/row{$row}.blade.php", range(1, 7)),
        ...array_map(
            fn (string $view): string => "landing/{$view}.blade.php",
            ['page', 'static', 'code', 'form', 'contact', 'carousel', 'categories', 'grid', 'news', 'gallery', 'staff', 'testimonials', 'partners', 'faq']
        ),
    ];

    foreach ($required as $relativePath) {
        expect(File::exists(resource_path('views/themes/velocity/'.$relativePath)))->toBeTrue();
    }

    $pageSource = File::get(resource_path('views/themes/velocity/landing/page.blade.php'));

    expect($pageSource)
        ->toContain('$velocityContentViews')
        ->toContain('$velocityBannerViews')
        ->toContain('$velocityResolveBlockView')
        ->not->toContain("theme_view('landing.').")
        ->not->toContain("theme_view('layouts.').");
});

test('velocity partner rows use the responsive static logo grid', function () {
    foreach (['homepage/row7.blade.php', 'landing/partners.blade.php'] as $relativePath) {
        $source = File::get(resource_path('views/themes/velocity/'.$relativePath));

        expect($source)
            ->toContain('velocity-partners-grid')
            ->toContain('role="list"')
            ->not->toContain('data-velocity-carousel-track')
            ->not->toContain("components.carousel-controls");
    }

    expect(File::get(resource_path('themes/velocity/css/components.css')))
        ->toContain('grid-template-columns: repeat(5, minmax(0, 1fr))')
        ->toContain('.velocity-partners-grid__content')
        ->toContain('transform: translateY(-0.3rem)')
        ->toContain('velocity-partners-grid__content::before');
});

test('velocity topic images use localized ALT text with the shared title fallback', function () {
    $expectedHelpers = [
        'components/topic-card.blade.php' => 'Helper::topicPhotoAlt($topic)',
        'homepage/row3.blade.php' => 'Helper::topicPhotoAlt($velocityPerson)',
        'homepage/row6.blade.php' => 'Helper::topicPhotoAlt($velocityTestimonial)',
        'homepage/row7.blade.php' => 'Helper::topicPhotoAlt($velocityPartner)',
        'landing/partners.blade.php' => 'Helper::topicPhotoAlt($velocityPartner)',
        'landing/staff.blade.php' => 'Helper::topicPhotoAlt($velocityPerson)',
        'landing/testimonials.blade.php' => 'Helper::topicPhotoAlt($velocityTestimonial)',
        'topic.blade.php' => 'Helper::topicPhotoAlt($Topic)',
    ];

    foreach ($expectedHelpers as $relativePath => $helperCall) {
        expect(File::get(resource_path('views/themes/velocity/'.$relativePath)))
            ->toContain($helperCall);
    }
});
