<?php
declare(strict_types=1);

namespace Controllers;

use Core\Auth;
use Core\Database;
use Core\View;

class EarningsController
{
    public function index(): void
    {
        Auth::require();
        $db     = Database::getInstance();
        $userId = Auth::id();

        // Gesamtstatistik
        $totals = $db->prepare('
            SELECT
                COALESCE(SUM(amount), 0)      AS total_earned,
                COALESCE(SUM(impressions), 0) AS total_impressions,
                COALESCE(SUM(clicks), 0)      AS total_clicks,
                COUNT(DISTINCT unit_id)       AS units_count
            FROM earnings WHERE user_id = ?
        ');
        $totals->execute([$userId]);
        $totals = $totals->fetch();

        // Pending (noch nicht ausgezahlt)
        $pending = $db->prepare('
            SELECT COALESCE(SUM(amount), 0) AS pending
            FROM earnings WHERE user_id = ? AND status = "pending"
        ');
        $pending->execute([$userId]);
        $pending = (float)$pending->fetchColumn();

        // Letzte 30 Tage täglich
        $daily = $db->prepare('
            SELECT date,
                   SUM(amount)      AS earned,
                   SUM(impressions) AS impressions,
                   SUM(clicks)      AS clicks
            FROM earnings
            WHERE user_id = ?
              AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY date
            ORDER BY date ASC
        ');
        $daily->execute([$userId]);
        $daily = $daily->fetchAll();

        // Per Unit Übersicht
        $byUnit = $db->prepare('
            SELECT u.id, u.name, u.size, u.type, u.status, u.uuid,
                   COALESCE(SUM(e.amount), 0)      AS earned,
                   COALESCE(SUM(e.impressions), 0) AS impressions,
                   COALESCE(SUM(e.clicks), 0)      AS clicks,
                   MAX(e.date)                      AS last_activity
            FROM ad_units u
            LEFT JOIN earnings e ON e.unit_id = u.id AND e.user_id = ?
            WHERE u.user_id = ?
            GROUP BY u.id
            ORDER BY earned DESC
        ');
        $byUnit->execute([$userId, $userId]);
        $byUnit = $byUnit->fetchAll();

        // Letzte Transaktionen
        $transactions = $db->prepare('
            SELECT e.date, e.amount, e.impressions, e.clicks, e.status,
                   u.name AS unit_name
            FROM earnings e
            JOIN ad_units u ON u.id = e.unit_id
            WHERE e.user_id = ?
            ORDER BY e.date DESC, e.id DESC
            LIMIT 50
        ');
        $transactions->execute([$userId]);
        $transactions = $transactions->fetchAll();

        // Balance
        $balance = $db->prepare('SELECT amount FROM balances WHERE user_id = ? AND currency = "BTC" LIMIT 1');
        $balance->execute([$userId]);
        $balance = (float)($balance->fetchColumn() ?: 0);

        View::render('dashboard/earnings', [
            'title'        => 'Earnings',
            'active'       => 'earnings',
            'totals'       => $totals,
            'pending'      => $pending,
            'balance'      => $balance,
            'daily'        => $daily,
            'byUnit'       => $byUnit,
            'transactions' => $transactions,
        ], 'dashboard');
    }
}
