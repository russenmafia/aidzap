<?php
declare(strict_types=1);

define('BASE_PATH',   dirname(__DIR__));
define('APP_PATH',    BASE_PATH . '/app');
define('CONFIG_PATH', BASE_PATH . '/config');
define('STORAGE_PATH',BASE_PATH . '/storage');

// Nur CLI erlaubt
if (PHP_SAPI !== 'cli') {
    http_response_code(403); exit('CLI only');
}

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

$job = $argv[1] ?? 'help';

$jobs = [
    'daily-stats'         => 'Cron\DailyStatsJob',
    'budget-reset'        => 'Cron\BudgetResetJob',
    'fraud-cleanup'       => 'Cron\FraudCleanupJob',
    'payment-check'       => 'Cron\PaymentCheckJob',
    'rate-limit-cleanup'  => 'Cron\RateLimitCleanupJob',
];

if ($job === 'help' || !isset($jobs[$job])) {
    echo "Usage: php runner.php <job>\n";
    echo "Jobs:\n";
    foreach ($jobs as $name => $class) {
        echo "  {$name}\n";
    }
    exit(0);
}

$className = $jobs[$job];
require_once APP_PATH . '/../cron/' . str_replace('Cron\\', '', $className) . '.php';

$instance = new $className();
$start    = microtime(true);

echo "[" . date('Y-m-d H:i:s') . "] Running: {$job}\n";

try {
    $result = $instance->run();
    $elapsed = round(microtime(true) - $start, 2);
    echo "[" . date('H:i:s') . "] Done in {$elapsed}s: {$result}\n";
} catch (\Throwable $e) {
    echo "[" . date('H:i:s') . "] ERROR: " . $e->getMessage() . "\n";
    error_log("Cron {$job} failed: " . $e->getMessage());
    exit(1);
}
