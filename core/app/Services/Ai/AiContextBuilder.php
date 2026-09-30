<?php

namespace App\Services\Ai;

use App\Models\Language;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Topic;
use App\Models\WebmasterSection;
use App\Services\ThemeService;

class AiContextBuilder
{
    public function build(
        WebmasterSection $section,
        Language $language,
        array $content = [],
        ?Topic $topic = null,
        array $categoryIds = [],
    ): array {
        $theme = ThemeService::active();
        $site = Setting::query()->where('theme_name', $theme)->where('status', 1)->first()
            ?: Setting::query()->find(1);
        $categories = $this->categories($section, $language, $topic, $categoryIds);

        return [
            'language' => [
                'code' => $language->code,
                'name' => $language->title,
                'direction' => $language->direction,
            ],
            'site' => [
                'title' => $site?->{'site_title_'.$language->code} ?: $site?->{'site_title_'.config('smartend.default_language')},
                'description' => $site?->{'site_desc_'.$language->code} ?: $site?->{'site_desc_'.config('smartend.default_language')},
            ],
            'module' => [
                'id' => $section->id,
                'title' => $section->{'title_'.$language->code} ?: $section->{'title_'.config('smartend.default_language')},
                'type' => $section->type,
                'rich_text' => (bool) $section->editor_status,
            ],
            'categories' => $categories,
            'theme' => [
                'name' => $theme,
                'framework' => in_array($theme, ['classic', 'modern'], true) ? 'bootstrap5' : 'semantic',
                'colors' => $this->themeColors($site),
            ],
            'topic' => [
                'id' => $topic?->id,
                'published' => (bool) ($topic?->status ?? false),
            ],
            'current' => $content,
        ];
    }

    private function categories(WebmasterSection $section, Language $language, ?Topic $topic, array $categoryIds): array
    {
        if ($topic && $categoryIds === []) {
            $categoryIds = $topic->categories()->pluck('section_id')->all();
        }

        return Section::query()
            ->where('webmaster_id', $section->id)
            ->whereIn('id', array_map('intval', $categoryIds))
            ->get()
            ->map(fn (Section $category) => [
                'id' => $category->id,
                'title' => $category->{'title_'.$language->code} ?: $category->{'title_'.config('smartend.default_language')},
            ])->values()->all();
    }

    private function themeColors(?Setting $setting): array
    {
        return collect(['style_color1', 'style_color2', 'style_color3', 'style_color4'])
            ->map(fn ($field) => trim((string) ($setting?->{$field} ?? '')))
            ->filter(fn ($color) => preg_match('/^#[0-9a-f]{6}$/i', $color) === 1)
            ->values()->all();
    }
}
