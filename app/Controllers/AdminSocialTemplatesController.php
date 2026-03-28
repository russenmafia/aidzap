<?php
declare(strict_types=1);
namespace Controllers;
use Core\AdminAuth;
use Core\Auth;
use Core\Database;
use Core\View;

class AdminSocialTemplatesController
{
    public function index(): void
    {
        AdminAuth::require();
        $db = Database::getInstance();
        $templates = $db->query('SELECT * FROM social_templates ORDER BY sort_order ASC, id ASC')->fetchAll();
        View::render('admin/social-templates', [
            'title'      => 'Social Media Templates',
            'active'     => 'social_templates',
            'templates'  => $templates,
            'csrf_token' => Auth::csrfToken(),
        ], 'admin');
    }

    public function create(): void
    {
        AdminAuth::require();
        Auth::csrfVerify($_POST['csrf_token'] ?? '');
        $db = Database::getInstance();
        $db->prepare('
            INSERT INTO social_templates (title, platform, lang, body, sort_order, status)
            VALUES (?,?,?,?,?,?)
        ')->execute([
            trim($_POST['title']      ?? ''),
            trim($_POST['platform']   ?? 'all'),
            trim($_POST['lang']       ?? 'en'),
            trim($_POST['body']       ?? ''),
            (int)($_POST['sort_order'] ?? 0),
            in_array($_POST['status'] ?? '', ['active','inactive']) ? $_POST['status'] : 'active',
        ]);
        header('Location: /admin/social-templates?saved=1'); exit;
    }

    public function update(): void
    {
        AdminAuth::require();
        Auth::csrfVerify($_POST['csrf_token'] ?? '');
        $db = Database::getInstance();
        $db->prepare('
            UPDATE social_templates SET title=?, platform=?, lang=?, body=?, sort_order=?, status=?
            WHERE id=?
        ')->execute([
            trim($_POST['title']      ?? ''),
            trim($_POST['platform']   ?? 'all'),
            trim($_POST['lang']       ?? 'en'),
            trim($_POST['body']       ?? ''),
            (int)($_POST['sort_order'] ?? 0),
            in_array($_POST['status'] ?? '', ['active','inactive']) ? $_POST['status'] : 'active',
            (int)($_POST['id']        ?? 0),
        ]);
        header('Location: /admin/social-templates?saved=1'); exit;
    }

    public function delete(): void
    {
        AdminAuth::require();
        Auth::csrfVerify($_POST['csrf_token'] ?? '');
        $db = Database::getInstance();
        $db->prepare('DELETE FROM social_templates WHERE id = ?')->execute([(int)($_POST['id'] ?? 0)]);
        header('Location: /admin/social-templates?saved=1'); exit;
    }
}
