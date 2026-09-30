<?php

namespace App\Services\Ai;

use App\Ai\Agents\ModuleDefinitionAgent;
use App\Exceptions\AiOperationException;
use App\Models\User;
use App\Services\Dashboard\ModuleDefinitionCatalog;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class ModuleAiService
{
    public function __construct(
        private readonly AiSettingsService $settings,
        private readonly AiProviderConfigurator $configurator,
        private readonly PromptLoader $prompts,
        private readonly AiUsageService $usage,
        private readonly AiExceptionMapper $exceptions,
        private readonly ModuleDefinitionValidator $validator,
        private readonly ModuleDefinitionCatalog $catalog,
    ) {}

    public function generateDefinition(User $user, string $description): array
    {
        $settings = $this->settings->settings();
        $provider = $this->settings->textProvider($settings);
        $this->configurator->apply($provider);
        $languages = $this->validator->languages()->map(fn ($language) => [
            'code' => $language->code,
            'title' => $language->title,
            'direction' => $language->direction,
        ])->values()->all();
        $modules = $this->validator->eligibleRelatedModules($user)->map(function ($module) use ($languages) {
            $titles = [];
            foreach ($languages as $language) {
                $titles[$language['code']] = $module->{'title_'.$language['code']};
            }

            return ['id' => $module->id, 'type' => $module->type, 'titles' => $titles, 'active' => true, 'eligible_for_reference' => true];
        })->values()->all();

        $agent = new ModuleDefinitionAgent(
            $this->prompts->load('system')."\n".$this->prompts->load('module-system'),
            $settings->ai_temperature,
            $settings->ai_max_tokens ?: 4000,
        );
        $prompt = $this->prompts->load('create-module', [
            'catalog' => $this->catalog->forPrompt(),
            'languages' => $languages,
            'existing_modules' => $modules,
            'locale' => app()->getLocale(),
            'request' => $description,
        ]);
        $log = $this->usage->start('module_creation', [
            'provider' => $provider,
            'model' => $settings->ai_text_model,
            'language' => app()->getLocale(),
            'metadata' => ['existing_module_count' => count($modules), 'active_language_count' => count($languages)],
        ]);
        $started = hrtime(true);

        try {
            $response = $agent->prompt($prompt, provider: $provider, model: $settings->ai_text_model ?: null, timeout: $settings->ai_timeout_seconds);
            $raw = $response->toArray();
            if (! array_key_exists('can_create', $raw) || ! is_bool($raw['can_create'])) {
                throw new \RuntimeException('Malformed structured output.');
            }
            if (! $raw['can_create']) {
                throw new AiOperationException('module_requirements_unsupported', 422, $log->request_id);
            }
            unset($raw['can_create']);
            $proposal = $this->validator->fromAgent($raw);
            $proposal = $this->validator->validate($proposal, $user, true, array_column($modules, 'id'));
            $contentFields = array_intersect_key($proposal['basic_fields'], array_flip(['title_status', 'date_status', 'longtext_status', 'photo_status', 'attach_file_status']));
            $recordFields = array_filter($proposal['custom_fields'], fn ($field) => $field['type'] !== 99);
            if (! in_array($proposal['module_type'], [1, 2, 3, 10], true) && ! in_array(true, $contentFields, true) && $recordFields === []) {
                throw new AiOperationException('module_requirements_unsupported', 422, $log->request_id);
            }
            $this->usage->succeed($log, $started, $response->usage, $response->meta);

            return $proposal;
        } catch (ValidationException $exception) {
            $this->usage->fail($log, $started, 'malformed_ai_output');
            Log::warning('Smartend module AI returned an invalid definition.', ['request_id' => $log->request_id]);
            throw new AiOperationException('malformed_ai_output', 502, $log->request_id, $exception);
        } catch (Throwable $exception) {
            $mapped = $this->exceptions->map($exception, $log->request_id);
            $this->usage->fail($log, $started, $mapped->errorCode);
            Log::warning('Smartend module AI operation failed.', ['request_id' => $log->request_id, 'exception' => $exception::class]);
            throw $mapped;
        }
    }

    public function uiContext(User $user): array
    {
        $settings = $this->settings->settings();
        $provider = $this->settings->textProvider($settings);
        $enabled = (bool) $user->permissionsGroup?->modules_status
            && (bool) $user->permissionsGroup?->ai_use
            && $this->settings->capabilityEnabled('content', $settings)
            && in_array($provider, app(AiProviderRegistry::class)->textProviders(), true)
            && $this->settings->providerReady($provider, $settings);

        return [
            'enabled' => $enabled,
            'manage_settings' => app(AiAccessService::class)->canManageSettings($user),
        ];
    }
}
