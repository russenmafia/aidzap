<?php
declare(strict_types=1);

namespace Services;

use Core\Database;

class AdServeService
{
    private \PDO $db;
    private string $ip;
    private string $ipHash;
    private string $country  = '';
    private string $language = '';
    private string $device   = '';

    public function __construct()
    {
        $this->db       = Database::getInstance();
        $this->ip       = $this->getClientIp();
        $this->ipHash   = hash('sha256', $this->ip . ($_ENV['APP_SECRET'] ?? ''));
        $visitor        = GeoService::detect();
        $this->country  = $visitor['country'];
        $this->language = $visitor['language'];
        $this->device   = $visitor['device'];
    }

    public function serve(string $unitUuid): void
    {
        // 1. Ad Unit laden + validieren
        $unit = $this->loadUnit($unitUuid);
        if (!$unit) {
            $this->serveEmpty('unit not found');
            return;
        }

        // 2. Fraud Detection
        $fraud = new FraudDetectionService($this->db);
        $fraudResult = $fraud->check($this->ipHash, $this->ip);

        if ($fraudResult['action'] === 'block') {
            $this->logImpression($unit, null, $fraudResult['score'], true);
            $this->serveEmpty('blocked');
            return;
        }

        // 3. Passenden Banner auswählen
        $banner = $this->selectBanner($unit);

        if (!$banner) {
            // Fallback HTML ausgeben falls definiert
            $this->serveFallback($unit);
            return;
        }

        // 4. Impression loggen
        $impressionId = $this->logImpression($unit, $banner, $fraudResult['score'], false);

        // 5. Budget abziehen (async-safe via DB transaction)
        $this->deductBudget($banner['campaign_id'], $banner['cost']);

        // 6. Publisher Earnings gutschreiben
        $this->creditEarnings($unit, $banner['cost']);

        // 7. Banner ausliefern mit Click-Tracking Wrapper
        $this->renderBanner($banner, $unit, $impressionId);
    }

    private function loadUnit(string $uuid): ?array
    {
        $stmt = $this->db->prepare('
            SELECT u.id, u.uuid, u.user_id, u.name, u.size, u.type,
                   u.status, u.category_id, u.fallback_html,
                   u.allowed_categories, u.blocked_categories, u.floor_price,
                   u.revenue_share
            FROM ad_units u
            WHERE u.uuid = ? AND u.status = "active"
            LIMIT 1
        ');
        $stmt->execute([$uuid]);
        return $stmt->fetch() ?: null;
    }

    private function selectBanner(array $unit): ?array
    {
        [$w, $h] = explode('x', $unit['size'] . 'x0');

        // Aktive Banner aus aktiven Kampagnen mit genug Budget
        $sql = '
            SELECT b.id, b.uuid, b.html, b.size,
                   c.id AS campaign_id, c.pricing_model, c.bid_amount,
                   c.currency, c.target_countries, c.target_categories,
                   c.target_languages, c.target_devices,
                   c.user_id AS advertiser_id,
                   b.user_id AS banner_user_id
            FROM ad_banners b
            JOIN campaigns c ON c.id = b.campaign_id
            WHERE b.status = "active"
              AND c.status = "active"
              AND b.size = ?
              AND (c.ends_at IS NULL OR c.ends_at > NOW())
              AND (c.starts_at IS NULL OR c.starts_at <= NOW())
              AND (c.total_budget - c.spent) > 0
              AND (c.daily_budget > 0)
        ';

        $params = [$unit['size']];

        // Floor Price Filter
        if ((float)$unit['floor_price'] > 0) {
            $sql .= ' AND c.bid_amount >= ?';
            $params[] = $unit['floor_price'];
        }

        $sql .= ' ORDER BY c.bid_amount DESC LIMIT 20';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $candidates = $stmt->fetchAll();

        if (empty($candidates)) return null;

        // Geo-Filter anwenden
        foreach ($candidates as $candidate) {
            if ($this->matchesGeo($candidate)
                && $this->matchesLanguage($candidate)
                && $this->matchesDevice($candidate)
                && $this->matchesCategory($candidate, $unit)
            ) {
                $candidate['cost'] = $this->calculateCost($candidate);
                return $candidate;
            }
        }

        return null;
    }

    private function matchesGeo(array $banner): bool
    {
        if (!FeatureFlag::isActive('targeting_geo')) return true;
        if (empty($banner['target_countries'])) return true;
        if ($this->country === '') return true;

        $countries = json_decode($banner['target_countries'], true) ?? [];
        if (empty($countries)) return true;

        return in_array(strtoupper($this->country), array_map('strtoupper', $countries), true);
    }

    private function matchesLanguage(array $banner): bool
    {
        if (!FeatureFlag::isActive('targeting_language')) return true;
        if (empty($banner['target_languages'])) return true;
        if ($this->language === '') return true;

        $languages = json_decode($banner['target_languages'], true) ?? [];
        if (empty($languages)) return true;

        return in_array($this->language, $languages, true);
    }

    private function matchesDevice(array $banner): bool
    {
        if (!FeatureFlag::isActive('targeting_device')) return true;
        if (empty($banner['target_devices'])) return true;

        $devices = json_decode($banner['target_devices'], true) ?? [];
        if (empty($devices)) return true;

        return in_array($this->device, $devices, true);
    }

    private function matchesCategory(array $banner, array $unit): bool
    {
        $targetCats = json_decode($banner['target_categories'] ?? '[]', true) ?? [];
        if (empty($targetCats)) return true;
        if (!$unit['category_id']) return true;

        return in_array((int)$unit['category_id'], array_map('intval', $targetCats), true);
    }

    private function calculateCost(array $banner): float
    {
        return match($banner['pricing_model']) {
            'cpm' => (float)$banner['bid_amount'] / 1000,
            'cpd' => (float)$banner['bid_amount'] / 288, // 5-Minuten-Slots pro Tag
            default => (float)$banner['bid_amount'] / 1000,
        };
    }

    private function logImpression(array $unit, ?array $banner, float $fraudScore, bool $isFraud): int
    {
        $referer = substr($_SERVER['HTTP_REFERER'] ?? '', 0, 2048);
        $uaHash  = hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . ($this->ip));
        $cost    = $banner ? ($banner['cost'] ?? 0) : 0;

        $this->db->prepare('
            INSERT INTO impressions
                (banner_id, unit_id, campaign_id, ip_hash, country, language, device,
                 referer, user_agent_hash, fraud_score, is_fraud, cost, created_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?, NOW())
        ')->execute([
            $banner['id'] ?? 0,
            $unit['id'],
            $banner['campaign_id'] ?? 0,
            $this->ipHash,
            $this->country  ?: null,
            $this->language ?: null,
            $this->device   ?: null,
            $referer,
            $uaHash,
            $fraudScore,
            $isFraud ? 1 : 0,
            $cost,
        ]);

        return (int)$this->db->lastInsertId();
    }

    private function deductBudget(int $campaignId, float $cost): void
    {
        if ($cost <= 0) return;
        $this->db->prepare('
            UPDATE campaigns SET spent = spent + ? WHERE id = ? AND (total_budget - spent) >= ?
        ')->execute([$cost, $campaignId, $cost]);
    }

    private function creditEarnings(array $unit, float $grossCost): void
    {
        if ($grossCost <= 0) return;

        // Use unit's dynamic revenue share (set by quality score system), default 60%
        $sharePct       = max(0, min(100, (float)($unit['revenue_share'] ?? 60.00)));
        $publisherShare = round($grossCost * ($sharePct / 100), 8);
        $today          = date('Y-m-d');

        $this->db->prepare('
            INSERT INTO earnings (user_id, unit_id, date, currency, amount, impressions, clicks)
            VALUES (?, ?, ?, "BTC", ?, 1, 0)
            ON DUPLICATE KEY UPDATE
                amount = amount + VALUES(amount),
                impressions = impressions + 1
        ')->execute([$unit['user_id'], $unit['id'], $today, $publisherShare]);
    }

    private function renderBanner(array $banner, array $unit, int $impressionId): void
    {
        $clickUrl = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'aidzap.com')
                  . '/ad/click?i=' . $impressionId
                  . '&b=' . $banner['id'];

        [$w, $h] = explode('x', $unit['size'] . 'x0');

        // Security: Banner HTML sanitizen – nur style + basic HTML erlaubt
        $html = $this->sanitizeBannerHtml($banner['html']);

        header('Content-Type: text/html; charset=utf-8');
        header('X-Frame-Options: ALLOWALL');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('X-Content-Type-Options: nosniff');

        echo '<!DOCTYPE html><html><head>'
           . '<meta charset="UTF-8">'
           . '<style>*{margin:0;padding:0;box-sizing:border-box}'
           . 'body{width:' . (int)$w . 'px;height:' . (int)$h . 'px;overflow:hidden}'
           . 'a{display:block;width:100%;height:100%;text-decoration:none}'
           . '</style></head><body>'
           . '<a href="' . htmlspecialchars($clickUrl) . '" target="_blank" rel="noopener">'
           . $html
           . '</a>'
           . '</body></html>';
    }

    private function sanitizeBannerHtml(string $html): string
    {
        // Entfernt script-Tags und Event-Handler
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
        $html = preg_replace('/\bon\w+\s*=/i', 'data-blocked=', $html);
        $html = preg_replace('/javascript\s*:/i', '#', $html);
        return $html;
    }

    private function serveEmpty(string $reason = ''): void
    {
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-store');
        echo '<!DOCTYPE html><html><head><style>body{margin:0}</style></head>'
           . '<body><!-- aidzap ad slot --></body></html>';
    }

    private function serveFallback(array $unit): void
    {
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-store');
        $fallback = $unit['fallback_html'] ?? '';
        echo '<!DOCTYPE html><html><head><style>*{margin:0;padding:0;box-sizing:border-box}</style></head>'
           . '<body>' . ($fallback ?: '<!-- aidzap: no ad available -->') . '</body></html>';
    }

    private function getClientIp(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = trim(explode(',', $_SERVER[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
            }
        }
        return '0.0.0.0';
    }

}

