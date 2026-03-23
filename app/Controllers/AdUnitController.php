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
        View::render('dashboard/unit-create', [
            'title'      => 'New Ad Unit',
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

        $name    = trim($_POST['name'] ?? '');
        $url     = trim($_POST['website_url'] ?? '');
        $size    = $_POST['size'] ?? '300x250';
        $cat     = (int)($_POST['category_id'] ?? 0) ?: null;
        $errors  = [];

        $validSizes = ['728x90','300x250','160x600','320x50','468x60','250x250','300x600'];

        if (strlen($name) < 3)           $errors[] = 'Name must be at least 3 characters.';
        if (!filter_var($url, FILTER_VALIDATE_URL)) $errors[] = 'Please enter a valid website URL.';
        if (!in_array($size, $validSizes, true))     $errors[] = 'Invalid banner size.';

        if (!empty($errors)) {
            $cats = Database::getInstance()->query('SELECT id, name FROM ad_categories WHERE is_active = 1')->fetchAll();
            View::render('dashboard/unit-create', [
                'title'      => 'New Ad Unit',
                'categories' => $cats,
                'errors'     => $errors,
                'old'        => compact('name','url','size','cat'),
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
}
