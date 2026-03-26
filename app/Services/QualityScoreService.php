<?php
declare(strict_types=1);

namespace Services;

use Core\Database;

class QualityScoreService
{
    private \PDO $db;
    private array $settings;

    public function __construct()
    {
        $this->db       = Database::getInstance();
        $this->settings = $this->loadSettings();
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    // ── Settings ──────────────────────────────────────────────────────────────

    private function loadSettings(): array
    {
        $row = $this->db->query('SELECT * FROM quality_settings WHERE id = 1 LIMIT 1')->fetch();
        return $row ?: $this->defaultSettings();
    }

    private function defaultSettings(): array
    {
        return [
            'bronze_max_ctr'       => 0.0010,
            'silver_max_ctr'       => 0.0030,
            'gold_max_ctr'         => 0.0080,
            'bronze_share'         => 60.00,
            'silver_share'         => 70.00,
            'gold_share'           => 80.00,
            'platinum_share'       => 85.00,
            'ref_multiplier_0'     => 0.00,
            'ref_multiplier_1'     => 0.50,
            'ref_multiplier_2'     => 1.00,
            'ref_multiplier_3plus' => 1.50,
            'min_own_level'        => 'silver',
            'concentration_cap_pct'=> 50,
            'cooling_period_days'  => 14,
            'activity_window_days' => 30,
            'max_fraud_score'      => 0.750,
        ];
    }

    public function saveSettings(array $data): void
    {
        $this->db->prepare('
            UPDATE quality_settings SET
                bronze_max_ctr        = ?,
                silver_max_ctr        = ?,
                gold_max_ctr          = ?,
                bronze_share          = ?,
                silver_share          = ?,
                gold_share            = ?,
                platinum_share        = ?,
                ref_multiplier_0      = ?,
                ref_multiplier_1      = ?,
                ref_multiplier_2      = ?,
                ref_multiplier_3plus  = ?,
                min_own_level         = ?,
                concentration_cap_pct = ?,
                cooling_period_days   = ?,
                activity_window_days  = ?,
                max_fraud_score       = ?
            WHERE id = 1
        ')->execute([
            $data['bronze_max_ctr'],
            $data['silver_max_ctr'],
            $data['gold_max_ctr'],
            $data['bronze_share'],
            $data['silver_share'],
            $data['gold_share'],
            $data['platinum_share'],
            $data['ref_multiplier_0'],
            $data['ref_multiplier_1'],
            $data['ref_multiplier_2'],
            $data['ref_multiplier_3plus'],
            $data['min_own_level'],
            $data['concentration_cap_pct'],
            $data['cooling_period_days'],
            $data['activity_window_days'],
            $data['max_fraud_score'],
        ]);
        $this->settings = $this->loadSettings();
    }

    // ── Unit Quality Processing ───────────────────────────────────────────────

    /**
     * Process all active ad units. Called by cron daily.
     * Returns counts: upgraded / downgraded / pending / unchanged
     */
    public function processAllUnits(): array
    {
        $results = ['upgraded' => 0, 'downgraded' => 0, 'pending' => 0, 'unchanged' => 0];

        $stmt = $this->db->query('
            SELECT au.*,
                   COALESCE(agg.clicks_30d,      0) AS clicks_30d,
                   COALESCE(agg.impressions_30d,  0) AS impressions_30d,
                   COALESCE(agg.avg_fraud_30d,    0) AS avg_fraud_30d
            FROM ad_units au
            LEFT JOIN (
                SELECT unit_id,
                       COUNT(*)         AS impressions_30d,
                       AVG(fraud_score) AS avg_fraud_30d
                FROM impressions
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY unit_id
            ) agg ON agg.unit_id = au.id
            LEFT JOIN (
                SELECT unit_id, COUNT(*) AS clicks_30d
                FROM clicks
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                  AND is_fraud = 0
                GROUP BY unit_id
            ) ca ON ca.unit_id = au.id
            WHERE au.status = "active"
        ');

        foreach ($stmt->fetchAll() as $unit) {
            $result = $this->processUnit($unit);
            $results[$result]++;
        }

        return $results;
    }

    public function processUnit(array $unit): string
    {
        $s            = $this->settings;
        $impressions  = (int)$unit['impressions_30d'];
        $clicks       = (int)$unit['clicks_30d'];
        $fraudScore   = (float)($unit['avg_fraud_30d'] ?? 0);
        $currentLevel = $unit['quality_level'] ?? 'bronze';

        $ctr = $impressions > 0 ? $clicks / $impressions : 0.0;

        // Set first_active_at on first detected traffic
        if (!$unit['first_active_at'] && $impressions > 0) {
            $this->db->prepare('UPDATE ad_units SET first_active_at = NOW() WHERE id = ?')
                     ->execute([$unit['id']]);
            return 'unchanged';
        }

        $daysSinceActive = $unit['first_active_at']
            ? (time() - strtotime($unit['first_active_at'])) / 86400
            : 0.0;

        $meetsActivityWindow = $daysSinceActive >= (int)$s['activity_window_days'];

        $targetLevel = $this->calculateLevel($ctr, $fraudScore, $meetsActivityWindow, $s);
        $targetShare = $this->getShare($targetLevel, $s);

        if ($this->levelRank($targetLevel) > $this->levelRank($currentLevel)) {
            $this->applyLevelChange($unit, $targetLevel, $targetShare, $ctr, $fraudScore, 'upgrade');
            $this->db->prepare('UPDATE ad_units SET quality_downgrade_pending_since = NULL WHERE id = ?')
                     ->execute([$unit['id']]);
            return 'upgraded';
        }

        if ($this->levelRank($targetLevel) < $this->levelRank($currentLevel)) {
            $pendingSince = $unit['quality_downgrade_pending_since'];

            if (!$pendingSince) {
                $this->db->prepare('UPDATE ad_units SET quality_downgrade_pending_since = NOW() WHERE id = ?')
                         ->execute([$unit['id']]);
                return 'pending';
            }

            $daysPending = (time() - strtotime($pendingSince)) / 86400;

            if ($daysPending >= (int)$s['cooling_period_days']) {
                $this->applyLevelChange($unit, $targetLevel, $targetShare, $ctr, $fraudScore, 'downgrade_after_cooling');
                $this->db->prepare('UPDATE ad_units SET quality_downgrade_pending_since = NULL WHERE id = ?')
                         ->execute([$unit['id']]);
                return 'downgraded';
            }

            return 'pending';
        }

        // Same level; clear any stale pending-downgrade flag
        if ($unit['quality_downgrade_pending_since']) {
            $this->db->prepare('UPDATE ad_units SET quality_downgrade_pending_since = NULL WHERE id = ?')
                     ->execute([$unit['id']]);
        }

        return 'unchanged';
    }

    private function calculateLevel(float $ctr, float $fraudScore, bool $meetsWindow, array $s): string
    {
        if ($fraudScore > (float)$s['max_fraud_score']) return 'bronze';
        if (!$meetsWindow)                              return 'bronze';

        if ($ctr >= (float)$s['gold_max_ctr'])   return 'platinum';
        if ($ctr >= (float)$s['silver_max_ctr']) return 'gold';
        if ($ctr >= (float)$s['bronze_max_ctr']) return 'silver';
        return 'bronze';
    }

    public function getShare(string $level, ?array $s = null): float
    {
        $s ??= $this->settings;
        return match($level) {
            'platinum' => (float)$s['platinum_share'],
            'gold'     => (float)$s['gold_share'],
            'silver'   => (float)$s['silver_share'],
            default    => (float)$s['bronze_share'],
        };
    }

    public function levelRank(string $level): int
    {
        return match($level) {
            'platinum' => 4,
            'gold'     => 3,
            'silver'   => 2,
            default    => 1,
        };
    }

    private function applyLevelChange(array $unit, string $newLevel, float $newShare,
                                       float $ctr, float $fraudScore, string $reason): void
    {
        $this->db->prepare('
            UPDATE ad_units
            SET quality_level = ?, revenue_share = ?, quality_updated_at = NOW()
            WHERE id = ?
        ')->execute([$newLevel, $newShare, $unit['id']]);

        $this->db->prepare('
            INSERT INTO quality_history
                (unit_id, old_level, new_level, old_share, new_share, ctr_30d, fraud_score, reason)
            VALUES (?,?,?,?,?,?,?,?)
        ')->execute([
            $unit['id'],
            $unit['quality_level'] ?? 'bronze',
            $newLevel,
            $unit['revenue_share'] ?? 60.00,
            $newShare,
            round($ctr, 6),
            round($fraudScore, 3),
            $reason,
        ]);
    }

    // ── Referral Multiplier ───────────────────────────────────────────────────

    /**
     * Calculate the referral multiplier for a user based on their active quality refs.
     */
    public function calculateReferralMultiplier(int $userId): array
    {
        $s        = $this->settings;
        $minLevel = $s['min_own_level'] ?? 'silver';

        // Referrer must have at least one unit at or above min_own_level
        $ownLevel = $this->getUserBestLevel($userId);
        if ($this->levelRank($ownLevel) < $this->levelRank($minLevel)) {
            return [
                'multiplier'     => 0.0,
                'active_refs'    => 0,
                'reason'         => 'own_level_too_low',
                'own_level'      => $ownLevel,
                'required_level' => $minLevel,
            ];
        }

        // Count direct referrals with at least Silver quality and past the activity window
        $stmt = $this->db->prepare('
            SELECT u.id
            FROM users u
            JOIN ad_units au ON au.user_id = u.id
            WHERE u.referred_by = ?
              AND au.quality_level IN ("silver","gold","platinum")
              AND au.first_active_at IS NOT NULL
              AND au.first_active_at <= DATE_SUB(NOW(), INTERVAL ? DAY)
              AND au.status = "active"
            GROUP BY u.id
            HAVING COUNT(au.id) > 0
        ');
        $stmt->execute([$userId, (int)$s['activity_window_days']]);
        $activeCount = (int)$stmt->rowCount();

        $multiplier = match(true) {
            $activeCount >= 3 => (float)$s['ref_multiplier_3plus'],
            $activeCount === 2 => (float)$s['ref_multiplier_2'],
            $activeCount === 1 => (float)$s['ref_multiplier_1'],
            default            => (float)$s['ref_multiplier_0'],
        };

        $concentrationWarnings = $this->checkConcentrationCap($userId, $s);

        return [
            'multiplier'             => $multiplier,
            'active_refs'            => $activeCount,
            'own_level'              => $ownLevel,
            'concentration_warnings' => $concentrationWarnings,
            'reason'                 => 'ok',
        ];
    }

    private function getUserBestLevel(int $userId): string
    {
        $stmt = $this->db->prepare('
            SELECT quality_level
            FROM ad_units
            WHERE user_id = ? AND status = "active"
            ORDER BY FIELD(quality_level, "platinum", "gold", "silver", "bronze")
            LIMIT 1
        ');
        $stmt->execute([$userId]);
        return (string)($stmt->fetchColumn() ?: 'bronze');
    }

    private function checkConcentrationCap(int $userId, array $s): array
    {
        $cap = (int)$s['concentration_cap_pct'];

        // Sum referral commissions earned by this user from each contributing user
        $stmt = $this->db->prepare('
            SELECT re.from_user_id AS referred_user_id,
                   u.username,
                   SUM(re.commission) AS earnings_30d
            FROM referral_earnings re
            JOIN users u ON u.id = re.from_user_id
            WHERE re.user_id = ?
              AND re.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY re.from_user_id
        ');
        $stmt->execute([$userId]);
        $earnings = $stmt->fetchAll();

        if (empty($earnings)) return [];

        $total = array_sum(array_column($earnings, 'earnings_30d'));
        if ($total <= 0) return [];

        $warnings = [];
        foreach ($earnings as $e) {
            $pct = round(($e['earnings_30d'] / $total) * 100);
            if ($pct > $cap) {
                $warnings[] = [
                    'username' => $e['username'],
                    'pct'      => $pct,
                    'cap'      => $cap,
                ];
            }
        }

        return $warnings;
    }

    /**
     * Recalculate and cache ref_multiplier for all active users. Called by cron.
     */
    public function updateAllReferralMultipliers(): int
    {
        $users = $this->db->query('SELECT id FROM users WHERE status = "active"')->fetchAll();
        $count = 0;

        foreach ($users as $user) {
            $result = $this->calculateReferralMultiplier((int)$user['id']);
            $this->db->prepare('
                UPDATE users
                SET ref_active_count = ?,
                    ref_multiplier = ?,
                    ref_multiplier_updated_at = NOW()
                WHERE id = ?
            ')->execute([
                $result['active_refs'],
                $result['multiplier'],
                $user['id'],
            ]);
            $count++;
        }

        return $count;
    }
}
