<?php
declare(strict_types=1);
namespace Core;

class RateLimit
{
    // [endpoint => [max_hits, window_seconds]]
    private const LIMITS = [
        'login'           => [10,  300],   // 10 attempts per 5 min
        'register'        => [5,   3600],  // 5 per hour
        'wallet_nonce'    => [20,  300],   // 20 per 5 min
        'wallet_login'    => [10,  300],   // 10 per 5 min
        'payment_deposit' => [10,  3600],  // 10 per hour
        'ad_serve'        => [300, 60],    // 300 per minute
        'admin_api'       => [60,  60],    // 60 per minute
    ];

    public static function check(string $endpoint, ?string $ip = null): void
    {
        if (!isset(self::LIMITS[$endpoint])) return;

        [$maxHits, $windowSeconds] = self::LIMITS[$endpoint];
        $ip      = $ip ?? ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        $keyHash = hash('sha256', $ip . '|' . $endpoint);
        $db      = Database::getInstance();

        // Cleanup expired windows for this endpoint only (cheap, scoped)
        $db->prepare(
            'DELETE FROM rate_limits WHERE endpoint = ? AND window_start < DATE_SUB(NOW(), INTERVAL ? SECOND)'
        )->execute([$endpoint, $windowSeconds]);

        // Get existing record
        $stmt = $db->prepare(
            'SELECT id, hits, window_start FROM rate_limits
             WHERE key_hash = ? AND endpoint = ? LIMIT 1'
        );
        $stmt->execute([$keyHash, $endpoint]);
        $row = $stmt->fetch();

        if (!$row) {
            // First hit in this window
            $db->prepare(
                'INSERT INTO rate_limits (key_hash, endpoint, hits) VALUES (?, ?, 1)'
            )->execute([$keyHash, $endpoint]);
            return;
        }

        // Window expired → reset
        if (time() - strtotime($row['window_start']) > $windowSeconds) {
            $db->prepare(
                'UPDATE rate_limits SET hits = 1, window_start = NOW() WHERE id = ?'
            )->execute([$row['id']]);
            return;
        }

        // Within window: increment first, then check
        $db->prepare(
            'UPDATE rate_limits SET hits = hits + 1 WHERE id = ?'
        )->execute([$row['id']]);

        if ((int)$row['hits'] + 1 > $maxHits) {
            self::block($endpoint);
        }
    }

    private static function block(string $endpoint): never
    {
        http_response_code(429);

        // JSON and ad-serve endpoints get a silent/structured response (no redirect)
        $nonRedirectEndpoints = ['payment_deposit', 'wallet_nonce', 'wallet_login', 'ad_serve', 'admin_api'];
        if (in_array($endpoint, $nonRedirectEndpoints, true)) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Too many requests. Please try again later.']);
            exit;
        }

        $_SESSION['flash_error'] = 'Too many requests. Please wait a few minutes.';
        header('Location: /login');
        exit;
    }
}
