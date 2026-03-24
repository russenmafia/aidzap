<?php
declare(strict_types=1);

namespace Controllers;

use Core\Auth;
use Core\Database;
use Core\View;

class BannersOverviewController
{
    public function index(): void
    {
        Auth::require();
        $db     = Database::getInstance();
        $userId = Auth::id();

        $campaigns = $db->prepare('
            SELECT c.id, c.uuid, c.name, c.status, c.pricing_model, c.currency,
                   COUNT(b.id) AS banner_count
            FROM campaigns c
            LEFT JOIN ad_banners b ON b.campaign_id = c.id
            WHERE c.user_id = ?
            GROUP BY c.id
            ORDER BY c.created_at DESC
        ');
        $campaigns->execute([$userId]);
        $campaigns = $campaigns->fetchAll();

        // Für jede Campaign: Banners laden
        foreach ($campaigns as &$c) {
            $banners = $db->prepare('
                SELECT id, uuid, name, size, status, html
                FROM ad_banners
                WHERE campaign_id = ?
                ORDER BY created_at DESC
                LIMIT 5
            ');
            $banners->execute([$c['id']]);
            $c['banners'] = $banners->fetchAll();
        }

        View::render('dashboard/banners-overview', [
            'title'     => 'Banners',
            'active'    => 'banners',
            'campaigns' => $campaigns,
        ], 'dashboard');
    }
}
