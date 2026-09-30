<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ai\UpdateAiSettingsRequest;
use App\Services\Ai\AiAccessService;
use App\Services\Ai\AiProviderRegistry;
use App\Services\Ai\AiSettingsService;
use Illuminate\Encryption\MissingAppKeyException;
use Illuminate\Http\RedirectResponse;

class AiSettingsController extends Controller
{
    public function __construct(
        private readonly AiSettingsService $settings,
        private readonly AiProviderRegistry $providers,
    ) {}

    public function edit(): RedirectResponse
    {
        abort_unless(app(AiAccessService::class)->canManageSettings(auth()->user()), 403);

        return redirect()->route('webmasterSettings', ['tab' => 'ai']);
    }

    public function update(UpdateAiSettingsRequest $request): RedirectResponse
    {
        $settings = $this->settings->settings();
        $validated = $request->validated();
        $credentials = $settings->ai_provider_credentials ?: [];
        $activeProviders = array_unique(array_filter([
            $validated['ai_text_provider'] ?? null,
            $request->boolean('ai_images_enabled') ? ($validated['ai_image_provider'] ?? null) : null,
        ]));

        $credentials = array_intersect_key($credentials, array_flip($activeProviders));

        foreach ((array) ($validated['credentials'] ?? []) as $provider => $fields) {
            if (! in_array($provider, $activeProviders, true)) {
                continue;
            }
            foreach ((array) $fields as $field => $value) {
                if (in_array($field, $this->providers->credentialFields($provider), true) && filled($value)) {
                    $credentials[$provider][$field] = $value;
                }
            }
        }

        foreach ((array) ($validated['remove_credentials'] ?? []) as $provider => $fields) {
            if (! in_array($provider, $activeProviders, true)) {
                continue;
            }
            foreach ((array) $fields as $field => $remove) {
                if ($remove && in_array($field, $this->providers->credentialFields($provider), true)) {
                    unset($credentials[$provider][$field]);
                }
            }
            if (($credentials[$provider] ?? []) === []) {
                unset($credentials[$provider]);
            }
        }

        $enabled = $request->boolean('ai_enabled');
        $textNeeded = $request->boolean('ai_content_enabled')
            || $request->boolean('ai_seo_enabled')
            || $request->boolean('ai_translation_enabled')
            || $request->boolean('ai_images_enabled');
        if ($enabled && $textNeeded && ! $this->providers->isReady($validated['ai_text_provider'], $credentials[$validated['ai_text_provider']] ?? [])) {
            return back()->withInput($request->except('credentials'))->withErrors(['ai_text_provider' => __('backend.aiProviderNotConfigured')]);
        }
        if ($enabled && $request->boolean('ai_images_enabled')
            && ! $this->providers->isReady($validated['ai_image_provider'], $credentials[$validated['ai_image_provider']] ?? [])) {
            return back()->withInput($request->except('credentials'))->withErrors(['ai_image_provider' => __('backend.aiProviderNotConfigured')]);
        }

        $settings->forceFill(collect($validated)->only([
            'ai_text_provider', 'ai_text_model', 'ai_default_tone', 'ai_custom_tone',
            'ai_default_content_length', 'ai_temperature', 'ai_max_tokens', 'ai_timeout_seconds',
            'ai_image_provider', 'ai_image_model', 'ai_image_quality', 'ai_image_orientation',
            'ai_image_style',
        ])->all());
        foreach ([
            'ai_enabled', 'ai_content_enabled', 'ai_seo_enabled', 'ai_translation_enabled',
            'ai_images_enabled',
        ] as $boolean) {
            $settings->{$boolean} = $request->boolean($boolean);
        }
        try {
            $settings->ai_provider_credentials = $credentials ?: null;
            $settings->updated_by = $request->user()->id;
            $settings->save();
        } catch (MissingAppKeyException) {
            return back()->withInput($request->except('credentials'))->withErrors([
                'credentials' => __('backend.aiEncryptionUnavailable'),
            ]);
        }

        return redirect()->route('webmasterSettings', ['tab' => 'ai'])
            ->with('doneMessage', __('backend.saveDone'));
    }
}
