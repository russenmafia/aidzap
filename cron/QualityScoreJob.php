<?php
declare(strict_types=1);

namespace Cron;

use Services\QualityScoreService;

class QualityScoreJob
{
    public function run(): string
    {
        $service = new QualityScoreService();

        // 1. Evaluate and update quality levels for all active units
        $results = $service->processAllUnits();

        // 2. Refresh referral multiplier cache for all active users
        $usersUpdated = $service->updateAllReferralMultipliers();

        return sprintf(
            'Quality: +%d upgraded, -%d downgraded, %d pending, %d unchanged | Multipliers: %d users updated',
            $results['upgraded'],
            $results['downgraded'],
            $results['pending'],
            $results['unchanged'],
            $usersUpdated
        );
    }
}
