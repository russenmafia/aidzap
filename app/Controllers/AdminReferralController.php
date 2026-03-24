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

        $db->prepare('
            UPDATE referral_settings SET
                enabled      = ?,
                level1_pct   = ?,
                level2_pct   = ?,
                level3_pct   = ?,
                on_earnings  = ?,
                on_spend     = ?,
                signup_bonus = ?
            WHERE id = 1
        ')->execute([
            isset($_POST['enabled'])     ? 1 : 0,
            min(50, max(0, (float)($_POST['level1_pct']   ?? 5))),
            min(50, max(0, (float)($_POST['level2_pct']   ?? 3))),
            min(50, max(0, (float)($_POST['level3_pct']   ?? 1))),
            isset($_POST['on_earnings']) ? 1 : 0,
            isset($_POST['on_spend'])    ? 1 : 0,
            max(0, (float)($_POST['signup_bonus'] ?? 0)),
        ]);

        header('Location: /admin/referrals?saved=1'); exit;
    }
}
