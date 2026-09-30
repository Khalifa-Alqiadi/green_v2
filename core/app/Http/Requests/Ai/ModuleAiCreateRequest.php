<?php

namespace App\Http\Requests\Ai;

use App\Services\Ai\AiProviderRegistry;
use App\Services\Ai\AiSettingsService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ModuleAiCreateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! is_string($this->input('description'))) {
            return;
        }

        $description = html_entity_decode($this->input('description'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $description = strip_tags($description);
        $description = str_replace(["\r\n", "\r"], "\n", $description);
        $description = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $description) ?? '';
        $this->merge(['description' => trim($description)]);
    }

    public function authorize(): bool
    {
        $settings = app(AiSettingsService::class);

        return (bool) $this->user()?->permissionsGroup?->modules_status
            && (bool) $this->user()?->permissionsGroup?->ai_use
            && $settings->capabilityEnabled('content')
            && in_array($settings->textProvider(), app(AiProviderRegistry::class)->textProviders(), true)
            && $settings->providerReady($settings->textProvider());
    }

    public function rules(): array
    {
        return ['description' => ['required', 'string', 'min:10', 'max:5000']];
    }

    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(response()->json(['message' => __('backend.aiUnavailable'), 'code' => 'ai_unavailable_or_unauthorized'], 403));
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json(['message' => __('validation.invalid'), 'code' => 'validation_failed', 'errors' => $validator->errors()], 422));
    }
}
