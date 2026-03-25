<?php
declare(strict_types=1);

define('BASE_PATH', __DIR__);
define('APP_PATH',    BASE_PATH . '/app');
define('CONFIG_PATH', BASE_PATH . '/config');
define('STORAGE_PATH',BASE_PATH . '/storage');

require_once APP_PATH . "/Core/Lang.php";
require_once APP_PATH . "/Core/helpers.php";
// Autoloader
spl_autoload_register(function (string $class): void {
    $file = APP_PATH . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// .env laden
$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
        putenv(trim($key) . '=' . trim($value));
    }
}

// Fehlerbehandlung
if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
    ini_set('log_errors', '1');
    ini_set('error_log', STORAGE_PATH . '/logs/php_error.log');
}

// Session
session_start([
    'cookie_httponly' => true,
    'cookie_secure'   => true,
    'cookie_samesite' => 'Strict',
    'gc_maxlifetime'  => (int)($_ENV['SESSION_LIFETIME'] ?? 7200),
]);
\Core\Lang::init();

// Router
require_once APP_PATH . '/Core/Router.php';
$router = new Core\Router();
require_once CONFIG_PATH . '/routes.php';
$router->dispatch();
