<?php

namespace App\Http\Requests\Ai;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class TopicAiContentRequest extends TopicAiRequest
{
    protected function capability(): string { return 'content'; }

    public function rules(): array
    {
        return [
            'topic_id' => ['nullable', 'integer', 'exists:topics,id'],
            'language' => ['required', 'string', 'size:2', $this->activeLanguageRule()],
            'operation' => ['required', Rule::in(config('smartend_ai.content_operations'))],
            'target' => ['required', Rule::in(['title', 'description', 'details'])],
            'content_context' => ['nullable', Rule::in(['landing_static'])],
            'value' => ['nullable', 'string', 'max:'.config('smartend_ai.max_input_characters')],
            'title' => ['nullable', 'string', 'max:191'],
            'details' => ['nullable', 'string', 'max:'.config('smartend_ai.max_input_characters')],
            'description' => ['nullable', 'string', 'max:'.config('smartend_ai.max_input_characters')],
            'tone' => ['nullable', Rule::in(config('smartend_ai.tones'))],
            'content_length' => ['nullable', Rule::in(config('smartend_ai.content_lengths'))],
            'custom_instructions' => ['nullable', 'string', 'max:2000'],
            'category_ids' => ['nullable', 'array', 'max:50'],
            'category_ids.*' => ['integer'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $operation = $this->input('operation');
            $target = $this->input('target');
            $landingStatic = $this->input('content_context') === 'landing_static';
            $validLandingStatic = $landingStatic && $target === 'details' && (int) $this->section()?->type === 10;
            if ($landingStatic && !$validLandingStatic) {
                $validator->errors()->add('content_context', __('backend.aiOperationNotAllowed'));
            }
            if (in_array($operation, ['make_engaging', 'optimize_title_seo'], true) && $target !== 'title') {
                $validator->errors()->add('operation', __('backend.aiOperationNotAllowed'));
            }
            if ($target === 'title' && $operation === 'redesign') {
                $validator->errors()->add('operation', __('backend.aiOperationNotAllowed'));
            }
            if ($operation === 'redesign' && ($target !== 'details' || (!$this->section()?->editor_status && !$validLandingStatic))) {
                $validator->errors()->add('operation', __('backend.aiOperationNotAllowed'));
            }
            if ($operation !== 'write' && blank($this->input('value'))) {
                $validator->errors()->add('value', __('backend.aiContentRequired'));
            }
        });
    }
}
