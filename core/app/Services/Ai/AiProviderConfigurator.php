<?php

namespace App\Services\Ai;

use Laravel\Ai\AiManager;

class AiProviderConfigurator
{
    public function __construct(
        private readonly AiSettingsService $settings,
        private readonly AiProviderRegistry $providers,
        private readonly AiManager $manager,
    ) {}

    public function apply(string $provider): void
    {
        abort_unless(in_array($provider, array_unique([
            ...$this->providers->textProviders(), ...$this->providers->imageProviders(),
        ]), true), 422, 'Unsupported AI provider.');

        $settings = $this->settings->settings();
        abort_unless(in_array($provider, $this->settings->activeProviders($settings), true), 422, 'Inactive AI provider.');

        $configured = [];
        foreach ($this->settings->activeProviders($settings) as $active) {
            if ($this->settings->providerReady($active, $settings)) {
                $configured[$active] = $this->providers->configuration($active, $this->settings->providerCredentials($active, $settings));
            }
        }
        foreach (array_unique([...array_keys(config('ai.providers', [])), ...array_keys($configured)]) as $name) {
            $this->manager->forgetInstance($name);
        }
        config(['ai.providers' => $configured]);
        abort_unless(isset($configured[$provider]), 422, 'AI provider is not configured.');
    }
}
