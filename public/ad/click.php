<?php
declare(strict_types=1);

define('BASE_PATH',   dirname(__DIR__, 2));
define('APP_PATH',    BASE_PATH . '/app');
define('CONFIG_PATH', BASE_PATH . '/config');
define('STORAGE_PATH',BASE_PATH . '/storage');

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
    }
}

ini_set('display_errors', '0');
error_reporting(0);

$impressionId = (int)($_GET['i'] ?? 0);
$bannerId     = (int)($_GET['b'] ?? 0);

if (!$impressionId || !$bannerId) {
    http_response_code(400); exit;
}

try {
    $db = \Core\Database::getInstance();

    // Banner + Campaign laden für Redirect
    $stmt = $db->prepare('
        SELECT c.target_url, b.id AS banner_id, b.campaign_id,
               b.user_id, u.id AS unit_id, u.user_id AS publisher_id
        FROM ad_banners b
        JOIN campaigns c ON c.id = b.campaign_id
        JOIN impressions i ON i.id = ?
        JOIN ad_units u ON u.id = i.unit_id
        WHERE b.id = ?
        LIMIT 1
    ');
    $stmt->execute([$impressionId, $bannerId]);
    $row = $stmt->fetch();

    if (!$row) {
        http_response_code(404); exit;
    }

    // IP hash
    $ip     = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '')[0]);
    $ipHash = hash('sha256', $ip . ($_ENV['APP_SECRET'] ?? ''));

    // Fraud check – Doppelklick in 30 Sek?
    $dupeCheck = $db->prepare('
        SELECT id FROM clicks
        WHERE ip_hash = ? AND banner_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 30 SECOND)
        LIMIT 1
    ');
    $dupeCheck->execute([$ipHash, $bannerId]);

    $isDupe = (bool)$dupeCheck->fetch();

    // Click loggen
    $db->prepare('
        INSERT INTO clicks
            (impression_id, banner_id, unit_id, campaign_id, ip_hash,
             country, referer, fraud_score, is_fraud, cost, created_at)
        VALUES (?,?,?,?,?,?,?,?,?,0, NOW())
    ')->execute([
        $impressionId,
        $bannerId,
        $row['unit_id'],
        $row['campaign_id'],
        $ipHash,
        strtoupper(substr($_SERVER['HTTP_CF_IPCOUNTRY'] ?? '', 0, 2)) ?: null,
        substr($_SERVER['HTTP_REFERER'] ?? '', 0, 2048),
        $isDupe ? 0.8 : 0.0,
        $isDupe ? 1 : 0,
    ]);

    // Earnings für Klick (CPA Modell) – hier nur für CPA relevant
    // CPM/CPD werden per Impression abgerechnet

} catch (\Exception $e) {
    // Fehler darf Redirect nicht blockieren
}

// Redirect zur Zielseite
$targetUrl = filter_var($row['target_url'] ?? '/', FILTER_VALIDATE_URL) ? $row['target_url'] : '/';
header('Location: ' . $targetUrl, true, 302);
exit;
