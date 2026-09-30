<?php

namespace App\Services;

use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Throwable;

class ThemeAssetService
{
    public function render(?string $theme = null): HtmlString
    {
        $theme ??= ThemeService::active();
        if ($theme !== ThemeService::active()) {
            return new HtmlString('');
        }

        $config = ThemeService::compiledThemeConfig($theme);
        if ($config === null) {
            return new HtmlString('');
        }

        $readiness = ThemeService::compiledReadiness($theme);
        if (! $readiness['ready']) {
            return $this->fallback($theme, $config, $readiness);
        }

        try {
            $vite = clone app(Vite::class);
            $vite->useHotFile($config['hot_file'])
                ->useBuildDirectory($config['build_directory'])
                ->useManifestFilename('manifest.json');

            return $vite($config['entry'], $config['build_directory']);
        } catch (Throwable $exception) {
            report($exception);

            return $this->fallback($theme, $config, [
                'code' => 'render_failed',
                'message' => 'Compiled theme assets could not be rendered.',
            ]);
        }
    }

    private function fallback(string $theme, array $config, array $readiness): HtmlString
    {
        Log::error('Compiled theme assets are unavailable.', [
            'theme' => $theme,
            'code' => $readiness['code'],
        ]);

        $fallbackPath = ThemeService::assetsPath($theme).'/'.$config['fallback_asset'];
        if (! File::isFile($fallbackPath)) {
            return new HtmlString('');
        }

        $url = e(ThemeService::assetFor($theme, $config['fallback_asset']));

        return new HtmlString('<link rel="stylesheet" href="'.$url.'" data-theme-asset-fallback>');
    }
}
