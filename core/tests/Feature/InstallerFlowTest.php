<?php

use App\Services\InstallerDatabaseManager;
use App\Services\InstallerEnvironmentManager;
use Froiden\LaravelInstaller\Helpers\DatabaseManager;
use Froiden\LaravelInstaller\Helpers\EnvironmentManager;
use Illuminate\Support\Facades\Artisan;

test('installer continues to migrations after saving valid database settings', function () {
    $manager = new class extends InstallerEnvironmentManager
    {
        public function continueAfterSave(array $response): array
        {
            return $this->continueToDatabase($response);
        }

        public function prepareEnvironment(string $contents): string
        {
            return $this->replaceEnvironmentValues($contents, [
                'SESSION_DRIVER' => 'file',
                'CACHE_STORE' => 'file',
            ]);
        }
    };

    $response = $manager->continueAfterSave([
        'status' => 'success',
        'action' => 'redirect',
        'url' => route('LaravelInstaller::requirements'),
    ]);

    expect($response['url'])->toBe(route('LaravelInstaller::database'))
        ->and(app(EnvironmentManager::class))->toBeInstanceOf(InstallerEnvironmentManager::class)
        ->and($manager->prepareEnvironment("SESSION_DRIVER=database\nCACHE_STORE=database\n"))
        ->toBe("SESSION_DRIVER=file\nCACHE_STORE=file\n");
});

test('installer preserves database validation failures', function () {
    $manager = new class extends InstallerEnvironmentManager
    {
        public function continueAfterSave(array $response): array
        {
            return $this->continueToDatabase($response);
        }
    };

    $response = [
        'status' => 'fail',
        'message' => 'DB Error',
    ];

    expect($manager->continueAfterSave($response))->toBe($response);
});

test('fresh installations use runtime stores that do not require migrated tables', function () {
    $environment = file_get_contents(base_path('.env.example'));

    expect($environment)
        ->toMatch('/^SESSION_DRIVER=file$/m')
        ->toMatch('/^CACHE_STORE=file$/m')
        ->toMatch('/^DB_CHARSET=utf8mb4$/m')
        ->toMatch('/^DB_COLLATION=utf8mb4_unicode_ci$/m');
});

test('installer forces database seeding and only reports success after it completes', function () {
    Artisan::shouldReceive('call')
        ->once()
        ->with('migrate:fresh', ['--force' => true])
        ->andReturn(0);
    Artisan::shouldReceive('call')
        ->once()
        ->with('db:seed', ['--force' => true])
        ->andReturn(0);

    $response = app(DatabaseManager::class)->migrateAndSeed();

    expect(app(DatabaseManager::class))->toBeInstanceOf(InstallerDatabaseManager::class)
        ->and($response['status'])->toBe('success');
});

test('installer does not finalize when seeding fails', function () {
    Artisan::shouldReceive('call')
        ->once()
        ->with('migrate:fresh', ['--force' => true])
        ->andReturn(0);
    Artisan::shouldReceive('call')
        ->once()
        ->with('db:seed', ['--force' => true])
        ->andReturn(1);
    Artisan::shouldReceive('output')->once()->andReturn('Seeder failed');

    expect(fn () => app(DatabaseManager::class)->migrateAndSeed())
        ->toThrow(RuntimeException::class, 'Seeder failed');
});
