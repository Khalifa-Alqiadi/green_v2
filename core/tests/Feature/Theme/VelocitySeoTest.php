<?php

use App\Models\Topic;
use App\Services\ThemeService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\Support\VelocityContentSeeder;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(VelocityContentSeeder::class);
    config(['smartend.theme' => 'velocity']);
    ThemeService::flushRuntimeCache();
});

afterEach(function () {
    ThemeService::flushRuntimeCache();
});

test('velocity emits canonical language and website metadata on the homepage', function () {
    $html = view('themes.velocity.home', [
        'PageTitle' => 'Velocity home',
        'Topic' => null,
        'Popup' => null,
        'page_type' => 'home',
        'page_id' => 0,
    ])->render();

    expect($html)
        ->toContain('<meta name="robots" content="index,follow">')
        ->toContain('<link rel="canonical" href="'.url('/').'">')
        ->toContain('hreflang="en"')
        ->toContain('hreflang="ar"')
        ->toContain('hreflang="x-default"')
        ->toContain('<meta property="og:type" content="website">')
        ->toContain('"@type":"WebSite"');
});

test('velocity keeps paginated canonicals self-referencing and noindexes searches', function () {
    app()->instance('request', Request::create('/velocity-pagination?page=2'));
    $topics = new LengthAwarePaginator([], 24, 12, 2, [
        'path' => 'http://localhost/velocity-pagination',
    ]);
    $paginated = view('themes.velocity.layouts.head', [
        'PageTitle' => 'Velocity pagination',
        'Topic' => null,
        'Topics' => $topics,
        'page_type' => '',
        'page_id' => 0,
    ])->render();

    expect($paginated)->toContain('<link rel="canonical" href="http://localhost/velocity-pagination?page=2">');

    app()->instance('request', Request::create('/velocity-search?search_word=Velocity'));
    $search = view('themes.velocity.layouts.head', [
        'PageTitle' => 'Velocity search',
        'Topic' => null,
        'page_type' => '',
        'page_id' => 0,
    ])->render();

    expect($search)
        ->toContain('<meta name="robots" content="noindex,follow">')
        ->toContain('<link rel="canonical" href="http://localhost/velocity-search">');
});

test('velocity emits article metadata only for content detail pages', function () {
    $topic = Topic::query()
        ->where('seo_url_slug_en', 'velocity-test-topic-0-primary')
        ->firstOrFail();

    $html = $this->get(Helper::topicURL($topic->id))->assertOk()->getContent();

    expect($html)
        ->toContain('<meta property="og:type" content="article">')
        ->toContain('"@type":"Article"')
        ->toContain('"mainEntityOfPage"')
        ->toContain('"dateModified":"'.\Illuminate\Support\Carbon::parse($topic->updated_at)->toIso8601String().'"');
});
