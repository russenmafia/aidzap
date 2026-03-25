<?php
declare(strict_types=1);

namespace Cron;

use Core\Database;

class RateLimitCleanupJob
{
    public function run(): string
    {
        $db = Database::getInstance();

        $stmt = $db->prepare(
            'DELETE FROM rate_limits WHERE window_start < DATE_SUB(NOW(), INTERVAL 1 HOUR)'
        );
        $stmt->execute();
        $removed = $stmt->rowCount();

        return "Removed {$removed} expired rate-limit record(s).";
    }
}
