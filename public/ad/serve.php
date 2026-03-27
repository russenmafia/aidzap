<?php
declare(strict_types=1);

defined('BASE_PATH') || define('BASE_PATH', dirname(__DIR__, 2));
defined('APP_PATH') || define('APP_PATH', BASE_PATH . '/app');
defined('CONFIG_PATH') || define('CONFIG_PATH', BASE_PATH . '/config');
defined('STORAGE_PATH') || define('STORAGE_PATH', BASE_PATH . '/storage');

// Autoloader
spl_autoload_register(function (string $class): void {
    $file = APP_PATH . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) require_once $file;
});

// .env laden
$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

// Fehler nie anzeigen – wir liefern HTML aus
ini_set('display_errors', '0');
error_reporting(0);

\Core\RateLimit::check('ad_serve');

// UUID aus URL extrahieren
// Route: /ad/{uuid} oder serve.php?unit={uuid}
$uuid = $_GET['unit'] ?? null;
if (!$uuid) {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $parts = explode('/', trim($path, '/'));
    // /ad/xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
    $uuid = $parts[1] ?? null;
}

if (!$uuid || !preg_match('/^[0-9a-f\-]{36}$/', $uuid)) {
    http_response_code(400);
    exit('<!-- aidzap: invalid unit -->');
}

// Ad Engine ausführen
$engine = new Services\AdServeService();
$engine->serve($uuid);
