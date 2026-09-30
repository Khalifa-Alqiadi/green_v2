<?php

namespace App\Services;

use Froiden\LaravelInstaller\Helpers\DatabaseManager;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;

class InstallerDatabaseManager extends DatabaseManager
{
    /**
     * Migrate and seed while treating either command failure as an installation failure.
     */
    public function migrateAndSeed(): array
    {
        $this->runInstallerCommand('migrate:fresh', ['--force' => true]);
        $this->runInstallerCommand('db:seed', ['--force' => true]);

        return [
            'status' => 'success',
            'message' => trans('installer_messages.final.finished'),
        ];
    }

    protected function runInstallerCommand(string $command, array $parameters): void
    {
        if (Artisan::call($command, $parameters) === 0) {
            return;
        }

        $output = trim(Artisan::output());

        throw new RuntimeException($output !== '' ? $output : "Installer command [{$command}] failed.");
    }
}
