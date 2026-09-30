<?php

namespace App\Http\Requests\Ai;

use Illuminate\Validation\Validator;

class ResourceAiTranslateRequest extends ResourceAiRequest
{
    protected function capability(): string { return 'translation'; }

    public function rules(): array
    {
        return $this->baseRules() + [
            'source_language' => ['required', 'string', 'max:10', 'different:target_language', $this->activeLanguageRule()],
            'target_language' => ['required', 'string', 'max:10', $this->activeLanguageRule()],
            'scope' => ['required', 'in:content,seo'],
            'fields' => ['required', 'array', 'min:1', 'max:3'],
            'fields.*' => ['nullable', 'string', 'max:'.config('smartend_ai.max_input_characters')],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $type = $this->input('resource_type');
            $allowed = match ($type) {
                'setting' => ['seo_title', 'seo_description', 'seo_keywords'],
                'menu' => ['title'],
                'banner', 'popup' => ['title', 'details'],
                default => [],
            };
            $fields = (array) $this->input('fields', []);
            if (array_diff(array_keys($fields), $allowed) !== [] || ($type === 'setting') !== ($this->input('scope') === 'seo')) {
                $validator->errors()->add('fields', __('backend.aiOperationNotAllowed'));
            }
            if (collect($fields)->filter(fn ($value) => filled($value))->isEmpty()) {
                $validator->errors()->add('fields', __('backend.aiContentRequired'));
            }
        });
    }
}
