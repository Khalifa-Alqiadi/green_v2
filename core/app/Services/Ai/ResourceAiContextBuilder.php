<?php

namespace App\Services\Ai;

use App\Models\Banner;
use App\Models\Language;
use App\Models\Menu;
use App\Models\Popup;
use App\Models\Setting;
use App\Models\WebmasterBanner;
use App\Services\ThemeService;
use Illuminate\Database\Eloquent\Model;

class ResourceAiContextBuilder
{
    public function build(string $type, Language $language, ?Model $resource, array $content = [], ?int $contextId = null): array
    {
        $site = Setting::query()->where('theme_name', ThemeService::active())->where('status', 1)->first()
            ?: Setting::query()->find(1);

        return [
            'language' => [
                'code' => $language->code,
                'name' => $language->title,
                'direction' => $language->direction,
            ],
            'site' => [
                'title' => $site?->{'site_title_'.$language->code},
                'description' => $site?->{'site_desc_'.$language->code},
                'url' => $site?->site_url,
                'theme' => $site?->theme_name ?: ThemeService::active(),
            ],
            'resource' => $this->resource($type, $language, $resource, $contextId),
            'current' => $content,
        ];
    }

    private function resource(string $type, Language $language, ?Model $resource, ?int $contextId): array
    {
        $localized = fn (Model $model, string $field) => $model->{$field.'_'.$language->code}
            ?: $model->{$field.'_'.config('smartend.default_language')};

        return match ($type) {
            'setting' => [
                'type' => 'website',
                'id' => $resource?->getKey(),
                'title' => $resource instanceof Setting ? $localized($resource, 'site_title') : null,
                'description' => $resource instanceof Setting ? $localized($resource, 'site_desc') : null,
                'keywords' => $resource instanceof Setting ? $localized($resource, 'site_keywords') : null,
                'url' => $resource instanceof Setting ? $resource->site_url : null,
                'theme' => $resource instanceof Setting ? $resource->theme_name : null,
            ],
            'banner' => [
                'type' => 'banner',
                'id' => $resource?->getKey(),
                'title' => $resource instanceof Banner ? $localized($resource, 'title') : null,
                'details' => $resource instanceof Banner ? $localized($resource, 'details') : null,
                'section' => $this->bannerSection($resource instanceof Banner ? $resource->section_id : $contextId, $language),
            ],
            'popup' => [
                'type' => 'popup',
                'id' => $resource?->getKey(),
                'title' => $resource instanceof Popup ? $localized($resource, 'title') : null,
                'details' => $resource instanceof Popup ? $localized($resource, 'details') : null,
            ],
            'menu' => [
                'type' => 'menu',
                'id' => $resource?->getKey(),
                'title' => $resource instanceof Menu ? $localized($resource, 'title') : null,
                'parent_title' => $this->menuTitle($resource instanceof Menu ? $resource->father_id : $contextId, $language),
                'link_type' => $resource instanceof Menu ? $resource->type : null,
                'link' => $resource instanceof Menu ? ($resource->{'link_'.$language->code} ?: $resource->link) : null,
            ],
            default => ['type' => $type, 'id' => $resource?->getKey()],
        };
    }

    private function bannerSection(?int $id, Language $language): ?array
    {
        $section = $id ? WebmasterBanner::query()->find($id) : null;
        if (!$section) {
            return null;
        }

        return [
            'id' => $section->id,
            'title' => $section->{'title_'.$language->code} ?: $section->{'title_'.config('smartend.default_language')},
            'type' => $section->type,
            'details_enabled' => (bool) $section->desc_status,
        ];
    }

    private function menuTitle(?int $id, Language $language): ?string
    {
        $menu = $id ? Menu::query()->find($id) : null;

        return $menu?->{'title_'.$language->code} ?: $menu?->{'title_'.config('smartend.default_language')};
    }
}
