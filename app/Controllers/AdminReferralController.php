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
                is_active        = ?,
                level1_pct       = ?,
                level2_pct       = ?,
                level3_pct       = ?,
                ai_banner_enabled = ?,
                ai_banner_price   = ?
            WHERE id = 1
        ')->execute([
            isset($_POST['enabled'])          ? 1 : 0,
            min(50, max(0, (float)($_POST['level1_pct']   ?? 5))),
            min(50, max(0, (float)($_POST['level2_pct']   ?? 3))),
            min(50, max(0, (float)($_POST['level3_pct']   ?? 1))),
            isset($_POST['ai_banner_enabled']) ? 1 : 0,
            max(0, (float)($_POST['ai_banner_price'] ?? 0)),
        ]);

        header('Location: /admin/referrals?saved=1'); exit;
    }
}
