<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use JsonException;
use RuntimeException;
use Throwable;

class ThemeService
{
    public const DEFAULT_THEME = 'classic';

    private static ?string $activeTheme = null;

    private static ?string $requestTheme = null;

    private static ?array $themeDirectories = null;

    private static ?array $enabledThemeNames = null;

    private static ?array $availableThemes = null;

    private static array $availability = [];

    public static function active(): string
    {
        if (self::$requestTheme !== null) {
            return self::$requestTheme;
        }

        if (self::$activeTheme !== null) {
            return self::$activeTheme;
        }

        $theme = (string) config('smartend.theme', self::DEFAULT_THEME);

        if (self::isAvailable($theme)) {
            return self::$activeTheme = $theme;
        }

        if (self::isAvailable(self::DEFAULT_THEME)) {
            return self::$activeTheme = self::DEFAULT_THEME;
        }

        return self::$activeTheme = self::availableThemes()[0] ?? self::DEFAULT_THEME;
    }

    public static function setRequestTheme(?string $theme): void
    {
        self::$requestTheme = $theme;
        self::$activeTheme = null;
    }

    public static function isValidName(?string $theme): bool
    {
        return is_string($theme) && preg_match('/^[a-z0-9_-]+$/', $theme) === 1;
    }

    public static function isAvailable(?string $theme): bool
    {
        if (! self::isValidName($theme)) {
            return false;
        }

        if (array_key_exists($theme, self::$availability)) {
            return self::$availability[$theme];
        }

        if (! File::isDirectory(self::viewsPath($theme)) || ! File::isDirectory(self::assetsPath($theme))) {
            return self::$availability[$theme] = false;
        }

        $enabledThemeNames = self::enabledThemeNames();
        if ($enabledThemeNames === null) {
            return self::$availability[$theme] = $theme === self::DEFAULT_THEME;
        }

        return self::$availability[$theme] = in_array($theme, $enabledThemeNames, true);
    }

    public static function view(string $view): string
    {
        return 'themes.'.self::active().'.'.$view;
    }

    public static function asset(string $path = ''): string
    {
        return self::assetFor(self::active(), $path);
    }

    public static function assetFor(string $theme, string $path = ''): string
    {
        if (! self::isValidName($theme)) {
            throw new \InvalidArgumentException('Invalid theme name.');
        }

        $path = ltrim($path, '/');

        return asset(trim('assets/'.$theme.'/'.$path, '/'));
    }

    public static function availableThemes(): array
    {
        if (self::$availableThemes !== null) {
            return self::$availableThemes;
        }

        $themes = self::themeDirectories();

        $enabledThemes = self::enabledThemeNames();
        if ($enabledThemes !== null) {
            $themes = array_values(array_filter($themes, fn ($theme) => in_array($theme, $enabledThemes, true)));
        } else {
            $themes = in_array(self::DEFAULT_THEME, $themes, true) ? [self::DEFAULT_THEME] : [];
        }

        $themes = array_values(array_filter($themes, fn ($theme) => File::isDirectory(self::assetsPath($theme))));

        sort($themes);

        return self::$availableThemes = $themes;
    }

    public static function previewAsset(string $theme): string
    {
        if (! self::isValidName($theme)) {
            throw new \InvalidArgumentException('Invalid theme name.');
        }

        return asset('assets/'.$theme.'/images/preview.png');
    }

    public static function resolveCustomView(string $view): ?string
    {
        if (preg_match('/^[a-zA-Z0-9_-]+$/', $view) !== 1) {
            return null;
        }

        $candidates = [
            'themes.'.self::active().'.custom.'.$view,
            'themes.'.self::DEFAULT_THEME.'.custom.'.$view,
            'frontEnd.custom.'.$view,
        ];

        foreach (array_unique($candidates) as $candidate) {
            if (View::exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    public static function compiledThemeConfig(?string $theme): ?array
    {
        if (! self::isValidName($theme)) {
            return null;
        }

        $config = config('smartend.compiled_themes.'.$theme);
        if (! is_array($config)) {
            return null;
        }

        $entry = self::safeRelativePath($config['entry'] ?? null);
        $buildDirectory = self::safeRelativePath($config['build_directory'] ?? null);
        $hotFile = self::safeRelativePath($config['hot_file'] ?? null);
        $fallbackAsset = self::safeRelativePath($config['fallback_asset'] ?? null);

        if ($entry === null || $buildDirectory === null || $hotFile === null || $fallbackAsset === null) {
            return null;
        }

        return [
            'entry' => $entry,
            'build_directory' => $buildDirectory,
            'hot_file' => base_path($hotFile),
            'manifest' => self::publicPath($buildDirectory.'/manifest.json'),
            'fallback_asset' => $fallbackAsset,
        ];
    }

    public static function compiledReadiness(?string $theme): array
    {
        if (! self::isValidName($theme)) {
            return self::readiness(false, false, 'invalid_theme', 'Invalid theme name.');
        }

        $rawConfig = config('smartend.compiled_themes.'.$theme);
        if ($rawConfig === null) {
            return self::readiness(false, true, 'static', null);
        }

        $config = self::compiledThemeConfig($theme);
        if ($config === null) {
            return self::readiness(true, false, 'config_invalid', 'Compiled theme asset configuration is invalid.');
        }

        if (app()->environment(['local', 'testing']) && File::isFile($config['hot_file'])) {
            try {
                $hotUrl = trim((string) File::get($config['hot_file']));
                if (self::isValidHotUrl($hotUrl)) {
                    return self::readiness(true, true, 'hot', null);
                }
            } catch (Throwable) {
                // A production manifest may still be usable.
            }
        }

        if (! File::isFile($config['manifest']) || ! File::isReadable($config['manifest'])) {
            return self::readiness(true, false, 'manifest_missing', 'Compiled theme manifest is missing.');
        }

        try {
            $manifest = json_decode(File::get($config['manifest']), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return self::readiness(true, false, 'manifest_invalid', 'Compiled theme manifest is not valid JSON.');
        } catch (Throwable) {
            return self::readiness(true, false, 'manifest_unreadable', 'Compiled theme manifest could not be read.');
        }

        $chunk = is_array($manifest) ? ($manifest[$config['entry']] ?? null) : null;
        if (! is_array($chunk) || self::safeRelativePath($chunk['file'] ?? null) === null) {
            return self::readiness(true, false, 'entry_missing', 'Compiled theme entry is missing from the manifest.');
        }

        $pending = [$config['entry']];
        $visited = [];
        while ($pending !== []) {
            $manifestKey = array_pop($pending);
            if (isset($visited[$manifestKey])) {
                continue;
            }

            $visited[$manifestKey] = true;
            $manifestChunk = $manifest[$manifestKey] ?? null;
            if (! is_array($manifestChunk)) {
                return self::readiness(true, false, 'chunk_missing', 'A compiled theme chunk is missing from the manifest.');
            }

            $css = $manifestChunk['css'] ?? [];
            $staticImports = $manifestChunk['imports'] ?? [];
            $dynamicImports = $manifestChunk['dynamicImports'] ?? [];
            if (! is_array($css) || ! is_array($staticImports) || ! is_array($dynamicImports)) {
                return self::readiness(true, false, 'manifest_invalid', 'Compiled theme manifest has an invalid entry.');
            }
            $imports = [...$staticImports, ...$dynamicImports];

            $files = [$manifestChunk['file'] ?? null, ...$css];
            foreach ($files as $file) {
                $relativeFile = self::safeRelativePath($file);
                if ($relativeFile === null || ! File::isFile(self::publicPath($config['build_directory'].'/'.$relativeFile))) {
                    return self::readiness(true, false, 'asset_missing', 'A compiled theme asset referenced by the manifest is missing.');
                }
            }

            foreach ($imports as $import) {
                if (! is_string($import) || self::safeRelativePath($import) === null) {
                    return self::readiness(true, false, 'manifest_invalid', 'Compiled theme manifest has an invalid import.');
                }

                $pending[] = $import;
            }
        }

        return self::readiness(true, true, 'manifest', null);
    }

    public static function activationIssues(?string $theme): array
    {
        if (! self::isAvailable($theme)) {
            return ['Invalid or unavailable theme.'];
        }

        $issues = [];
        if (! File::isFile(self::assetsPath($theme).'/images/preview.png')) {
            $issues[] = 'Theme preview image is missing.';
        }

        $config = self::compiledThemeConfig($theme);
        if ($config !== null && ! File::isFile(self::assetsPath($theme).'/'.$config['fallback_asset'])) {
            $issues[] = 'Compiled theme fallback stylesheet is missing.';
        }

        $readiness = self::compiledReadiness($theme);
        if (! $readiness['ready'] && $readiness['message'] !== null) {
            $issues[] = $readiness['message'];
        }

        return $issues;
    }

    public static function activate(string $theme): void
    {
        $issues = self::activationIssues($theme);
        if ($issues !== []) {
            throw new \InvalidArgumentException(implode(' ', $issues));
        }

        $envPath = self::envPath();
        $envExisted = File::exists($envPath);
        if ($envExisted && ! File::isReadable($envPath)) {
            throw new RuntimeException('The environment file is not readable.');
        }

        $previousContents = $envExisted ? File::get($envPath) : null;
        $runtime = self::runtimeThemeState();

        try {
            self::writeEnvValue('THEME', $theme);
            self::setRuntimeTheme($theme);

            self::runCacheCommand('config:clear');
            self::runCacheCommand('view:clear');

            self::flushRuntimeCache();
            if (self::active() !== $theme) {
                throw new RuntimeException('The requested theme did not become active.');
            }
        } catch (Throwable $exception) {
            $rollbackError = self::rollbackActivation($envPath, $envExisted, $previousContents, $runtime);
            $message = 'Theme activation failed and the previous theme was restored.';
            if ($rollbackError !== null) {
                $message = 'Theme activation failed and rollback also failed: '.$rollbackError->getMessage();
            }

            throw new RuntimeException($message, 0, $exception);
        }
    }

    public static function viewsPath(string $theme): string
    {
        return resource_path('views/themes/'.$theme);
    }

    public static function assetsPath(string $theme): string
    {
        return self::publicPath('assets/'.$theme);
    }

    public static function environmentPath(): string
    {
        return self::envPath();
    }

    public static function flushRuntimeCache(): void
    {
        self::$activeTheme = null;
        self::$themeDirectories = null;
        self::$enabledThemeNames = null;
        self::$availableThemes = null;
        self::$availability = [];
    }

    private static function themeDirectories(): array
    {
        if (self::$themeDirectories === null) {
            self::$themeDirectories = array_map('basename', File::directories(resource_path('views/themes')));
        }

        return self::$themeDirectories;
    }

    private static function publicPath(string $path = ''): string
    {
        return str_replace('/core/public', '', str_replace('\\', '/', public_path($path)));
    }

    private static function enabledThemeNames(): ?array
    {
        if (self::$enabledThemeNames === null) {
            try {
                self::$enabledThemeNames = Setting::query()
                    ->where('status', 1)
                    ->whereNotNull('theme_name')
                    ->pluck('theme_name')
                    ->filter(fn ($theme) => self::isValidName($theme))
                    ->values()
                    ->all();
            } catch (QueryException) {
                return null;
            }
        }

        return self::$enabledThemeNames;
    }

    private static function writeEnvValue(string $key, string $value): void
    {
        $envPath = self::envPath();
        $line = $key.'='.$value;

        if (! File::exists($envPath)) {
            self::replaceFileAtomically($envPath, $line.PHP_EOL);

            return;
        }

        $contents = File::get($envPath);
        if (preg_match('/^'.preg_quote($key, '/').'=.*$/m', $contents)) {
            $contents = preg_replace('/^'.preg_quote($key, '/').'=.*$/m', $line, $contents);
        } else {
            $contents = rtrim($contents);
            $contents = ($contents === '' ? '' : $contents.PHP_EOL).$line.PHP_EOL;
        }

        self::replaceFileAtomically($envPath, $contents);
    }

    private static function safeRelativePath(mixed $path): ?string
    {
        if (! is_string($path)) {
            return null;
        }

        $path = trim(str_replace('\\', '/', $path), '/');
        if ($path === '' || str_contains($path, "\0")) {
            return null;
        }

        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return null;
            }
        }

        return $path;
    }

    private static function readiness(bool $compiled, bool $ready, string $code, ?string $message): array
    {
        return compact('compiled', 'ready', 'code', 'message');
    }

    private static function isValidHotUrl(string $url): bool
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        return in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true);
    }

    private static function envPath(): string
    {
        $configured = config('smartend.theme_env_path');

        return is_string($configured) && $configured !== '' ? $configured : base_path('.env');
    }

    private static function runtimeThemeState(): array
    {
        return [
            'config' => config('smartend.theme'),
            'environment' => getenv('THEME'),
            'env_exists' => array_key_exists('THEME', $_ENV),
            'env' => $_ENV['THEME'] ?? null,
            'server_exists' => array_key_exists('THEME', $_SERVER),
            'server' => $_SERVER['THEME'] ?? null,
        ];
    }

    private static function setRuntimeTheme(string $theme): void
    {
        putenv('THEME='.$theme);
        $_ENV['THEME'] = $theme;
        $_SERVER['THEME'] = $theme;
        config(['smartend.theme' => $theme]);
        self::flushRuntimeCache();
    }

    private static function restoreRuntimeTheme(array $runtime): void
    {
        if ($runtime['environment'] === false) {
            putenv('THEME');
        } else {
            putenv('THEME='.$runtime['environment']);
        }

        if ($runtime['env_exists']) {
            $_ENV['THEME'] = $runtime['env'];
        } else {
            unset($_ENV['THEME']);
        }

        if ($runtime['server_exists']) {
            $_SERVER['THEME'] = $runtime['server'];
        } else {
            unset($_SERVER['THEME']);
        }

        config(['smartend.theme' => $runtime['config']]);
        self::flushRuntimeCache();
    }

    private static function runCacheCommand(string $command): void
    {
        if (Artisan::call($command) !== 0) {
            throw new RuntimeException('Application cache clearing failed.');
        }
    }

    private static function rollbackActivation(
        string $envPath,
        bool $envExisted,
        ?string $previousContents,
        array $runtime
    ): ?Throwable {
        try {
            $currentContents = File::exists($envPath) && File::isReadable($envPath)
                ? File::get($envPath)
                : null;

            if ($envExisted) {
                if (! File::exists($envPath) || $currentContents !== $previousContents) {
                    self::replaceFileAtomically($envPath, (string) $previousContents);
                }
            } elseif (File::exists($envPath)) {
                if (! File::delete($envPath)) {
                    throw new RuntimeException('Unable to remove the newly created environment file.');
                }
            }

            self::restoreRuntimeTheme($runtime);

            return null;
        } catch (Throwable $rollbackError) {
            return $rollbackError;
        }
    }

    private static function replaceFileAtomically(string $path, string $contents): void
    {
        $directory = dirname($path);
        if (! File::isDirectory($directory) || ! File::isWritable($directory)) {
            throw new RuntimeException('The environment directory is not writable.');
        }

        if (File::exists($path) && ! File::isWritable($path)) {
            throw new RuntimeException('The environment file is not writable.');
        }

        $permissions = File::exists($path) ? fileperms($path) : false;
        $mode = $permissions === false ? null : ($permissions & 0777);
        File::replace($path, $contents, $mode);

        if (! File::exists($path) || File::get($path) !== $contents) {
            throw new RuntimeException('The environment file could not be updated atomically.');
        }
    }
}
