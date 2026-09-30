<?php

namespace App\Services\Ai;

use App\Models\AiUsageLog;
use Illuminate\Support\Str;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\Usage;

class AiUsageService
{
    public function start(string $action, array $context = []): AiUsageLog
    {
        return AiUsageLog::query()->create([
            'request_id' => (string) Str::uuid(),
            'user_id' => auth()->id(),
            'action' => $action,
            'provider' => $context['provider'] ?? null,
            'model' => $context['model'] ?? null,
            'webmaster_section_id' => $context['webmaster_section_id'] ?? null,
            'topic_id' => $context['topic_id'] ?? null,
            'language' => $context['language'] ?? null,
            'status' => 'started',
            'metadata' => $context['metadata'] ?? null,
        ]);
    }

    public function succeed(AiUsageLog $log, int $startedAt, ?Usage $usage = null, ?Meta $meta = null): AiUsageLog
    {
        $log->update([
            'provider' => $meta?->provider ?: $log->provider,
            'model' => $meta?->model ?: $log->model,
            'prompt_tokens' => $this->nullableToken($usage?->promptTokens),
            'completion_tokens' => $this->nullableToken($usage?->completionTokens),
            'cache_read_tokens' => $this->nullableToken($usage?->cacheReadInputTokens),
            'cache_write_tokens' => $this->nullableToken($usage?->cacheWriteInputTokens),
            'reasoning_tokens' => $this->nullableToken($usage?->reasoningTokens),
            'status' => 'success',
            'duration_ms' => max(0, (int) round((hrtime(true) - $startedAt) / 1_000_000)),
        ]);

        return $log->refresh();
    }

    public function fail(AiUsageLog $log, int $startedAt, string $code): AiUsageLog
    {
        $log->update([
            'status' => 'failed',
            'error_code' => $code,
            'duration_ms' => max(0, (int) round((hrtime(true) - $startedAt) / 1_000_000)),
        ]);

        return $log->refresh();
    }

    private function nullableToken(?int $tokens): ?int
    {
        return $tokens && $tokens > 0 ? $tokens : null;
    }
}
