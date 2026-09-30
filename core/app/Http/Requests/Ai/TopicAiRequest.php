<?php

namespace App\Http\Requests\Ai;

use App\Models\Language;
use App\Models\Topic;
use App\Models\WebmasterSection;
use App\Services\Ai\AiAccessService;
use App\Services\Ai\AiSettingsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

abstract class TopicAiRequest extends FormRequest
{
    abstract protected function capability(): string;

    protected function needsImageProvider(): bool
    {
        return false;
    }

    public function authorize(): bool
    {
        $section = $this->section();
        $topic = $this->topic();
        if (!$section || ($this->filled('topic_id') && !$topic)) {
            return false;
        }

        $settings = app(AiSettingsService::class);
        if (!$settings->capabilityEnabled($this->capability())) {
            return false;
        }
        $textProvider = $settings->textProvider();
        if (!$settings->providerReady($textProvider)) {
            return false;
        }
        if ($this->needsImageProvider() && !$settings->providerReady($settings->imageProvider())) {
            return false;
        }

        return app(AiAccessService::class)->canUse($this->user(), $section, $topic);
    }

    public function section(): ?WebmasterSection
    {
        return once(fn () => WebmasterSection::query()->where('status', 1)->find($this->route('webmasterId')));
    }

    public function topic(): ?Topic
    {
        return once(fn () => $this->filled('topic_id') ? Topic::query()->find($this->integer('topic_id')) : null);
    }

    public function language(string $key = 'language'): ?Language
    {
        return Language::query()->where('status', 1)->where('box_status', 1)->where('code', $this->input($key))->first();
    }

    protected function activeLanguageRule(): mixed
    {
        return Rule::exists('languages', 'code')->where(
            fn ($query) => $query->where('status', 1)->where('box_status', 1),
        );
    }

    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(response()->json([
            'message' => __('backend.aiUnavailable'),
            'code' => 'ai_unavailable_or_unauthorized',
        ], 403));
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => __('validation.invalid'),
            'code' => 'validation_failed',
            'errors' => $validator->errors(),
        ], 422));
    }
}
