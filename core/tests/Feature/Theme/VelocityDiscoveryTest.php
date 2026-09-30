<?php

use App\Models\WebmasterSection;
use App\Services\ThemeService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Tests\Support\VelocityContentSeeder;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(VelocityContentSeeder::class);

    config([
        'smartend.theme' => 'velocity',
        'smartend.frontend_pagination' => 1,
    ]);
    ThemeService::flushRuntimeCache();
});

afterEach(function () {
    ThemeService::flushRuntimeCache();
});

function velocityDiscoveryModule(int $type): WebmasterSection
{
    return WebmasterSection::query()
        ->where('seo_url_slug_en', 'velocity-test-module-'.$type)
        ->firstOrFail();
}

test('velocity renders every supported module through its native server route', function () {
    $expectations = [
        0 => ['velocity-topic-results', 'data-velocity-native-pagination'],
        5 => ['velocity-data-table', 'advanced-search-form'],
        7 => ['velocity-status--error', 'role="alert"'],
        8 => ['velocity-faq-grid', '<details'],
        9 => ['velocity-docs-layout', 'docs-topic-'],
    ];

    foreach ($expectations as $type => $markers) {
        $module = velocityDiscoveryModule($type);
        $response = $this->get(Helper::sectionURL($module->id, '', $module))->assertOk();

        foreach ($markers as $marker) {
            $response->assertSee($marker, false);
        }
    }
});

test('velocity topic listing preserves native query state and exposes reusable html fragments', function () {
    $module = velocityDiscoveryModule(0);

    $this->get(Helper::sectionURL($module->id, '', $module).'?search_word=Velocity')
        ->assertOk()
        ->assertSee('name="search_word"', false)
        ->assertSee('value="Velocity"', false)
        ->assertSee('search_word=Velocity', false)
        ->assertSee('data-velocity-component="topic-explorer"', false);

    $this->get(route('HomeTopicsList', [
        'id' => $module->id,
        'cat' => 0,
        'count' => 1,
        'page' => 1,
        'lang' => 'en',
        'search_word' => 'Velocity',
    ]))
        ->assertOk()
        ->assertSee('data-velocity-topic-card', false)
        ->assertDontSee('<html', false)
        ->assertDontSee('data-theme="velocity"', false);
});

test('velocity topic listings show a sidebar only when the module has a category', function () {
    $module = velocityDiscoveryModule(0);
    $url = Helper::sectionURL($module->id, '', $module);

    $this->get($url)
        ->assertOk()
        ->assertSee('velocity-content-layout--full', false)
        ->assertSee('velocity-section-listing--uncategorized', false)
        ->assertSee('velocity-topic-card--listing-featured', false)
        ->assertDontSee('class="velocity-sidebar"', false);

    DB::table('sections')->insert([
        'title_en' => 'Velocity category',
        'title_ar' => 'تصنيف فيلوسيتي',
        'seo_url_slug_en' => 'velocity-category',
        'seo_url_slug_ar' => 'velocity-category-ar',
        'status' => 1,
        'webmaster_id' => $module->id,
        'father_id' => 0,
        'row_no' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->get($url)
        ->assertOk()
        ->assertSee('class="velocity-sidebar"', false)
        ->assertSee('velocity-section-listing--categorized', false)
        ->assertDontSee('velocity-content-layout--full', false);
});

test('velocity type zero listings expose responsive featured and supporting layouts', function () {
    $view = File::get(resource_path('views/themes/velocity/topics.blade.php'));
    $fragment = File::get(resource_path('views/themes/velocity/topic/listing.blade.php'));
    $styles = File::get(resource_path('themes/velocity/css/components.css'));

    expect($view)
        ->toContain('$velocityIsSectionIndex')
        ->toContain("'variant' => 'listing-featured'")
        ->toContain('velocity-topic-grid--{{ $velocitySectionIndexMode }}')
        ->and($fragment)
        ->toContain('$velocityFragmentSectionType === 0')
        ->toContain("'variant' => 'service'")
        ->and($styles)
        ->toContain('.velocity-section-listing__lead .velocity-topic-card--listing-featured')
        ->toContain('.velocity-topic-grid--section-index.velocity-topic-grid--uncategorized')
        ->toContain('.velocity-results .velocity-topic-card:hover')
        ->toContain('border-color: var(--color-border-soft);')
        ->toContain('grid-template-columns: repeat(2, minmax(0, 1fr));');
});

test('velocity table renders custom columns and an accessible chart text equivalent', function () {
    $module = velocityDiscoveryModule(5);
    $topicId = DB::table('topics')->where('webmaster_id', $module->id)->value('id');
    $fieldId = DB::table('webmaster_section_fields')->insertGetId([
        'webmaster_id' => $module->id,
        'type' => 6,
        'title_en' => 'Delivery status',
        'title_ar' => 'حالة التسليم',
        'details_en' => "Open\nClosed",
        'details_ar' => "مفتوح\nمغلق",
        'status' => 1,
        'in_search' => 1,
        'in_listing' => 1,
        'in_statics' => 1,
        'lang_code' => 'all',
        'colors' => "#2563eb\n#0f766e",
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('topic_fields')->insert([
        'topic_id' => $topicId,
        'field_id' => $fieldId,
        'field_value' => '1',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->get(Helper::sectionURL($module->id, '', $module))
        ->assertOk()
        ->assertSee('Delivery status')
        ->assertSee('Open')
        ->assertSee('data-velocity-chart', false)
        ->assertSee('velocity-chart-table', false);
});

test('velocity tag directory and xml sitemap retain their public contracts', function () {
    DB::table('tags')->insert([
        'title' => 'Velocity',
        'seo_url' => 'velocity',
        'details' => 'Velocity test tag.',
        'status' => 1,
        'visits' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->get(route('tagsListPage'))
        ->assertOk()
        ->assertSee('velocity-tag-cloud', false);

    $this->get(route('siteMap'))
        ->assertOk()
        ->assertHeader('content-type', 'text/xml; charset=UTF-8')
        ->assertSee('<?xml version="1.0" encoding="UTF-8"?>', false)
        ->assertSee('xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"', false)
        ->assertDontSee('<html', false);
});

test('velocity source remains bootstrap free and includes native and enhanced discovery paths', function () {
    $required = [
        'topics.blade.php',
        'search.blade.php',
        'faq.blade.php',
        'docs.blade.php',
        'table.blade.php',
        'tags.blade.php',
        'sitemap.blade.php',
        'layouts/side.blade.php',
        'layouts/search.blade.php',
        'topic/card.blade.php',
        'topic/card2.blade.php',
        'topic/listing.blade.php',
        'topic/accordion.blade.php',
        'topic/cover.blade.php',
    ];

    foreach ($required as $relativePath) {
        $source = File::get(resource_path('views/themes/velocity/'.$relativePath));

        expect($source)
            ->not->toContain('bootstrap')
            ->not->toContain('jquery')
            ->not->toContain('$(');
    }

    expect(File::get(resource_path('views/themes/velocity/topics.blade.php')))
        ->toContain('data-velocity-native-pagination')
        ->toContain("route('HomeTopicsList'")
        ->toContain('topic-explorer')
        ->and(File::get(resource_path('views/themes/velocity/table.blade.php')))
        ->toContain('data-velocity-chart')
        ->toContain('velocity-chart-table');
});

test('velocity secondary topic cards use a compact accessible media layout', function () {
    $card = File::get(resource_path('views/themes/velocity/topic/card2.blade.php'));
    $styles = File::get(resource_path('themes/velocity/css/components.css'));

    expect($card)
        ->toContain('class="velocity-topic-card2"')
        ->toContain('aria-labelledby=')
        ->toContain('sizes="(max-width: 30rem) 5.25rem, 6.75rem"')
        ->toContain("['name' => 'image']")
        ->and($styles)
        ->toContain('.velocity-topic-card2 {')
        ->toContain('grid-template-columns: clamp(5.25rem, 28%, 6.75rem) minmax(0, 1fr);')
        ->toContain('-webkit-line-clamp: 3;')
        ->toContain('@media (prefers-reduced-motion: reduce)');
});

test('velocity sidebar categories expose a connected rtl-safe hierarchy', function () {
    $tree = File::get(resource_path('views/themes/velocity/components/category-tree.blade.php'));
    $styles = File::get(resource_path('themes/velocity/css/components.css'));

    expect($tree)
        ->toContain('data-category-depth=')
        ->toContain('velocity-category-list__item--parent')
        ->toContain("'depth' => \$depth + 1")
        ->toContain('velocity-category-list__chevron')
        ->and($styles)
        ->toContain('.velocity-category-list--nested > .velocity-category-list__item::before')
        ->toContain('inset-inline-start: -1.45rem;')
        ->toContain('border-inline-start: 1px solid');
});
