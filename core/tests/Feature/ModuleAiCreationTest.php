<?php

use App\Ai\Agents\ModuleDefinitionAgent;
use App\Models\AiUsageLog;
use App\Models\Language;
use App\Models\Permissions;
use App\Models\User;
use App\Models\WebmasterSection;
use App\Models\WebmasterSectionField;
use App\Models\WebmasterSetting;
use App\Services\Ai\ModuleDefinitionValidator;
use App\Services\Ai\PromptLoader;
use App\Services\Dashboard\ModuleCreationService;
use App\Services\Dashboard\ModuleDefinitionCatalog;
use Database\Seeders\WebmasterSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Exceptions\InsufficientCreditsException;
use Laravel\Ai\Exceptions\RateLimitedException;

uses(RefreshDatabase::class);

test('choice fields persist translated labels and partial colors in exact option order', function (int $type) {
    $fixtures = moduleAiFixtures();
    $definition = validAgentModuleDefinition();
    $definition['custom_fields'][0]['type'] = $type;
    $definition['custom_fields'][0]['default_value'] = in_array($type, [6, 13], true) ? '2' : null;
    $definition['custom_fields'][0]['options'] = [
        ['labels' => [['language' => 'en', 'value' => 'High'], ['language' => 'ar', 'value' => 'عالية']], 'color' => '#dc2626'],
        ['labels' => [['language' => 'en', 'value' => 'Medium'], ['language' => 'ar', 'value' => 'متوسطة']], 'color' => null],
        ['labels' => [['language' => 'en', 'value' => 'Low'], ['language' => 'ar', 'value' => 'منخفضة']], 'color' => '#16a34a'],
        ['labels' => [['language' => 'en', 'value' => 'Deferred'], ['language' => 'ar', 'value' => 'مؤجلة']], 'color' => null],
    ];
    createAiModule($this, $fixtures['user'], $definition)->assertCreated();
    $field = WebmasterSectionField::query()->firstOrFail();
    expect($field->details_en)->toBe("High\nMedium\nLow\nDeferred")
        ->and($field->details_ar)->toBe("عالية\nمتوسطة\nمنخفضة\nمؤجلة")
        ->and($field->colors)->toBe("#dc2626\n\n#16a34a\n");
})->with([6, 13, 7, 17]);

test('uncolored choice fields use the manual null representation', function () {
    $fixtures = moduleAiFixtures();
    createAiModule($this, $fixtures['user'], validAgentModuleDefinition())->assertCreated();
    expect(WebmasterSectionField::query()->firstOrFail()->colors)->toBeNull();
});

test('invalid option objects are rejected before any module or field is created', function (string $case) {
    $fixtures = moduleAiFixtures();
    $definition = validModuleDefinition();
    $field = &$definition['custom_fields'][0];
    switch ($case) {
        case 'missing translation': unset($field['options'][1]['labels']['ar']);
            break;
        case 'extra translation': $field['options'][1]['labels']['fr'] = 'Maison';
            break;
        case 'multiline': $field['options'][0]['labels']['en'] = "High\nLow";
            break;
        case 'color injection': $field['options'][0]['color'] = '#ffffff;background:url(secret)';
            break;
        case 'unsupported color format': $field['options'][0]['color'] = 'rgba(0,0,0,1)';
            break;
        case 'empty color': $field['options'][0]['color'] = '';
            break;
        case 'separate mismatched colors': $field['colors'] = ['#ffffff'];
            break;
        case 'nonchoice': $field['type'] = 14;
            $field['default_value'] = '1';
            break;
        case 'old per-language lists': $field['options'] = ['en' => ['High'], 'ar' => ['عالية']];
            break;
        case 'unknown option key': $field['options'][0]['sql'] = 'unexpected';
            break;
    }
    createAiModule($this, $fixtures['user'], $definition)->assertStatus(502);
    expect(WebmasterSection::query()->count())->toBe(0)->and(WebmasterSectionField::query()->count())->toBe(0);
})->with(['missing translation', 'extra translation', 'multiline', 'color injection', 'unsupported color format', 'empty color', 'separate mismatched colors', 'nonchoice', 'old per-language lists', 'unknown option key']);

test('AI context supplies only eligible related modules and saves relationships by ID', function () {
    $fixtures = moduleAiFixtures(permissions: ['view_status' => 1]);
    $brand = WebmasterSection::query()->forceCreate(['title_en' => 'Brands', 'title_ar' => 'العلامات', 'type' => 5, 'status' => 1, 'created_by' => $fixtures['user']->id]);
    WebmasterSection::query()->forceCreate(['title_en' => 'Inactive secret', 'status' => 0, 'created_by' => $fixtures['user']->id]);
    WebmasterSection::query()->forceCreate(['title_en' => 'Inaccessible secret', 'status' => 1, 'created_by' => 999]);
    $definition = validModuleDefinition();
    $definition['custom_fields'][0]['type'] = 18;
    $definition['custom_fields'][0]['options'] = [];
    $definition['custom_fields'][0]['default_value'] = null;
    $definition['custom_fields'][0]['parent_module_id'] = $brand->id;
    createAiModule($this, $fixtures['user'], $definition)->assertCreated();
    expect((int) WebmasterSectionField::query()->value('parent_module_id'))->toBe($brand->id);
    ModuleDefinitionAgent::assertPrompted(fn ($prompt) => str_contains($prompt->prompt, 'ELIGIBLE_RELATED_MODULES')
        && str_contains($prompt->prompt, 'Brands')
        && str_contains($prompt->prompt, 'eligible_for_reference')
        && ! str_contains($prompt->prompt, 'Inactive secret')
        && ! str_contains($prompt->prompt, 'Inaccessible secret'));
});

test('related modules are revalidated inside creation after becoming unavailable', function (string $change) {
    $fixtures = moduleAiFixtures(permissions: ['view_status' => 1]);
    $parent = WebmasterSection::query()->forceCreate(['title_en' => 'Brands', 'type' => 5, 'status' => 1, 'created_by' => $fixtures['user']->id]);
    $definition = validModuleDefinition();
    $definition['custom_fields'][0]['type'] = 18;
    $definition['custom_fields'][0]['options'] = [];
    $definition['custom_fields'][0]['default_value'] = null;
    $definition['custom_fields'][0]['parent_module_id'] = $parent->id;
    $definition = app(ModuleDefinitionValidator::class)->validate($definition, $fixtures['user']);
    match ($change) {
        'disabled' => $parent->forceFill(['status' => 0])->save(),
        'inaccessible' => $parent->forceFill(['created_by' => 999])->save(),
        'deleted' => $parent->delete(),
    };
    expect(fn () => app(ModuleCreationService::class)->createFromDefinition($definition, $fixtures['user']))
        ->toThrow(ValidationException::class);
    expect(WebmasterSection::query()->where('title_en', 'Properties')->exists())->toBeFalse()
        ->and(WebmasterSectionField::query()->count())->toBe(0)
        ->and($fixtures['group']->fresh()->data_sections)->toBe('');
})->with(['disabled', 'inaccessible', 'deleted']);

test('a module absent from the original context cannot be introduced during generation', function () {
    $fixtures = moduleAiFixtures();
    $parent = WebmasterSection::query()->forceCreate(['title_en' => 'Brands', 'status' => 1]);
    $definition = validModuleDefinition();
    $definition['custom_fields'][0]['type'] = 18;
    $definition['custom_fields'][0]['options'] = [];
    $definition['custom_fields'][0]['default_value'] = null;
    $definition['custom_fields'][0]['parent_module_id'] = $parent->id;
    expect(fn () => app(ModuleDefinitionValidator::class)->validate($definition, $fixtures['user'], true, []))
        ->toThrow(ValidationException::class);
});

test('catalog and module system instructions distinguish field selection semantics', function () {
    $catalog = app(ModuleDefinitionCatalog::class)->forPrompt()['custom_field_types'];
    expect(array_keys($catalog))->toEqual(array_keys(ModuleDefinitionCatalog::CUSTOM_FIELD_TYPES));
    foreach ($catalog as $type) {
        expect($type['purpose'])->not->toBeEmpty();
    }
    expect($catalog[6]['purpose'])->toContain('compact dropdown')
        ->and($catalog[13]['purpose'])->toContain('two to five')
        ->and($catalog[7]['purpose'])->toContain('Multiple choices')
        ->and($catalog[17]['purpose'])->toContain('visible checkboxes')
        ->and($catalog[14]['options'])->toBe('Empty array only.');
    $instructions = app(PromptLoader::class)->load('module-system');
    expect($instructions)->toContain('equivalent existing basic', 'known finite values', 'including over duplicated choice options', 'Do not pack unrelated structured properties');
});

test('manual choice creation preserves translated details and partial color lines', function () {
    $fixtures = moduleAiFixtures();
    $section = WebmasterSection::query()->forceCreate(['title_en' => 'Manual', 'type' => 5, 'status' => 1]);
    $fixtures['group']->forceFill(['data_sections' => (string) $section->id])->save();
    $this->actingAs($fixtures['user'])->post(route('webmasterFieldsStore', $section->id), [
        'title_en' => 'Priority', 'title_ar' => 'الأولوية', 'type' => 6,
        'details_en' => "High\nMedium\nLow", 'details_ar' => "عالية\nمتوسطة\nمنخفضة",
        'colors' => "#dc2626\n\n#16a34a", 'lang_code' => 'all',
    ])->assertRedirect();
    $field = $section->allCustomFields()->firstOrFail();
    expect($field->details_en)->toBe("High\nMedium\nLow")
        ->and($field->details_ar)->toBe("عالية\nمتوسطة\nمنخفضة")
        ->and($field->colors)->toBe("#dc2626\n\n#16a34a");
});

function moduleAiFixtures(array $permissions = [], array $settings = []): array
{
    Language::query()->forceCreate(['title' => 'English', 'code' => 'en', 'direction' => 'ltr', 'status' => 1, 'box_status' => 1]);
    Language::query()->forceCreate(['title' => 'Arabic', 'code' => 'ar', 'direction' => 'rtl', 'status' => 1, 'box_status' => 1]);
    $admin = Permissions::query()->forceCreate(['id' => 1, 'name' => 'Super admin', 'status' => 1, 'modules_status' => 1, 'data_sections' => '']);
    $group = Permissions::query()->forceCreate(array_merge([
        'name' => 'Module managers', 'status' => 1, 'modules_status' => 1,
        'add_status' => 0, 'edit_status' => 0, 'delete_status' => 0,
        'ai_use' => 1, 'data_sections' => '',
    ], $permissions));
    $user = User::query()->create([
        'name' => 'Module manager', 'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('password'), 'permissions_id' => $group->id, 'status' => 1,
    ]);
    (new WebmasterSettingsSeeder)->run();
    $webmasterSettings = WebmasterSetting::query()->findOrFail(1);
    $webmasterSettings->forceFill(array_merge([
        'ai_enabled' => 1, 'ai_content_enabled' => 1, 'ai_text_provider' => 'openai',
        'ai_provider_credentials' => ['openai' => ['key' => 'fake-provider-key']],
    ], $settings))->save();

    return compact('group', 'admin', 'user', 'webmasterSettings');
}

function validModuleDefinition(array $overrides = []): array
{
    $basic = array_fill_keys(array_keys(ModuleDefinitionCatalog::BASIC_FIELDS), false);
    $basic['title_status'] = true;
    $tabs = array_fill_keys(array_keys(ModuleDefinitionCatalog::ADDITIONAL_TABS), false);

    return array_replace_recursive([
        'titles' => ['en' => 'Properties', 'ar' => 'العقارات'],
        'description' => 'A real estate listings module.',
        'module_type' => 5,
        'sections_status' => true,
        'basic_fields' => $basic,
        'additional_tabs' => $tabs,
        'custom_fields' => [[
            'labels' => ['en' => 'Property type', 'ar' => 'نوع العقار'],
            'type' => 6, 'required' => true, 'default_value' => '1',
            'options' => [
                ['labels' => ['en' => 'Apartment', 'ar' => 'شقة'], 'color' => null],
                ['labels' => ['en' => 'Villa', 'ar' => 'فيلا'], 'color' => null],
            ],
            'fixed_texts' => [], 'parent_module_id' => null,
            'in_table' => true, 'in_search' => true, 'in_listing' => true, 'in_page' => true, 'in_statics' => false,
        ]],
        'explanation' => ['A table module fits structured listings.'],
        'warnings' => [], 'unsupported_requirements' => [],
    ], $overrides);
}

function validAgentModuleDefinition(): array
{
    $proposal = validModuleDefinition();
    $proposal['titles'] = [
        ['language' => 'en', 'value' => 'Properties'],
        ['language' => 'ar', 'value' => 'العقارات'],
    ];
    $proposal['custom_fields'][0]['labels'] = [
        ['language' => 'en', 'value' => 'Property type'],
        ['language' => 'ar', 'value' => 'نوع العقار'],
    ];
    $proposal['custom_fields'][0]['options'] = [
        ['labels' => [['language' => 'en', 'value' => 'Apartment'], ['language' => 'ar', 'value' => 'شقة']], 'color' => null],
        ['labels' => [['language' => 'en', 'value' => 'Villa'], ['language' => 'ar', 'value' => 'فيلا']], 'color' => null],
    ];
    $proposal['custom_fields'][0]['fixed_texts'] = [];

    return $proposal;
}

function createAiModule($test, User $user, array $definition, ?string $description = null)
{
    ModuleDefinitionAgent::fake([$definition + ['can_create' => true]])->preventStrayPrompts();

    return $test->actingAs($user)->postJson(route('WebmasterSectionsAiCreate'), [
        'description' => $description ?? 'Create the requested module '.fake()->uuid(),
    ]);
}

test('module managers with AI access directly create multilingual modules without action permissions', function () {
    $fixtures = moduleAiFixtures();
    $response = createAiModule($this, $fixtures['user'], validAgentModuleDefinition())
        ->assertCreated()->assertSessionHas('doneMessage', __('backend.addDone'));
    $section = WebmasterSection::query()->where('title_en', 'Properties')->firstOrFail();
    $field = $section->allCustomFields()->firstOrFail();
    expect($field->title_ar)->toBe('نوع العقار')
        ->and($field->details_en)->toBe("Apartment\nVilla")
        ->and($field->row_no)->toBe(1)
        ->and((int) $field->in_table)->toBe(1)
        ->and($section->seo_url_slug_en)->not->toBeEmpty()
        ->and(explode(',', $fixtures['group']->fresh()->data_sections))->toContain((string) $section->id)
        ->and(explode(',', $fixtures['admin']->fresh()->data_sections))->toContain((string) $section->id)
        ->and($response->json('redirect'))->toBe(route('WebmasterSectionsEdit', $section->id))
        ->and(AiUsageLog::query()->value('action'))->toBe('module_creation');
    $this->get($response->json('redirect'))->assertOk()->assertSee(__('backend.addDone'));
});

test('module AI creation requires the unified AI permission', function () {
    $fixtures = moduleAiFixtures(['ai_use' => 0]);

    createAiModule($this, $fixtures['user'], validAgentModuleDefinition())->assertForbidden();

    expect(WebmasterSection::query()->count())->toBe(0);
});

test('generation sends minimal visible context and normalizes requirements', function () {
    $fixtures = moduleAiFixtures(permissions: ['view_status' => 1]);
    WebmasterSection::query()->forceCreate(['title_en' => 'Visible news', 'title_ar' => 'أخبار', 'type' => 5, 'status' => 1, 'created_by' => $fixtures['user']->id]);
    WebmasterSection::query()->forceCreate(['title_en' => 'Hidden module', 'type' => 5, 'created_by' => 999]);
    createAiModule($this, $fixtures['user'], validAgentModuleDefinition(), '<b>Create properties</b> with type.')->assertCreated();
    ModuleDefinitionAgent::assertPrompted(fn ($prompt) => str_contains($prompt->prompt, 'Visible news')
        && str_contains($prompt->prompt, 'rtl')
        && str_contains($prompt->prompt, 'Create properties')
        && ! str_contains($prompt->prompt, '<b>')
        && ! str_contains($prompt->prompt, 'Hidden module')
        && ! str_contains($prompt->prompt, 'fake-provider-key'));
});

test('AI readiness is required before direct creation', function (array $settings) {
    $fixtures = moduleAiFixtures(settings: $settings);
    ModuleDefinitionAgent::fake()->preventStrayPrompts();
    $this->actingAs($fixtures['user'])->postJson(route('WebmasterSectionsAiCreate'), [
        'description' => 'Create a property module.',
    ])->assertForbidden();
    expect(WebmasterSection::query()->count())->toBe(0)->and(AiUsageLog::query()->count())->toBe(0);
})->with([
    'disabled' => [['ai_enabled' => 0]],
    'content disabled' => [['ai_content_enabled' => 0]],
    'unsupported provider' => [['ai_text_provider' => 'invalid-provider']],
    'missing credentials' => [['ai_provider_credentials' => ['openai' => ['key' => '']]]],
]);

test('unauthorized users cannot create AI modules', function () {
    $fixtures = moduleAiFixtures(permissions: ['modules_status' => 0]);
    $this->actingAs($fixtures['user'])->postJson(route('WebmasterSectionsAiCreate'), ['description' => 'Create properties module.'])
        ->assertRedirect(route('NoPermission'));
    expect(WebmasterSection::query()->count())->toBe(0);
});

test('invalid requirements never invoke AI or persist client definitions', function () {
    $fixtures = moduleAiFixtures();
    ModuleDefinitionAgent::fake()->preventStrayPrompts();
    $this->actingAs($fixtures['user'])->postJson(route('WebmasterSectionsAiCreate'), [
        'description' => 'short', 'proposal' => validModuleDefinition(),
    ])->assertUnprocessable()->assertJsonValidationErrors('description');
    expect(WebmasterSection::query()->count())->toBe(0)->and(AiUsageLog::query()->count())->toBe(0);
});

test('direct creation retains the per-user AI rate limit', function () {
    $fixtures = moduleAiFixtures();
    config(['smartend_ai.text_rate_limit' => 1]);
    ModuleDefinitionAgent::fake([validAgentModuleDefinition() + ['can_create' => true]])->preventStrayPrompts();
    $this->actingAs($fixtures['user'])->postJson(route('WebmasterSectionsAiCreate'), ['description' => 'Create a property module.'])->assertCreated();
    $this->postJson(route('WebmasterSectionsAiCreate'), ['description' => 'Create a different module.'])->assertStatus(429);
    expect(WebmasterSection::query()->count())->toBe(1)->and(AiUsageLog::query()->count())->toBe(1);
});

test('module modal has one form and abandoned endpoints are removed', function () {
    $fixtures = moduleAiFixtures();
    $this->actingAs($fixtures['user'])->get(route('WebmasterSections'))
        ->assertOk()->assertSee('smartend-module-ai-form', false)
        ->assertSee('dir="auto"', false)
        ->assertDontSee('smartend-module-ai-step-review', false)
        ->assertDontSee('smartend-module-ai-generate', false);
    expect(Route::has('WebmasterSectionsAiProposal'))->toBeFalse()
        ->and(Route::has('WebmasterSectionsAiStore'))->toBeFalse();
});

test('repeated requirements reuse a committed result without another provider request', function () {
    $fixtures = moduleAiFixtures();
    ModuleDefinitionAgent::fake([validAgentModuleDefinition() + ['can_create' => true]])->preventStrayPrompts();
    $payload = ['description' => 'Create a property listing module.'];
    $first = $this->actingAs($fixtures['user'])->postJson(route('WebmasterSectionsAiCreate'), $payload)->assertCreated();
    $second = $this->postJson(route('WebmasterSectionsAiCreate'), $payload)->assertCreated();
    expect($second->json('redirect'))->toBe($first->json('redirect'))
        ->and(WebmasterSection::query()->count())->toBe(1)
        ->and(WebmasterSectionField::query()->count())->toBe(1)
        ->and(AiUsageLog::query()->count())->toBe(1);
});

test('concurrent requests for the same user are rejected while creation is running', function () {
    $fixtures = moduleAiFixtures();
    $lock = Cache::lock('module-ai:user:'.$fixtures['user']->id, 900);
    $lock->get();
    try {
        $this->actingAs($fixtures['user'])->postJson(route('WebmasterSectionsAiCreate'), ['description' => 'Create a property listing module.'])
            ->assertStatus(409)->assertJsonPath('code', 'module_creation_busy');
        expect(WebmasterSection::query()->count())->toBe(0)->and(AiUsageLog::query()->count())->toBe(0);
    } finally {
        $lock->release();
    }
});

test('optional unsupported requirements are shown after creating the useful module', function () {
    $fixtures = moduleAiFixtures();
    $definition = validAgentModuleDefinition();
    $definition['unsupported_requirements'] = ['Automated valuation is unavailable.'];
    $response = createAiModule($this, $fixtures['user'], $definition)->assertCreated()
        ->assertSessionHas('warningMessage', 'Automated valuation is unavailable.');
    $this->get($response->json('redirect'))->assertOk()->assertSee('Automated valuation is unavailable.');
});

test('unmappable core requirements never create a module', function () {
    $fixtures = moduleAiFixtures();
    createAiModule($this, $fixtures['user'], validAgentModuleDefinition() + ['can_create' => false])
        ->assertUnprocessable()->assertJsonPath('code', 'module_requirements_unsupported')
        ->assertJsonPath('message', __('backend.aiModuleClarify'));
    expect(WebmasterSection::query()->count())->toBe(0)->and(WebmasterSectionField::query()->count())->toBe(0);
});

test('an empty configuration is rejected even when AI claims it is useful', function () {
    $fixtures = moduleAiFixtures();
    $definition = validModuleDefinition();
    $definition['basic_fields'] = array_fill_keys(array_keys(ModuleDefinitionCatalog::BASIC_FIELDS), false);
    $definition['custom_fields'] = [];
    createAiModule($this, $fixtures['user'], $definition)->assertUnprocessable()
        ->assertJsonPath('code', 'module_requirements_unsupported');
    expect(WebmasterSection::query()->count())->toBe(0);
});

test('missing suitability decision is malformed and never creates a module', function () {
    $fixtures = moduleAiFixtures();
    ModuleDefinitionAgent::fake([validAgentModuleDefinition()])->preventStrayPrompts();
    $this->actingAs($fixtures['user'])->postJson(route('WebmasterSectionsAiCreate'), ['description' => 'Create a property module.'])
        ->assertStatus(502)->assertJsonPath('code', 'malformed_ai_output');
    expect(WebmasterSection::query()->count())->toBe(0);
});

test('failed generation releases duplicate protection so the same requirements can be retried', function () {
    $fixtures = moduleAiFixtures();
    $description = 'Create a property module.';
    createAiModule($this, $fixtures['user'], validAgentModuleDefinition() + ['can_create' => false], $description)->assertUnprocessable();
    createAiModule($this, $fixtures['user'], validAgentModuleDefinition(), $description)->assertCreated();
    expect(WebmasterSection::query()->count())->toBe(1);
});

test('provider quota and rate limits create no records', function (string $exception, int $status) {
    $fixtures = moduleAiFixtures();
    ModuleDefinitionAgent::fake(function () use ($exception) {
        throw new $exception('openai');
    })->preventStrayPrompts();
    $this->actingAs($fixtures['user'])->postJson(route('WebmasterSectionsAiCreate'), ['description' => 'Create a property module.'])
        ->assertStatus($status);
    expect(WebmasterSection::query()->count())->toBe(0)->and(WebmasterSectionField::query()->count())->toBe(0);
})->with([
    [InsufficientCreditsException::class, 402],
    [RateLimitedException::class, 429],
]);

test('permission persistence failure rolls back the module and all fields', function () {
    $fixtures = moduleAiFixtures();
    Permissions::updating(function () {
        throw new RuntimeException('Forced permission failure.');
    });
    createAiModule($this, $fixtures['user'], validAgentModuleDefinition())->assertStatus(500);
    expect(WebmasterSection::query()->count())->toBe(0)
        ->and(WebmasterSectionField::query()->count())->toBe(0)
        ->and($fixtures['group']->fresh()->data_sections)->toBe('')
        ->and($fixtures['admin']->fresh()->data_sections)->toBe('');
});

test('malformed unsafe and incompatible generated definitions create nothing', function (string $case) {
    $fixtures = moduleAiFixtures();
    $definition = validModuleDefinition();
    switch ($case) {
        case 'type': $definition['module_type'] = 123;
            break;
        case 'unknown': $definition['php_code'] = 'unexpected';
            break;
        case 'html': $definition['titles']['en'] = '<script>alert(1)</script>';
            break;
        case 'translation': unset($definition['titles']['ar']);
            break;
        case 'options': $definition['custom_fields'][0]['options'][1]['labels']['en'] = 'Apartment';
            break;
        case 'default': $definition['custom_fields'][0]['default_value'] = '99';
            break;
        case 'landing': $definition['module_type'] = 10;
            break;
        case 'related':
            $definition['custom_fields'][0]['type'] = 18;
            $definition['custom_fields'][0]['options'] = [];
            $definition['custom_fields'][0]['parent_module_id'] = 999;
            break;
        case 'oversized': $definition['custom_fields'] = array_fill(0, 26, $definition['custom_fields'][0]);
            break;
    }
    createAiModule($this, $fixtures['user'], $definition)->assertStatus(502)->assertJsonPath('code', 'malformed_ai_output');
    expect(WebmasterSection::query()->count())->toBe(0)->and(WebmasterSectionField::query()->count())->toBe(0);
})->with(['type', 'unknown', 'html', 'translation', 'options', 'default', 'landing', 'related', 'oversized']);

test('duplicate generated titles do not create another module', function () {
    $fixtures = moduleAiFixtures();
    WebmasterSection::query()->forceCreate(['title_en' => 'Properties', 'title_ar' => 'العقارات', 'type' => 5]);
    createAiModule($this, $fixtures['user'], validAgentModuleDefinition())->assertStatus(502);
    expect(WebmasterSection::query()->count())->toBe(1)->and(WebmasterSectionField::query()->count())->toBe(0);
});

test('provider failures are safe and create no records', function (string $failure, int $status) {
    $fixtures = moduleAiFixtures();
    ModuleDefinitionAgent::fake(function () use ($failure) {
        throw new RuntimeException($failure.' secret-provider-response');
    })->preventStrayPrompts();
    $response = $this->actingAs($fixtures['user'])->postJson(route('WebmasterSectionsAiCreate'), ['description' => 'Create a property module.'])
        ->assertStatus($status)->assertDontSee('secret-provider-response');
    expect($response->json('request_id'))->not->toBeEmpty()
        ->and(WebmasterSection::query()->count())->toBe(0)
        ->and(WebmasterSectionField::query()->count())->toBe(0)
        ->and(AiUsageLog::query()->value('status'))->toBe('failed');
})->with([['timeout', 504], ['malformed structured output', 502], ['api key', 502], ['invalid model', 422]]);

test('direct creation supports each module type and forced landing settings', function () {
    $fixtures = moduleAiFixtures();
    $this->withoutMiddleware(ThrottleRequests::class);
    foreach (ModuleDefinitionCatalog::MODULE_TYPES as $type) {
        $definition = validModuleDefinition();
        $definition['titles'] = ['en' => 'Module '.$type, 'ar' => 'وحدة '.$type];
        $definition['module_type'] = $type;
        $definition['custom_fields'] = [];
        createAiModule($this, $fixtures['user'], $definition)->assertCreated();
    }
    expect(WebmasterSection::query()->count())->toBe(count(ModuleDefinitionCatalog::MODULE_TYPES));
    $landing = WebmasterSection::query()->where('type', 10)->firstOrFail();
    expect($landing->title_status)->toBe(1)->and($landing->editor_status)->toBe(0);
});

test('manual module and field creation continue through the shared creator', function () {
    $fixtures = moduleAiFixtures();
    $statusValues = array_fill_keys([
        ...array_keys(ModuleDefinitionCatalog::BASIC_FIELDS),
        ...array_keys(ModuleDefinitionCatalog::ADDITIONAL_TABS),
    ], 0);
    $this->actingAs($fixtures['user'])->post(route('WebmasterSectionsStore'), $statusValues + [
        'title_en' => 'Manual module', 'title_ar' => 'وحدة يدوية', 'type' => 0, 'sections_status' => 0,
    ])->assertRedirect();

    $section = WebmasterSection::query()->where('title_en', 'Manual module')->firstOrFail();
    $this->actingAs($fixtures['user']->fresh())->post(route('webmasterFieldsStore', $section->id), [
        'title_en' => 'Reference', 'title_ar' => 'مرجع', 'type' => 0,
        'details_en' => null, 'details_ar' => null, 'required' => 1, 'in_table' => 1,
        'in_search' => 1, 'in_listing' => 0, 'in_page' => 1, 'in_statics' => 0,
        'lang_code' => 'all', 'view_permission_groups' => [0],
        'add_permission_groups' => [0], 'edit_permission_groups' => [0],
    ])->assertRedirect();

    expect($section->fresh()->title_ar)->toBe('وحدة يدوية')
        ->and($section->allCustomFields()->value('title_en'))->toBe('Reference')
        ->and($section->allCustomFields()->value('view_permission_groups'))->toBe('0');
});

test('direct creation supports every catalog custom field type', function () {
    $fixtures = moduleAiFixtures();
    $parent = WebmasterSection::query()->forceCreate(['title_en' => 'Related records', 'title_ar' => 'سجلات مرتبطة', 'type' => 5, 'status' => 1, 'created_by' => $fixtures['user']->id]);
    $proposal = validModuleDefinition(['titles' => ['en' => 'All fields', 'ar' => 'كل الحقول']]);
    $proposal['custom_fields'] = [];
    foreach (array_keys(ModuleDefinitionCatalog::CUSTOM_FIELD_TYPES) as $type) {
        $choice = in_array($type, ModuleDefinitionCatalog::CHOICE_FIELD_TYPES, true);
        $proposal['custom_fields'][] = [
            'labels' => ['en' => 'Field '.$type, 'ar' => 'حقل '.$type],
            'type' => $type, 'required' => false, 'default_value' => null,
            'options' => $choice ? [
                ['labels' => ['en' => 'One', 'ar' => 'واحد'], 'color' => null],
                ['labels' => ['en' => 'Two', 'ar' => 'اثنان'], 'color' => null],
            ] : [],
            'fixed_texts' => $type === 99 ? ['en' => 'Divider', 'ar' => 'فاصل'] : [],
            'parent_module_id' => $type === 18 ? $parent->id : null,
            'in_table' => false, 'in_search' => false, 'in_listing' => false, 'in_page' => true, 'in_statics' => false,
        ];
    }

    createAiModule($this, $fixtures['user'], $proposal)->assertCreated();
    $section = WebmasterSection::query()->where('title_en', 'All fields')->firstOrFail();
    expect($section->allCustomFields()->pluck('type')->map(fn ($type) => (int) $type)->all())
        ->toEqualCanonicalizing(array_keys(ModuleDefinitionCatalog::CUSTOM_FIELD_TYPES));
});

test('a field persistence failure rolls back the complete AI module', function () {
    $fixtures = moduleAiFixtures();
    $proposal = validModuleDefinition(['titles' => ['en' => 'Rollback module', 'ar' => 'وحدة تراجع']]);
    $second = $proposal['custom_fields'][0];
    $second['labels'] = ['en' => 'Fail persistence', 'ar' => 'فشل الحفظ'];
    $proposal['custom_fields'][] = $second;

    WebmasterSectionField::creating(function (WebmasterSectionField $field) {
        if ($field->title_en === 'Fail persistence') {
            throw new RuntimeException('Forced field failure.');
        }
    });

    createAiModule($this, $fixtures['user'], $proposal)
        ->assertStatus(500)->assertJsonPath('code', 'module_creation_failed');

    expect(WebmasterSection::query()->where('title_en', 'Rollback module')->exists())->toBeFalse()
        ->and(WebmasterSectionField::query()->count())->toBe(0)
        ->and($fixtures['group']->fresh()->data_sections)->toBe('');
});
