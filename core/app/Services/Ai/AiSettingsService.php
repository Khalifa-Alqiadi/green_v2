<?php

namespace App\Services\Ai;

use App\Models\WebmasterSetting;
use RuntimeException;

class AiSettingsService
{
    public function __construct(private readonly AiProviderRegistry $providers) {}

    public function settings(): WebmasterSetting
    {
        return WebmasterSetting::query()->find(1)
            ?? throw new RuntimeException('Smartend webmaster settings are not initialized.');
    }

    public function textProvider(?WebmasterSetting $settings = null): string
    {
        $settings ??= $this->settings();

        return $settings->ai_text_provider ?: (string) config('ai.default', 'openai');
    }

    public function imageProvider(?WebmasterSetting $settings = null): string
    {
        $settings ??= $this->settings();

        return $settings->ai_image_provider ?: (string) config('ai.default_for_images', 'gemini');
    }

    public function activeProviders(?WebmasterSetting $settings = null): array
    {
        $settings ??= $this->settings();

        return array_unique(array_filter([
            $this->textProvider($settings),
            $settings->ai_images_enabled ? $this->imageProvider($settings) : null,
        ]));
    }

    public function providerCredentials(string $provider, ?WebmasterSetting $settings = null): array
    {
        $settings ??= $this->settings();
        if (!in_array($provider, $this->activeProviders($settings), true)) {
            return [];
        }
        $all = $settings->ai_provider_credentials ?: [];

        return is_array($all[$provider] ?? null) ? $all[$provider] : [];
    }

    public function providerReady(string $provider, ?WebmasterSetting $settings = null): bool
    {
        return in_array($provider, $this->activeProviders($settings), true)
            && $this->providers->isReady($provider, $this->providerCredentials($provider, $settings));
    }

    public function capabilityEnabled(string $capability, ?WebmasterSetting $settings = null): bool
    {
        $settings ??= $this->settings();
        $column = match ($capability) {
            'content' => 'ai_content_enabled',
            'seo' => 'ai_seo_enabled',
            'translation' => 'ai_translation_enabled',
            'images' => 'ai_images_enabled',
            default => null,
        };

        return (bool) $settings->ai_enabled && $column !== null && (bool) $settings->{$column};
    }

    public function resolvedTone(?string $requested = null, ?WebmasterSetting $settings = null): string
    {
        $settings ??= $this->settings();
        $tone = in_array($requested, config('smartend_ai.tones', []), true)
            ? $requested
            : ($settings->ai_default_tone ?: 'professional');

        if ($tone === 'custom') {
            return trim((string) $settings->ai_custom_tone) ?: 'professional';
        }

        return $tone;
    }
}
