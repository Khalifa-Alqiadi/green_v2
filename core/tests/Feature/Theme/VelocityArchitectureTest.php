<?php

use Illuminate\Support\Facades\File;

test('velocity maintainer documentation describes the native production architecture', function () {
    $guidePath = base_path('Docs/THEME_DEVELOPMENT_GUIDE.md');

    expect(File::isFile($guidePath))->toBeTrue();

    $guide = File::get($guidePath);
    foreach ([
        '## General theme contract',
        '## Velocity purpose and support boundary',
        '## Development workflow',
        '## Blade and component rules',
        '## Tokens and CSS architecture',
        '### Supported CMS/editor content classes',
        '## JavaScript and Vue islands',
        '## Settings and trusted customization',
        '## Verification commands',
        '## Build and deployment',
        '## Production monitoring',
        '## Theme switching and recovery',
        '## Troubleshooting',
    ] as $requiredSection) {
        expect($guide)->toContain($requiredSection);
    }

    expect($guide)->toContain('Velocity is a native Smartend theme');
});

test('velocity build and verification commands remain registered', function () {
    $package = json_decode(File::get(base_path('package.json')), true, 512, JSON_THROW_ON_ERROR);
    $scripts = array_keys($package['scripts'] ?? []);

    foreach ([
        'dev:velocity',
        'build:velocity',
        'test:unit',
        'test:velocity-build',
        'test:velocity',
        'test:e2e',
        'test:lighthouse:velocity',
    ] as $script) {
        expect($scripts)->toContain($script);
    }

    expect($package['scripts']['build:velocity'])->toBe('vite build --config vite.velocity.config.js');
});

test('velocity documented cms rich content compatibility classes remain implemented', function () {
    $css = File::get(resource_path('themes/velocity/css/rich-content.css'));

    expect($css)
        ->toContain(':is(.velocity-richtext, .velocity-rich-content)')
        ->toContain('.row')
        ->toContain('.container, .container-fluid')
        ->toContain('.text-center')
        ->toContain('.text-end, .text-right');

    $guide = File::get(base_path('Docs/THEME_DEVELOPMENT_GUIDE.md'));
    foreach ([
        '`velocity-richtext`',
        '`velocity-rich-content`',
        '`row`',
        '`container`',
        '`container-fluid`',
        '`text-center`',
        '`text-end`',
        '`text-right`',
    ] as $documentedClass) {
        expect($guide)->toContain($documentedClass);
    }
});
