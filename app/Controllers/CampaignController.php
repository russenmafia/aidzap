<?php
declare(strict_types=1);

namespace Controllers;

use Core\Auth;
use Core\Database;
use Core\View;

class CampaignController
{
    public function index(): void
    {
        Auth::require();
        $campaigns = Database::getInstance()->prepare('
            SELECT c.*, COUNT(b.id) AS banner_count
            FROM campaigns c
            LEFT JOIN ad_banners b ON b.campaign_id = c.id
            WHERE c.user_id = ?
            GROUP BY c.id
            ORDER BY c.created_at DESC
        ');
        $campaigns->execute([Auth::id()]);

        View::render('dashboard/campaigns', [
            'title'     => 'Campaigns',
            'campaigns' => $campaigns->fetchAll(),
        ], 'dashboard');
    }

    public function createForm(): void
    {
        Auth::require();
        $cats = Database::getInstance()->query('SELECT id, name FROM ad_categories WHERE is_active = 1 ORDER BY name')->fetchAll();
        View::render('dashboard/campaign-create', [
            'title'      => 'New Campaign',
            'categories' => $cats,
            'errors'     => [],
            'old'        => [],
            'csrf_token' => \Core\Auth::csrfToken(),
        ], 'dashboard');
    }

    public function create(): void
    {
        Auth::require();
        \Core\Auth::csrfVerify($_POST['csrf_token'] ?? '');

        $name         = trim($_POST['name'] ?? '');
        $model        = $_POST['pricing_model'] ?? 'cpd';
        $dailyBudget  = (float)($_POST['daily_budget'] ?? 0);
        $totalBudget  = (float)($_POST['total_budget'] ?? 0);
        $bidAmount    = (float)($_POST['bid_amount'] ?? 0);
        $targetUrl    = trim($_POST['target_url'] ?? '');
        $currency     = $_POST['currency'] ?? 'BTC';
        $errors       = [];

        if (strlen($name) < 3)    $errors[] = 'Campaign name must be at least 3 characters.';
        if (!filter_var($targetUrl, FILTER_VALIDATE_URL)) $errors[] = 'Please enter a valid target URL.';
        if ($dailyBudget <= 0)    $errors[] = 'Daily budget must be greater than 0.';
        if ($totalBudget <= 0)    $errors[] = 'Total budget must be greater than 0.';
        if ($bidAmount <= 0)      $errors[] = 'Bid amount must be greater than 0.';
        if (!in_array($model, ['cpd','cpm','cpa'], true)) $errors[] = 'Invalid pricing model.';

        if (!empty($errors)) {
            $cats = Database::getInstance()->query('SELECT id, name FROM ad_categories WHERE is_active = 1')->fetchAll();
            View::render('dashboard/campaign-create', [
                'title'      => 'New Campaign',
                'categories' => $cats,
                'errors'     => $errors,
                'old'        => $_POST,
                'csrf_token' => \Core\Auth::csrfToken(),
            ], 'dashboard');
            return;
        }

        $db   = Database::getInstance();
        $uuid = $this->uuid();

        $countries   = !empty($_POST['countries']) ? json_encode(array_map('trim', explode(',', $_POST['countries']))) : null;
        $categories  = !empty($_POST['category_ids']) ? json_encode($_POST['category_ids']) : null;

        $db->prepare('
            INSERT INTO campaigns
                (uuid, user_id, name, pricing_model, daily_budget, total_budget,
                 bid_amount, target_url, currency, target_countries, target_categories, status)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,"draft")
        ')->execute([$uuid, Auth::id(), $name, $model, $dailyBudget, $totalBudget,
                     $bidAmount, $targetUrl, $currency, $countries, $categories]);

        header('Location: /advertiser/campaigns?created=1'); exit;
    }

    private function uuid(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),
            mt_rand(0,0x0fff)|0x4000,mt_rand(0,0x3fff)|0x8000,
            mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff));
    }
}
