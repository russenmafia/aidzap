<?php
declare(strict_types=1);

namespace Cron;

use Core\Database;

class BudgetResetJob
{
    public function run(): string
    {
        $db = Database::getInstance();

        // Abgelaufene Campaigns pausieren
        $expired = $db->prepare("
            UPDATE campaigns
            SET status = 'completed'
            WHERE status = 'active'
              AND ends_at IS NOT NULL
              AND ends_at < NOW()
        ");
        $expired->execute();
        $expiredCount = $expired->rowCount();

        // Budget erschöpfte Campaigns pausieren
        $depleted = $db->prepare("
            UPDATE campaigns
            SET status = 'paused'
            WHERE status = 'active'
              AND total_budget > 0
              AND spent >= total_budget
        ");
        $depleted->execute();
        $depletedCount = $depleted->rowCount();

        // Earnings die älter als 30 Tage und noch pending sind → confirmed
        $db->prepare("
            UPDATE earnings
            SET status = 'confirmed'
            WHERE status = 'pending'
              AND date < DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ")->execute();

        // Abgelaufene Nonces aufräumen
        $db->prepare("DELETE FROM auth_nonces WHERE expires_at < NOW()")->execute();

        return "Expired: {$expiredCount}, Depleted: {$depletedCount}";
    }
}
