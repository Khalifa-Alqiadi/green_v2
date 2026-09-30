<?php

namespace App\Services\Ai;

use App\Exceptions\AiOperationException;
use Laravel\Ai\Exceptions\InsufficientCreditsException;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Exceptions\RateLimitedException;
use Throwable;

class AiExceptionMapper
{
    public function map(Throwable $exception, ?string $requestId = null): AiOperationException
    {
        if ($exception instanceof AiOperationException) {
            return $exception;
        }

        [$code, $status] = match (true) {
            $exception instanceof RateLimitedException => ['provider_rate_limited', 429],
            $exception instanceof InsufficientCreditsException => ['provider_quota_exhausted', 402],
            $exception instanceof ProviderOverloadedException => ['provider_unavailable', 503],
            str_contains(strtolower($exception->getMessage()), 'timeout') => ['provider_timeout', 504],
            str_contains(strtolower($exception->getMessage()), 'credential'),
            str_contains(strtolower($exception->getMessage()), 'unauthorized'),
            str_contains(strtolower($exception->getMessage()), 'api key') => ['provider_auth_failed', 502],
            str_contains(strtolower($exception->getMessage()), 'model') => ['provider_model_invalid', 422],
            str_contains(strtolower($exception->getMessage()), 'policy'),
            str_contains(strtolower($exception->getMessage()), 'safety'),
            str_contains(strtolower($exception->getMessage()), 'moderation') => ['provider_policy_rejected', 422],
            str_contains(strtolower($exception->getMessage()), 'structured'),
            str_contains(strtolower($exception->getMessage()), 'malformed'),
            str_contains(strtolower($exception->getMessage()), 'json') => ['malformed_ai_output', 502],
            str_contains(strtolower($exception->getMessage()), 'image') => ['image_generation_failed', 502],
            default => ['ai_generation_failed', 502],
        };

        return new AiOperationException($code, $status, $requestId, $exception);
    }
}
