<?php
declare(strict_types=1);

namespace Services;

use Core\Database;

class ReferralService
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ── Einstellungen laden ───────────────────────────────────────────────
    public function getSettings(): array
    {
        return $this->db->query('SELECT * FROM referral_settings WHERE id = 1 LIMIT 1')->fetch()
            ?: ['level1_pct'=>5,'level2_pct'=>3,'level3_pct'=>1,'signup_bonus'=>0,'on_earnings'=>1,'on_spend'=>1,'enabled'=>1];
    }

    // ── Ref-Code für User holen/generieren ────────────────────────────────
    public function getRefCode(int $userId): string
    {
        $stmt = $this->db->prepare('SELECT ref_code FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $code = $stmt->fetchColumn();

        if (!$code) {
            $code = strtoupper(substr(md5($userId . time()), 0, 8));
            $this->db->prepare('UPDATE users SET ref_code = ? WHERE id = ?')->execute([$code, $userId]);
        }
        return $code;
    }

    // ── Referral bei Registrierung verarbeiten ────────────────────────────
    public function processSignup(int $newUserId, string $refCode): void
    {
        $settings = $this->getSettings();
        if (!$settings['enabled']) return;

        // Referrer finden
        $stmt = $this->db->prepare('SELECT id FROM users WHERE ref_code = ? LIMIT 1');
        $stmt->execute([$refCode]);
        $referrer = $stmt->fetchColumn();
        if (!$referrer) return;

        $referrerId = (int)$referrer;

        // Level 1 eintragen
        $this->db->prepare('
            INSERT IGNORE INTO referrals (user_id, referred_by, level, ref_code)
            VALUES (?,?,1,?)
        ')->execute([$referrerId, $newUserId, $refCode]);

        // Level 2: Wer hat den Referrer geworben?
        $stmt = $this->db->prepare('SELECT user_id FROM referrals WHERE referred_by = ? LIMIT 1');
        $stmt->execute([$referrerId]);
        $level2 = $stmt->fetchColumn();
        if ($level2) {
            $this->db->prepare('
                INSERT IGNORE INTO referrals (user_id, referred_by, level, ref_code)
                VALUES (?,?,2,?)
            ')->execute([(int)$level2, $newUserId, $refCode]);

            // Level 3
            $stmt->execute([(int)$level2]);
            $level3 = $stmt->fetchColumn();
            if ($level3) {
                $this->db->prepare('
                    INSERT IGNORE INTO referrals (user_id, referred_by, level, ref_code)
                    VALUES (?,?,3,?)
                ')->execute([(int)$level3, $newUserId, $refCode]);
            }
        }

        // referred_by setzen
        $this->db->prepare('UPDATE users SET referred_by = ? WHERE id = ?')->execute([$referrerId, $newUserId]);

        // Signup-Bonus
        if ((float)$settings['signup_bonus'] > 0) {
            $this->creditCommission($referrerId, $newUserId, 1, 'signup',
                (float)$settings['signup_bonus'], 100, (float)$settings['signup_bonus']);
        }
    }

    // ── Provision bei Publisher Earnings gutschreiben ─────────────────────
    public function processEarnings(int $publisherId, float $amount): void
    {
        $settings = $this->getSettings();
        if (!$settings['enabled'] || !$settings['on_earnings'] || $amount <= 0) return;

        $pcts = [1 => $settings['level1_pct'], 2 => $settings['level2_pct'], 3 => $settings['level3_pct']];

        $stmt = $this->db->prepare('SELECT user_id, level FROM referrals WHERE referred_by = ?');
        $stmt->execute([$publisherId]);

        foreach ($stmt->fetchAll() as $row) {
            $pct        = (float)$pcts[$row['level']];
            $commission = round($amount * $pct / 100, 8);
            if ($commission <= 0) continue;

            $this->creditCommission((int)$row['user_id'], $publisherId, $row['level'],
                'earnings', $amount, $pct, $commission);
        }
    }

    // ── Provision bei Advertiser Spend gutschreiben ───────────────────────
    public function processSpend(int $advertiserId, float $amount): void
    {
        $settings = $this->getSettings();
        if (!$settings['enabled'] || !$settings['on_spend'] || $amount <= 0) return;

        $pcts = [1 => $settings['level1_pct'], 2 => $settings['level2_pct'], 3 => $settings['level3_pct']];

        $stmt = $this->db->prepare('SELECT user_id, level FROM referrals WHERE referred_by = ?');
        $stmt->execute([$advertiserId]);

        foreach ($stmt->fetchAll() as $row) {
            $pct        = (float)$pcts[$row['level']];
            $commission = round($amount * $pct / 100, 8);
            if ($commission <= 0) continue;

            $this->creditCommission((int)$row['user_id'], $advertiserId, $row['level'],
                'spend', $amount, $pct, $commission);
        }
    }

    // ── Provision gutschreiben ────────────────────────────────────────────
    private function creditCommission(int $userId, int $fromUserId, int $level,
                                      string $type, float $base, float $pct, float $commission): void
    {
        // referral_earnings Eintrag
        $this->db->prepare('
            INSERT INTO referral_earnings
                (user_id, from_user_id, level, type, base_amount, pct, commission, currency)
            VALUES (?,?,?,?,?,?,?,"BTC")
        ')->execute([$userId, $fromUserId, $level, $type, $base, $pct, $commission]);

        // Balance gutschreiben
        $this->db->prepare('
            INSERT INTO balances (user_id, currency, amount)
            VALUES (?, "BTC", ?)
            ON DUPLICATE KEY UPDATE amount = amount + VALUES(amount)
        ')->execute([$userId, $commission]);
    }

    // ── Statistiken für Dashboard ─────────────────────────────────────────
    public function getStats(int $userId): array
    {
        // Direktgeworbene
        $stmt = $this->db->prepare('
            SELECT COUNT(*) AS total,
                   SUM(CASE WHEN level=1 THEN 1 ELSE 0 END) AS level1,
                   SUM(CASE WHEN level=2 THEN 1 ELSE 0 END) AS level2,
                   SUM(CASE WHEN level=3 THEN 1 ELSE 0 END) AS level3
            FROM referrals WHERE user_id = ?
        ');
        $stmt->execute([$userId]);
        $counts = $stmt->fetch();

        // Gesamtprovision
        $stmt = $this->db->prepare('
            SELECT COALESCE(SUM(commission),0) AS total,
                   COALESCE(SUM(CASE WHEN type="earnings" THEN commission ELSE 0 END),0) AS from_earnings,
                   COALESCE(SUM(CASE WHEN type="spend"    THEN commission ELSE 0 END),0) AS from_spend,
                   COALESCE(SUM(CASE WHEN type="signup"   THEN commission ELSE 0 END),0) AS from_signup
            FROM referral_earnings WHERE user_id = ?
        ');
        $stmt->execute([$userId]);
        $earnings = $stmt->fetch();

        // Letzte Referrals
        $stmt = $this->db->prepare('
            SELECT r.*, u.username, u.created_at AS user_joined
            FROM referrals r
            JOIN users u ON u.id = r.referred_by
            WHERE r.user_id = ?
            ORDER BY r.created_at DESC
            LIMIT 20
        ');
        $stmt->execute([$userId]);
        $referrals = $stmt->fetchAll();

        return compact('counts', 'earnings', 'referrals');
    }
}
