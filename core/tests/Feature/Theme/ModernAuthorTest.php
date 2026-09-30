<?php

use App\Models\Author;
use App\Models\Topic;
use App\Models\WebmasterSetting;
use App\Services\ThemeService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Support\VelocityContentSeeder;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(VelocityContentSeeder::class);
    WebmasterSetting::whereKey(1)->update(['authors_status' => 1]);
    Cache::forget('_Loader_WebmasterSettings');
    config(['smartend.theme' => 'modern']);
    ThemeService::flushRuntimeCache();
});

afterEach(function () {
    ThemeService::flushRuntimeCache();
});

test('modern topic links to an author profile listing published topics', function () {
    $topic = Topic::where('seo_url_slug_en', 'velocity-test-topic-0-primary')->firstOrFail();
    $author = Author::create([
        'name_ar' => 'كاتب تجريبي',
        'name_en' => 'Test Author',
        'position_ar' => 'باحث',
        'position_en' => 'Researcher',
        'status' => 1,
    ]);
    $topic->authors()->attach($author->id);

    $this->get(Helper::topicURL($topic->id))
        ->assertOk()
        ->assertSee('Test Author')
        ->assertSee(route('authorProfile', ['author' => $author->id]), false);

    $this->get(route('authorProfile', ['author' => $author->id]))
        ->assertOk()
        ->assertSee('modern-author-profile', false)
        ->assertSee('Test Author')
        ->assertSee($topic->title_en);

    $this->get(route('authorProfileLocalized', ['lang' => 'ar', 'author' => $author->id]))
        ->assertOk()
        ->assertSee('كاتب تجريبي');

    $author->update(['status' => 0]);
    $this->get(route('authorProfile', ['author' => $author->id]))->assertNotFound();
});
