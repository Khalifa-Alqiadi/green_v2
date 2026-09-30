<?php

namespace App\Services\Ai;

use App\Models\AiGeneratedAsset;
use App\Models\Language;
use App\Models\Topic;
use App\Models\User;
use App\Models\WebmasterSection;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Image;
use Throwable;

class TopicImageService
{
    public function __construct(
        private readonly AiSettingsService $settings,
        private readonly AiProviderConfigurator $configurator,
        private readonly TopicAiService $topicAi,
        private readonly AiUsageService $usage,
        private readonly AiExceptionMapper $exceptions,
        private readonly GeneratedImageAssetService $assets,
        private readonly SafeImageValidator $imageValidator,
    ) {}

    public function generate(User $user, WebmasterSection $section, Language $language, array $input, ?Topic $topic): array
    {
        $settings = $this->settings->settings();
        $provider = $this->settings->imageProvider($settings);
        $orientation = $this->orientation($input['orientation'] ?? $settings->ai_image_orientation, $settings);
        $quality = $input['quality'] ?? $settings->ai_image_quality ?: 'medium';
        $style = $input['style'] ?? $settings->ai_image_style ?: 'automatic';
        $previous = $this->previousAsset($input['previous_asset_id'] ?? null, $user);

        if (filled($input['prompt_override'] ?? null)) {
            $prompt = trim($input['prompt_override']);
            $promptRequestId = null;
        } elseif (($input['mode'] ?? 'generate') === 'variation' && $previous) {
            $prompt = $previous->optimized_prompt.' Create a visibly distinct variation with a different composition while keeping the same subject and brand direction.';
            $promptRequestId = null;
        } else {
            $optimized = $this->topicAi->imagePrompt($section, $language, $input, $topic, $orientation, $style);
            $prompt = $optimized['prompt'];
            $promptRequestId = $optimized['request_id'];
        }

        $negative = trim((string) config('smartend_ai.default_negative_instructions'));
        $finalPrompt = trim($prompt)."\n\nMandatory exclusions: ".$negative;
        $this->configurator->apply($provider);
        $log = $this->usage->start('image_generate', [
            'provider' => $provider,
            'model' => $settings->ai_image_model,
            'webmaster_section_id' => $section->id,
            'topic_id' => $topic?->id,
            'language' => $language->code,
            'metadata' => compact('quality', 'orientation', 'style'),
        ]);
        $started = hrtime(true);

        try {
            $pending = Image::of($finalPrompt)->quality($quality)->timeout($settings->ai_timeout_seconds ?: 120);
            $pending = match ($orientation) {
                'landscape' => $pending->landscape(),
                'portrait' => $pending->portrait(),
                'square' => $pending->square(),
                default => $pending,
            };
            $response = $pending->generate($provider, $settings->ai_image_model ?: null);
            [$bytes, $mime, $width, $height] = $this->validateImage($response->firstImage()->image);
            $this->usage->succeed($log, $started, $response->usage, $response->meta);
            $asset = $this->assets->create($user, $bytes, $mime, $width, $height, $finalPrompt, $log);
            if ($previous) {
                $this->assets->delete($previous);
            }

            return [
                'data' => [
                    'asset_id' => $asset->id,
                    'preview_url' => $this->assets->previewUrl($asset),
                    'optimized_prompt' => $finalPrompt,
                    'width' => $width,
                    'height' => $height,
                    'mime' => $mime,
                    'expires_at' => $asset->expires_at->toIso8601String(),
                    'style' => $style,
                    'orientation' => $orientation,
                    'quality' => $quality,
                ],
                'meta' => ['request_id' => $log->request_id, 'prompt_request_id' => $promptRequestId],
            ];
        } catch (Throwable $exception) {
            $mapped = $this->exceptions->map($exception, $log->request_id);
            $this->usage->fail($log, $started, $mapped->errorCode);
            Log::warning('Smartend AI image generation failed.', ['request_id' => $log->request_id, 'provider' => $provider, 'exception' => $exception::class]);
            throw $mapped;
        }
    }

    private function validateImage(string $base64): array
    {
        $bytes = base64_decode($base64, true);
        if ($bytes === false || $bytes === '' || strlen($bytes) > config('smartend_ai.max_image_bytes')) {
            throw new \RuntimeException('Generated image data is invalid or too large.');
        }

        [$mime, $width, $height] = $this->imageValidator->validate($bytes);

        return [$bytes, $mime, $width, $height];
    }

    private function orientation(?string $requested, object $settings): string
    {
        if ($requested && $requested !== 'automatic') {
            return $requested;
        }
        if ($settings->image_resize && $settings->image_resize_width && $settings->image_resize_height) {
            $ratio = $settings->image_resize_width / $settings->image_resize_height;
            return $ratio > 1.15 ? 'landscape' : ($ratio < 0.87 ? 'portrait' : 'square');
        }

        return $settings->ai_image_orientation !== 'automatic' ? $settings->ai_image_orientation : 'automatic';
    }

    private function previousAsset(?string $id, User $user): ?AiGeneratedAsset
    {
        return $id ? $this->assets->findAvailable($id, $user) : null;
    }
}
