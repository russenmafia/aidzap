<?php
declare(strict_types=1);

namespace Cron;

use Core\Database;

class DailyStatsJob
{
    public function run(): string
    {
        $db      = Database::getInstance();
        $date    = date('Y-m-d', strtotime('-1 day')); // Gestern
        $count   = 0;

        // Impressions nach Campaign + Unit + Country aggregieren
        $rows = $db->prepare('
            SELECT
                campaign_id,
                unit_id,
                banner_id,
                country,
                DATE(created_at)            AS day,
                COUNT(*)                    AS impressions,
                SUM(is_fraud)               AS fraud_impressions,
                COALESCE(SUM(cost), 0)      AS spend
            FROM impressions
            WHERE DATE(created_at) = ?
            GROUP BY campaign_id, unit_id, banner_id, country, DATE(created_at)
        ');
        $rows->execute([$date]);

        // Clicks für den gleichen Tag
        $clickData = $db->prepare('
            SELECT campaign_id, unit_id, banner_id, country,
                   COUNT(*) AS clicks, SUM(is_fraud) AS fraud_clicks
            FROM clicks
            WHERE DATE(created_at) = ?
            GROUP BY campaign_id, unit_id, banner_id, country
        ');
        $clickData->execute([$date]);

        $clicks = [];
        foreach ($clickData->fetchAll() as $c) {
            $key = $c['campaign_id'] . '_' . $c['unit_id'] . '_' . $c['banner_id'] . '_' . $c['country'];
            $clicks[$key] = $c;
        }

        foreach ($rows->fetchAll() as $row) {
            $key       = $row['campaign_id'] . '_' . $row['unit_id'] . '_' . $row['banner_id'] . '_' . $row['country'];
            $clickRow  = $clicks[$key] ?? ['clicks' => 0, 'fraud_clicks' => 0];
            $ctr       = $row['impressions'] > 0 ? $clickRow['clicks'] / $row['impressions'] : 0;
            $ecpm      = $row['impressions'] > 0 ? ($row['spend'] / $row['impressions']) * 1000 : 0;

            // Publisher Earnings für diesen Tag
            $earnRow = $db->prepare('
                SELECT COALESCE(SUM(amount),0) AS earnings
                FROM earnings
                WHERE unit_id = ? AND date = ?
            ');
            $earnRow->execute([$row['unit_id'], $date]);
            $earnings = (float)$earnRow->fetchColumn();

            $db->prepare('
                INSERT INTO daily_stats
                    (date, campaign_id, unit_id, banner_id, country,
                     impressions, clicks, fraud_impressions, fraud_clicks,
                     spend, earnings, ctr, ecpm)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
                ON DUPLICATE KEY UPDATE
                    impressions      = VALUES(impressions),
                    clicks           = VALUES(clicks),
                    fraud_impressions= VALUES(fraud_impressions),
                    fraud_clicks     = VALUES(fraud_clicks),
                    spend            = VALUES(spend),
                    earnings         = VALUES(earnings),
                    ctr              = VALUES(ctr),
                    ecpm             = VALUES(ecpm)
            ')->execute([
                $date,
                $row['campaign_id'] ?: null,
                $row['unit_id']     ?: null,
                $row['banner_id']   ?: null,
                $row['country'],
                $row['impressions'],
                $clickRow['clicks'],
                $row['fraud_impressions'],
                $clickRow['fraud_clicks'],
                $row['spend'],
                $earnings,
                round($ctr, 4),
                round($ecpm, 8),
            ]);

            $count++;
        }

        return "Aggregated {$count} rows for {$date}";
    }
}
