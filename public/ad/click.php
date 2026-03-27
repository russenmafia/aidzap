<?php
declare(strict_types=1);
defined('BASE_PATH') || define('BASE_PATH', dirname(__DIR__, 2));
defined('APP_PATH')  || define('APP_PATH', BASE_PATH . '/app');

spl_autoload_register(function(string $class): void {
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

ini_set("display_errors", "0");
error_reporting(0);

$impressionId = (int)($_GET['i'] ?? 0);
if (!$impressionId) {
    http_response_code(400);
    exit;
}

$db = \Core\Database::getInstance();

// Impression laden
$stmt = $db->prepare('
    SELECT i.*, b.html, c.pricing_model, c.bid_amount, c.id AS campaign_id,
           au.user_id AS publisher_id, au.id AS unit_id
    FROM impressions i
    JOIN ad_banners b ON b.id = i.banner_id
    JOIN campaigns c ON c.id = i.campaign_id
    JOIN ad_units au ON au.id = i.unit_id
    WHERE i.id = ? AND i.is_fraud = 0
    LIMIT 1
');
$stmt->execute([$impressionId]);
$impression = $stmt->fetch(\PDO::FETCH_ASSOC);

if (!$impression) {
    http_response_code(404);
    exit;
}

// Klick-Ziel aus Banner HTML extrahieren
preg_match('/href=["\']([^"\']+)["\']/', $impression['html'] ?? '', $m);
$targetUrl = $m[1] ?? '/';

// Klick in impressions loggen
$db->prepare('UPDATE impressions SET clicks = COALESCE(clicks, 0) + 1 WHERE id = ?')
   ->execute([$impressionId]);

// CPA: Budget + Earnings bei Klick abrechnen
if ($impression['pricing_model'] === 'cpa') {
    $cost = (float)$impression['bid_amount'];
    if ($cost > 0) {
        // Budget abziehen
        $db->prepare('
            UPDATE campaigns
            SET spent = spent + ?
            WHERE id = ? AND (total_budget - spent) >= ?
        ')->execute([$cost, $impression['campaign_id'], $cost]);

        // Publisher Earnings
        $share = (float)$db->prepare('SELECT revenue_share FROM ad_units WHERE id = ? LIMIT 1')
            ->execute([$impression['unit_id']]) ? 
            $db->query('SELECT revenue_share FROM ad_units WHERE id = ' . $impression['unit_id'] . ' LIMIT 1')->fetchColumn() : 80;
        
        $publisherEarning = round($cost * ($share / 100), 8);
        
        $db->prepare('
            INSERT INTO earnings (user_id, unit_id, date, currency, amount, impressions, clicks)
            VALUES (?, ?, CURDATE(), "BTC", ?, 0, 1)
            ON DUPLICATE KEY UPDATE amount = amount + VALUES(amount), clicks = clicks + 1
        ')->execute([$impression['publisher_id'], $impression['unit_id'], $publisherEarning]);

        $db->prepare('
            INSERT INTO balances (user_id, currency, amount)
            VALUES (?, "BTC", ?)
            ON DUPLICATE KEY UPDATE amount = amount + VALUES(amount)
        ')->execute([$impression['publisher_id'], $publisherEarning]);
    }
}

// Redirect zum Ziel
header('Location: ' . $targetUrl, true, 302);
exit;
