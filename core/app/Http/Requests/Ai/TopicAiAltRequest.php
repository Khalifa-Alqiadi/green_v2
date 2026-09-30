<?php

namespace App\Http\Requests\Ai;

class TopicAiAltRequest extends TopicAiRequest
{
    protected function capability(): string { return 'content'; }

    public function rules(): array
    {
        return [
            'topic_id' => ['nullable', 'integer', 'exists:topics,id'],
            'language' => ['required', 'string', 'size:2', $this->activeLanguageRule()],
            'title' => ['nullable', 'string', 'max:191'],
            'details' => ['nullable', 'string', 'max:'.config('smartend_ai.max_input_characters')],
            'asset_id' => ['nullable', 'uuid', 'exists:ai_generated_assets,id'],
        ];
    }
}
