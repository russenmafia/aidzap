<?php
declare(strict_types=1);

namespace Controllers;

use Core\Auth;
use Core\Database;
use Core\View;

class AdUnitController
{
    public function index(): void
    {
        Auth::require();
        $db    = Database::getInstance();
        $units = $db->prepare('
            SELECT u.*, c.name AS category_name,
                   COALESCE(SUM(e.impressions),0) AS impressions,
                   COALESCE(SUM(e.clicks),0)      AS clicks,
                   COALESCE(SUM(e.amount),0)      AS earned
            FROM ad_units u
            LEFT JOIN ad_categories c ON c.id = u.category_id
            LEFT JOIN earnings e ON e.unit_id = u.id
            WHERE u.user_id = ?
            GROUP BY u.id
            ORDER BY u.created_at DESC
        ');
        $units->execute([Auth::id()]);

        View::render('dashboard/units', [
            'title' => 'Ad Units',
            'units' => $units->fetchAll(),
        ], 'dashboard');
    }

    public function createForm(): void
    {
        Auth::require();
        $cats = Database::getInstance()->query('SELECT id, name FROM ad_categories WHERE is_active = 1 ORDER BY name')->fetchAll();
        $sizes = $this->getActiveBannerSizes();
        View::render('dashboard/unit-create', [
            'title'      => 'New Ad Unit',
            'categories' => $cats,
            'sizes'      => $sizes,
            'errors'     => [],
            'old'        => [],
            'csrf_token' => \Core\Auth::csrfToken(),
        ], 'dashboard');
    }

    public function create(): void
    {
        Auth::require();
        \Core\Auth::csrfVerify($_POST['csrf_token'] ?? '');

        $name    = trim($_POST['name'] ?? '');
        $url     = trim($_POST['website_url'] ?? '');
        $sizes   = $this->getActiveBannerSizes();
        $size    = $_POST['size'] ?? ((string)(array_key_first($sizes) ?: '300x250'));
        $cat     = (int)($_POST['category_id'] ?? 0) ?: null;
        $errors  = [];

        $validSizes = array_keys($sizes);
        if (empty($validSizes)) {
            $validSizes = ['300x250'];
        }

        if (strlen($name) < 3)           $errors[] = 'Name must be at least 3 characters.';
        if (!filter_var($url, FILTER_VALIDATE_URL)) $errors[] = 'Please enter a valid website URL.';
        if (!in_array($size, $validSizes, true))     $errors[] = 'Invalid banner size.';

        if (!empty($errors)) {
            $cats = Database::getInstance()->query('SELECT id, name FROM ad_categories WHERE is_active = 1')->fetchAll();
            View::render('dashboard/unit-create', [
                'title'      => 'New Ad Unit',
                'categories' => $cats,
                'sizes'      => $sizes,
                'errors'     => $errors,
                'old'        => [
                    'name' => $name,
                    'website_url' => $url,
                    'size' => $size,
                    'category_id' => $cat,
                    'type' => (string)($_POST['type'] ?? 'banner'),
                    'sticky_position' => (string)($_POST['sticky_position'] ?? 'bottom'),
                    'floor_price' => (string)($_POST['floor_price'] ?? '0'),
                    'fallback_html' => (string)($_POST['fallback_html'] ?? ''),
                    'native_title_max' => (string)($_POST['native_title_max'] ?? '60'),
                    'native_desc_max' => (string)($_POST['native_desc_max'] ?? '120'),
                    'native_css_class' => (string)($_POST['native_css_class'] ?? ''),
                ],
                'csrf_token' => \Core\Auth::csrfToken(),
            ], 'dashboard');
            return;
        }

        $db   = Database::getInstance();
        $uuid = $this->uuid();
        $db->prepare('
            INSERT INTO ad_units (uuid, user_id, name, website_url, size, category_id, status)
            VALUES (?,?,?,?,?,?,"pending_review")
        ')->execute([$uuid, Auth::id(), $name, $url, $size, $cat]);

        header('Location: /publisher/units?created=1'); exit;
    }

    private function uuid(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),
            mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000,
            mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff));
    }

    private function getActiveBannerSizes(): array
    {
        try {
            $rows = Database::getInstance()->query('SELECT size_key, name, width, height FROM banner_formats WHERE is_active = 1 ORDER BY sort_order ASC, id ASC')->fetchAll();
            $sizes = [];
            foreach ($rows as $row) {
                $key = (string)($row['size_key'] ?? '');
                if ($key === '') {
                    continue;
                }

                $name = trim((string)($row['name'] ?? ''));
                $width = (int)($row['width'] ?? 0);
                $height = (int)($row['height'] ?? 0);
                $label = $name !== '' ? $name : $key;
                if ($width > 0 && $height > 0) {
                    $label .= ' (' . $width . 'x' . $height . ')';
                }
                $sizes[$key] = $label;
            }

            if (!empty($sizes)) {
                return $sizes;
            }
        } catch (\Throwable $e) {
            error_log('AdUnitController::getActiveBannerSizes - ' . $e->getMessage());
        }

        return ['300x250' => 'Medium Rectangle (300x250)'];
    }
}
