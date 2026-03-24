<?php
declare(strict_types=1);

namespace Cron;

use Core\Database;

class FraudCleanupJob
{
    public function run(): string
    {
        $db = Database::getInstance();

        // Abgelaufene Blacklist-Einträge entfernen
        $stmt = $db->prepare("
            DELETE FROM ip_blacklist
            WHERE expires_at IS NOT NULL AND expires_at < NOW()
        ");
        $stmt->execute();
        $removed = $stmt->rowCount();

        // IPs die in letzten 24h > 100 Fraud-Impressions hatten → auto blacklist
        $highFraud = $db->query("
            SELECT ip_hash, COUNT(*) AS cnt
            FROM impressions
            WHERE is_fraud = 1
              AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY ip_hash
            HAVING cnt > 100
        ")->fetchAll();

        $autoBanned = 0;
        foreach ($highFraud as $row) {
            $db->prepare("
                INSERT IGNORE INTO ip_blacklist (ip_hash, reason, auto_banned, expires_at)
                VALUES (?, 'Auto: >100 fraud impressions/24h', 1, DATE_ADD(NOW(), INTERVAL 30 DAY))
            ")->execute([$row['ip_hash']]);
            $autoBanned++;
        }

        // Alte Fraud-Logs (>90 Tage) löschen
        $db->prepare("
            DELETE FROM fraud_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)
        ")->execute();

        return "Removed {$removed} expired blacklist entries, auto-banned {$autoBanned} IPs";
    }
}
