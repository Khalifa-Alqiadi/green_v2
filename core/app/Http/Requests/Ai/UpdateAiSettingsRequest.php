<?php

namespace App\Http\Requests\Ai;

use App\Services\Ai\AiAccessService;
use App\Services\Ai\AiProviderRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAiSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(AiAccessService::class)->canManageSettings($this->user());
    }

    public function rules(): array
    {
        $providers = app(AiProviderRegistry::class);

        return [
            'ai_enabled' => ['nullable', 'boolean'],
            'ai_text_provider' => ['required', $providers->providerRule()],
            'ai_text_model' => [Rule::requiredIf($this->boolean('ai_enabled')), 'nullable', 'string', 'max:191'],
            'ai_default_tone' => ['required', Rule::in(config('smartend_ai.tones'))],
            'ai_custom_tone' => ['nullable', 'string', 'max:500', 'required_if:ai_default_tone,custom'],
            'ai_default_content_length' => ['required', Rule::in(config('smartend_ai.content_lengths'))],
            'ai_temperature' => ['nullable', 'numeric', 'between:0,1'],
            'ai_max_tokens' => ['required', 'integer', 'between:256,16000'],
            'ai_timeout_seconds' => ['required', 'integer', 'between:5,300'],
            'ai_content_enabled' => ['nullable', 'boolean'],
            'ai_seo_enabled' => ['nullable', 'boolean'],
            'ai_translation_enabled' => ['nullable', 'boolean'],
            'ai_images_enabled' => ['nullable', 'boolean'],
            'ai_image_provider' => ['required', $providers->providerRule(true)],
            'ai_image_model' => [Rule::requiredIf($this->boolean('ai_enabled') && $this->boolean('ai_images_enabled')), 'nullable', 'string', 'max:191'],
            'ai_image_quality' => ['required', Rule::in(config('smartend_ai.image_qualities'))],
            'ai_image_orientation' => ['required', Rule::in(config('smartend_ai.image_orientations'))],
            'ai_image_style' => ['required', Rule::in(config('smartend_ai.image_styles'))],
            'credentials' => ['nullable', 'array'],
            'credentials.*' => ['array'],
            'credentials.*.*' => ['nullable', 'string', 'max:2048'],
            'remove_credentials' => ['nullable', 'array'],
            'remove_credentials.*' => ['array'],
            'remove_credentials.*.*' => ['nullable', 'boolean'],
        ];
    }
}
