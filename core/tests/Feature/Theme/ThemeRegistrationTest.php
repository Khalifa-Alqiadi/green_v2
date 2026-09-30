<?php

use App\Models\Setting;
use App\Models\User;
use App\Services\ThemeService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function () {
    ThemeService::flushRuntimeCache();
});


test('social settings endpoints use the selected velocity row', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::query()->firstOrFail();
    $velocityLinks = [[
        'row_id' => 91,
        'row_no' => 1,
        'title' => 'Velocity network',
        'url' => 'https://example.test/velocity',
        'icon' => 'fas fa-bolt',
        'color' => '#0057B8',
    ]];

    Setting::query()->where('theme_name', 'velocity')->update([
        'social_links' => json_encode($velocityLinks),
    ]);
    ThemeService::flushRuntimeCache();

    $this->actingAs($user)
        ->getJson(route('settingsGetSocialLinks', ['selected_theme' => 'velocity']))
        ->assertOk()
        ->assertExactJson(['social_links' => $velocityLinks]);
});

test('a disabled velocity settings row is not discoverable or selectable', function () {
    $this->seed(DatabaseSeeder::class);
    Setting::query()->where('theme_name', 'velocity')->update(['status' => 0]);
    ThemeService::flushRuntimeCache();

    expect(ThemeService::isAvailable('velocity'))->toBeFalse()
        ->and(ThemeService::availableThemes())->not->toContain('velocity')
        ->and(ThemeService::activationIssues('velocity'))->toBe(['Invalid or unavailable theme.']);
});
