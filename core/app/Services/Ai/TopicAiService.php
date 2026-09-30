<?php

namespace App\Services\Ai;

use App\Ai\Agents\ImageAltAgent;
use App\Ai\Agents\SmartendAgent;
use App\Ai\Agents\TopicImagePromptAgent;
use App\Ai\Agents\TopicSeoAgent;
use App\Ai\Agents\TopicTransformAgent;
use App\Ai\Agents\TopicTranslatorAgent;
use App\Ai\Agents\TopicWriterAgent;
use App\Models\Language;
use App\Models\Topic;
use App\Models\WebmasterSection;
use App\Exceptions\AiOperationException;
use Helper;
use Illuminate\Support\Facades\Log;
use Throwable;

class TopicAiService
{
    public function __construct(
        private readonly AiSettingsService $settings,
        private readonly AiProviderConfigurator $configurator,
        private readonly AiContextBuilder $context,
        private readonly PromptLoader $prompts,
        private readonly AiHtmlSanitizer $sanitizer,
        private readonly HtmlTranslationService $translationHtml,
        private readonly AiUsageService $usage,
        private readonly AiExceptionMapper $exceptions,
    ) {}

    public function content(WebmasterSection $section, Language $language, array $input, ?Topic $topic): array
    {
        $settings = $this->settings->settings();
        $provider = $this->settings->textProvider($settings);
        $operation = $input['operation'];
        $target = $input['target'];
        $landingStatic = ($input['content_context'] ?? null) === 'landing_static' && (int) $section->type === 10;
        $format = $target === 'details' && ($section->editor_status || $landingStatic) ? 'html' : 'plain';
        $context = $this->context->build($section, $language, $input, $topic, $input['category_ids'] ?? []);
        $tone = $this->settings->resolvedTone($input['tone'] ?? null, $settings);
        $length = $input['content_length'] ?? $settings->ai_default_content_length ?: 'medium';
        $promptName = $this->operationPrompt($operation);
        $values = compact('target', 'format', 'tone', 'context') + [
            'content_length' => $length,
            'content' => $input,
            'goal' => $operation,
            'framework' => $context['theme']['framework'],
        ];

        $agent = $operation === 'write'
            ? $this->agent(TopicWriterAgent::class, $settings)
            : $this->agent(TopicTransformAgent::class, $settings);
        $response = $this->prompt($agent, $this->prompts->load($promptName, $values), $provider, $settings->ai_text_model, $settings->ai_timeout_seconds, $operation, $section, $language, $topic);
        $result = $format === 'html'
            ? $this->sanitizer->sanitize($response['response']->text)
            : $this->sanitizer->plainText($response['response']->text, $target === 'title' ? 191 : 50000);

        return [
            'data' => compact('operation', 'target', 'format', 'result') + ['language' => $language->code],
            'meta' => ['request_id' => $response['request_id']],
        ];
    }

    public function seo(WebmasterSection $section, Language $language, array $input, Topic $topic): array
    {
        $settings = $this->settings->settings();
        $provider = $this->settings->textProvider($settings);
        $context = $this->context->build($section, $language, $input, $topic);
        $agent = $this->agent(TopicSeoAgent::class, $settings);
        $run = $this->prompt($agent, $this->prompts->load('seo-topic', ['context' => $context, 'content' => $input]), $provider, $settings->ai_text_model, $settings->ai_timeout_seconds, 'seo', $section, $language, $topic);
        $suggestions = $run['response']->toArray();
        $suggestions['seo_title'] = $this->sanitizer->plainText((string) ($suggestions['seo_title'] ?? ''), 191);
        $suggestions['seo_description'] = $this->sanitizer->plainText((string) ($suggestions['seo_description'] ?? ''), 500);
        $suggestions['keywords'] = array_values(array_filter(array_map(fn ($keyword) => $this->sanitizer->plainText((string) $keyword, 100), (array) ($suggestions['keywords'] ?? []))));
        $suggestions['slug'] = Helper::URLSlug((string) ($suggestions['slug'] ?? ''), 'topic', $topic->id);
        $suggestions['image_alt'] = $this->sanitizer->plainText((string) ($suggestions['image_alt'] ?? ''), 191);

        return ['data' => ['language' => $language->code, 'suggestions' => $suggestions], 'meta' => ['request_id' => $run['request_id']]];
    }

    public function translate(WebmasterSection $section, Language $source, Language $target, array $fields, ?Topic $topic): array
    {
        $settings = $this->settings->settings();
        $segments = [];
        foreach ($fields as $field => $value) {
            $segments = [...$segments, ...$this->translationHtml->segments($field, (string) $value, $field === 'details' && (bool) $section->editor_status)];
        }
        $context = $this->context->build($section, $target, $fields, $topic);
        $agent = $this->agent(TopicTranslatorAgent::class, $settings);
        $run = $this->prompt($agent, $this->prompts->load('translate-topic', [
            'source_language' => $source->code,
            'target_language' => $target->code,
            'target_direction' => $target->direction,
            'context' => $context,
            'segments' => $segments,
        ]), $this->settings->textProvider($settings), $settings->ai_text_model, $settings->ai_timeout_seconds, 'translate', $section, $target, $topic);
        $translated = collect($run['response']->toArray()['segments'] ?? [])->pluck('text', 'id')->all();
        $expectedIds = array_column($segments, 'id');
        $actualIds = array_keys($translated);
        sort($expectedIds);
        sort($actualIds);
        if (count($translated) !== count($segments) || $actualIds !== $expectedIds) {
            $run['log']->update(['status' => 'failed', 'error_code' => 'malformed_ai_output']);
            throw new AiOperationException('malformed_ai_output', 502, $run['request_id']);
        }
        $output = [];
        foreach ($fields as $field => $value) {
            $output[$field] = $this->translationHtml->reconstruct($field, (string) $value, $translated, $field === 'details' && (bool) $section->editor_status);
            if ($field === 'seo_url_slug') {
                $output[$field] = Helper::URLSlug($output[$field], 'topic', $topic?->id ?? 0);
            }
        }

        return ['data' => ['source_language' => $source->code, 'target_language' => $target->code, 'fields' => $output], 'meta' => ['request_id' => $run['request_id']]];
    }

    public function alt(WebmasterSection $section, Language $language, array $input, ?Topic $topic, string $imagePrompt = ''): array
    {
        $settings = $this->settings->settings();
        $context = $this->context->build($section, $language, $input, $topic);
        $agent = $this->agent(ImageAltAgent::class, $settings);
        $run = $this->prompt($agent, $this->prompts->load('image-alt', [
            'language' => $language->code,
            'context' => $context,
            'content' => $input,
            'image_prompt' => $imagePrompt,
        ]), $this->settings->textProvider($settings), $settings->ai_text_model, $settings->ai_timeout_seconds, 'image_alt', $section, $language, $topic);

        return ['data' => ['language' => $language->code, 'result' => $this->sanitizer->plainText($run['response']->text, 191)], 'meta' => ['request_id' => $run['request_id']]];
    }

    public function imagePrompt(WebmasterSection $section, Language $language, array $input, ?Topic $topic, string $orientation, string $style): array
    {
        $settings = $this->settings->settings();
        $context = $this->context->build($section, $language, $input, $topic, $input['category_ids'] ?? []);
        $agent = $this->agent(TopicImagePromptAgent::class, $settings);
        $run = $this->prompt($agent, $this->prompts->load('image-topic', [
            'image_style' => $style,
            'image_orientation' => $orientation,
            'theme_colors' => $context['theme']['colors'],
            'brand_context' => [
                'site' => $context['site'],
                'theme' => $context['theme'],
            ],
            'custom_instructions' => $input['custom_instructions'] ?? '',
            'context' => $context,
            'content' => $input,
        ]), $this->settings->textProvider($settings), $settings->ai_text_model, $settings->ai_timeout_seconds, 'image_prompt', $section, $language, $topic);

        $optimizedPrompt = trim((string) ($run['response']->toArray()['optimized_prompt'] ?? ''));
        if ($optimizedPrompt === '') {
            $run['log']->update(['status' => 'failed', 'error_code' => 'malformed_ai_output']);
            throw new AiOperationException('malformed_ai_output', 502, $run['request_id']);
        }

        return ['prompt' => $optimizedPrompt, 'request_id' => $run['request_id']];
    }

    private function prompt(SmartendAgent $agent, string $prompt, string $provider, ?string $model, int $timeout, string $action, WebmasterSection $section, Language $language, ?Topic $topic): array
    {
        $this->configurator->apply($provider);
        $log = $this->usage->start($action, ['provider' => $provider, 'model' => $model, 'webmaster_section_id' => $section->id, 'topic_id' => $topic?->id, 'language' => $language->code]);
        $started = hrtime(true);
        try {
            $response = $agent->prompt($prompt, provider: $provider, model: $model ?: null, timeout: $timeout);
            $this->usage->succeed($log, $started, $response->usage, $response->meta);
            return ['response' => $response, 'request_id' => $log->request_id, 'log' => $log];
        } catch (Throwable $exception) {
            $mapped = $this->exceptions->map($exception, $log->request_id);
            $this->usage->fail($log, $started, $mapped->errorCode);
            Log::warning('Smartend AI operation failed.', ['request_id' => $log->request_id, 'action' => $action, 'provider' => $provider, 'exception' => $exception::class]);
            throw $mapped;
        }
    }

    private function agent(string $class, object $settings): SmartendAgent
    {
        return new $class($this->prompts->load('system'), $settings->ai_temperature, $settings->ai_max_tokens ?: 4000);
    }

    private function operationPrompt(string $operation): string
    {
        return match ($operation) {
            'write' => 'write-topic',
            'rewrite', 'make_engaging', 'optimize_title_seo' => 'rewrite-topic',
            'improve' => 'improve-topic',
            'shorten' => 'shorten-topic',
            'expand' => 'expand-topic',
            'fix_grammar' => 'fix-grammar',
            'change_tone' => 'change-tone',
            'redesign' => 'redesign-topic',
        };
    }
}
