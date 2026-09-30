<?php

use App\Models\WebmasterSection;
use App\Models\Author;
use App\Models\Topic;
use App\Models\WebmasterSetting;
use App\Services\ThemeService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Tests\Support\VelocityContentSeeder;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(VelocityContentSeeder::class);
    WebmasterSetting::whereKey(1)->update(['authors_status' => 1]);
    Cache::forget('_Loader_WebmasterSettings');

    config([
        'smartend.theme' => 'velocity',
        'smartend.google_maps_key' => 'velocity-test-key',
    ]);
    ThemeService::flushRuntimeCache();
});

afterEach(function () {
    ThemeService::flushRuntimeCache();
});

function velocityDetailModule(int $type): WebmasterSection
{
    return WebmasterSection::query()
        ->where('seo_url_slug_en', 'velocity-test-module-'.$type)
        ->firstOrFail();
}

function velocityDetailTopic(int $type, string $suffix = 'primary')
{
    return DB::table('topics')
        ->where('seo_url_slug_en', "velocity-test-topic-{$type}-{$suffix}")
        ->first();
}

test('velocity topic detail renders the complete server-side content and submission surface', function () {
    $module = velocityDetailModule(0);
    $topic = velocityDetailTopic(0);

    $this->get(Helper::topicURL($topic->id))
        ->assertOk()
        ->assertSee('velocity-topic-detail', false)
        ->assertSee('velocity-detail-gallery', false)
        ->assertSee('velocity-file-list', false)
        ->assertSee('velocity-map-locations', false)
        ->assertSee('velocity-topic-share', false)
        ->assertSee('velocity-comment-list', false)
        ->assertSee('name="comment_name"', false)
        ->assertSee('name="order_phone"', false)
        ->assertSee('name="order_phone_full"', false)
        ->assertSee('data-velocity-component="cms-form-enhancer"', false);

    expect($module->type)->toBe(0);
});

test('velocity editorial topics use full-width body content and retain the main cover', function () {
    $module = velocityDetailModule(0);
    $topic = velocityDetailTopic(0);

    DB::table('webmaster_sections')->where('id', $module->id)->update(['photo' => 'nav-bg.png']);
    DB::table('topics')->where('id', $topic->id)->update(['photo_file' => 'default.png']);

    $this->get(Helper::topicURL($topic->id))
        ->assertOk()
        ->assertSee('velocity-page-heading--media', false)
        ->assertSee('velocity-page-heading--has-media', false)
        ->assertSee('nav-bg.png', false)
        ->assertSee('velocity-topic-body', false)
        ->assertSee('velocity-detail-gallery', false)
        ->assertSee('href="'.route('fileView', ['path' => 'topics/default.png']).'"', false);

    $styles = File::get(resource_path('themes/velocity/css/components.css'));

    expect($styles)
        ->toContain('[data-theme="velocity"] .velocity-topic-detail--editorial .velocity-topic-body')
        ->toContain('[data-theme="velocity"] .velocity-topic-detail--editorial .velocity-topic-meta')
        ->toContain('.velocity-topic-body {')
        ->toContain('max-width: none;')
        ->toContain('margin: 0;')
        ->toContain('padding: 0;');
});

test('velocity topic metadata uses the shared responsive information row', function () {
    $topic = velocityDetailTopic(0);

    $this->get(Helper::topicURL($topic->id))
        ->assertOk()
        ->assertSee('class="velocity-topic-meta" role="list"', false)
        ->assertSee('velocity-topic-meta__item', false)
        ->assertSee('velocity-topic-meta__icon', false);

    $view = File::get(resource_path('views/themes/velocity/topic.blade.php'));
    $icons = File::get(resource_path('views/themes/velocity/components/icon.blade.php'));
    $styles = File::get(resource_path('themes/velocity/css/components.css'));

    expect($view)
        ->toContain("['name' => 'folder']")
        ->toContain("['name' => 'calendar']")
        ->toContain("['name' => 'eye']")
        ->and($icons)
        ->toContain("@case('calendar')")
        ->toContain("@case('eye')")
        ->toContain("@case('folder')")
        ->and($styles)
        ->toContain('[data-theme="velocity"] .velocity-topic-meta__item')
        ->toContain('[data-theme="velocity"] .velocity-topic-meta__categories a')
        ->toContain('min-height: 3.5rem;');
});

test('velocity uses native video and audio elements while preserving embed support', function () {
    $video = velocityDetailTopic(2);
    $videoEmbed = velocityDetailTopic(2, 'embed');
    $audio = velocityDetailTopic(3);
    $audioEmbed = velocityDetailTopic(3, 'embed');

    $this->get(Helper::topicURL($video->id))
        ->assertOk()
        ->assertSee('<video controls autoplay muted playsinline preload="metadata"', false);
    $this->get(Helper::topicURL($videoEmbed->id))
        ->assertOk()
        ->assertSee('Velocity test video', false);
    $this->get(Helper::topicURL($audio->id))
        ->assertOk()
        ->assertSee('<audio controls preload="metadata"', false);
    $this->get(Helper::topicURL($audioEmbed->id))
        ->assertOk()
        ->assertSee('Velocity test audio', false);
});

test('velocity video listings show clickable previews and autoplay supported players', function () {
    $module = velocityDetailModule(2);
    $video = velocityDetailTopic(2);

    DB::table('topics')->where('id', $video->id)->update([
        'video_type' => 1,
        'video_file' => 'https://www.youtube.com/watch?v=JQs4QyKnYMQ',
    ]);

    $this->get(Helper::sectionURL($module->id, '', $module))
        ->assertOk()
        ->assertSee('https://i.ytimg.com/vi/JQs4QyKnYMQ/hqdefault.jpg', false)
        ->assertSee('class="velocity-topic-card__media"', false)
        ->assertSee('class="velocity-topic-card__media-mark"', false);

    $this->get(Helper::topicURL($video->id))
        ->assertOk()
        ->assertSee('embed/JQs4QyKnYMQ?autoplay=1&amp;mute=1', false);
});

test('velocity audio listings expose a player for every native audio topic and preserve embeds', function () {
    $module = velocityDetailModule(3);
    $audio = velocityDetailTopic(3);

    $this->get(Helper::sectionURL($module->id, '', $module))
        ->assertOk()
        ->assertSee('data-velocity-audio-player', false)
        ->assertSee('<audio controls preload="none">', false)
        ->assertSee(route('fileView', ['path' => 'topics/'.$audio->audio_file]), false)
        ->assertSee('Velocity test audio', false);
});

test('velocity public form exposes every custom field type and saves localized values', function () {
    $module = velocityDetailModule(6);

    $response = $this->get(Helper::sectionURL($module->id, '', $module))->assertOk();
    foreach (range(0, 18) as $type) {
        $fieldId = DB::table('webmaster_section_fields')
            ->where('webmaster_id', $module->id)
            ->where('type', $type)
            ->value('id');
        $response->assertSee('name="customField_'.$fieldId, false);
    }
    $response
        ->assertSee('name="title_en"', false)
        ->assertSee('name="details_en"', false)
        ->assertSee('enctype="multipart/form-data"', false)
        ->assertSee('name="WebmasterSectionId"', false);

    $this->from(Helper::sectionURL($module->id, '', $module))
        ->post(route('formSubmit'), [
            'WebmasterSectionId' => encrypt($module->id),
            'date' => '2026-07-25',
            'section_id' => 0,
            'title_en' => 'Velocity localized submission',
            'details_en' => 'Stored from localized public form fields.',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('topics', [
        'webmaster_id' => $module->id,
        'title_en' => 'Velocity localized submission',
        'details_en' => 'Stored from localized public form fields.',
        'status' => 0,
    ]);
});

test('velocity contact and order retain native invalid submission state', function () {
    $topic = velocityDetailTopic(0);
    $contactUrl = url('/velocity-native-form-return');

    $this->from($contactUrl)
        ->post(route('contactPageSubmit'), ['contact_name' => 'Retained'])
        ->assertRedirect($contactUrl)
        ->assertSessionHasErrors(['contact_email', 'contact_phone', 'contact_subject', 'contact_message'])
        ->assertSessionHasInput('contact_name', 'Retained');

    $this->from($contactUrl)
        ->post(route('orderSubmit'), [
            'topic_id' => $topic->id,
            'order_name' => 'Retained order',
            'order_phone' => '+20 100 000 0000',
        ])
        ->assertRedirect($contactUrl)
        ->assertSessionHasErrors(['order_email'])
        ->assertSessionHasInput('order_phone', '+20 100 000 0000');
});

test('velocity detail source is bootstrap free and keeps native fallbacks beside enhancements', function () {
    $required = [
        'topic.blade.php',
        'contact.blade.php',
        'form_page.blade.php',
        'form.blade.php',
        'topic/comments.blade.php',
        'topic/fields.blade.php',
        'topic/files.blade.php',
        'topic/maps.blade.php',
        'topic/next_prev.blade.php',
        'topic/order.blade.php',
        'topic/related.blade.php',
        'topic/share.blade.php',
        'topic/tags.blade.php',
    ];

    foreach ($required as $relativePath) {
        $source = File::get(resource_path('views/themes/velocity/'.$relativePath));

        expect(strtolower($source))
            ->not->toContain('bootstrap')
            ->not->toContain('jquery')
            ->not->toContain('$(');
    }

    expect(File::get(resource_path('views/themes/velocity/topic.blade.php')))
        ->toContain('<video controls')
        ->toContain('<audio controls')
        ->toContain('data-velocity-lightbox')
        ->toContain('data-velocity-video-player')
        ->toContain('data-velocity-audio-player')
        ->and(File::get(resource_path('themes/velocity/css/vendor.css')))
        ->toContain('[data-velocity-audio-enhanced] > audio')
        ->toContain('display: none;')
        ->toContain('.velocity-topic-card:has(.velocity-topic-card__audio)')
        ->toContain('overflow: visible;')
        ->and(File::get(resource_path('themes/velocity/js/modules/vendor-integrations.js')))
        ->toContain('showDownloadButton: false')
        ->and(File::get(resource_path('views/themes/velocity/form.blade.php')))
        ->toContain('data-velocity-native-form')
        ->toContain('old(')
        ->and(File::get(resource_path('views/themes/velocity/components/contact-form.blade.php')))
        ->toContain('name="contact_phone_full"')
        ->toContain('data-velocity-phone-input')
        ->toContain('data-velocity-component="cms-form-enhancer"')
        ->and(File::get(resource_path('themes/velocity/css/bootstrap-compat.css')))
        ->toContain('.col-md-6')
        ->toContain('.btn-primary')
        ->toContain('.table-responsive')
        ->and(File::get(resource_path('themes/velocity/js/modules/form-controls.js')))
        ->toContain('data-velocity-enhanced-select')
        ->toContain("select.dispatchEvent(new Event('change'");
});

test('velocity comments provide an accessible discussion and validation surface', function () {
    $comments = File::get(resource_path('views/themes/velocity/topic/comments.blade.php'));
    $styles = File::get(resource_path('themes/velocity/css/components.css'));

    expect($comments)
        ->toContain('velocity-comments__count')
        ->toContain('velocity-comment__avatar')
        ->toContain("__('frontend.noData')")
        ->toContain("__('frontend.newComment')")
        ->toContain('aria-invalid="true"')
        ->toContain('velocity-field-error')
        ->and($styles)
        ->toContain('.velocity-comment-composer')
        ->toContain('.velocity-comment__message')
        ->toContain('.velocity-comments__empty');
});

test('velocity order form provides an accessible responsive checkout surface', function () {
    $order = File::get(resource_path('views/themes/velocity/topic/order.blade.php'));
    $styles = File::get(resource_path('themes/velocity/css/components.css'));

    expect($order)
        ->toContain('velocity-order-composer')
        ->toContain('velocity-order-form')
        ->toContain("['name' => 'order']")
        ->toContain('data-velocity-phone-full-name="order_phone_full"')
        ->toContain('aria-invalid="true"')
        ->toContain('velocity-order-form__footer')
        ->and($styles)
        ->toContain('.velocity-order-composer')
        ->toContain('.velocity-order-form .velocity-textarea')
        ->toContain('.velocity-order-form__footer');
});

test('velocity topic sharing uses recognizable responsive network controls', function () {
    $share = File::get(resource_path('views/themes/velocity/topic/share.blade.php'));
    $socialIcon = File::get(resource_path('views/themes/velocity/components/social-icon.blade.php'));
    $styles = File::get(resource_path('themes/velocity/css/components.css'));

    expect($share)
        ->toContain('velocity-topic-share__heading')
        ->toContain("['name' => 'share']")
        ->toContain("'tumblr' => ['label' => 'Tumblr', 'icon' => 'tumblr']")
        ->toContain('rel="noopener noreferrer"')
        ->and($socialIcon)
        ->toContain("@case('tumblr')")
        ->and($styles)
        ->toContain('.velocity-topic-share__link--facebook')
        ->toContain('grid-template-columns: repeat(auto-fit, minmax(min(7.5rem, 100%), 1fr));')
        ->toContain('@media (prefers-reduced-motion: reduce)');
});

test('velocity topic navigation uses explicit directional controls', function () {
    $navigation = File::get(resource_path('views/themes/velocity/topic/next_prev.blade.php'));
    $styles = File::get(resource_path('themes/velocity/css/components.css'));

    expect($navigation)
        ->toContain('velocity-topic-navigation__link--previous')
        ->toContain('velocity-topic-navigation__link--next')
        ->toContain("['name' => 'arrow-left']")
        ->toContain("['name' => 'arrow-right']")
        ->and($styles)
        ->toContain('[dir="rtl"] .velocity-topic-navigation__arrow .velocity-icon')
        ->toContain('.velocity-topic-navigation__link--previous')
        ->toContain('.velocity-topic-navigation__link--next');
});

test('velocity shows linked authors and their public publications', function () {
    $topic = Topic::findOrFail(velocityDetailTopic(0)->id);
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
        ->assertSee('velocity-author-profile', false)
        ->assertSee('Test Author')
        ->assertSee($topic->title_en);

    $author->update(['status' => 0]);
    $this->get(route('authorProfile', ['author' => $author->id]))->assertNotFound();
});
