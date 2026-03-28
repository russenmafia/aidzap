<?php
declare(strict_types=1);

define('BASE_PATH',   dirname(__DIR__));
define('APP_PATH',    BASE_PATH . '/app');
define('CONFIG_PATH', BASE_PATH . '/config');
define('STORAGE_PATH',BASE_PATH . '/storage');

require_once APP_PATH . "/Core/Lang.php";
require_once APP_PATH . "/Core/helpers.php";
spl_autoload_register(function (string $class): void {
    $file = APP_PATH . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) require_once $file;
});

$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
        putenv(trim($key) . '=' . trim($value));
    }
}

if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
    ini_set('log_errors', '1');
    ini_set('error_log', STORAGE_PATH . '/logs/php_error.log');
}

session_start([
    'cookie_httponly' => true,
    'cookie_secure'   => true,
    'cookie_samesite' => 'Strict',
    'gc_maxlifetime'  => (int)($_ENV['SESSION_LIFETIME'] ?? 7200),
]);

\Core\Lang::init();

// Run pending database migrations
require_once APP_PATH . '/Core/Migration.php';
\Core\Migration::init();

// Optional maintenance mode for non-admin visitors.
try {
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if (!is_string($requestPath) || $requestPath === '') {
        $requestPath = '/';
    }

    $isAdminSession = (string)($_SESSION['role'] ?? '') === 'admin';
    $isAdminRoute = str_starts_with($requestPath, '/admin');
    $allowDuringMaintenance = $isAdminRoute || in_array($requestPath, ['/login', '/register', '/logout', '/lang/en', '/lang/de'], true);

    if (!$isAdminSession && !$allowDuringMaintenance) {
        $db = \Core\Database::getInstance();
        $rows = $db->query('SELECT `key`, `value` FROM site_settings WHERE `key` IN ("maintenance_mode", "maintenance_notice")')->fetchAll(\PDO::FETCH_KEY_PAIR);
        $maintenanceMode = (string)($rows['maintenance_mode'] ?? '0');

        if ($maintenanceMode === '1') {
            $notice = (string)($rows['maintenance_notice'] ?? 'We are back soon.');
            http_response_code(503);
            header('Content-Type: text/html; charset=UTF-8');
            echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Maintenance</title></head><body style="margin:0;background:#070b10;color:#dce7f0;font-family:Arial,sans-serif;display:flex;min-height:100vh;align-items:center;justify-content:center;padding:20px"><div style="max-width:680px;border:1px solid rgba(255,255,255,.1);border-radius:12px;padding:28px;background:rgba(255,255,255,.03)"><h1 style="margin:0 0 12px;font-size:28px;color:#3ecf8e">Maintenance Mode</h1><p style="margin:0;font-size:16px;line-height:1.6">' . htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') . '</p></div></body></html>';
            exit;
        }
    }
} catch (\Throwable $e) {
    error_log('public/index maintenance_mode - ' . $e->getMessage());
}

require_once APP_PATH . '/Core/Router.php';
$router = new Core\Router();
require_once CONFIG_PATH . '/routes.php';
$router->dispatch();
