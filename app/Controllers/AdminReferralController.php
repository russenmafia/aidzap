<?php
declare(strict_types=1);

namespace Controllers;

use Core\AdminAuth;
use Core\Database;
use Core\View;

class AdminReferralController
{
    public function index(): void
    {
        AdminAuth::require();
        View::render('admin/referrals', [
            'title'  => 'Referral System',
            'active' => 'referrals',
        ], 'admin');
    }

    public function save(): void
    {
        AdminAuth::require();
        $db = Database::getInstance();

        // Validate JSON if social_messages provided
        $socialMessages = $_POST['social_messages'] ?? '[]';
        if (!empty($socialMessages)) {
            json_decode($socialMessages);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $socialMessages = '[]';
            }
        }

        $db->prepare('
            UPDATE referral_settings SET
                is_active        = ?,
                level1_pct       = ?,
                level2_pct       = ?,
                level3_pct       = ?,
                ai_banner_enabled = ?,
                ai_banner_price   = ?,
                impression_interval_min = ?,
                signup_bonus_active = ?,
                signup_bonus_amount = ?,
                on_earnings = ?,
                on_spend = ?,
                social_messages  = ?
            WHERE id = 1
        ')->execute([
            isset($_POST['enabled'])          ? 1 : 0,
            min(50, max(0, (float)($_POST['level1_pct']   ?? 5))),
            min(50, max(0, (float)($_POST['level2_pct']   ?? 3))),
            min(50, max(0, (float)($_POST['level3_pct']   ?? 1))),
            isset($_POST['ai_banner_enabled']) ? 1 : 0,
            max(0, (float)($_POST['ai_banner_price'] ?? 0)),
            min(1440, max(1, (int)($_POST['impression_interval_min'] ?? 60))),
            isset($_POST['signup_bonus_active']) ? 1 : 0,
            max(0, (float)($_POST['signup_bonus'] ?? 0)),
            isset($_POST['on_earnings']) ? 1 : 0,
            isset($_POST['on_spend']) ? 1 : 0,
            $socialMessages,
        ]);

        header('Location: /admin/referrals?saved=1'); exit;
    }
}
