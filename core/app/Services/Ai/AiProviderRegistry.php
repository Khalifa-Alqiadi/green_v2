<?php

namespace App\Services\Ai;

class AiProviderRegistry
{
    public function textProviders(): array
    {
        return config('smartend_ai.text_providers', []);
    }

    public function imageProviders(): array
    {
        return config('smartend_ai.image_providers', []);
    }

    public function credentialFields(string $provider): array
    {
        return match ($provider) {
            'azure' => ['key', 'url', 'api_version', 'deployment', 'image_deployment'],
            'bedrock' => [
                'region', 'key', 'access_key_id', 'secret_access_key', 'session_token',
                'use_default_credential_provider', 'assume_role_arn', 'assume_role_session_name',
                'assume_role_duration_seconds', 'assume_role_external_id',
            ],
            'ollama', 'openai-compatible' => ['key', 'url'],
            'anthropic', 'gemini', 'openai' => ['key', 'url'],
            default => ['key'],
        };
    }

    public function secretFields(): array
    {
        return ['key', 'access_key_id', 'secret_access_key', 'session_token'];
    }

    public function providerRule(bool $images = false): mixed
    {
        return \Illuminate\Validation\Rule::in($images ? $this->imageProviders() : $this->textProviders());
    }

    public function configuration(string $provider, array $credentials): array
    {
        $configuration = (array) config('ai.provider_defaults.'.$provider, []);
        foreach ($this->credentialFields($provider) as $field) {
            if (!array_key_exists($field, $credentials) || $credentials[$field] === null || $credentials[$field] === '') {
                continue;
            }
            $path = str_starts_with($field, 'assume_role_')
                ? 'assume_role.'.substr($field, strlen('assume_role_')) : $field;
            data_set($configuration, $path, $credentials[$field]);
        }

        return $configuration;
    }

    public function isReady(string $provider, array $credentials = []): bool
    {
        $configuration = $this->configuration($provider, $credentials);

        return match ($provider) {
            'ollama' => filled($configuration['url'] ?? null),
            'openai-compatible' => filled($configuration['url'] ?? null),
            'azure' => filled($configuration['key'] ?? null) && filled($configuration['url'] ?? null),
            'bedrock' => filled($configuration['key'] ?? null)
                || (filled($configuration['access_key_id'] ?? null) && filled($configuration['secret_access_key'] ?? null))
                || (bool) ($configuration['use_default_credential_provider'] ?? false),
            default => filled($configuration['key'] ?? null),
        };
    }

    public function label(string $provider): string
    {
        return match ($provider) {
            'openai' => 'OpenAI',
            'openai-compatible' => 'OpenAI Compatible',
            'openrouter' => 'OpenRouter',
            'xai' => 'xAI',
            'groq' => 'Groq',
            'ollama' => 'Ollama',
            default => str($provider)->headline()->toString(),
        };
    }
}
