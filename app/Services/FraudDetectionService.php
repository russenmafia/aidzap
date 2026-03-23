<?php
declare(strict_types=1);

namespace Services;

use PDO;

class FraudDetectionService
{
    private PDO $db;
    private float $threshold;

    // Bekannte Datacenter/Bot IP-Ranges (CIDR)
    private const DATACENTER_RANGES = [
        '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16', // Private
    ];

    public function __construct(PDO $db)
    {
        $this->db        = $db;
        $this->threshold = (float)($_ENV['FRAUD_SCORE_THRESHOLD'] ?? 0.75);
    }

    public function check(string $ipHash, string $ip): array
    {
        $signals = [];
        $score   = 0.0;

        // 1. IP auf Blacklist?
        if ($this->isBlacklisted($ipHash)) {
            return ['score' => 1.0, 'action' => 'block', 'signals' => ['blacklisted' => true]];
        }

        // 2. Frequency Check – mehr als 10 Impressions in 60 Sekunden?
        $recentCount = $this->recentImpressionCount($ipHash, 60);
        if ($recentCount > 10) {
            $signals['high_frequency'] = true;
            $score += 0.4;
        } elseif ($recentCount > 5) {
            $signals['medium_frequency'] = true;
            $score += 0.2;
        }

        // 3. User Agent Check
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (empty($ua)) {
            $signals['no_user_agent'] = true;
            $score += 0.3;
        } elseif ($this->isBotUserAgent($ua)) {
            $signals['bot_user_agent'] = true;
            $score += 0.5;
        }

        // 4. Referer Check
        if (empty($_SERVER['HTTP_REFERER'])) {
            $signals['no_referer'] = true;
            $score += 0.1;
        }

        // 5. Private IP / Datacenter Range
        if ($this->isDatacenterIp($ip)) {
            $signals['datacenter_ip'] = true;
            $score += 0.35;
        }

        // 6. Kein Accept-Language Header
        if (empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $signals['no_accept_language'] = true;
            $score += 0.15;
        }

        $score = min(1.0, round($score, 2));

        // Fraud Log schreiben wenn verdächtig
        if ($score >= 0.3) {
            $this->logFraud($ipHash, $score, $signals);
        }

        // Auto-Blacklist bei hohem Score
        if ($score >= 0.9) {
            $this->autoBlacklist($ipHash, $signals);
        }

        $action = $score >= $this->threshold ? 'block' : ($score >= 0.3 ? 'flag' : 'allow');

        return compact('score', 'action', 'signals');
    }

    private function isBlacklisted(string $ipHash): bool
    {
        $stmt = $this->db->prepare('
            SELECT id FROM ip_blacklist
            WHERE ip_hash = ?
              AND (expires_at IS NULL OR expires_at > NOW())
            LIMIT 1
        ');
        $stmt->execute([$ipHash]);
        return (bool)$stmt->fetch();
    }

    private function recentImpressionCount(string $ipHash, int $seconds): int
    {
        $stmt = $this->db->prepare('
            SELECT COUNT(*) FROM impressions
            WHERE ip_hash = ?
              AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ');
        $stmt->execute([$ipHash, $seconds]);
        return (int)$stmt->fetchColumn();
    }

    private function isBotUserAgent(string $ua): bool
    {
        $botPatterns = [
            'bot', 'crawler', 'spider', 'scraper', 'curl', 'wget',
            'python', 'java/', 'go-http', 'axios', 'libwww', 'httpclient',
            'phantomjs', 'headless', 'selenium',
        ];
        $uaLower = strtolower($ua);
        foreach ($botPatterns as $pattern) {
            if (str_contains($uaLower, $pattern)) return true;
        }
        return false;
    }

    private function isDatacenterIp(string $ip): bool
    {
        // Private Ranges
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return true;
        }
        return false;
    }

    private function logFraud(string $ipHash, float $score, array $signals): void
    {
        try {
            $this->db->prepare('
                INSERT INTO fraud_logs (type, ref_id, ip_hash, score, signals, action, created_at)
                VALUES ("impression", 0, ?, ?, ?, IF(? >= ?, "block", IF(? >= 0.3, "flag", "allow")), NOW())
            ')->execute([
                $ipHash,
                $score,
                json_encode($signals),
                $score, $this->threshold,
                $score,
            ]);
        } catch (\Exception $e) {
            // Silent fail – Fraud-Log darf Auslieferung nicht blockieren
        }
    }

    private function autoBlacklist(string $ipHash, array $signals): void
    {
        try {
            $reason = implode(', ', array_keys(array_filter($signals)));
            $this->db->prepare('
                INSERT IGNORE INTO ip_blacklist (ip_hash, reason, auto_banned, expires_at, created_at)
                VALUES (?, ?, 1, DATE_ADD(NOW(), INTERVAL 7 DAY), NOW())
            ')->execute([$ipHash, $reason]);
        } catch (\Exception $e) {
            // Silent fail
        }
    }
}
