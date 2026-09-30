<?php

namespace App\Services\Ai;

use App\Ai\Agents\SmartendAgent;
use App\Ai\Agents\TopicTransformAgent;
use App\Ai\Agents\TopicTranslatorAgent;
use App\Ai\Agents\TopicWriterAgent;
use App\Ai\Agents\WebsiteSeoAgent;
use App\Exceptions\AiOperationException;
use App\Models\Language;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

class ResourceAiService
{
    public function __construct(
        private readonly AiSettingsService $settings,
        private readonly AiProviderConfigurator $configurator,
        private readonly ResourceAiContextBuilder $context,
        private readonly PromptLoader $prompts,
        private readonly AiHtmlSanitizer $sanitizer,
        private readonly HtmlTranslationService $translationHtml,
        private readonly AiUsageService $usage,
        private readonly AiExceptionMapper $exceptions,
    ) {}

    public function content(string $type, Language $language, array $input, ?Model $resource): array
    {
        $settings = $this->settings->settings();
        $operation = $input['operation'];
        $target = $input['target'];
        $rich = $type === 'popup' && $target === 'details';
        $format = $rich ? 'html' : 'plain';
        $context = $this->context->build($type, $language, $resource, $input, $input['context_id'] ?? null);
        $tone = $this->settings->resolvedTone($input['tone'] ?? null, $settings);
        $length = $input['content_length'] ?? $settings->ai_default_content_length ?: 'medium';
        $values = [
            'target' => $target,
            'format' => $format,
            'tone' => $tone,
            'content_length' => $length,
            'context' => $context,
            'content' => $input,
            'goal' => $operation,
            'framework' => 'bootstrap5',
        ];
        $agent = $operation === 'write'
            ? $this->agent(TopicWriterAgent::class, $settings)
            : $this->agent(TopicTransformAgent::class, $settings);
        $run = $this->prompt(
            $agent,
            $this->prompts->load($operation === 'write' ? 'write-resource' : 'transform-resource', $values),
            $operation,
            $type,
            $language,
            $resource,
        );
        $result = $rich
            ? $this->sanitizer->sanitize($run['response']->text)
            : $this->sanitizer->plainText($run['response']->text, $target === 'title' ? 191 : 50000);

        return [
            'data' => compact('operation', 'target', 'format', 'result') + ['language' => $language->code],
            'meta' => ['request_id' => $run['request_id']],
        ];
    }

    public function translate(string $type, Language $source, Language $target, array $fields, ?Model $resource, ?int $contextId): array
    {
        $settings = $this->settings->settings();
        $segments = [];
        foreach ($fields as $field => $value) {
            $segments = [...$segments, ...$this->translationHtml->segments(
                $field,
                (string) $value,
                $type === 'popup' && $field === 'details',
            )];
        }
        $context = $this->context->build($type, $target, $resource, $fields, $contextId);
        $run = $this->prompt(
            $this->agent(TopicTranslatorAgent::class, $settings),
            $this->prompts->load('translate-topic', [
                'source_language' => $source->code,
                'target_language' => $target->code,
                'target_direction' => $target->direction,
                'context' => $context,
                'segments' => $segments,
            ]),
            'translate',
            $type,
            $target,
            $resource,
        );
        $translated = collect($run['response']->toArray()['segments'] ?? [])->pluck('text', 'id')->all();
        $expected = array_column($segments, 'id');
        $actual = array_keys($translated);
        sort($expected);
        sort($actual);
        if (count($translated) !== count($segments) || $actual !== $expected) {
            $run['log']->update(['status' => 'failed', 'error_code' => 'malformed_ai_output']);
            throw new AiOperationException('malformed_ai_output', 502, $run['request_id']);
        }

        $output = [];
        foreach ($fields as $field => $value) {
            $output[$field] = $this->translationHtml->reconstruct(
                $field,
                (string) $value,
                $translated,
                $type === 'popup' && $field === 'details',
            );
        }

        return ['data' => ['source_language' => $source->code, 'target_language' => $target->code, 'fields' => $output], 'meta' => ['request_id' => $run['request_id']]];
    }

    public function websiteSeo(Language $language, array $input, Model $setting): array
    {
        $settings = $this->settings->settings();
        $context = $this->context->build('setting', $language, $setting, $input);
        $run = $this->prompt(
            $this->agent(WebsiteSeoAgent::class, $settings),
            $this->prompts->load('website-seo', ['context' => $context, 'content' => $input]),
            'website_seo',
            'setting',
            $language,
            $setting,
        );
        $suggestions = $run['response']->toArray();
        $suggestions['seo_title'] = $this->sanitizer->plainText((string) ($suggestions['seo_title'] ?? ''), 191);
        $suggestions['seo_description'] = $this->sanitizer->plainText((string) ($suggestions['seo_description'] ?? ''), 1000);
        $suggestions['keywords'] = array_values(array_filter(array_map(
            fn ($keyword) => $this->sanitizer->plainText((string) $keyword, 100),
            (array) ($suggestions['keywords'] ?? []),
        )));

        return ['data' => ['language' => $language->code, 'suggestions' => $suggestions], 'meta' => ['request_id' => $run['request_id']]];
    }

    private function prompt(SmartendAgent $agent, string $prompt, string $action, string $type, Language $language, ?Model $resource): array
    {
        $settings = $this->settings->settings();
        $provider = $this->settings->textProvider($settings);
        $this->configurator->apply($provider);
        $log = $this->usage->start($action, [
            'provider' => $provider,
            'model' => $settings->ai_text_model,
            'language' => $language->code,
            'metadata' => ['resource_type' => $type, 'resource_id' => $resource?->getKey()],
        ]);
        $started = hrtime(true);
        try {
            $response = $agent->prompt($prompt, provider: $provider, model: $settings->ai_text_model ?: null, timeout: $settings->ai_timeout_seconds);
            $this->usage->succeed($log, $started, $response->usage, $response->meta);

            return ['response' => $response, 'request_id' => $log->request_id, 'log' => $log];
        } catch (Throwable $exception) {
            $mapped = $this->exceptions->map($exception, $log->request_id);
            $this->usage->fail($log, $started, $mapped->errorCode);
            Log::warning('Smartend resource AI operation failed.', ['request_id' => $log->request_id, 'action' => $action, 'resource_type' => $type, 'exception' => $exception::class]);
            throw $mapped;
        }
    }

    private function agent(string $class, object $settings): SmartendAgent
    {
        return new $class($this->prompts->load('system'), $settings->ai_temperature, $settings->ai_max_tokens ?: 4000);
    }
}
