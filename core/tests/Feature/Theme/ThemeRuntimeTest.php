<?php

use App\Services\ThemeAssetService;
use App\Services\ThemeService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    config([
        'smartend.nocaptcha_status' => false,
        'smartend.newsletter_status' => false,
    ]);
    ThemeService::flushRuntimeCache();
});

afterEach(function () {
    ThemeService::flushRuntimeCache();
});

test('custom frontend views resolve to the active classic or legacy namespace', function () {
    $viewRoot = storage_path('framework/testing/theme-runtime-views-'.bin2hex(random_bytes(4)));
    File::ensureDirectoryExists($viewRoot.'/themes/modern/custom');
    File::ensureDirectoryExists($viewRoot.'/themes/classic/custom');
    File::ensureDirectoryExists($viewRoot.'/frontEnd/custom');
    File::put($viewRoot.'/themes/modern/custom/active_override.blade.php', 'active');
    File::put($viewRoot.'/themes/classic/custom/classic_override.blade.php', 'classic');
    File::put($viewRoot.'/frontEnd/custom/legacy_override.blade.php', 'legacy');
    View::addLocation($viewRoot);

    config(['smartend.theme' => 'modern']);
    ThemeService::flushRuntimeCache();

    try {
        expect(ThemeService::resolveCustomView('active_override'))->toBe('themes.modern.custom.active_override')
            ->and(ThemeService::resolveCustomView('classic_override'))->toBe('themes.classic.custom.classic_override')
            ->and(ThemeService::resolveCustomView('legacy_override'))->toBe('frontEnd.custom.legacy_override')
            ->and(ThemeService::resolveCustomView('../unsafe'))->toBeNull()
            ->and(ThemeService::resolveCustomView('missing_override'))->toBeNull();
    } finally {
        File::deleteDirectory($viewRoot);
    }
});

test('sitemap uses the active theme view and returns valid XML', function () {
    config(['smartend.theme' => 'modern']);
    ThemeService::flushRuntimeCache();

    $response = $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('content-type', 'text/xml; charset=UTF-8');

    $xml = simplexml_load_string($response->getContent());

    expect($xml)->not->toBeFalse()
        ->and($xml->getName())->toBe('urlset');
});

test('theme filesystem paths support a public document root outside core', function () {
    $originalPublicPath = public_path();
    $deploymentRoot = storage_path('framework/testing/live-layout-'.bin2hex(random_bytes(4)));
    $theme = 'live-layout';
    $buildDirectory = 'build/'.$theme;

    File::ensureDirectoryExists($deploymentRoot.'/assets/'.$theme);
    File::ensureDirectoryExists($deploymentRoot.'/'.$buildDirectory);
    app()->usePublicPath($deploymentRoot.'/core/public');

    config(['smartend.compiled_themes.'.$theme => [
        'entry' => 'resources/themes/'.$theme.'/js/app.js',
        'build_directory' => $buildDirectory,
        'hot_file' => 'storage/'.$theme.'.hot',
        'fallback_asset' => 'css/fallback.css',
    ]]);

    try {
        expect(ThemeService::assetsPath($theme))->toBe($deploymentRoot.'/assets/'.$theme)
            ->and(ThemeService::compiledThemeConfig($theme)['manifest'])
            ->toBe($deploymentRoot.'/'.$buildDirectory.'/manifest.json');
    } finally {
        app()->usePublicPath($originalPublicPath);
        File::deleteDirectory($deploymentRoot);
    }
});

test('compiled theme readiness rejects missing corrupt and incomplete builds', function () {
    $suffix = bin2hex(random_bytes(4));
    $theme = 'compiled-test-'.$suffix;
    $buildDirectory = 'build/'.$theme;
    $manifestPath = public_path($buildDirectory.'/manifest.json');
    $entry = 'resources/themes/'.$theme.'/js/app.js';

    config(['smartend.compiled_themes.'.$theme => [
        'entry' => $entry,
        'build_directory' => $buildDirectory,
        'hot_file' => 'storage/'.$theme.'.hot',
        'fallback_asset' => 'css/fallback.css',
    ]]);

    expect(ThemeService::compiledReadiness($theme)['code'])->toBe('manifest_missing');

    File::ensureDirectoryExists(dirname($manifestPath));

    try {
        File::put($manifestPath, '{invalid');
        expect(ThemeService::compiledReadiness($theme)['code'])->toBe('manifest_invalid');

        File::put($manifestPath, json_encode([
            $entry => ['file' => 'assets/app.js', 'css' => ['assets/app.css']],
        ], JSON_THROW_ON_ERROR));
        expect(ThemeService::compiledReadiness($theme)['code'])->toBe('asset_missing');

        File::put($manifestPath, json_encode([
            $entry => ['file' => 'assets/app.js', 'css' => 'assets/app.css'],
        ], JSON_THROW_ON_ERROR));
        expect(ThemeService::compiledReadiness($theme)['code'])->toBe('manifest_invalid');

        File::put($manifestPath, json_encode([
            $entry => ['file' => 'assets/app.js', 'css' => ['assets/app.css']],
        ], JSON_THROW_ON_ERROR));
        File::ensureDirectoryExists(public_path($buildDirectory.'/assets'));
        File::put(public_path($buildDirectory.'/assets/app.js'), 'export default {};');
        File::put(public_path($buildDirectory.'/assets/app.css'), 'body{}');

        expect(ThemeService::compiledReadiness($theme))
            ->toMatchArray(['compiled' => true, 'ready' => true, 'code' => 'manifest']);

        File::put($manifestPath, json_encode([
            $entry => [
                'file' => 'assets/app.js',
                'css' => ['assets/app.css'],
                'dynamicImports' => ['resources/themes/'.$theme.'/js/lazy.js'],
            ],
        ], JSON_THROW_ON_ERROR));
        expect(ThemeService::compiledReadiness($theme)['code'])->toBe('chunk_missing');

        File::put($manifestPath, json_encode([
            $entry => [
                'file' => 'assets/app.js',
                'css' => ['assets/app.css'],
                'dynamicImports' => ['resources/themes/'.$theme.'/js/lazy.js'],
            ],
            'resources/themes/'.$theme.'/js/lazy.js' => [
                'file' => 'assets/lazy.js',
                'imports' => [$entry],
            ],
        ], JSON_THROW_ON_ERROR));
        expect(ThemeService::compiledReadiness($theme)['code'])->toBe('asset_missing');

        File::put(public_path($buildDirectory.'/assets/lazy.js'), 'export default {};');
        expect(ThemeService::compiledReadiness($theme))
            ->toMatchArray(['compiled' => true, 'ready' => true, 'code' => 'manifest']);
    } finally {
        File::deleteDirectory(public_path($buildDirectory));
    }
});

test('compiled theme readiness rejects unsafe asset configuration', function () {
    config(['smartend.compiled_themes.unsafe-theme' => [
        'entry' => '../outside.js',
        'build_directory' => '/build/unsafe',
        'hot_file' => 'storage/vite.unsafe.hot',
        'fallback_asset' => 'css/fallback.css',
    ]]);

    expect(ThemeService::compiledReadiness('unsafe-theme'))
        ->toMatchArray(['compiled' => true, 'ready' => false, 'code' => 'config_invalid']);
});

test('compiled asset rendering falls back without throwing when a manifest is missing', function () {
    config([
        'smartend.theme' => 'modern',
        'smartend.compiled_themes.modern' => [
            'entry' => 'resources/themes/modern/js/app.js',
            'build_directory' => 'build/modern-missing',
            'hot_file' => 'storage/vite.modern-missing.hot',
            'fallback_asset' => 'css/style.css',
        ],
    ]);
    ThemeService::flushRuntimeCache();

    $html = app(ThemeAssetService::class)->render()->toHtml();

    expect($html)->toContain('data-theme-asset-fallback')
        ->and($html)->toContain('/assets/modern/css/style.css');
});

test('theme activation updates the environment atomically after successful cache clearing', function () {
    $envPath = storage_path('framework/testing/theme-runtime-env-'.bin2hex(random_bytes(4)));
    File::put($envPath, "APP_NAME=Smartend\nTHEME=classic\n");
    config([
        'smartend.theme' => 'classic',
        'smartend.theme_env_path' => $envPath,
    ]);
    ThemeService::flushRuntimeCache();

    Artisan::shouldReceive('call')->once()->with('config:clear')->andReturn(0);
    Artisan::shouldReceive('call')->once()->with('view:clear')->andReturn(0);

    try {
        ThemeService::activate('modern');

        expect(File::get($envPath))->toBe("APP_NAME=Smartend\nTHEME=modern\n")
            ->and(config('smartend.theme'))->toBe('modern')
            ->and(ThemeService::active())->toBe('modern');
    } finally {
        File::delete($envPath);
        putenv('THEME');
        unset($_ENV['THEME'], $_SERVER['THEME']);
    }
});

test('theme activation restores the previous environment and runtime when cache clearing fails', function () {
    $envPath = storage_path('framework/testing/theme-runtime-env-'.bin2hex(random_bytes(4)));
    $original = "APP_NAME=Smartend\nTHEME=classic\n";
    File::put($envPath, $original);
    config([
        'smartend.theme' => 'classic',
        'smartend.theme_env_path' => $envPath,
    ]);
    ThemeService::flushRuntimeCache();

    Artisan::shouldReceive('call')->once()->with('config:clear')->andReturn(0);
    Artisan::shouldReceive('call')->once()->with('view:clear')->andThrow(new RuntimeException('simulated failure'));

    try {
        expect(fn () => ThemeService::activate('modern'))
            ->toThrow(RuntimeException::class, 'Theme activation failed and the previous theme was restored.');

        expect(File::get($envPath))->toBe($original)
            ->and(config('smartend.theme'))->toBe('classic')
            ->and(ThemeService::active())->toBe('classic');
    } finally {
        File::delete($envPath);
        putenv('THEME');
        unset($_ENV['THEME'], $_SERVER['THEME']);
    }
});

test('theme activation reports a read only environment without changing runtime state', function () {
    $envPath = storage_path('framework/testing/theme-runtime-env-'.bin2hex(random_bytes(4)));
    $original = "THEME=classic\n";
    File::put($envPath, $original);
    chmod($envPath, 0444);
    config([
        'smartend.theme' => 'classic',
        'smartend.theme_env_path' => $envPath,
    ]);
    ThemeService::flushRuntimeCache();

    try {
        expect(fn () => ThemeService::activate('modern'))
            ->toThrow(RuntimeException::class, 'Theme activation failed and the previous theme was restored.');

        expect(File::get($envPath))->toBe($original)
            ->and(config('smartend.theme'))->toBe('classic')
            ->and(ThemeService::active())->toBe('classic');
    } finally {
        chmod($envPath, 0644);
        File::delete($envPath);
    }
});

test('public forms preserve JSON responses and redirect native HTML submissions with feedback', function () {
    foreach (['/subscribe', '/contact-submit', '/comment', '/order'] as $endpoint) {
        $this->postJson($endpoint)
            ->assertOk()
            ->assertJson(['stat' => 'error'])
            ->assertJsonStructure(['stat', 'msg']);

        $this->from('/form-origin')->post($endpoint)
            ->assertRedirect('/form-origin')
            ->assertSessionHas('frontendFormFeedback.status', 'error')
            ->assertSessionHas('frontendFormFeedback.message')
            ->assertSessionHasErrors();
    }

    $email = 'theme-runtime-'.bin2hex(random_bytes(4)).'@example.test';
    $this->from('/')->post('/subscribe', ['subscribe_email' => $email])
        ->assertRedirect('/')
        ->assertSessionHas('frontendFormFeedback.status', 'success');

    $this->assertDatabaseHas('contacts', ['email' => $email]);
});

test('classic and modern shells render native form feedback accessibly', function () {
    foreach (['classic', 'modern'] as $theme) {
        config(['smartend.theme' => $theme]);
        ThemeService::flushRuntimeCache();

        $this->withSession([
            'frontendFormFeedback' => [
                'status' => 'success',
                'message' => 'Native form success',
            ],
        ])->get('/')
            ->assertOk()
            ->assertSee('data-frontend-form-feedback', false)
            ->assertSee('aria-live="polite"', false)
            ->assertSeeText('Native form success');
    }
});
