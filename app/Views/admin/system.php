<?php $active = 'system'; ?>

<div class="page-header">
  <h1 class="page-title">System Overview</h1>
  <span style="font-size:12px;color:rgba(255,255,255,0.3);font-family:'DM Mono',monospace"><?= date('d.m.Y H:i:s') ?></span>
</div>

<?php
$db = \Core\Database::getInstance();

// ── Umgebung ──────────────────────────────────────────────────────────────
$phpVersion  = PHP_VERSION;
$serverSoft  = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
$appEnv      = $_ENV['APP_ENV'] ?? 'production';
$appDebug    = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
$appUrl      = $_ENV['APP_URL'] ?? 'https://aidzap.com';
$diskFree    = disk_free_space('/');
$diskTotal   = disk_total_space('/');
$diskUsedPct = $diskTotal > 0 ? round((1 - $diskFree / $diskTotal) * 100) : 0;
$memLimit    = ini_get('memory_limit');
$uploadMax   = ini_get('upload_max_filesize');
$maxExec     = ini_get('max_execution_time');

// ── Datenbank ─────────────────────────────────────────────────────────────
$dbVersion  = $db->query('SELECT VERSION()')->fetchColumn();
$dbSize     = $db->query("
    SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2)
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
")->fetchColumn();

$tableRows = $db->query("
    SELECT table_name, table_rows, ROUND((data_length + index_length)/1024,1) AS size_kb
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
    ORDER BY table_rows DESC
")->fetchAll();

// ── API Status ────────────────────────────────────────────────────────────
function checkApi(string $url, array $headers = []): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_HTTPHEADER     => $headers,
    ]);
    $result   = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);
    return ['code' => $httpCode, 'error' => $error, 'body' => $result];
}

// NOWPayments
$npKey    = $_ENV['NOWPAYMENTS_API_KEY'] ?? '';
$npStatus = !empty($npKey) ? checkApi('https://api.nowpayments.io/v1/status', ['x-api-key: ' . $npKey]) : ['code' => 0];
$npOk     = ($npStatus['code'] === 200 && str_contains($npStatus['body'] ?? '', 'OK'));

// Anthropic
$anKey    = $_ENV['ANTHROPIC_API_KEY'] ?? '';
$anOk     = !empty($anKey);

// ── Kennzahlen ────────────────────────────────────────────────────────────
$stats = [];
$stats['users_total']      = (int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn();
$stats['users_today']      = (int)$db->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$stats['campaigns_active'] = (int)$db->query("SELECT COUNT(*) FROM campaigns WHERE status='active'")->fetchColumn();
$stats['campaigns_total']  = (int)$db->query("SELECT COUNT(*) FROM campaigns")->fetchColumn();
$stats['banners_active']   = (int)$db->query("SELECT COUNT(*) FROM ad_banners WHERE status='active'")->fetchColumn();
$stats['units_active']     = (int)$db->query("SELECT COUNT(*) FROM ad_units WHERE status='active'")->fetchColumn();
$stats['impressions_total']= (int)$db->query("SELECT COUNT(*) FROM impressions")->fetchColumn();
$stats['impressions_today']= (int)$db->query("SELECT COUNT(*) FROM impressions WHERE DATE(created_at)=CURDATE()")->fetchColumn();
$stats['clicks_total']     = (int)$db->query("SELECT COUNT(*) FROM clicks")->fetchColumn();
$stats['revenue_total']    = (float)$db->query("SELECT COALESCE(SUM(cost),0) FROM impressions WHERE is_fraud=0")->fetchColumn();
$stats['fraud_total']      = (int)$db->query("SELECT COUNT(*) FROM impressions WHERE is_fraud=1")->fetchColumn();
$stats['blacklisted']      = (int)$db->query("SELECT COUNT(*) FROM ip_blacklist WHERE expires_at IS NULL OR expires_at > NOW()")->fetchColumn();
$stats['pending_review']   = (int)$db->query("SELECT COUNT(*) FROM ad_banners WHERE status='pending_review'")->fetchColumn()
                           + (int)$db->query("SELECT COUNT(*) FROM ad_units WHERE status='pending_review'")->fetchColumn();
$stats['total_balance']    = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM balances WHERE currency='BTC'")->fetchColumn();
$stats['referrals_total']  = (int)$db->query("SELECT COUNT(*) FROM referrals")->fetchColumn();

// Cron letzte Ausführung
$lastCron = '';
$logFile  = BASE_PATH . '/storage/logs/cron.log';
if (file_exists($logFile)) {
    $lines = array_filter(explode("\n", file_get_contents($logFile)));
    $last  = end($lines);
    $lastCron = $last ? substr($last, 0, 60) : 'No entries';
}

// Git Info
$gitBranch  = trim(shell_exec('cd ' . BASE_PATH . ' && git rev-parse --abbrev-ref HEAD 2>/dev/null') ?? '');
$gitCommit  = trim(shell_exec('cd ' . BASE_PATH . ' && git log -1 --format="%h %s" 2>/dev/null') ?? '');
$gitDate    = trim(shell_exec('cd ' . BASE_PATH . ' && git log -1 --format="%ci" 2>/dev/null') ?? '');

// Upload-Ordner
$uploadDir  = BASE_PATH . '/public/uploads/banners/';
$uploadFiles= is_dir($uploadDir) ? count(glob($uploadDir . '*')) : 0;
$uploadSize = 0;
if (is_dir($uploadDir)) {
    foreach (glob($uploadDir . '*') as $f) $uploadSize += filesize($f);
}
?>

<!-- Metric Cards -->
<div class="admin-metrics" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px">
  <div class="metric">
    <div class="metric-label">Total Users</div>
    <div class="metric-val"><?= number_format($stats['users_total']) ?></div>
    <div class="metric-sub">+<?= $stats['users_today'] ?> today</div>
  </div>
  <div class="metric">
    <div class="metric-label">Active Campaigns</div>
    <div class="metric-val"><?= $stats['campaigns_active'] ?></div>
    <div class="metric-sub"><?= $stats['campaigns_total'] ?> total</div>
  </div>
  <div class="metric">
    <div class="metric-label">Total Impressions</div>
    <div class="metric-val"><?= number_format($stats['impressions_total']) ?></div>
    <div class="metric-sub"><?= number_format($stats['impressions_today']) ?> today</div>
  </div>
  <div class="metric">
    <div class="metric-label">Total Revenue</div>
    <div class="metric-val green"><?= number_format($stats['revenue_total'], 8) ?></div>
    <div class="metric-sub">BTC gross</div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">

<!-- Umgebung -->
<div class="admin-section">
  <div class="section-bar" style="padding:14px 20px"><h2 class="section-title">Environment</h2></div>
  <div style="padding:16px 20px">
    <?php foreach ([
      ['PHP Version',     $phpVersion,  version_compare($phpVersion, '8.2', '>=') ? 'green' : 'red'],
      ['App Environment', $appEnv,      $appEnv === 'production' ? 'green' : 'yellow'],
      ['Debug Mode',      $appDebug ? 'ON' : 'OFF', $appDebug ? 'red' : 'green'],
      ['App URL',         $appUrl,      ''],
      ['Memory Limit',    $memLimit,    ''],
      ['Upload Max',      $uploadMax,   ''],
      ['Max Exec Time',   $maxExec . 's', ''],
      ['Disk Usage',      $diskUsedPct . '% (' . round($diskFree/1024/1024/1024, 1) . ' GB free)', $diskUsedPct > 80 ? 'red' : 'green'],
    ] as [$label, $value, $color]): ?>
    <div class="summary-row">
      <span class="summary-label"><?= $label ?></span>
      <span class="summary-val <?= $color ?>" style="font-family:'DM Mono',monospace;font-size:12px"><?= htmlspecialchars($value) ?></span>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- API Status -->
<div class="admin-section">
  <div class="section-bar" style="padding:14px 20px"><h2 class="section-title">API & Services</h2></div>
  <div style="padding:16px 20px">
    <?php foreach ([
      ['NOWPayments',    $npOk,    $npOk ? 'Connected' : 'Error', empty($npKey) ? 'Not configured' : ''],
      ['Anthropic (AI)', $anOk,    $anOk ? 'Key set' : 'Not configured', ''],
      ['IPN Webhook',    !empty($_ENV['NOWPAYMENTS_IPN_SECRET']), 'Secret set', 'Not configured'],
      ['App Secret',     !empty($_ENV['APP_SECRET']), 'Set', 'Missing!'],
    ] as [$name, $ok, $okText, $failText]): ?>
    <div class="summary-row">
      <span class="summary-label"><?= $name ?></span>
      <span style="font-size:12px;color:<?= $ok ? '#3ecf8e' : '#e05454' ?>">
        <?= $ok ? '✓ ' . $okText : '✗ ' . ($failText ?: 'Error') ?>
      </span>
    </div>
    <?php endforeach; ?>

    <div class="summary-divider"></div>

    <?php foreach ([
      ['Active Ad Units',   $stats['units_active']],
      ['Active Banners',    $stats['banners_active']],
      ['Pending Review',    $stats['pending_review']],
      ['Fraud Blocked IPs', $stats['blacklisted']],
      ['Referrals',         $stats['referrals_total']],
      ['Total BTC Balance', number_format($stats['total_balance'], 8) . ' BTC'],
      ['Banner Uploads',    $uploadFiles . ' files (' . round($uploadSize/1024, 1) . ' KB)'],
    ] as [$label, $value]): ?>
    <div class="summary-row">
      <span class="summary-label"><?= $label ?></span>
      <span class="summary-val" style="font-family:'DM Mono',monospace;font-size:12px"><?= htmlspecialchars((string)$value) ?></span>
    </div>
    <?php endforeach; ?>
  </div>
</div>

</div><!-- /grid -->

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">

<!-- Git Info -->
<div class="admin-section">
  <div class="section-bar" style="padding:14px 20px"><h2 class="section-title">Deployment</h2></div>
  <div style="padding:16px 20px">
    <?php foreach ([
      ['Branch',         $gitBranch ?: 'N/A'],
      ['Last Commit',    $gitCommit ?: 'N/A'],
      ['Commit Date',    $gitDate   ?: 'N/A'],
      ['Last Cron',      $lastCron  ?: 'No log'],
    ] as [$label, $value]): ?>
    <div class="summary-row" style="align-items:flex-start">
      <span class="summary-label" style="flex-shrink:0"><?= $label ?></span>
      <span class="summary-val" style="font-family:'DM Mono',monospace;font-size:11px;word-break:break-all;text-align:right"><?= htmlspecialchars($value) ?></span>
    </div>
    <?php endforeach; ?>
    <div class="summary-divider"></div>
    <form method="POST" action="/admin/system/clear-cache" style="display:inline">
      <button class="btn-ghost-sm">Clear OPcache</button>
    </form>
  </div>
</div>

<!-- Datenbank -->
<div class="admin-section">
  <div class="section-bar" style="padding:14px 20px"><h2 class="section-title">Database</h2></div>
  <div style="padding:16px 20px">
    <div class="summary-row">
      <span class="summary-label">MySQL Version</span>
      <span class="summary-val" style="font-family:'DM Mono',monospace;font-size:12px"><?= htmlspecialchars($dbVersion) ?></span>
    </div>
    <div class="summary-row">
      <span class="summary-label">DB Size</span>
      <span class="summary-val" style="font-family:'DM Mono',monospace;font-size:12px"><?= $dbSize ?> MB</span>
    </div>
    <div class="summary-divider"></div>
    <div style="max-height:200px;overflow-y:auto">
    <?php foreach ($tableRows as $t): ?>
    <div style="display:flex;justify-content:space-between;padding:4px 0;border-bottom:0.5px solid rgba(255,255,255,0.04);font-size:11px">
      <span style="font-family:'DM Mono',monospace;color:rgba(255,255,255,0.5)"><?= htmlspecialchars($t['table_name']) ?></span>
      <span style="color:rgba(255,255,255,0.3)"><?= number_format((int)$t['table_rows']) ?> rows · <?= $t['size_kb'] ?> KB</span>
    </div>
    <?php endforeach; ?>
    </div>
  </div>
</div>

</div><!-- /grid -->

<!-- Features Status -->
<div class="admin-section">
  <div class="section-bar" style="padding:14px 20px"><h2 class="section-title">Feature Status</h2></div>
  <div style="padding:16px 20px">
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px">
      <?php
      $refSettings = $db->query('SELECT * FROM referral_settings WHERE id=1 LIMIT 1')->fetch();
      $features = [
        ['Ad Serving',         true,                              'Core feature'],
        ['Fraud Detection',    true,                              'Active'],
        ['NOWPayments',        $npOk,                             $npOk ? 'Connected' : 'Check API key'],
        ['AI Banner Generator',$refSettings['ai_banner_enabled'] ?? 0, 'Price: ' . number_format((float)($refSettings['ai_banner_price'] ?? 0), 8) . ' BTC'],
        ['Referral System',    $refSettings['is_active'] ?? 0,   'L1: ' . ($refSettings['level1_pct'] ?? 0) . '% / L2: ' . ($refSettings['level2_pct'] ?? 0) . '% / L3: ' . ($refSettings['level3_pct'] ?? 0) . '%'],
        ['MetaMask Login',     true,                              'SIWE enabled'],
        ['Cron Jobs',          file_exists(BASE_PATH . '/storage/logs/cron.log'), 'Check /admin/crons'],
        ['SEO',                true,                              'Sitemap + OG Image'],
      ];
      foreach ($features as [$name, $active, $desc]): ?>
      <div style="background:#080c10;border:0.5px solid rgba(255,255,255,<?= $active ? '0.1' : '0.05' ?>);border-radius:10px;padding:14px">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
          <span style="width:8px;height:8px;border-radius:50%;background:<?= $active ? '#3ecf8e' : '#e05454' ?>;flex-shrink:0"></span>
          <span style="font-size:13px;font-weight:500;color:<?= $active ? '#fff' : 'rgba(255,255,255,0.4)' ?>"><?= htmlspecialchars($name) ?></span>
        </div>
        <div style="font-size:11px;color:rgba(255,255,255,0.3)"><?= htmlspecialchars($desc) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
