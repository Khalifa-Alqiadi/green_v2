<?php

use App\Services\ThemeService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    ThemeService::flushRuntimeCache();
});

afterEach(function () {
    ThemeService::setRequestTheme(null);
    ThemeService::flushRuntimeCache();
});

test('customer installations reject crafted demo theme requests', function () {
    config(['smartend.demo_theme_switcher.enabled' => false]);

    $this->get('/demo/theme/modern')->assertNotFound();
    $this->assertNull(session('demo_theme'));
});

test('configured demo host is an additional gate', function () {
    config([
        'smartend.demo_theme_switcher.enabled' => true,
        'smartend.demo_theme_switcher.host' => 'demo.example.com',
    ]);

    $this->get('http://customer.example.com/demo/theme/modern')->assertNotFound();
    $this->get('http://demo.example.com/demo/theme/modern?return=%2Fnews')
        ->assertRedirect('/news')
        ->assertSessionHas('demo_theme', 'modern');
});

test('demo selections are session only and invalid themes are rejected', function () {
    config([
        'smartend.demo_theme_switcher.enabled' => true,
        'smartend.demo_theme_switcher.host' => null,
        'smartend.demo_theme_switcher.default' => 'velocity',
    ]);

    $globalTheme = config('smartend.theme');
    $this->get('/demo/theme/classic?return=https%3A%2F%2Fevil.example')
        ->assertRedirect('/')
        ->assertSessionHas('demo_theme', 'classic');
    $this->get('/demo/theme/not-a-theme')->assertNotFound();
    expect(config('smartend.theme'))->toBe($globalTheme);
});

test('request theme override does not outlive the public request', function () {
    config([
        'smartend.demo_theme_switcher.enabled' => true,
        'smartend.demo_theme_switcher.host' => null,
    ]);

    $globalTheme = ThemeService::active();
    $this->withSession(['demo_theme' => 'modern'])->get('/demo/theme/modern')->assertRedirect('/');

    expect(ThemeService::active())->toBe($globalTheme);
});
