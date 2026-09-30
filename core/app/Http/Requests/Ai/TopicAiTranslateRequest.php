<?php

namespace App\Http\Requests\Ai;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class TopicAiTranslateRequest extends TopicAiRequest
{
    protected function capability(): string { return 'translation'; }

    public function rules(): array
    {
        return [
            'topic_id' => ['nullable', 'integer', 'exists:topics,id'],
            'source_language' => ['required', 'string', 'size:2', 'different:target_language', $this->activeLanguageRule()],
            'target_language' => ['required', 'string', 'size:2', $this->activeLanguageRule()],
            'scope' => ['required', Rule::in(['content', 'seo'])],
            'fields' => ['required', 'array', 'min:1', 'max:6'],
            'fields.*' => ['nullable', 'string', 'max:'.config('smartend_ai.max_input_characters')],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $allowed = $this->input('scope') === 'seo'
                ? ['seo_title', 'seo_description', 'seo_keywords', 'seo_url_slug', 'photo_alt']
                : ['title', 'description', 'details', 'photo_alt'];
            $fields = (array) $this->input('fields', []);
            if (array_diff(array_keys($fields), $allowed) !== []) {
                $validator->errors()->add('fields', __('backend.aiOperationNotAllowed'));
            }
            if (collect($fields)->filter(fn ($value) => filled($value))->isEmpty()) {
                $validator->errors()->add('fields', __('backend.aiContentRequired'));
            }
        });
    }
}
