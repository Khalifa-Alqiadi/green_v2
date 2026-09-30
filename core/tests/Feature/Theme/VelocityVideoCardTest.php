<?php

use App\Models\Topic;
use App\Services\ThemeService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

test('velocity compact video cards show youtube previews for most viewed and related topics', function () {
    $topicId = DB::table('topics')
        ->where('seo_url_slug_en', 'velocity-test-topic-2-primary')
        ->value('id');

    DB::table('topics')->where('id', $topicId)->update([
        'video_type' => 1,
        'video_file' => 'https://www.youtube.com/watch?v=JQs4QyKnYMQ',
    ]);

    $topic = Topic::query()
        ->with(['photos', 'webmasterSection'])
        ->findOrFail($topicId);

    $html = view('themes.velocity.topic.card2', ['Post' => $topic])->render();

    expect($html)
        ->toContain('https://i.ytimg.com/vi/JQs4QyKnYMQ/hqdefault.jpg')
        ->toContain('velocity-topic-card2__media-mark')
        ->toContain('▶');
});
