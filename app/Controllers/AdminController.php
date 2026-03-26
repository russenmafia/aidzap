<?php
declare(strict_types=1);

namespace Controllers;

use Core\AdminAuth;
use Core\Auth;
use Core\Database;
use Core\View;

class AdminController
{
    // ── Dashboard / Stats ────────────────────────────────────────────────────
    public function index(): void
    {
        AdminAuth::require();
        $db = Database::getInstance();

        $stats = [];

        // Impressions heute
        $stats['impressions_today'] = (int)$db->query('
            SELECT COUNT(*) FROM impressions WHERE DATE(created_at) = CURDATE()
        ')->fetchColumn();

        // Impressions gesamt
        $stats['impressions_total'] = (int)$db->query('
            SELECT COUNT(*) FROM impressions
        ')->fetchColumn();

        // Fraud heute
        $stats['fraud_today'] = (int)$db->query('
            SELECT COUNT(*) FROM impressions WHERE is_fraud = 1 AND DATE(created_at) = CURDATE()
        ')->fetchColumn();

        // Revenue heute (Advertiser spend)
        $stats['revenue_today'] = (float)$db->query('
            SELECT COALESCE(SUM(cost),0) FROM impressions
            WHERE is_fraud = 0 AND DATE(created_at) = CURDATE()
        ')->fetchColumn();

        // Revenue gesamt
        $stats['revenue_total'] = (float)$db->query('
            SELECT COALESCE(SUM(cost),0) FROM impressions WHERE is_fraud = 0
        ')->fetchColumn();

        // Clicks heute
        $stats['clicks_today'] = (int)$db->query('
            SELECT COUNT(*) FROM clicks WHERE DATE(created_at) = CURDATE()
        ')->fetchColumn();

        // Pending Reviews
        $stats['pending_units']     = (int)$db->query("SELECT COUNT(*) FROM ad_units WHERE status = 'pending_review'")->fetchColumn();
        $stats['pending_campaigns'] = (int)$db->query("SELECT COUNT(*) FROM campaigns WHERE status = 'pending_review'")->fetchColumn();
        $stats['pending_banners']   = (int)$db->query("SELECT COUNT(*) FROM ad_banners WHERE status = 'pending_review'")->fetchColumn();

        // User stats
        $stats['users_total']  = (int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $stats['users_today']  = (int)$db->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()")->fetchColumn();

        // CTR heute
        $stats['ctr_today'] = $stats['impressions_today'] > 0
            ? round(($stats['clicks_today'] / $stats['impressions_today']) * 100, 2)
            : 0;

        // Letzte 7 Tage Impressions
        $daily = $db->query('
            SELECT DATE(created_at) AS day, COUNT(*) AS cnt, SUM(is_fraud) AS fraud
            FROM impressions
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY day ASC
        ')->fetchAll();

        // Letzte Fraud Logs
        $fraudLogs = $db->query('
            SELECT fl.*, DATE_FORMAT(fl.created_at, "%d.%m %H:%i") AS ts
            FROM fraud_logs fl
            ORDER BY fl.created_at DESC
            LIMIT 10
        ')->fetchAll();

        View::render('admin/index', [
            'title'     => 'Admin',
            'active'    => 'stats',
            'stats'     => $stats,
            'daily'     => $daily,
            'fraudLogs' => $fraudLogs,
        ], 'admin');
    }

    // ── Users ────────────────────────────────────────────────────────────────
    public function users(): void
    {
        AdminAuth::require();
        $db = Database::getInstance();

        $users = $db->query('
            SELECT u.id, u.uuid, u.username, u.role, u.status,
                   u.created_at, u.last_login_at,
                   COUNT(DISTINCT au.id) AS unit_count,
                   COUNT(DISTINCT c.id)  AS campaign_count
            FROM users u
            LEFT JOIN ad_units au ON au.user_id = u.id
            LEFT JOIN campaigns c ON c.user_id = u.id
            GROUP BY u.id
            ORDER BY u.created_at DESC
        ')->fetchAll();

        View::render('admin/users', [
            'title'  => 'Users',
            'active' => 'users',
            'users'  => $users,
        ], 'admin');
    }

    public function userAction(): void
    {
        AdminAuth::require();
        $uuid   = $_POST['uuid'] ?? '';
        $action = $_POST['action'] ?? '';

        if (!in_array($action, ['suspend','activate','ban','make_admin'], true)) {
            header('Location: /admin/users'); exit;
        }

        $statusMap = ['suspend' => 'suspended', 'activate' => 'active', 'ban' => 'banned'];
        $db = Database::getInstance();

        if ($action === 'make_admin') {
            $db->prepare('UPDATE users SET role = "admin" WHERE uuid = ?')->execute([$uuid]);
        } else {
            $db->prepare('UPDATE users SET status = ? WHERE uuid = ?')
               ->execute([$statusMap[$action], $uuid]);
        }

        header('Location: /admin/users?done=1'); exit;
    }

    // ── Review: Units ────────────────────────────────────────────────────────
    public function reviewUnits(): void
    {
        AdminAuth::require();
        $db    = Database::getInstance();
        $units = $db->query('
            SELECT u.*, usr.username,
                   c.name AS category_name
            FROM ad_units u
            JOIN users usr ON usr.id = u.user_id
            LEFT JOIN ad_categories c ON c.id = u.category_id
            WHERE u.status = "pending_review"
            ORDER BY u.created_at ASC
        ')->fetchAll();

        View::render('admin/review-units', [
            'title'  => 'Review Ad Units',
            'active' => 'review',
            'units'  => $units,
        ], 'admin');
    }

    public function reviewUnitAction(): void
    {
        AdminAuth::require();
        $uuid   = $_POST['uuid'] ?? '';
        $action = $_POST['action'] ?? '';
        $reason = trim($_POST['reason'] ?? '');

        if (!in_array($action, ['approve','reject'], true)) {
            header('Location: /admin/review/units'); exit;
        }

        $status = $action === 'approve' ? 'active' : 'rejected';
        $db = Database::getInstance();
        $db->prepare('UPDATE ad_units SET status = ? WHERE uuid = ?')->execute([$status, $uuid]);

        header('Location: /admin/review/units?done=' . $action); exit;
    }

    // ── Review: Banners ──────────────────────────────────────────────────────
    public function reviewBanners(): void
    {
        AdminAuth::require();
        $db      = Database::getInstance();
        $banners = $db->query('
            SELECT b.*, usr.username, c.name AS campaign_name, c.target_url
            FROM ad_banners b
            JOIN users usr ON usr.id = b.user_id
            JOIN campaigns c ON c.id = b.campaign_id
            WHERE b.status = "pending_review"
            ORDER BY b.created_at ASC
        ')->fetchAll();

        View::render('admin/review-banners', [
            'title'   => 'Review Banners',
            'active'  => 'review',
            'banners' => $banners,
        ], 'admin');
    }

    public function reviewBannerAction(): void
    {
        AdminAuth::require();
        $uuid   = $_POST['uuid'] ?? '';
        $action = $_POST['action'] ?? '';
        $reason = trim($_POST['reason'] ?? '');

        if (!in_array($action, ['approve','reject'], true)) {
            header('Location: /admin/review/banners'); exit;
        }

        $status = $action === 'approve' ? 'active' : 'rejected';
        $db = Database::getInstance();
        $db->prepare('UPDATE ad_banners SET status = ?, reject_reason = ? WHERE uuid = ?')
           ->execute([$status, $reason ?: null, $uuid]);

        // Bei Approve: Campaign auch auf active setzen falls noch draft
        if ($action === 'approve') {
            $db->prepare('
                UPDATE campaigns c
                JOIN ad_banners b ON b.campaign_id = c.id
                SET c.status = "active"
                WHERE b.uuid = ? AND c.status = "draft"
            ')->execute([$uuid]);
        }

        header('Location: /admin/review/banners?done=' . $action); exit;
    }

    // ── Fraud Logs ───────────────────────────────────────────────────────────
    public function fraud(): void
    {
        AdminAuth::require();
        $db = Database::getInstance();

        $logs = $db->query('
            SELECT fl.*,
                   DATE_FORMAT(fl.created_at, "%d.%m.%Y %H:%i:%s") AS ts
            FROM fraud_logs fl
            ORDER BY fl.created_at DESC
            LIMIT 100
        ')->fetchAll();

        $blacklist = $db->query('
            SELECT *,
                   DATE_FORMAT(created_at, "%d.%m.%Y") AS ts,
                   DATE_FORMAT(expires_at, "%d.%m.%Y") AS exp
            FROM ip_blacklist
            ORDER BY created_at DESC
            LIMIT 50
        ')->fetchAll();

        View::render('admin/fraud', [
            'title'     => 'Fraud & Security',
            'active'    => 'fraud',
            'logs'      => $logs,
            'blacklist' => $blacklist,
        ], 'admin');
    }

    public function unblacklist(): void
    {
        AdminAuth::require();
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            Database::getInstance()->prepare('DELETE FROM ip_blacklist WHERE id = ?')->execute([$id]);
        }
        header('Location: /admin/fraud?done=1'); exit;
    }

    // ── Legal Pages ──────────────────────────────────────────────────────────
    public function legalPages(): void
    {
        AdminAuth::require();
        $db = Database::getInstance();

        $pages = $db->query('SELECT id, slug, title, content, updated_at FROM legal_pages ORDER BY FIELD(slug, "terms", "privacy", "impressum")')->fetchAll();

        View::render('admin/legal/index', [
            'title' => 'Legal Pages',
            'active' => 'legal',
            'pages' => $pages,
        ], 'admin');
    }

    public function legalPageEdit(string $slug): void
    {
        AdminAuth::require();
        $db = Database::getInstance();

        $stmt = $db->prepare('SELECT id, slug, title, content, updated_at FROM legal_pages WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        $page = $stmt->fetch();

        if (!$page) {
            http_response_code(404);
            echo 'Not found';
            return;
        }

        View::render('admin/legal/edit', [
            'title' => 'Edit Legal Page',
            'active' => 'legal',
            'page' => $page,
            'csrf_token' => Auth::csrfToken(),
        ], 'admin');
    }

    public function legalPageUpdate(string $slug): void
    {
        AdminAuth::require();
        Auth::csrfVerify($_POST['csrf_token'] ?? '');

        $title = trim($_POST['title'] ?? '');
        $content = trim((string)($_POST['content'] ?? ''));

        if ($title === '') {
            $title = ucfirst($slug);
        }

        $db = Database::getInstance();
        $stmt = $db->prepare('UPDATE legal_pages SET title = ?, content = ? WHERE slug = ?');
        $stmt->execute([$title, $content, $slug]);

        header('Location: /admin/legal/' . urlencode($slug) . '?saved=1');
        exit;
    }

    // ── FAQ ─────────────────────────────────────────────────────────────────
    public function faqIndex(): void
    {
        AdminAuth::require();
        $db = Database::getInstance();

        $items = $db->query('SELECT id, question, answer, sort_order, is_active, created_at FROM faq_items ORDER BY sort_order ASC, id ASC')->fetchAll();

        View::render('admin/faq/index', [
            'title' => 'FAQ',
            'active' => 'faq',
            'items' => $items,
            'csrf_token' => Auth::csrfToken(),
        ], 'admin');
    }

    public function faqAdd(): void
    {
        AdminAuth::require();

        View::render('admin/faq/form', [
            'title' => 'Add FAQ Item',
            'active' => 'faq',
            'mode' => 'add',
            'item' => [
                'question' => '',
                'answer' => '',
                'sort_order' => 1,
            ],
            'csrf_token' => Auth::csrfToken(),
        ], 'admin');
    }

    public function faqStore(): void
    {
        AdminAuth::require();
        Auth::csrfVerify($_POST['csrf_token'] ?? '');

        $question = trim($_POST['question'] ?? '');
        $answer = trim((string)($_POST['answer'] ?? ''));
        $sortOrder = max(1, (int)($_POST['sort_order'] ?? 1));

        if ($question === '' || $answer === '') {
            header('Location: /admin/faq/add?error=1');
            exit;
        }

        $db = Database::getInstance();
        $stmt = $db->prepare('INSERT INTO faq_items (question, answer, sort_order, is_active) VALUES (?, ?, ?, 1)');
        $stmt->execute([$question, $answer, $sortOrder]);

        header('Location: /admin/faq?saved=1');
        exit;
    }

    public function faqEdit(int $id): void
    {
        AdminAuth::require();
        $db = Database::getInstance();

        $stmt = $db->prepare('SELECT id, question, answer, sort_order, is_active FROM faq_items WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $item = $stmt->fetch();

        if (!$item) {
            http_response_code(404);
            echo 'Not found';
            return;
        }

        View::render('admin/faq/form', [
            'title' => 'Edit FAQ Item',
            'active' => 'faq',
            'mode' => 'edit',
            'item' => $item,
            'csrf_token' => Auth::csrfToken(),
        ], 'admin');
    }

    public function faqUpdate(int $id): void
    {
        AdminAuth::require();
        Auth::csrfVerify($_POST['csrf_token'] ?? '');

        $question = trim($_POST['question'] ?? '');
        $answer = trim((string)($_POST['answer'] ?? ''));
        $sortOrder = max(1, (int)($_POST['sort_order'] ?? 1));

        if ($question === '' || $answer === '') {
            header('Location: /admin/faq/' . $id . '?error=1');
            exit;
        }

        $db = Database::getInstance();
        $stmt = $db->prepare('UPDATE faq_items SET question = ?, answer = ?, sort_order = ?, is_active = 1 WHERE id = ?');
        $stmt->execute([$question, $answer, $sortOrder, $id]);

        header('Location: /admin/faq?saved=1');
        exit;
    }

    public function faqDelete(int $id): void
    {
        AdminAuth::require();
        Auth::csrfVerify($_POST['csrf_token'] ?? '');

        $db = Database::getInstance();
        $stmt = $db->prepare('DELETE FROM faq_items WHERE id = ?');
        $stmt->execute([$id]);

        header('Location: /admin/faq?deleted=1');
        exit;
    }

    // ── Cron Overview ────────────────────────────────────────────────────────
    public function crons(): void
    {
        AdminAuth::require();
        View::render('admin/crons', [
            'title'  => 'Cron Jobs',
            'active' => 'crons',
        ], 'admin');
    }

    // ── Cron manuell ausführen (POST) ─────────────────────────────────────
    public function runCron(): void
    {
        AdminAuth::require();
        $job     = $_POST['job'] ?? '';
        $allowed = ['daily-stats','budget-reset','fraud-cleanup','payment-check'];

        if (!in_array($job, $allowed, true)) {
            header('Location: /admin/crons'); exit;
        }

        $runner = BASE_PATH . '/cron/runner.php';
        $logFile= STORAGE_PATH . '/logs/cron.log';

        $output = shell_exec("php {$runner} {$job} 2>&1");
        file_put_contents($logFile, $output, FILE_APPEND);

        header('Location: /admin/crons?ran=' . urlencode($job)); exit;
    }

    // ── Cron via HTTP (für cURL/wget Crontab) ────────────────────────────
    public function runCronHttp(): void
    {
        $secret = $_GET['secret'] ?? '';
        if (!hash_equals($_ENV['APP_SECRET'] ?? '', $secret)) {
            http_response_code(403); echo 'Forbidden'; return;
        }

        $job     = $_GET['job'] ?? '';
        $allowed = ['daily-stats','budget-reset','fraud-cleanup','payment-check'];
        if (!in_array($job, $allowed, true)) {
            http_response_code(400); echo 'Invalid job'; return;
        }

        $runner = BASE_PATH . '/cron/runner.php';
        $output = shell_exec("php {$runner} {$job} 2>&1");

        header('Content-Type: text/plain');
        echo $output;
    }

    public function system(): void
    {
        AdminAuth::require();
        View::render('admin/system', [
            'title'  => 'System Overview',
            'active' => 'system',
        ], 'admin');
    }

    public function clearCache(): void
    {
        AdminAuth::require();
        if (function_exists('opcache_reset')) opcache_reset();
        header('Location: /admin/system?cleared=1'); exit;
    }

    // ── Feature Flags ────────────────────────────────────────────────────────
    public function features(): void
    {
        AdminAuth::require();
        $flags = \Services\FeatureFlag::all();
        View::render('admin/features', [
            'title'      => 'Feature Flags',
            'active'     => 'features',
            'flags'      => $flags,
            'csrf_token' => Auth::csrfToken(),
        ], 'admin');
    }

    public function toggleFeature(): void
    {
        AdminAuth::require();
        Auth::csrfVerify($_POST['csrf_token'] ?? '');

        $key   = $_POST['flag_key'] ?? '';
        $value = (bool)(int)($_POST['value'] ?? 0);

        if (!in_array($key, ['targeting_geo', 'targeting_language', 'targeting_device'], true)) {
            header('Location: /admin/features'); exit;
        }

        \Services\FeatureFlag::set($key, $value);
        header('Location: /admin/features?saved=1'); exit;
    }
}
