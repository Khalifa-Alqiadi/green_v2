<?php

namespace App\Http\Requests\Ai;

use Illuminate\Validation\Rule;

class TopicAiImageRequest extends TopicAiRequest
{
    protected function capability(): string { return 'images'; }
    protected function needsImageProvider(): bool { return true; }

    public function rules(): array
    {
        return [
            'topic_id' => ['nullable', 'integer', 'exists:topics,id'],
            'language' => ['required', 'string', 'size:2', $this->activeLanguageRule()],
            'title' => ['nullable', 'string', 'max:191'],
            'details' => ['nullable', 'string', 'max:'.config('smartend_ai.max_input_characters')],
            'category_ids' => ['nullable', 'array', 'max:50'],
            'category_ids.*' => ['integer'],
            'style' => ['nullable', Rule::in(config('smartend_ai.image_styles'))],
            'orientation' => ['nullable', Rule::in(config('smartend_ai.image_orientations'))],
            'quality' => ['nullable', Rule::in(config('smartend_ai.image_qualities'))],
            'custom_instructions' => ['nullable', 'string', 'max:2000'],
            'prompt_override' => ['nullable', 'string', 'max:2000'],
            'mode' => ['nullable', Rule::in(['generate', 'regenerate', 'variation'])],
            'previous_asset_id' => ['nullable', 'uuid', 'exists:ai_generated_assets,id'],
        ];
    }
}
