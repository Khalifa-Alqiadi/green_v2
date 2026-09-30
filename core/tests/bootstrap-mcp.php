<?php

require dirname(__DIR__).'/vendor/autoload.php';

// This check runs before any RefreshDatabase trait can migrate or drop tables.
$root = dirname(__DIR__);
if (! is_file($root.'/.env.testing')) {
    throw new RuntimeException('MCP tests require an explicit .env.testing MySQL configuration.');
}
$test = Dotenv\Dotenv::parse(file_get_contents($root.'/.env.testing'));
$database = $test['DB_DATABASE'] ?? '';
$username = $test['DB_USERNAME'] ?? '';
if (($test['APP_ENV'] ?? '') !== 'testing' || ($test['DB_CONNECTION'] ?? '') !== 'mysql'
    || ! preg_match('/\A[a-zA-Z0-9_]+_testing\z/', $database)
    || ($test['MCP_TEST_DATABASE'] ?? '') !== $database
    || ($test['DB_TABLE_PREFIX'] ?? '') === ''
    || ! preg_match('/\A[a-zA-Z0-9_]*test[a-zA-Z0-9_]*\z/', $username)
    || empty($test['DB_PASSWORD']) || ! empty($test['DB_URL'])
    || ! in_array($test['DB_HOST'] ?? '', ['localhost', '127.0.0.1', '::1'], true)) {
    throw new RuntimeException('Refusing MCP tests: an explicit local prefixed MySQL test database and restricted test credentials are required.');
}
foreach (['.env', '.env.production'] as $file) {
    if (is_file($root.'/'.$file)) {
        $existing = Dotenv\Dotenv::parse(file_get_contents($root.'/'.$file));
        if (($existing['DB_DATABASE'] ?? null) === $database || ($existing['DB_USERNAME'] ?? null) === $username) {
            throw new RuntimeException('Refusing MCP tests: test database and credentials must be separate from the application.');
        }
    }
}

try {
    $dsn = ! empty($test['DB_SOCKET'])
        ? 'mysql:unix_socket='.$test['DB_SOCKET'].';dbname='.$database
        : 'mysql:host='.$test['DB_HOST'].';port='.($test['DB_PORT'] ?? '3306').';dbname='.$database;
    $pdo = new PDO($dsn, $username, $test['DB_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    foreach ($pdo->query('SHOW GRANTS FOR CURRENT_USER()')->fetchAll(PDO::FETCH_COLUMN) as $grant) {
        if (! str_starts_with($grant, 'GRANT USAGE ON *.* TO ')
            && (! str_contains($grant, ' ON `'.$database.'`.* TO ') || str_contains($grant, 'WITH GRANT OPTION'))) {
            throw new RuntimeException('Unsafe grants');
        }
    }
} catch (Throwable) {
    throw new RuntimeException('MCP test database connection/grant check failed; no migrations were run.');
}
$pdo = null;
define('SMARTEND_MCP_MYSQL_TESTS', true);

foreach ($test as $key => $value) {
    $_ENV[$key] = $_SERVER[$key] = $value;
    putenv($key.'='.$value);
}
// Never load cached production routes/config, even when the shell supplies paths.
foreach (['APP_CONFIG_CACHE' => 'bootstrap/cache/config-mcp-testing.php', 'APP_ROUTES_CACHE' => 'bootstrap/cache/routes-mcp-testing.php'] as $key => $path) {
    if (is_file($root.'/'.$path)) {
        throw new RuntimeException('Remove the MCP testing configuration/route cache before running database tests.');
    }
    $_ENV[$key] = $_SERVER[$key] = $path;
    putenv($key.'='.$path);
}
