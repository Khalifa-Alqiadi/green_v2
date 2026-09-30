<?php

use App\Services\ThemeService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\Support\VelocityContentSeeder;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(VelocityContentSeeder::class);
    config([
        'smartend.theme' => 'velocity',
        'smartend.google_maps_key' => 'velocity-test-key',
    ]);
    ThemeService::flushRuntimeCache();
});

afterEach(function () {
    ThemeService::flushRuntimeCache();
});

test('velocity representative routes emit stable responsive and deferred media', function () {
    $topic = DB::table('topics')
        ->where('seo_url_slug_en', 'velocity-test-topic-0-primary')
        ->first();

    $html = $this->get(Helper::topicURL($topic->id))
        ->assertOk()
        ->getContent();

    expect($html)
        ->toContain('srcset=')
        ->toContain('sizes=')
        ->toContain('decoding="async"')
        ->toContain('loading="lazy"')
        ->toContain('data-velocity-map');
});

test('velocity dark mode derives its palette from the configured theme colors', function () {
    $brand = strtoupper(Helper::GeneralSiteSettings('style_color1'));
    $brandStrong = strtoupper(Helper::GeneralSiteSettings('style_color2'));
    $muted = strtoupper(Helper::GeneralSiteSettings('style_color3'));
    $border = strtoupper(Helper::GeneralSiteSettings('style_color4'));
    $darkSurface = Helper::colorHexToDarken($brandStrong, 2.4);
    $darkSurfaceMuted = Helper::colorHexToDarken($brandStrong, 1.75);
    $darkSurfaceElevated = Helper::colorHexToDarken($brandStrong, 1.4);
    $darkSurfaceSoft = Helper::colorHexToDarken($brandStrong, 2);

    $colors = view('themes.velocity.layouts.colors')->render();

    expect($colors)
        ->toContain(':root[data-theme="velocity"][data-appearance="dark"]')
        ->toContain("--color-surface: {$darkSurface};")
        ->toContain("--color-surface-muted: {$darkSurfaceMuted};")
        ->toContain("--color-surface-elevated: {$darkSurfaceElevated};")
        ->toContain("--color-surface-soft: {$darkSurfaceSoft};")
        ->toContain("--color-text: {$muted};")
        ->toContain("--color-text-muted: color-mix(in srgb, {$border} 78%, {$brandStrong});")
        ->toContain("--color-control-border: color-mix(in srgb, {$brand} 46%, {$darkSurfaceElevated});");
});

test('velocity hardening sources cover contrast motion imports and cache policy', function () {
    $colors = File::get(resource_path('views/themes/velocity/layouts/colors.blade.php'));
    $baseCss = File::get(resource_path('themes/velocity/css/base.css'));
    $globalUi = File::get(resource_path('themes/velocity/js/modules/global-ui.js'));
    $maps = File::get(resource_path('themes/velocity/js/modules/maps.js'));
    $embeds = File::get(resource_path('views/themes/velocity/components/topic-field-value.blade.php'));
    $apache = File::get(public_path('.htaccess'));

    expect($colors)
        ->toContain('$darkContrast')
        ->toContain('$lightContrast')
        ->toContain('--color-brand-strong-contrast')
        ->and($baseCss)
        ->toContain('@media (forced-colors: active)')
        ->toContain('prefers-reduced-motion')
        ->and($globalUi)
        ->toContain("behavior: reduceMotion ? 'auto' : 'smooth'")
        ->and($maps)
        ->toContain('IntersectionObserver')
        ->toContain("map.dataset.velocityMapReady = 'pending'")
        ->and($embeds)
        ->toContain('referrerpolicy="strict-origin-when-cross-origin"')
        ->and($apache)
        ->toContain('velocity_hashed_asset')
        ->toContain('max-age=31536000, immutable')
        ->toContain('no-cache, no-store, must-revalidate');
});
