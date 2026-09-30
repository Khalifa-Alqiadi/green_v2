<?php

namespace App\Services;

use Illuminate\Http\Request;

class DemoThemeService
{
    public const SESSION_KEY = 'demo_theme';

    public const THEMES = ['classic', 'modern', 'velocity'];

    public static function enabled(Request $request): bool
    {
        if (! config('smartend.demo_theme_switcher.enabled', false)) {
            return false;
        }

        $configuredHost = trim((string) config('smartend.demo_theme_switcher.host'));
        if ($configuredHost === '') {
            return true;
        }

        $host = parse_url(str_contains($configuredHost, '://') ? $configuredHost : '//'.$configuredHost, PHP_URL_HOST);

        return is_string($host) && strcasecmp($request->getHost(), $host) === 0;
    }

    public static function themes(): array
    {
        return array_values(array_filter(
            self::THEMES,
            fn (string $theme): bool => ThemeService::isAvailable($theme)
        ));
    }

    public static function selected(Request $request): string
    {
        $themes = self::themes();
        $selected = $request->session()->get(self::SESSION_KEY);

        if (is_string($selected) && in_array($selected, $themes, true)) {
            return $selected;
        }

        $default = (string) config('smartend.demo_theme_switcher.default', 'velocity');

        return in_array($default, $themes, true)
            ? $default
            : (ThemeService::active() ?: ThemeService::DEFAULT_THEME);
    }
}
