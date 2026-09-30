<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

abstract class SmartendAgent implements Agent
{
    use Promptable;

    public function __construct(
        private readonly string $systemInstructions,
        private readonly ?float $configuredTemperature = null,
        private readonly int $configuredMaxTokens = 4000,
    ) {}

    public function instructions(): Stringable|string
    {
        return $this->systemInstructions;
    }

    public function temperature(): ?float
    {
        return $this->configuredTemperature;
    }

    public function maxTokens(): int
    {
        return $this->configuredMaxTokens;
    }
}
