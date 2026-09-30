<?php

namespace App\Http\Requests\Ai;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ResourceAiContentRequest extends ResourceAiRequest
{
    protected function capability(): string { return 'content'; }

    public function rules(): array
    {
        return $this->baseRules() + [
            'language' => ['required', 'string', 'max:10', $this->activeLanguageRule()],
            'operation' => ['required', Rule::in(config('smartend_ai.content_operations'))],
            'target' => ['required', Rule::in(['title', 'details'])],
            'value' => ['nullable', 'string', 'max:'.config('smartend_ai.max_input_characters')],
            'title' => ['nullable', 'string', 'max:191'],
            'details' => ['nullable', 'string', 'max:'.config('smartend_ai.max_input_characters')],
            'tone' => ['nullable', Rule::in(config('smartend_ai.tones'))],
            'content_length' => ['nullable', Rule::in(config('smartend_ai.content_lengths'))],
            'custom_instructions' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $type = $this->input('resource_type');
            $target = $this->input('target');
            $operation = $this->input('operation');
            if ($type === 'setting' || ($type === 'menu' && $target !== 'title')) {
                $validator->errors()->add('target', __('backend.aiOperationNotAllowed'));
            }
            if (in_array($operation, ['make_engaging', 'optimize_title_seo'], true) && $target !== 'title') {
                $validator->errors()->add('operation', __('backend.aiOperationNotAllowed'));
            }
            if ($operation === 'redesign' && !($type === 'popup' && $target === 'details')) {
                $validator->errors()->add('operation', __('backend.aiOperationNotAllowed'));
            }
            if ($target === 'title' && $operation === 'redesign') {
                $validator->errors()->add('operation', __('backend.aiOperationNotAllowed'));
            }
            if ($operation !== 'write' && blank($this->input('value'))) {
                $validator->errors()->add('value', __('backend.aiContentRequired'));
            }
        });
    }
}
