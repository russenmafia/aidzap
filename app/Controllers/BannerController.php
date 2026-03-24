<?php
declare(strict_types=1);

namespace Controllers;

use Core\Auth;
use Core\Database;
use Core\View;

class BannerController
{
    // Erlaubte Bild-MIME-Types
    private const ALLOWED_MIME = ['image/jpeg','image/png','image/gif','image/webp'];
    private const MAX_FILE_SIZE = 512 * 1024; // 512 KB

    // ── Banner Liste für Campaign ─────────────────────────────────────────
    public function index(string $campaignUuid): void
    {
        Auth::require();
        $db = Database::getInstance();

        $campaign = $this->loadCampaign($campaignUuid);
        if (!$campaign) { http_response_code(404); echo 'Campaign not found'; return; }

        $banners = $db->prepare('
            SELECT b.*,
                   COUNT(i.id) AS impressions,
                   COUNT(c.id) AS clicks
            FROM ad_banners b
            LEFT JOIN impressions i ON i.banner_id = b.id
            LEFT JOIN clicks c ON c.banner_id = b.id
            WHERE b.campaign_id = ?
            GROUP BY b.id
            ORDER BY b.created_at DESC
        ');
        $banners->execute([$campaign['id']]);

        View::render('dashboard/banners', [
            'title'    => 'Banners – ' . $campaign['name'],
            'active'   => 'banners',
            'campaign' => $campaign,
            'banners'  => $banners->fetchAll(),
        ], 'dashboard');
    }

    // ── Banner Erstellen – Formular ───────────────────────────────────────
    public function createForm(string $campaignUuid): void
    {
        Auth::require();
        $campaign = $this->loadCampaign($campaignUuid);
        if (!$campaign) { http_response_code(404); return; }

        // Templates aus DB oder Hardcode
        $templates = $this->getTemplates();

        View::render('dashboard/banner-create', [
            'title'      => 'New Banner',
            'active'     => 'banners',
            'campaign'   => $campaign,
            'templates'  => $templates,
            'csrf_token' => \Core\Auth::csrfToken(),
            'errors'     => [],
            'old'        => [],
        ], 'dashboard');
    }

    // ── Banner Erstellen – POST ───────────────────────────────────────────
    public function create(string $campaignUuid): void
    {
        Auth::require();
        \Core\Auth::csrfVerify($_POST['csrf_token'] ?? '');

        $campaign = $this->loadCampaign($campaignUuid);
        if (!$campaign) { http_response_code(404); return; }

        $method  = $_POST['method'] ?? 'html'; // html, template, upload, ai
        $size    = $_POST['size'] ?? '300x250';
        $name    = trim($_POST['name'] ?? '');
        $errors  = [];

        $validSizes = ['728x90','300x250','160x600','320x50','468x60','250x250','300x600'];
        if (!in_array($size, $validSizes, true)) $errors[] = 'Invalid size.';
        if (strlen($name) < 2) $errors[] = 'Name required.';

        $html = '';

        if ($method === 'html') {
            $html = $_POST['html'] ?? '';
            if (strlen(trim($html)) < 10) $errors[] = 'HTML content required.';
            $html = $this->sanitizeHtml($html);

        } elseif ($method === 'template') {
            $tplId = (int)($_POST['template_id'] ?? 0);
            $tpl   = $this->getTemplate($tplId);
            if (!$tpl) { $errors[] = 'Invalid template.'; }
            else {
                // Template-Variablen ersetzen
                $html = $this->applyTemplate($tpl, $_POST);
            }

        } elseif ($method === 'upload') {
            $result = $this->handleUpload($size);
            if (isset($result['error'])) {
                $errors[] = $result['error'];
            } else {
                $html = $result['html'];
            }

        } elseif ($method === 'ai') {
            $prompt = trim($_POST['ai_prompt'] ?? '');
            if (strlen($prompt) < 5) {
                $errors[] = 'AI prompt required.';
            } else {
                // AI-Generierung wird per AJAX aufgerufen, hier nur Fallback
                $html = $_POST['ai_html'] ?? '';
                if (empty($html)) $errors[] = 'AI generation failed. Please try again.';
                $html = $this->sanitizeHtml($html);
            }
        }

        if (!empty($errors)) {
            View::render('dashboard/banner-create', [
                'title'      => 'New Banner',
                'active'     => 'banners',
                'campaign'   => $campaign,
                'templates'  => $this->getTemplates(),
                'csrf_token' => \Core\Auth::csrfToken(),
                'errors'     => $errors,
                'old'        => $_POST,
            ], 'dashboard');
            return;
        }

        // Banner speichern
        $db   = Database::getInstance();
        $uuid = $this->uuid();

        $db->prepare('
            INSERT INTO ad_banners (uuid, campaign_id, user_id, name, size, type, status, html)
            VALUES (?, ?, ?, ?, ?, "banner", "pending_review", ?)
        ')->execute([$uuid, $campaign['id'], Auth::id(), $name, $size, $html]);

        // Campaign von draft auf pending_review setzen
        $db->prepare('
            UPDATE campaigns SET status = "pending_review"
            WHERE id = ? AND status = "draft"
        ')->execute([$campaign['id']]);

        header('Location: /advertiser/campaigns/' . $campaignUuid . '/banners?created=1');
        exit;
    }

    // ── AJAX: AI Banner generieren ────────────────────────────────────────
    public function generateAi(): void
    {
        Auth::require();
        header('Content-Type: application/json');

        $prompt = trim($_POST['prompt'] ?? '');
        $size   = $_POST['size'] ?? '300x250';

        if (strlen($prompt) < 5) {
            echo json_encode(['error' => 'Prompt too short']); return;
        }

        $db = Database::getInstance();

        // AI Banner Einstellungen laden
        $settings = $db->query('
            SELECT ai_banner_enabled, ai_banner_price FROM referral_settings WHERE id = 1 LIMIT 1
        ')->fetch();

        // Feature deaktiviert?
        if (!$settings || !$settings['ai_banner_enabled']) {
            echo json_encode(['error' => 'AI banner generation is currently disabled.']); return;
        }

        $price = (float)($settings['ai_banner_price'] ?? 0);

        // Balance prüfen falls Preis > 0
        if ($price > 0) {
            $stmt = $db->prepare('
                SELECT COALESCE(SUM(amount),0) FROM balances WHERE user_id = ? AND currency = "BTC"
            ');
            $stmt->execute([Auth::id()]);
            $balance = (float)$stmt->fetchColumn();

            if ($balance < $price) {
                echo json_encode([
                    'error'   => 'Insufficient balance. AI generation costs ' . number_format($price, 8) . ' BTC per banner.',
                    'price'   => $price,
                    'balance' => $balance,
                ]); return;
            }

            // Balance abbuchen
            $updated = $db->prepare('
                UPDATE balances SET amount = amount - ?
                WHERE user_id = ? AND currency = "BTC" AND amount >= ?
            ');
            $updated->execute([$price, Auth::id(), $price]);

            if ($updated->rowCount() === 0) {
                echo json_encode(['error' => 'Insufficient balance.']); return;
            }

            // Buchung protokollieren
            $db->prepare('
                INSERT INTO payments (uuid, user_id, type, currency, amount, status, provider, created_at)
                VALUES (?,?,"fee","BTC",?,"completed","ai_banner", NOW())
            ')->execute([bin2hex(random_bytes(16)), Auth::id(), $price]);
        }

        [$w, $h] = explode('x', $size . 'x0');

        // Claude API aufrufen
        $response = $this->callClaudeApi($prompt, (int)$w, (int)$h);

        if (isset($response['error'])) {
            // Bei Fehler: Betrag zurückbuchen
            if ($price > 0) {
                $db->prepare('
                    INSERT INTO balances (user_id, currency, amount)
                    VALUES (?,?,?)
                    ON DUPLICATE KEY UPDATE amount = amount + VALUES(amount)
                ')->execute([Auth::id(), 'BTC', $price]);
            }
            echo json_encode(['error' => $response['error']]); return;
        }

        echo json_encode([
            'html'    => $response['html'],
            'charged' => $price > 0 ? number_format($price, 8) . ' BTC' : null,
        ]);
    }

    // ── Banner löschen ────────────────────────────────────────────────────
    public function delete(string $campaignUuid, string $bannerUuid): void
    {
        Auth::require();
        $db = Database::getInstance();

        $db->prepare('
            DELETE b FROM ad_banners b
            JOIN campaigns c ON c.id = b.campaign_id
            WHERE b.uuid = ? AND c.uuid = ? AND c.user_id = ?
              AND b.status IN ("draft","rejected")
        ')->execute([$bannerUuid, $campaignUuid, Auth::id()]);

        header('Location: /advertiser/campaigns/' . $campaignUuid . '/banners'); exit;
    }

    // ── Helpers ───────────────────────────────────────────────────────────
    private function loadCampaign(string $uuid): ?array
    {
        $stmt = Database::getInstance()->prepare('
            SELECT * FROM campaigns WHERE uuid = ? AND user_id = ? LIMIT 1
        ');
        $stmt->execute([$uuid, Auth::id()]);
        return $stmt->fetch() ?: null;
    }

    private function sanitizeHtml(string $html): string
    {
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
        $html = preg_replace('/\bon\w+\s*=/i', 'data-removed=', $html);
        $html = preg_replace('/javascript\s*:/i', '#', $html);
        $html = preg_replace('/<link\b[^>]*>/i', '', $html);
        $html = preg_replace('/<meta\b[^>]*>/i', '', $html);
        return trim($html);
    }

    private function handleUpload(string $size): array
    {
        if (!isset($_FILES['banner_image']) || $_FILES['banner_image']['error'] !== UPLOAD_ERR_OK) {
            return ['error' => 'Upload failed. Please try again.'];
        }

        $file = $_FILES['banner_image'];

        if ($file['size'] > self::MAX_FILE_SIZE) {
            return ['error' => 'File too large. Max 512 KB.'];
        }

        // MIME-Type prüfen
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, self::ALLOWED_MIME, true)) {
            return ['error' => 'Only JPG, PNG, GIF and WebP allowed.'];
        }

        // Dateiname sichern
        $ext      = match($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
            default      => 'jpg'
        };
        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $destDir  = BASE_PATH . '/public/uploads/banners/';
        $destPath = $destDir . $filename;

        if (!is_dir($destDir)) mkdir($destDir, 0755, true);

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            return ['error' => 'Could not save file.'];
        }

        [$w, $h] = explode('x', $size . 'x0');
        $url = '/uploads/banners/' . $filename;

        $html = '<div style="width:' . (int)$w . 'px;height:' . (int)$h . 'px;overflow:hidden">'
              . '<img src="https://aidzap.com' . $url . '" width="' . (int)$w . '" height="' . (int)$h . '" style="display:block;width:100%;height:100%;object-fit:cover" alt="">'
              . '</div>';

        return ['html' => $html];
    }

    private function callClaudeApi(string $prompt, int $w, int $h): array
    {
        $apiKey = $_ENV['ANTHROPIC_API_KEY'] ?? '';
        if (!$apiKey) return ['error' => 'AI generation not configured.'];

        $systemPrompt = "You are a banner ad designer. Create a pure HTML/CSS banner ad. "
            . "STRICT RULES: No JavaScript, no external resources, no tracking pixels. "
            . "Only inline HTML and CSS. Width: {$w}px, Height: {$h}px. "
            . "Output ONLY the raw HTML code, nothing else. No explanation, no markdown.";

        $body = json_encode([
            'model'      => 'claude-sonnet-4-20250514',
            'max_tokens' => 1000,
            'system'     => $systemPrompt,
            'messages'   => [['role' => 'user', 'content' => $prompt]],
        ]);

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_TIMEOUT        => 30,
        ]);

        $result   = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) return ['error' => 'AI generation failed (HTTP ' . $httpCode . ').'];

        $data = json_decode($result, true);
        $html = $data['content'][0]['text'] ?? '';

        if (empty($html)) return ['error' => 'AI returned empty response.'];

        return ['html' => $this->sanitizeHtml($html)];
    }

    private function getTemplates(): array
    {
        return [
            ['id' => 1, 'name' => 'Clean Dark',     'preview_color' => '#0d1117', 'accent' => '#3ecf8e'],
            ['id' => 2, 'name' => 'Bold Green',      'preview_color' => '#0a2e1a', 'accent' => '#00ff88'],
            ['id' => 3, 'name' => 'Crypto Orange',   'preview_color' => '#1a0f00', 'accent' => '#ff8c00'],
            ['id' => 4, 'name' => 'Minimal White',   'preview_color' => '#ffffff', 'accent' => '#000000'],
            ['id' => 5, 'name' => 'Purple Tech',     'preview_color' => '#0f0a1a', 'accent' => '#7f77dd'],
            ['id' => 6, 'name' => 'Red Alert',       'preview_color' => '#1a0a0a', 'accent' => '#e05454'],
        ];
    }

    private function getTemplate(int $id): ?array
    {
        $templates = $this->getTemplates();
        foreach ($templates as $t) {
            if ($t['id'] === $id) return $t;
        }
        return null;
    }

    private function applyTemplate(array $tpl, array $data): string
    {
        $headline = htmlspecialchars($data['tpl_headline'] ?? 'Your Headline');
        $subline  = htmlspecialchars($data['tpl_subline'] ?? 'Your subline here');
        $cta      = htmlspecialchars($data['tpl_cta'] ?? 'Learn More');
        $bg       = $tpl['preview_color'];
        $accent   = $tpl['accent'];
        $textColor= $bg === '#ffffff' ? '#000000' : '#ffffff';

        $size     = $data['size'] ?? '300x250';
        [$w, $h]  = explode('x', $size . 'x0');

        return '<div style="width:' . (int)$w . 'px;height:' . (int)$h . 'px;background:' . $bg . ';'
             . 'display:flex;flex-direction:column;align-items:center;justify-content:center;'
             . 'padding:20px;box-sizing:border-box;font-family:sans-serif;text-align:center">'
             . '<div style="font-size:' . max(14, min(24, (int)$w/14)) . 'px;font-weight:700;color:' . $textColor . ';margin-bottom:8px">' . $headline . '</div>'
             . '<div style="font-size:' . max(10, min(14, (int)$w/22)) . 'px;color:' . $textColor . ';opacity:.7;margin-bottom:16px">' . $subline . '</div>'
             . '<div style="background:' . $accent . ';color:' . ($bg === '#ffffff' ? '#fff' : $bg) . ';'
             . 'padding:8px 20px;border-radius:4px;font-size:12px;font-weight:600">' . $cta . '</div>'
             . '</div>';
    }

    private function uuid(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),
            mt_rand(0,0x0fff)|0x4000,mt_rand(0,0x3fff)|0x8000,
            mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff));
    }
}
