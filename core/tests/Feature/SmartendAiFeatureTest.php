<?php

use App\Ai\Agents\TopicWriterAgent;
use App\Ai\Agents\TopicTransformAgent;
use App\Ai\Agents\TopicTranslatorAgent;
use App\Ai\Agents\WebsiteSeoAgent;
use App\Ai\Agents\TopicImagePromptAgent;
use App\Models\AiGeneratedAsset;
use App\Models\AiUsageLog;
use App\Models\Language;
use App\Models\Permissions;
use App\Models\Topic;
use App\Models\TopicBlock;
use App\Models\User;
use App\Models\WebmasterSection;
use App\Models\WebmasterSetting;
use App\Models\Banner;
use App\Models\Popup;
use App\Models\Menu;
use App\Models\Setting;
use App\Models\WebmasterBanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Image;

uses(RefreshDatabase::class);

test('permissions schema keeps only the unified AI role column', function () {
    expect(Schema::hasColumn('permissions', 'ai_use'))->toBeTrue();

    foreach (['ai_manage_settings', 'ai_generate_content', 'ai_translate', 'ai_generate_images', 'ai_seo'] as $column) {
        expect(Schema::hasColumn('permissions', $column))->toBeFalse();
    }
});

function aiFeatureFixtures(array $permissionOverrides = [], array $settingsOverrides = []): array
{
    $section = WebmasterSection::query()->forceCreate([
        'title_en' => 'Articles',
        'editor_status' => 1,
        'status' => 1,
    ]);
    Language::query()->forceCreate(['title' => 'English', 'code' => 'en', 'direction' => 'ltr', 'status' => 1, 'box_status' => 1]);
    $group = Permissions::query()->forceCreate(array_merge([
        'name' => 'AI Editors',
        'status' => 1,
        'add_status' => 1,
        'edit_status' => 1,
        'active_status' => 1,
        'data_sections' => (string) $section->id,
        'ai_use' => 1,
        'webmaster_status' => 1,
        'settings_status' => 1,
        'banners_status' => 1,
        'popups_status' => 1,
        'menus_status' => 1,
    ], $permissionOverrides));
    $user = User::query()->create([
        'name' => 'AI Editor',
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('password'),
        'permissions_id' => $group->id,
        'status' => 1,
    ]);
    (new Database\Seeders\SettingsSeeder)->run();
    (new Database\Seeders\WebmasterSettingsSeeder)->run();
    $settings = WebmasterSetting::query()->findOrFail(1);
    $settings->forceFill(array_merge([
        'ai_enabled' => 1,
        'ai_content_enabled' => 1,
        'ai_seo_enabled' => 1,
        'ai_translation_enabled' => 1,
        'ai_images_enabled' => 0,
        'ai_text_provider' => 'openai',
        'ai_provider_credentials' => ['openai' => ['key' => 'fake-provider-key']],
    ], $settingsOverrides))->save();

    return compact('section', 'group', 'user', 'settings');
}

test('AI remains disabled by default and direct endpoint access returns a safe denial', function () {
    $fixtures = aiFeatureFixtures(settingsOverrides: ['ai_enabled' => 0]);

    $this->actingAs($fixtures['user'])->postJson(route('topicsAiContent', $fixtures['section']), [
        'language' => 'en',
        'operation' => 'write',
        'target' => 'title',
    ])->assertForbidden()->assertJson([
        'code' => 'ai_unavailable_or_unauthorized',
    ]);

    expect($fixtures['settings']->fresh()->ai_enabled)->toBeFalse();
});

test('AI content endpoint enforces the unified AI permission before validation', function () {
    $fixtures = aiFeatureFixtures(permissionOverrides: ['ai_use' => 0]);

    $this->actingAs($fixtures['user'])->postJson(route('topicsAiContent', $fixtures['section']), [
        'language' => 'en',
        'operation' => 'not-an-operation',
        'target' => 'title',
    ])->assertForbidden();

    expect(AiUsageLog::query()->count())->toBe(0);
});

test('AI agents use Laravel AI fakes and sanitize suggestions without saving a Topic', function () {
    $fixtures = aiFeatureFixtures();
    TopicWriterAgent::fake(['<section class="container evil"><h2>Generated title</h2><script>alert(1)</script></section>'])
        ->preventStrayPrompts();

    $response = $this->actingAs($fixtures['user'])->postJson(route('topicsAiContent', $fixtures['section']), [
        'language' => 'en',
        'operation' => 'write',
        'target' => 'details',
        'value' => '',
        'title' => 'Source title',
        'details' => '',
    ])->assertOk()->assertJsonPath('data.format', 'html');

    expect($response->json('data.result'))->toContain('<section class="container">')
        ->and($response->json('data.result'))->not->toContain('<script')
        ->and(DB::table('topics')->count())->toBe(0)
        ->and(AiUsageLog::query()->value('status'))->toBe('success')
        ->and(AiUsageLog::query()->value('request_id'))->toBe($response->json('meta.request_id'));

    TopicWriterAgent::assertPrompted(fn ($prompt) => str_contains($prompt->prompt, 'Source title'));
});

test('AI block descriptions are generated as plain text', function () {
    $fixtures = aiFeatureFixtures();
    TopicWriterAgent::fake(['<strong>Concise block description</strong>'])->preventStrayPrompts();

    $this->actingAs($fixtures['user'])->postJson(route('topicsAiContent', $fixtures['section']), [
        'language' => 'en',
        'operation' => 'write',
        'target' => 'description',
        'value' => '',
        'description' => '',
    ])->assertOk()
        ->assertJsonPath('data.format', 'plain')
        ->assertJsonPath('data.result', 'Concise block description');
});

test('AI redesigns landing static block details as sanitized rich content', function () {
    $fixtures = aiFeatureFixtures();
    $fixtures['section']->forceFill(['type' => 10, 'editor_status' => 0])->save();
    TopicTransformAgent::fake(['<section class="container unsafe"><h2>Redesigned block</h2><script>alert(1)</script></section>'])
        ->preventStrayPrompts();

    $this->actingAs($fixtures['user'])->postJson(route('topicsAiContent', $fixtures['section']), [
        'language' => 'en',
        'operation' => 'redesign',
        'target' => 'details',
        'content_context' => 'landing_static',
        'value' => '<p>Original block</p>',
        'details' => '<p>Original block</p>',
    ])->assertOk()
        ->assertJsonPath('data.format', 'html')
        ->assertJsonPath('data.result', '<section class="container"><h2>Redesigned block</h2></section>');

    $fixtures['section']->forceFill(['type' => 1])->save();
    $this->postJson(route('topicsAiContent', $fixtures['section']), [
        'language' => 'en',
        'operation' => 'redesign',
        'target' => 'details',
        'content_context' => 'landing_static',
        'value' => '<p>Not a landing block</p>',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('content_context');
});

test('resource AI writes plain banner content without saving the banner', function () {
    $fixtures = aiFeatureFixtures();
    $bannerSection = WebmasterBanner::query()->forceCreate([
        'title_en' => 'Homepage hero',
        'type' => 1,
        'desc_status' => 1,
        'status' => 1,
    ]);
    $banner = Banner::query()->forceCreate([
        'section_id' => $bannerSection->id,
        'title_en' => 'Original banner',
        'details_en' => 'Original details',
        'created_by' => $fixtures['user']->id,
    ]);
    TopicWriterAgent::fake(['<strong>Sharper banner copy</strong>'])->preventStrayPrompts();

    $this->actingAs($fixtures['user'])->postJson(route('resourceAiContent'), [
        'resource_type' => 'banner',
        'resource_id' => $banner->id,
        'context_id' => $bannerSection->id,
        'language' => 'en',
        'operation' => 'write',
        'target' => 'details',
        'value' => '',
        'title' => 'Original banner',
    ])->assertOk()
        ->assertJsonPath('data.format', 'plain')
        ->assertJsonPath('data.result', 'Sharper banner copy');

    expect($banner->fresh()->details_en)->toBe('Original details')
        ->and(AiUsageLog::query()->latest('id')->first()->metadata['resource_type'])->toBe('banner');
});

test('resource AI sanitizes rich popup details and enforces module permission', function () {
    $fixtures = aiFeatureFixtures();
    $popup = Popup::query()->forceCreate([
        'title_en' => 'Offer',
        'details_en' => '<p>Original</p>',
        'created_by' => $fixtures['user']->id,
    ]);
    TopicWriterAgent::fake(['<section class="container unsafe"><h2>Offer</h2><script>alert(1)</script></section>'])->preventStrayPrompts();

    $response = $this->actingAs($fixtures['user'])->postJson(route('resourceAiContent'), [
        'resource_type' => 'popup',
        'resource_id' => $popup->id,
        'language' => 'en',
        'operation' => 'write',
        'target' => 'details',
        'value' => '',
    ])->assertOk()->assertJsonPath('data.format', 'html');

    expect($response->json('data.result'))->toContain('<section class="container">')
        ->and($response->json('data.result'))->not->toContain('<script')
        ->and($popup->fresh()->details_en)->toBe('<p>Original</p>');

    $fixtures['group']->forceFill(['popups_status' => 0])->save();
    $this->actingAs($fixtures['user']->fresh())->postJson(route('resourceAiContent'), [
        'resource_type' => 'popup',
        'resource_id' => $popup->id,
        'language' => 'en',
        'operation' => 'write',
        'target' => 'title',
    ])->assertForbidden();
});

test('resource AI enforces create edit ownership and valid resource identifiers', function () {
    $fixtures = aiFeatureFixtures(permissionOverrides: ['view_status' => 1]);
    $otherPopup = Popup::query()->forceCreate(['title_en' => 'Private popup', 'created_by' => $fixtures['user']->id + 100]);
    $payload = [
        'resource_type' => 'popup',
        'resource_id' => $otherPopup->id,
        'language' => 'en',
        'operation' => 'write',
        'target' => 'title',
    ];

    $this->actingAs($fixtures['user'])->postJson(route('resourceAiContent'), $payload)->assertForbidden();
    $this->postJson(route('resourceAiContent'), array_replace($payload, ['resource_id' => 999999]))->assertForbidden();

    $fixtures['group']->forceFill(['view_status' => 0, 'add_status' => 0])->save();
    $this->actingAs($fixtures['user']->fresh())->postJson(route('resourceAiContent'), [
        'resource_type' => 'popup',
        'language' => 'en',
        'operation' => 'write',
        'target' => 'title',
    ])->assertForbidden();
});

test('website AI returns structured SEO suggestions and never saves settings', function () {
    $fixtures = aiFeatureFixtures();
    $setting = Setting::query()->firstOrFail();
    $setting->forceFill(['site_title_en' => 'Original website', 'site_desc_en' => 'Original description'])->save();
    WebsiteSeoAgent::fake([[
        'seo_title' => '<b>Search-ready website</b>',
        'seo_description' => '<span>A concise description</span>',
        'keywords' => ['consulting', '<b>business growth</b>'],
    ]])->preventStrayPrompts();

    $this->actingAs($fixtures['user'])->postJson(route('websiteAiSeo'), [
        'resource_type' => 'setting',
        'resource_id' => $setting->id,
        'language' => 'en',
        'seo_title' => 'Original website',
        'seo_description' => 'Original description',
        'seo_keywords' => '',
    ])->assertOk()
        ->assertJsonPath('data.suggestions.seo_title', 'Search-ready website')
        ->assertJsonPath('data.suggestions.seo_description', 'A concise description')
        ->assertJsonPath('data.suggestions.keywords.1', 'business growth');

    expect($setting->fresh()->site_title_en)->toBe('Original website');
});

test('resource AI translates only supported website SEO fields', function () {
    $fixtures = aiFeatureFixtures();
    Language::query()->forceCreate(['title' => 'Arabic', 'code' => 'ar', 'direction' => 'rtl', 'status' => 1, 'box_status' => 1]);
    $setting = Setting::query()->firstOrFail();
    TopicTranslatorAgent::fake([[
        'segments' => [
            ['id' => 'seo_title', 'text' => 'عنوان الموقع'],
            ['id' => 'seo_description', 'text' => 'وصف الموقع'],
        ],
    ]])->preventStrayPrompts();

    $this->actingAs($fixtures['user'])->postJson(route('resourceAiTranslate'), [
        'resource_type' => 'setting',
        'resource_id' => $setting->id,
        'source_language' => 'en',
        'target_language' => 'ar',
        'scope' => 'seo',
        'fields' => ['seo_title' => 'Website title', 'seo_description' => 'Website description'],
    ])->assertOk()
        ->assertJsonPath('data.fields.seo_title', 'عنوان الموقع')
        ->assertJsonPath('data.fields.seo_description', 'وصف الموقع');

    $this->postJson(route('resourceAiTranslate'), [
        'resource_type' => 'setting',
        'resource_id' => $setting->id,
        'source_language' => 'en',
        'target_language' => 'ar',
        'scope' => 'seo',
        'fields' => ['details' => 'Not allowed'],
    ])->assertUnprocessable();
});

test('System Settings permission grants AI settings access and credentials remain protected', function () {
    $fixtures = aiFeatureFixtures(permissionOverrides: ['webmaster_status' => 1]);
    $this->actingAs($fixtures['user'])->followingRedirects()->get(route('aiSettings'))
        ->assertOk()
        ->assertSee(__('backend.aiSettings'))
        ->assertSee('id="ai-settings-form"', false)
        ->assertSee('id="settingsForm"', false)
        ->assertDontSee('fake-provider-key');
    $payload = [
        'ai_text_provider' => 'openai',
        'ai_text_model' => '',
        'ai_default_tone' => 'professional',
        'ai_default_content_length' => 'medium',
        'ai_max_tokens' => 4000,
        'ai_timeout_seconds' => 120,
        'ai_image_provider' => 'openai',
        'ai_image_model' => '',
        'ai_image_quality' => 'medium',
        'ai_image_orientation' => 'automatic',
        'ai_image_style' => 'automatic',
        'credentials' => [
            'openai' => ['key' => 'database-secret-key'],
            'gemini' => ['key' => 'inactive-provider-key'],
        ],
    ];

    $this->actingAs($fixtures['user'])->put(route('aiSettingsUpdate'), $payload)->assertRedirect();
    $settings = $fixtures['settings']->fresh();
    $raw = DB::table('webmaster_settings')->where('id', 1)->value('ai_provider_credentials');
    expect($raw)->not->toContain('database-secret-key')
        ->and($settings->ai_provider_credentials['openai']['key'])->toBe('database-secret-key')
        ->and($settings->ai_provider_credentials)->not->toHaveKey('gemini')
        ->and($settings->toArray())->not->toHaveKey('ai_provider_credentials');

    $payload['credentials']['openai']['key'] = '';
    $this->actingAs($fixtures['user'])->put(route('aiSettingsUpdate'), $payload)->assertRedirect();
    expect($fixtures['settings']->fresh()->ai_provider_credentials['openai']['key'])->toBe('database-secret-key');

    $payload['remove_credentials'] = ['openai' => ['key' => 1]];
    $this->actingAs($fixtures['user'])->put(route('aiSettingsUpdate'), $payload)->assertRedirect();
    expect($fixtures['settings']->fresh()->ai_provider_credentials)->toBeNull();

    // Environment/SDK credentials must never satisfy database-only readiness.
    config(['ai.providers.openai.key' => 'environment-key']);
    $payload['ai_enabled'] = 1;
    $payload['ai_content_enabled'] = 1;
    $payload['ai_text_model'] = 'text-model';
    unset($payload['remove_credentials']);
    $this->put(route('aiSettingsUpdate'), $payload)->assertSessionHasErrors('ai_text_provider');

    $payload['credentials']['openai']['key'] = 'new-secret';
    $payload['ai_text_model'] = '';
    $this->put(route('aiSettingsUpdate'), $payload)->assertSessionHasErrors('ai_text_model');
    expect(session()->getOldInput('credentials'))->toBeNull();

    $payload['ai_text_model'] = 'text-model';
    $payload['ai_images_enabled'] = 1;
    $this->put(route('aiSettingsUpdate'), $payload)->assertSessionHasErrors('ai_image_model');
    $payload['ai_image_model'] = 'image-model';
    $this->put(route('aiSettingsUpdate'), $payload)->assertSessionHasNoErrors();

    $payload['ai_text_provider'] = 'gemini';
    $payload['credentials']['gemini']['key'] = 'gemini-secret';
    $this->put(route('aiSettingsUpdate'), $payload)->assertSessionHasNoErrors();
    expect($fixtures['settings']->fresh()->ai_provider_credentials)->toHaveKeys(['openai', 'gemini']);

    $payload['ai_images_enabled'] = 0;
    $this->put(route('aiSettingsUpdate'), $payload)->assertSessionHasNoErrors();
    expect($fixtures['settings']->fresh()->ai_provider_credentials)->not->toHaveKey('openai');
    $service = app(\App\Services\Ai\AiSettingsService::class);
    expect($service->providerReady('openai'))->toBeFalse();
    app(\App\Services\Ai\AiProviderConfigurator::class)->apply('gemini');
    expect(array_keys(config('ai.providers')))->toBe(['gemini'])
        ->and(config('ai.providers.gemini.key'))->toBe('gemini-secret');
});

test('AI settings reject users without the System Settings permission', function () {
    $fixtures = aiFeatureFixtures(permissionOverrides: ['webmaster_status' => 0]);

    $this->actingAs($fixtures['user'])->get(route('aiSettings'))
        ->assertRedirect(route('NoPermission'));
});

test('Topic assistant view compiles safely when controls are unavailable', function () {
    $fixtures = aiFeatureFixtures();

    $this->view('dashboard.topics.ai.assistant', [
        'aiCapabilities' => [
            'content' => false,
            'seo' => false,
            'translation' => false,
            'images' => false,
            'alt' => false,
        ],
        'WebmasterSection' => $fixtures['section'],
    ])->assertDontSee('smartend-ai-image-generate', false);
});

test('Topic AI controls use one accessible menu and preview actions start disabled', function () {
    $fixtures = aiFeatureFixtures();
    $capabilities = ['content' => true, 'seo' => true, 'translation' => true, 'images' => false, 'alt' => false];

    $this->view('dashboard.topics.ai.field-actions', [
        'aiCapabilities' => $capabilities,
        'aiTarget' => 'title',
        'aiLanguage' => 'en',
        'WebmasterSection' => $fixtures['section'],
    ])->assertSee('input-group-btn dropdown', false)
        ->assertSee('smartend-ai-translate', false)
        ->assertSee('smartend-ai-translate-all', false);

    $this->view('dashboard.topics.ai.field-actions', [
        'aiCapabilities' => $capabilities,
        'aiTarget' => 'details',
        'aiLanguage' => 'en',
        'aiPlacement' => 'editor',
        'WebmasterSection' => $fixtures['section'],
    ])->assertSee('smartend-ai-editor-tools dropdown', false)
        ->assertDontSee('input-group-btn', false);

    $this->view('dashboard.topics.ai.assistant', [
        'aiCapabilities' => $capabilities,
        'WebmasterSection' => $fixtures['section'],
    ])->assertSee('id="smartend-ai-replace" disabled', false)
        ->assertSee('id="smartend-ai-insert" disabled', false)
        ->assertSee('id="smartend-ai-copy" disabled', false)
        ->assertSee('id="smartend-ai-regenerate" disabled', false)
        ->assertSee('id="smartend-ai-apply-all" hidden disabled', false)
        ->assertSee('id="smartend-ai-original-html"', false)
        ->assertDontSee('<iframe id="smartend-ai-html-result"', false);

    expect(File::get(public_path('assets/dashboard/js/smartend-ai.js')))
        ->toContain('function sanitizePreviewHtml(html)')
        ->toContain('function seoOriginalText(payload)')
        ->toContain('preparePreview(seoOriginalText(payload))')
        ->toContain("setPreviewContent('original', preview.original, rich)")
        ->toContain("setPreviewContent('result', preview.result, rich)")
        ->not->toContain('srcdoc');

    $topic = Topic::query()->forceCreate([
        'webmaster_id' => $fixtures['section']->id,
        'created_by' => $fixtures['user']->id,
        'title_en' => 'SEO topic',
        'status' => 1,
    ]);
    $fixtures['section']->forceFill(['seo_status' => 1])->save();
    $this->view('dashboard.topics.tabs.seo', [
        'aiCapabilities' => $capabilities,
        'WebmasterSection' => $fixtures['section'],
        'Topic' => $topic,
        'tab_2' => 'active',
    ])->assertSee('id="seo_photo_alt_en"', false)
        ->assertSee('smartend-ai-seo', false)
        ->assertDontSee('smartend-ai-alt', false);
});

test('Static landing block loads AI editor actions with an isolated field id', function () {
    $fixtures = aiFeatureFixtures();
    $fixtures['section']->forceFill(['type' => 10, 'editor_status' => 0])->save();
    $topic = Topic::query()->forceCreate([
        'webmaster_id' => $fixtures['section']->id,
        'created_by' => $fixtures['user']->id,
        'title_en' => 'Landing page',
        'status' => 1,
    ]);

    $createResponse = $this->actingAs($fixtures['user'])->post(route('topicBlocksCreate'), [
        'topic_id' => encrypt($topic->id),
        'content_type' => 'static',
    ])->assertOk();

    $createResponse
        ->assertSee('id="landing_block_title_new_en"', false)
        ->assertSee('data-ai-field="landing_block_title_new_en"', false)
        ->assertSee('id="landing_block_description_new_en"', false)
        ->assertSee('data-ai-field="landing_block_description_new_en"', false)
        ->assertSee('id="landing_block_details_new_en"', false)
        ->assertSee('data-ai-field="landing_block_details_new_en"', false)
        ->assertSee('data-ai-content-context="landing_static"', false)
        ->assertSee('data-operation="redesign"', false)
        ->assertSee('smartend-ai-editor-tools dropdown', false)
        ->assertSee('smartend-ai-translate', false);

    $block = TopicBlock::query()->forceCreate([
        'topic_id' => $topic->id,
        'type' => 0,
        'row_no' => 1,
        'status' => 1,
        'content' => json_encode(['details_en' => '<p>Existing block</p>']),
        'created_by' => $fixtures['user']->id,
    ]);

    $this->post(route('topicBlocksEdit'), [
        'topic_id' => encrypt($topic->id),
        'block_id' => encrypt($block->id),
    ])->assertOk()
        ->assertSee('id="landing_block_details_'.$block->id.'_en"', false)
        ->assertSee('data-ai-content-context="landing_static"', false)
        ->assertSee('data-operation="redesign"', false);
});

test('image generation uses fakes mandatory exclusions private signed previews and owner cancellation', function () {
    Storage::fake('local');
    $fixtures = aiFeatureFixtures(settingsOverrides: [
        'ai_images_enabled' => 1,
        'ai_image_provider' => 'openai',
        'ai_image_negative_instructions' => 'Legacy custom exclusion that must be ignored',
    ]);
    TopicImagePromptAgent::fake([['optimized_prompt' => 'A calm editorial workspace']])->preventStrayPrompts();

    $image = imagecreatetruecolor(3, 2);
    imagefill($image, 0, 0, imagecolorallocate($image, 10, 50, 90));
    ob_start();
    imagepng($image);
    $bytes = (string) ob_get_clean();
    imagedestroy($image);
    Image::fake([base64_encode($bytes)])->preventStrayImages();

    $response = $this->actingAs($fixtures['user'])->postJson(route('topicsAiImage', $fixtures['section']), [
        'language' => 'en',
        'title' => 'A secure CMS workflow',
        'orientation' => 'landscape',
        'quality' => 'medium',
    ])->assertOk()->assertJsonPath('data.width', 3)->assertJsonPath('data.height', 2);

    $asset = AiGeneratedAsset::query()->findOrFail($response->json('data.asset_id'));
    Storage::disk('local')->assertExists($asset->path);
    expect($asset->optimized_prompt)->toContain('Mandatory exclusions:')
        ->and($asset->optimized_prompt)->toContain('No written text')
        ->and($asset->optimized_prompt)->not->toContain('Legacy custom exclusion');
    TopicImagePromptAgent::assertPrompted(fn ($prompt) => str_contains($prompt->prompt, 'BRAND_CONTEXT:')
        && str_contains($prompt->prompt, 'THEME_COLORS:'));
    Image::assertGenerated(fn ($prompt) => str_contains($prompt->prompt, 'Mandatory exclusions:'));

    $this->actingAs($fixtures['user'])->get($response->json('data.preview_url'))
        ->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');

    $other = User::query()->forceCreate([
        'name' => 'Other AI Editor',
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('password'),
        'permissions_id' => $fixtures['group']->id,
        'status' => 1,
    ]);
    $this->actingAs($other)->get($response->json('data.preview_url'))->assertNotFound();
    $this->actingAs($fixtures['user'])->deleteJson(route('aiAssetDestroy', $asset))->assertOk();
    expect(AiGeneratedAsset::query()->find($asset->id))->toBeNull();
    Storage::disk('local')->assertMissing($asset->path);
});

test('named AI throttles return stable JSON errors per user', function () {
    config(['smartend_ai.text_rate_limit' => 2]);
    $fixtures = aiFeatureFixtures();
    $payload = ['language' => 'en', 'operation' => 'invalid', 'target' => 'title'];

    $this->actingAs($fixtures['user'])->postJson(route('topicsAiContent', $fixtures['section']), $payload)->assertUnprocessable();
    $this->actingAs($fixtures['user'])->postJson(route('topicsAiContent', $fixtures['section']), $payload)->assertUnprocessable();
    $this->actingAs($fixtures['user'])->postJson(route('topicsAiContent', $fixtures['section']), $payload)
        ->assertTooManyRequests()->assertJson(['code' => 'ai_rate_limited']);
});
