<?php

namespace App\Http\Requests\Ai;

class WebsiteAiSeoRequest extends ResourceAiRequest
{
    protected function capability(): string { return 'seo'; }

    public function rules(): array
    {
        return array_merge($this->baseRules(), [
            'resource_type' => ['required', 'in:setting'],
            'resource_id' => ['required', 'integer', 'exists:settings,id'],
            'language' => ['required', 'string', 'max:10', $this->activeLanguageRule()],
            'seo_title' => ['nullable', 'string', 'max:191'],
            'seo_description' => ['nullable', 'string', 'max:1000'],
            'seo_keywords' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
