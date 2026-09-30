<?php

namespace App\Services;

use Froiden\LaravelInstaller\Helpers\EnvironmentManager;
use Froiden\LaravelInstaller\Helpers\Reply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class InstallerEnvironmentManager extends EnvironmentManager
{
    /**
     * Save the database settings and continue with the migration step.
     */
    public function saveFile(Request $input)
    {
        $response = parent::saveFile($input);

        if (! is_array($response)) {
            return $response;
        }

        if (($response['status'] ?? null) === 'success'
            && ($response['action'] ?? null) === 'redirect'
            && ! $this->useFileBackedRuntimeStores()) {
            return Reply::error(trans('messages.environment.errors'));
        }

        if (($response['status'] ?? null) === 'success'
            && ($response['action'] ?? null) === 'redirect'
            && Artisan::call('config:clear') !== 0) {
            return Reply::error(trans('messages.environment.errors'));
        }

        return $this->continueToDatabase($response);
    }

    /**
     * Avoid querying unmigrated runtime tables and guarantee multilingual database storage.
     */
    protected function useFileBackedRuntimeStores(): bool
    {
        $path = base_path('.env');
        $contents = @file_get_contents($path);

        if ($contents === false) {
            return false;
        }

        $contents = $this->replaceEnvironmentValues($contents, [
            'SESSION_DRIVER' => 'file',
            'CACHE_STORE' => 'file',
            'DB_CHARSET' => 'utf8mb4',
            'DB_COLLATION' => 'utf8mb4_unicode_ci',
        ]);

        return @file_put_contents($path, $contents, LOCK_EX) !== false;
    }

    protected function replaceEnvironmentValues(string $contents, array $values): string
    {
        foreach ($values as $key => $value) {
            $line = $key.'='.$value;
            $pattern = '/^'.preg_quote($key, '/').'\s*=.*$/m';

            if (preg_match($pattern, $contents) === 1) {
                $contents = preg_replace($pattern, $line, $contents);
            } else {
                $contents = rtrim($contents).PHP_EOL.$line.PHP_EOL;
            }
        }

        return $contents;
    }

    /**
     * The upstream installer incorrectly sends a successful save back to requirements.
     */
    protected function continueToDatabase(array $response): array
    {
        if (($response['status'] ?? null) === 'success'
            && ($response['action'] ?? null) === 'redirect') {
            $response['url'] = route('LaravelInstaller::database');
        }

        return $response;
    }
}
