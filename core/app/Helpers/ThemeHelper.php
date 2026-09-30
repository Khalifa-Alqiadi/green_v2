<?php

use App\Services\ThemeAssetService;
use App\Services\ThemeService;
use Illuminate\Support\HtmlString;

if (! function_exists('theme_view')) {
    function theme_view(string $view): string
    {
        return ThemeService::view($view);
    }
}

if (! function_exists('theme_asset')) {
    function theme_asset(string $path = ''): string
    {
        return ThemeService::asset($path);
    }
}

if (! function_exists('theme_compiled_assets')) {
    function theme_compiled_assets(): HtmlString
    {
        return app(ThemeAssetService::class)->render();
    }
}
