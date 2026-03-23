<?php
declare(strict_types=1);

namespace Controllers;

use Core\Auth;
use Core\Database;
use Core\View;

class DashboardController
{
    public function index(): void
    {
        Auth::require();
        $db     = Database::getInstance();
        $userId = Auth::id();

        $user = $db->prepare('SELECT username, role FROM users WHERE id = ? LIMIT 1');
        $user->execute([$userId]);
        $user = $user->fetch();

        // Publisher stats
        $pubStats = $db->prepare('
            SELECT
                COALESCE(SUM(impressions), 0) AS total_impressions,
                COALESCE(SUM(clicks), 0)      AS total_clicks,
                COALESCE(SUM(amount), 0)      AS total_earned
            FROM earnings
            WHERE user_id = ?
        ');
        $pubStats->execute([$userId]);
        $pubStats = $pubStats->fetch();

        // Publisher ad units
        $units = $db->prepare('
            SELECT u.id, u.uuid, u.name, u.size, u.status, u.website_url,
                   COALESCE(SUM(e.impressions), 0) AS impressions,
                   COALESCE(SUM(e.clicks), 0)      AS clicks,
                   COALESCE(SUM(e.amount), 0)      AS earned
            FROM ad_units u
            LEFT JOIN earnings e ON e.unit_id = u.id
            WHERE u.user_id = ?
            GROUP BY u.id
            ORDER BY u.created_at DESC
        ');
        $units->execute([$userId]);
        $units = $units->fetchAll();

        // Advertiser stats
        $advStats = $db->prepare('
            SELECT
                COUNT(*) AS total_campaigns,
                SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) AS active_campaigns,
                COALESCE(SUM(spent), 0) AS total_spent
            FROM campaigns
            WHERE user_id = ?
        ');
        $advStats->execute([$userId]);
        $advStats = $advStats->fetch();

        // Advertiser campaigns
        $campaigns = $db->prepare('
            SELECT id, uuid, name, status, pricing_model, spent, currency,
                   daily_budget, total_budget, created_at
            FROM campaigns
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT 10
        ');
        $campaigns->execute([$userId]);
        $campaigns = $campaigns->fetchAll();

        // Balance
        $balance = $db->prepare('
            SELECT currency, amount FROM balances WHERE user_id = ? ORDER BY amount DESC
        ');
        $balance->execute([$userId]);
        $balances = $balance->fetchAll();

        View::render('dashboard/index', [
            'title'      => 'Dashboard',
            'user'       => $user,
            'pubStats'   => $pubStats,
            'advStats'   => $advStats,
            'units'      => $units,
            'campaigns'  => $campaigns,
            'balances'   => $balances,
        ], 'dashboard');
    }
}
