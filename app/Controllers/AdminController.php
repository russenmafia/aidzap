<?php
declare(strict_types=1);

namespace Controllers;

use Core\AdminAuth;
use Core\Auth;
use Core\Database;
use Core\View;
use Services\MailService;

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
        $allowed = ['daily-stats','budget-reset','fraud-cleanup','payment-check','quality-score','geoip-update'];

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
        $allowed = ['daily-stats','budget-reset','fraud-cleanup','payment-check','quality-score','geoip-update'];
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
        $db = Database::getInstance();

        $defaults = [
            'site_name' => 'aidzap.com',
            'site_url' => 'https://aidzap.com',
            'site_email' => 'noreply@aidzap.com',
            'support_email' => 'support@aidzap.com',
            'ga_enabled' => '0',
            'ga_id' => '',
            'smtp_enabled' => '0',
            'smtp_host' => '',
            'smtp_port' => '587',
            'smtp_user' => '',
            'smtp_pass' => '',
            'smtp_from_email' => '',
            'smtp_from_name' => 'aidzap.com',
            'smtp_encryption' => 'tls',
            'double_optin' => '0',
            'maintenance_mode' => '0',
            'maintenance_notice' => 'We are back soon.',
            'newsletter_enabled' => '0',
        ];

        $settings = $defaults;
        try {
            $settingsRows = $db->query('SELECT `key`, `value` FROM site_settings')->fetchAll();
            foreach ($settingsRows as $row) {
                if (isset($row['key'])) {
                    $settings[(string)$row['key']] = (string)($row['value'] ?? '');
                }
            }
        } catch (\Throwable $e) {
            error_log('AdminController::system settings - ' . $e->getMessage());
        }

        $bannerFormats = [];
        try {
            $bannerFormats = $db->query('SELECT * FROM banner_formats ORDER BY sort_order ASC, id ASC')->fetchAll();
        } catch (\Throwable $e) {
            error_log('AdminController::system banner_formats - ' . $e->getMessage());
        }

        View::render('admin/system', [
            'title'  => 'System Overview',
            'active' => 'system',
            'settings' => $settings,
            'bannerFormats' => $bannerFormats,
            'turnstileSiteKey' => (string)($_ENV['TURNSTILE_SITE_KEY'] ?? ''),
            'turnstileSecretKey' => (string)($_ENV['TURNSTILE_SECRET_KEY'] ?? ''),
            'csrf_token' => Auth::csrfToken(),
        ], 'admin');
    }

    public function saveSettings(): void
    {
        AdminAuth::require();
        Auth::csrfVerify($_POST['csrf_token'] ?? '');

        $db = Database::getInstance();
        $allowed = [
            'site_name', 'site_url', 'site_email', 'support_email',
            'ga_enabled', 'ga_id',
            'smtp_enabled', 'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass',
            'smtp_from_email', 'smtp_from_name', 'smtp_encryption',
            'double_optin', 'maintenance_mode', 'maintenance_notice', 'newsletter_enabled',
        ];
        $checkboxes = ['ga_enabled', 'smtp_enabled', 'double_optin', 'maintenance_mode', 'newsletter_enabled'];

        $stmt = $db->prepare('INSERT INTO site_settings (`key`, `value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)');
        foreach ($allowed as $key) {
            $value = $_POST[$key] ?? '';
            if (in_array($key, $checkboxes, true)) {
                $value = isset($_POST[$key]) ? '1' : '0';
            }
            if ($key === 'smtp_port') {
                $value = (string)max(1, (int)$value);
            }
            if ($key === 'smtp_encryption' && !in_array((string)$value, ['tls', 'ssl', 'none'], true)) {
                $value = 'tls';
            }

            $stmt->execute([$key, (string)$value]);
        }

        $tab = (string)($_POST['redirect_tab'] ?? 'site');
        if (!in_array($tab, ['site', 'mail', 'maintenance'], true)) {
            $tab = 'site';
        }
        header('Location: /admin/system?tab=' . urlencode($tab) . '&saved=1');
        exit;
    }

    public function createBannerFormat(): void
    {
        AdminAuth::require();
        Auth::csrfVerify($_POST['csrf_token'] ?? '');

        $name = trim((string)($_POST['name'] ?? ''));
        $width = max(1, (int)($_POST['width'] ?? 0));
        $height = max(1, (int)($_POST['height'] ?? 0));
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($name !== '') {
            $sizeKey = $width . 'x' . $height;
            Database::getInstance()->prepare('INSERT IGNORE INTO banner_formats (name, width, height, size_key, is_active, sort_order) VALUES (?,?,?,?,?,?)')
                ->execute([$name, $width, $height, $sizeKey, $isActive, $sortOrder]);
        }

        header('Location: /admin/system?tab=formats&saved=1');
        exit;
    }

    public function updateBannerFormat(): void
    {
        AdminAuth::require();
        Auth::csrfVerify($_POST['csrf_token'] ?? '');

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            header('Location: /admin/system?tab=formats&error=1');
            exit;
        }

        $name = trim((string)($_POST['name'] ?? ''));
        $width = max(1, (int)($_POST['width'] ?? 0));
        $height = max(1, (int)($_POST['height'] ?? 0));
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $sizeKey = $width . 'x' . $height;

        if ($name === '') {
            header('Location: /admin/system?tab=formats&error=1');
            exit;
        }

        Database::getInstance()->prepare('UPDATE banner_formats SET name = ?, width = ?, height = ?, size_key = ?, is_active = ?, sort_order = ? WHERE id = ?')
            ->execute([$name, $width, $height, $sizeKey, $isActive, $sortOrder, $id]);

        header('Location: /admin/system?tab=formats&saved=1');
        exit;
    }

    public function deleteBannerFormat(): void
    {
        AdminAuth::require();
        Auth::csrfVerify($_POST['csrf_token'] ?? '');

        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            Database::getInstance()->prepare('DELETE FROM banner_formats WHERE id = ?')->execute([$id]);
        }

        header('Location: /admin/system?tab=formats&saved=1');
        exit;
    }

    public function sendSystemTestMail(): void
    {
        AdminAuth::require();
        Auth::csrfVerify($_POST['csrf_token'] ?? '');

        $db = Database::getInstance();
        $settingsRows = [];
        try {
            $settingsRows = $db->query('SELECT `key`, `value` FROM site_settings')->fetchAll();
        } catch (\Throwable $e) {
            error_log('AdminController::sendSystemTestMail settings - ' . $e->getMessage());
        }

        $settings = [];
        foreach ($settingsRows as $row) {
            $settings[(string)$row['key']] = (string)($row['value'] ?? '');
        }

        $to = trim((string)($_POST['test_email'] ?? ''));
        if ($to === '') {
            $to = (string)($settings['support_email'] ?? $settings['site_email'] ?? '');
        }

        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            header('Location: /admin/system?tab=mail&mail_test=invalid');
            exit;
        }

        $subject = 'Aidzap SMTP test';
        $body = '<p>This is a test email from Aidzap system settings.</p><p>Sent at: ' . date('Y-m-d H:i:s') . '</p>';
        $ok = MailService::send($to, $subject, $body);

        header('Location: /admin/system?tab=mail&mail_test=' . ($ok ? 'ok' : 'fail'));
        exit;
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

    // ── Quality Score System ─────────────────────────────────────────────────
    public function quality(): void
    {
        AdminAuth::require();
        $service  = new \Services\QualityScoreService();
        $settings = $service->getSettings();
        View::render('admin/quality', [
            'title'      => 'Quality Score',
            'active'     => 'quality',
            'settings'   => $settings,
            'csrf_token' => Auth::csrfToken(),
        ], 'admin');
    }

    public function saveQuality(): void
    {
        AdminAuth::require();
        Auth::csrfVerify($_POST['csrf_token'] ?? '');

        $service = new \Services\QualityScoreService();
        $service->saveSettings([
            'bronze_max_ctr'        => min(1, max(0, (float)($_POST['bronze_max_ctr']        ?? 0.0010))),
            'silver_max_ctr'        => min(1, max(0, (float)($_POST['silver_max_ctr']        ?? 0.0030))),
            'gold_max_ctr'          => min(1, max(0, (float)($_POST['gold_max_ctr']          ?? 0.0080))),
            'bronze_share'          => min(100, max(0, (float)($_POST['bronze_share']         ?? 60))),
            'silver_share'          => min(100, max(0, (float)($_POST['silver_share']         ?? 70))),
            'gold_share'            => min(100, max(0, (float)($_POST['gold_share']           ?? 80))),
            'platinum_share'        => min(100, max(0, (float)($_POST['platinum_share']       ?? 85))),
            'ref_multiplier_0'      => min(10,  max(0, (float)($_POST['ref_multiplier_0']     ?? 0))),
            'ref_multiplier_1'      => min(10,  max(0, (float)($_POST['ref_multiplier_1']     ?? 0.5))),
            'ref_multiplier_2'      => min(10,  max(0, (float)($_POST['ref_multiplier_2']     ?? 1.0))),
            'ref_multiplier_3plus'  => min(10,  max(0, (float)($_POST['ref_multiplier_3plus'] ?? 1.5))),
            'min_own_level'         => in_array($_POST['min_own_level'] ?? '', ['bronze','silver','gold','platinum'], true)
                                        ? $_POST['min_own_level']
                                        : 'silver',
            'concentration_cap_pct' => min(100, max(1, (int)($_POST['concentration_cap_pct'] ?? 50))),
            'cooling_period_days'   => min(365, max(0, (int)($_POST['cooling_period_days']   ?? 14))),
            'activity_window_days'  => min(365, max(1, (int)($_POST['activity_window_days']  ?? 30))),
            'max_fraud_score'       => min(1,   max(0, (float)($_POST['max_fraud_score']     ?? 0.75))),
        ]);

        header('Location: /admin/quality?saved=1'); exit;
    }

    // ── Finance Dashboard ────────────────────────────────────────────────────
    public function finance(): void
    {
        AdminAuth::require();
        $db = Database::getInstance();

        // ── Heute ────────────────────────────────────────────────────────
        $todayRow = $db->query("
            SELECT
                COALESCE(SUM(cost), 0) AS revenue,
                COUNT(id)              AS impressions
            FROM impressions
            WHERE DATE(created_at) = CURDATE() AND is_fraud = 0
        ")->fetch();
        $today = [
            'revenue'     => (float)($todayRow['revenue']     ?? 0),
            'impressions' => (int)  ($todayRow['impressions'] ?? 0),
            'clicks'      => (int)$db->query("
                SELECT COUNT(*) FROM clicks
                WHERE DATE(created_at) = CURDATE() AND is_fraud = 0
            ")->fetchColumn(),
        ];

        $payouts_today = (float)$db->query("
            SELECT COALESCE(SUM(amount), 0) FROM earnings WHERE date = CURDATE()
        ")->fetchColumn();

        $ref_today = 0.0;
        try {
            $ref_today = (float)$db->query("
                SELECT COALESCE(SUM(commission), 0)
                FROM referral_earnings
                WHERE DATE(created_at) = CURDATE()
            ")->fetchColumn();
        } catch (\Throwable $e) {}

        // ── Letzte 30 Tage ───────────────────────────────────────────────
        $monthlyRow = $db->query("
            SELECT
                COALESCE(SUM(cost), 0) AS revenue,
                COUNT(id)              AS impressions
            FROM impressions
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
              AND is_fraud = 0
        ")->fetch();
        $monthly = [
            'revenue'     => (float)($monthlyRow['revenue']     ?? 0),
            'impressions' => (int)  ($monthlyRow['impressions'] ?? 0),
            'clicks'      => (int)$db->query("
                SELECT COUNT(*) FROM clicks
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND is_fraud = 0
            ")->fetchColumn(),
        ];

        $payouts_monthly = (float)$db->query("
            SELECT COALESCE(SUM(amount), 0) FROM earnings
            WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ")->fetchColumn();

        $ref_monthly = 0.0;
        try {
            $ref_monthly = (float)$db->query("
                SELECT COALESCE(SUM(commission), 0)
                FROM referral_earnings
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ")->fetchColumn();
        } catch (\Throwable $e) {}

        // ── Per-Impression Analyse (by pricing model, 30d) ────────────────
        // Revenue per model from impressions; publisher payout from earnings aggregated
        // separately — avoids cross-product JOIN between impressions and earnings.
        $perImpression = $db->query("
            SELECT
                c.pricing_model,
                COUNT(i.id)                                         AS impressions,
                COALESCE(SUM(i.cost), 0)                           AS total_revenue,
                COALESCE(SUM(i.cost), 0) / NULLIF(COUNT(i.id), 0) AS avg_revenue_per_imp
            FROM impressions i
            JOIN campaigns c ON c.id = i.campaign_id
            WHERE i.is_fraud = 0
              AND i.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY c.pricing_model
        ")->fetchAll();

        // Publisher payouts by campaign pricing model (via unit→impression linkage)
        // Publisher Share = Revenue * avg_revenue_share
        $pubByModel = [];
        try {
            $rows = $db->query("
                SELECT c.pricing_model, AVG(au.revenue_share) AS avg_share
                FROM impressions i
                JOIN campaigns c ON c.id = i.campaign_id
                JOIN ad_units au ON au.id = i.unit_id
                WHERE i.is_fraud = 0
                  AND i.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY c.pricing_model
            ")->fetchAll();
            foreach ($rows as $r) {
                $pubByModel[$r["pricing_model"]] = (float)$r["avg_share"] / 100;
            }
        } catch (\Throwable $e) {}
        $refByModel = [];
        try {
            $total_ref = (float)$db->query("SELECT COALESCE(SUM(commission),0) FROM referral_earnings WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
            $total_rev = (float)$db->query("SELECT COALESCE(SUM(cost),0) FROM impressions WHERE is_fraud=0 AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
            $refRatioGlobal = $total_rev > 0 ? $total_ref / $total_rev : 0.0;
        } catch (\Throwable $e) { $refRatioGlobal = 0.0; }
        foreach ($perImpression as &$row) {
            $model = $row["pricing_model"];
            $rev = (float)$row["total_revenue"];
            $shareRatio = $pubByModel[$model] ?? 0.8;
            $row["total_publisher_payout"] = round($rev * $shareRatio, 8);
            $row["total_ref_payout"] = round($rev * $refRatioGlobal, 8);
        }
        unset($row);
        // ── Revenue Share Verteilung ──────────────────────────────────────
        $revenueShare = [];
        try {
            $revenueShare = $db->query("
                SELECT
                    au.quality_level,
                    au.revenue_share,
                    COUNT(au.id)               AS unit_count,
                    COALESCE(
                        (SELECT SUM(e2.amount) FROM earnings e2
                         WHERE e2.unit_id = au.id
                           AND e2.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)),
                        0
                    ) AS total_paid
                FROM ad_units au
                GROUP BY au.quality_level, au.revenue_share
                ORDER BY au.revenue_share DESC
            ")->fetchAll();
        } catch (\Throwable $e) {}

        // ── Referral Kosten nach Level ────────────────────────────────────
        $refByLevel = [];
        try {
            $refByLevel = $db->query("
                SELECT
                    level,
                    COUNT(*)                     AS transactions,
                    COALESCE(SUM(commission), 0) AS total_commission,
                    COALESCE(AVG(pct), 0)        AS avg_pct
                FROM referral_earnings
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY level
                ORDER BY level
            ")->fetchAll();
        } catch (\Throwable $e) {}

        // ── Letzte Transaktionen ─────────────────────────────────────────
        $recentTransactions = $db->query("
            SELECT
                i.cost       AS amount,
                i.created_at,
                c.name       AS campaign_name,
                au.name      AS unit_name
            FROM impressions i
            JOIN campaigns c  ON c.id  = i.campaign_id
            JOIN ad_units au  ON au.id = i.unit_id
            WHERE i.is_fraud = 0 AND i.cost > 0
            ORDER BY i.created_at DESC
            LIMIT 20
        ")->fetchAll();

        View::render('admin/finance', [
            'title'              => 'Finance Dashboard',
            'active'             => 'finance',
            'today'              => $today,
            'payouts_today'      => $payouts_today,
            'ref_today'          => $ref_today,
            'monthly'            => $monthly,
            'payouts_monthly'    => $payouts_monthly,
            'ref_monthly'        => $ref_monthly,
            'perImpression'      => $perImpression,
            'revenueShare'       => $revenueShare,
            'refByLevel'         => $refByLevel,
            'recentTransactions' => $recentTransactions,
        ], 'admin');
    }
    public function reviewCampaigns(): void
    {
        AdminAuth::require();
        $db = Database::getInstance();
        $campaigns = $db->query("
            SELECT c.*, u.username,
                   COUNT(b.id) AS banner_count
            FROM campaigns c
            JOIN users u ON u.id = c.user_id
            LEFT JOIN ad_banners b ON b.campaign_id = c.id
            WHERE c.status = 'pending_review'
            GROUP BY c.id
            ORDER BY c.created_at ASC
        ")->fetchAll();
        View::render('admin/review-campaigns', [
            'title'     => 'Campaign Review',
            'active'    => 'review_campaigns',
            'campaigns' => $campaigns,
            'csrf_token'=> Auth::csrfToken(),
        ], 'admin');
    }

    public function reviewCampaignAction(): void
    {
        AdminAuth::require();
        Auth::csrfVerify($_POST['csrf_token'] ?? '');
        $id     = (int)($_POST['campaign_id'] ?? 0);
        $action = $_POST['action'] ?? '';
        if (!$id || !in_array($action, ['approve','reject'], true)) {
            header('Location: /admin/review/campaigns'); exit;
        }
        $db = Database::getInstance();
        $status = $action === 'approve' ? 'active' : 'rejected';
        $db->prepare('UPDATE campaigns SET status = ? WHERE id = ?')->execute([$status, $id]);
        header('Location: /admin/review/campaigns?done=1'); exit;
    }

}