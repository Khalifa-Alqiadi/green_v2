<?php

namespace App\Http\Requests\Ai;

class TopicAiSeoRequest extends TopicAiRequest
{
    protected function capability(): string { return 'seo'; }

    public function rules(): array
    {
        return [
            'topic_id' => ['required', 'integer', 'exists:topics,id'],
            'language' => ['required', 'string', 'size:2', $this->activeLanguageRule()],
            'title' => ['nullable', 'string', 'max:191'],
            'details' => ['nullable', 'string', 'max:'.config('smartend_ai.max_input_characters')],
            'seo_title' => ['nullable', 'string', 'max:191'],
            'seo_description' => ['nullable', 'string', 'max:1000'],
            'seo_keywords' => ['nullable', 'string', 'max:2000'],
            'seo_url_slug' => ['nullable', 'string', 'max:191'],
        ];
    }
}
