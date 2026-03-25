<?php
declare(strict_types=1);

namespace Controllers;

use Core\Auth;
use Core\Database;
use Core\View;

class CampaignController
{
    // ── Liste ─────────────────────────────────────────────────────────────
    public function index(): void
    {
        Auth::require();
        $db   = Database::getInstance();
        $stmt = $db->prepare('
            SELECT c.*,
                   COUNT(b.id) AS banner_count,
                   COUNT(CASE WHEN b.status="active" THEN 1 END) AS active_banners
            FROM campaigns c
            LEFT JOIN ad_banners b ON b.campaign_id = c.id
            WHERE c.user_id = ?
            GROUP BY c.id
            ORDER BY c.created_at DESC
        ');
        $stmt->execute([Auth::id()]);

        // Balance holen
        $balStmt = $db->prepare('SELECT COALESCE(SUM(amount),0) FROM balances WHERE user_id = ? AND currency = "BTC"');
        $balStmt->execute([Auth::id()]);
        $balance = (float)$balStmt->fetchColumn();

        View::render('dashboard/campaigns', [
            'title'     => 'Campaigns',
            'active'    => 'campaigns',
            'campaigns' => $stmt->fetchAll(),
            'balance'   => $balance,
        ], 'dashboard');
    }

    // ── Formular ──────────────────────────────────────────────────────────
    public function createForm(): void
    {
        Auth::require();
        $db = Database::getInstance();

        // Balance prüfen
        $balStmt = $db->prepare('SELECT COALESCE(SUM(amount),0) FROM balances WHERE user_id = ? AND currency = "BTC"');
        $balStmt->execute([Auth::id()]);
        $balance = (float)$balStmt->fetchColumn();

        $categories = $db->query('SELECT * FROM ad_categories ORDER BY name')->fetchAll();

        View::render('dashboard/campaign-create', [
            'title'      => 'New Campaign',
            'active'     => 'campaigns',
            'categories' => $categories,
            'balance'    => $balance,
            'csrf_token' => Auth::csrfToken(),
            'errors'     => [],
            'old'        => [],
        ], 'dashboard');
    }

    // ── Speichern ─────────────────────────────────────────────────────────
    public function create(): void
    {
        Auth::require();
        Auth::csrfVerify($_POST['csrf_token'] ?? '');

        $db      = Database::getInstance();
        $userId  = Auth::id();
        $errors  = [];

        // Felder
        $name         = trim($_POST['name'] ?? '');
        $targetUrl    = trim($_POST['target_url'] ?? '');
        $pricingModel = $_POST['pricing_model'] ?? 'cpm';
        $bidAmount    = (float)($_POST['bid_amount'] ?? 0);
        $dailyBudget  = (float)($_POST['daily_budget'] ?? 0);
        $totalBudget  = (float)($_POST['total_budget'] ?? 0);
        $currency     = 'BTC';
        $startsAt     = $_POST['starts_at'] ?? null;
        $endsAt       = $_POST['ends_at'] ?? null;

        // Validierung
        if (strlen($name) < 2)                    $errors[] = 'Campaign name required.';
        if (!filter_var($targetUrl, FILTER_VALIDATE_URL)) $errors[] = 'Valid target URL required.';
        if (!in_array($pricingModel, ['cpm','cpd','cpa'], true)) $errors[] = 'Invalid pricing model.';
        if ($bidAmount <= 0)                       $errors[] = 'Bid amount must be greater than 0.';
        if ($dailyBudget <= 0)                     $errors[] = 'Daily budget required.';

        // Guthaben prüfen: mind. 1 Tag Budget
        if (empty($errors)) {
            $balStmt = $db->prepare('SELECT COALESCE(SUM(amount),0) FROM balances WHERE user_id = ? AND currency = "BTC"');
            $balStmt->execute([$userId]);
            $balance = (float)$balStmt->fetchColumn();

            if ($balance < $dailyBudget) {
                // Zur Billing-Seite weiterleiten mit Hinweis
                header('Location: /advertiser/billing?insufficient=1&needed=' . urlencode(number_format($dailyBudget, 8)));
                exit;
            }
        }

        if (!empty($errors)) {
            $categories = $db->query('SELECT * FROM ad_categories ORDER BY name')->fetchAll();
            $balStmt->execute([$userId]);
            View::render('dashboard/campaign-create', [
                'title'      => 'New Campaign',
                'active'     => 'campaigns',
                'categories' => $categories,
                'balance'    => (float)$balStmt->fetchColumn(),
                'csrf_token' => Auth::csrfToken(),
                'errors'     => $errors,
                'old'        => $_POST,
            ], 'dashboard');
            return;
        }

        $uuid = $this->uuid();
        $db->prepare('
            INSERT INTO campaigns
                (uuid, user_id, name, status, pricing_model, bid_amount,
                 daily_budget, total_budget, spent, currency,
                 target_url, starts_at, ends_at, created_at)
            VALUES (?,?,?,?,?,?,?,?,0,?,?,?,?, NOW())
        ')->execute([
            $uuid, $userId, $name, 'draft', $pricingModel, $bidAmount,
            $dailyBudget, $totalBudget ?: null, $currency, $targetUrl,
            $startsAt ?: null, $endsAt ?: null,
        ]);

        header('Location: /advertiser/campaigns/' . $uuid . '/banners/create?new=1'); exit;
    }

    private function uuid(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),
            mt_rand(0,0x0fff)|0x4000,mt_rand(0,0x3fff)|0x8000,
            mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff));
    }

    // ── Edit Formular ─────────────────────────────────────────────────────
    public function editForm(string $uuid): void
    {
        Auth::require();
        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM campaigns WHERE uuid = ? AND user_id = ? LIMIT 1');
        $stmt->execute([$uuid, Auth::id()]);
        $campaign = $stmt->fetch();

        if (!$campaign) { http_response_code(404); echo 'Not found'; return; }

        $categories = $db->query('SELECT * FROM ad_categories ORDER BY name')->fetchAll();

        $balStmt = $db->prepare('SELECT COALESCE(SUM(amount),0) FROM balances WHERE user_id = ? AND currency = "BTC"');
        $balStmt->execute([Auth::id()]);
        $balance = (float)$balStmt->fetchColumn();

        View::render('dashboard/campaign-edit', [
            'title'      => 'Edit Campaign',
            'active'     => 'campaigns',
            'campaign'   => $campaign,
            'categories' => $categories,
            'balance'    => $balance,
            'csrf_token' => Auth::csrfToken(),
            'errors'     => [],
        ], 'dashboard');
    }

    // ── Edit Speichern ────────────────────────────────────────────────────
    public function edit(string $uuid): void
    {
        Auth::require();
        Auth::csrfVerify($_POST['csrf_token'] ?? '');

        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM campaigns WHERE uuid = ? AND user_id = ? LIMIT 1');
        $stmt->execute([$uuid, Auth::id()]);
        $campaign = $stmt->fetch();

        if (!$campaign) { http_response_code(404); return; }

        $errors      = [];
        $name        = trim($_POST['name'] ?? '');
        $targetUrl   = trim($_POST['target_url'] ?? '');
        $bidAmount   = (float)($_POST['bid_amount'] ?? 0);
        $dailyBudget = (float)($_POST['daily_budget'] ?? 0);
        $totalBudget = (float)($_POST['total_budget'] ?? 0);
        $startsAt    = $_POST['starts_at'] ?? null;
        $endsAt      = $_POST['ends_at'] ?? null;
        $status      = $_POST['status'] ?? $campaign['status'];

        if (strlen($name) < 2)                         $errors[] = 'Campaign name required.';
        if (!filter_var($targetUrl, FILTER_VALIDATE_URL)) $errors[] = 'Valid target URL required.';
        if ($bidAmount <= 0)                           $errors[] = 'Bid amount must be greater than 0.';
        if ($dailyBudget <= 0)                         $errors[] = 'Daily budget required.';

        // Bei Reaktivierung Guthaben prüfen
        if (empty($errors) && $status === 'active' && $campaign['status'] !== 'active') {
            $balStmt = $db->prepare('SELECT COALESCE(SUM(amount),0) FROM balances WHERE user_id = ? AND currency = "BTC"');
            $balStmt->execute([Auth::id()]);
            $balance = (float)$balStmt->fetchColumn();

            if ($balance < $dailyBudget) {
                header('Location: /advertiser/billing?insufficient=1&needed=' . urlencode(number_format($dailyBudget, 8)));
                exit;
            }
        }

        if (!empty($errors)) {
            $categories = $db->query('SELECT * FROM ad_categories ORDER BY name')->fetchAll();
            View::render('dashboard/campaign-edit', [
                'title'      => 'Edit Campaign',
                'active'     => 'campaigns',
                'campaign'   => array_merge($campaign, $_POST),
                'categories' => $categories,
                'balance'    => 0,
                'csrf_token' => Auth::csrfToken(),
                'errors'     => $errors,
            ], 'dashboard');
            return;
        }

        $allowedStatuses = ['draft', 'paused', 'active'];
        if (!in_array($status, $allowedStatuses, true)) $status = $campaign['status'];

        $db->prepare('
            UPDATE campaigns SET
                name         = ?,
                target_url   = ?,
                bid_amount   = ?,
                daily_budget = ?,
                total_budget = ?,
                starts_at    = ?,
                ends_at      = ?,
                status       = ?
            WHERE uuid = ? AND user_id = ?
        ')->execute([
            $name, $targetUrl, $bidAmount, $dailyBudget,
            $totalBudget ?: null,
            $startsAt ?: null,
            $endsAt   ?: null,
            $status,
            $uuid, Auth::id(),
        ]);

        header('Location: /advertiser/campaigns?updated=1'); exit;
    }

    // ── Kampagne pausieren/aktivieren ─────────────────────────────────────
    public function toggleStatus(string $uuid): void
    {
        Auth::require();
        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT status FROM campaigns WHERE uuid = ? AND user_id = ? LIMIT 1');
        $stmt->execute([$uuid, Auth::id()]);
        $campaign = $stmt->fetch();

        if (!$campaign) { http_response_code(404); return; }

        $newStatus = $campaign['status'] === 'active' ? 'paused' : 'active';
        $db->prepare('UPDATE campaigns SET status = ? WHERE uuid = ? AND user_id = ?')
           ->execute([$newStatus, $uuid, Auth::id()]);

        header('Location: /advertiser/campaigns?toggled=1'); exit;
    }
}
