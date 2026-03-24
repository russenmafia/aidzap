<?php $active = 'crons'; ?>

<div class="page-header">
  <h1 class="page-title">Cron Jobs</h1>
  <span style="font-size:12px;color:rgba(255,255,255,0.3);font-family:'DM Mono',monospace"><?= date('d.m.Y H:i:s') ?></span>
</div>

<?php if (isset($_GET['ran'])): ?>
<div class="flash flash-success">Job "<?= htmlspecialchars($_GET['ran']) ?>" wurde ausgeführt. Siehe Log unten.</div>
<?php endif; ?>

<!-- Status Cards -->
<div class="admin-metrics" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px">
  <?php
  $db = \Core\Database::getInstance();
  $lastStats   = $db->query("SELECT MAX(date) FROM daily_stats")->fetchColumn();
  $lastBudget  = $db->query("SELECT COUNT(*) FROM campaigns WHERE status='completed' AND DATE(updated_at)=CURDATE()")->fetchColumn();
  $blacklisted = $db->query("SELECT COUNT(*) FROM ip_blacklist WHERE expires_at IS NULL OR expires_at > NOW()")->fetchColumn();
  $pendingPay  = $db->query("SELECT COUNT(*) FROM payment_invoices WHERE status IN ('waiting','confirming')")->fetchColumn();
  ?>
  <div class="metric">
    <div class="metric-label">Last daily stats</div>
    <div class="metric-val" style="font-size:16px"><?= $lastStats ? date('d.m.Y', strtotime($lastStats)) : 'Never' ?></div>
    <div class="metric-sub">daily-stats job</div>
  </div>
  <div class="metric">
    <div class="metric-label">Completed today</div>
    <div class="metric-val"><?= (int)$lastBudget ?></div>
    <div class="metric-sub">campaigns finished</div>
  </div>
  <div class="metric">
    <div class="metric-label">IP blacklist</div>
    <div class="metric-val <?= $blacklisted > 0 ? 'red' : '' ?>"><?= (int)$blacklisted ?></div>
    <div class="metric-sub">active entries</div>
  </div>
  <div class="metric">
    <div class="metric-label">Pending payments</div>
    <div class="metric-val <?= $pendingPay > 0 ? 'green' : '' ?>"><?= (int)$pendingPay ?></div>
    <div class="metric-sub">awaiting confirmation</div>
  </div>
</div>

<!-- Cron Jobs Tabelle -->
<div class="admin-section">
  <div class="section-bar" style="padding:16px 20px">
    <h2 class="section-title">Jobs</h2>
  </div>

  <?php
  $phpBin  = PHP_BINARY ?: '/usr/bin/php';
  $cronDir = BASE_PATH . '/cron/runner.php';
  $logFile = STORAGE_PATH . '/logs/cron.log';

  $jobs = [
    [
      'name'        => 'daily-stats',
      'label'       => 'Daily Stats',
      'desc'        => 'Aggregiert Impressions, Clicks und Revenue pro Tag in die daily_stats Tabelle.',
      'schedule'    => '5 0 * * *',
      'schedule_hr' => 'Täglich 00:05 Uhr',
      'cron_cmd'    => "5 0 * * * {$phpBin} {$cronDir} daily-stats >> {$logFile} 2>&1",
    ],
    [
      'name'        => 'budget-reset',
      'label'       => 'Budget Reset',
      'desc'        => 'Pausiert erschöpfte Campaigns, setzt abgelaufene auf completed, bestätigt alte Earnings.',
      'schedule'    => '1 0 * * *',
      'schedule_hr' => 'Täglich 00:01 Uhr',
      'cron_cmd'    => "1 0 * * * {$phpBin} {$cronDir} budget-reset >> {$logFile} 2>&1",
    ],
    [
      'name'        => 'fraud-cleanup',
      'label'       => 'Fraud Cleanup',
      'desc'        => 'Entfernt abgelaufene Blacklist-Einträge, auto-bannt High-Fraud IPs, löscht alte Logs.',
      'schedule'    => '0 3 * * *',
      'schedule_hr' => 'Täglich 03:00 Uhr',
      'cron_cmd'    => "0 3 * * * {$phpBin} {$cronDir} fraud-cleanup >> {$logFile} 2>&1",
    ],
    [
      'name'        => 'payment-check',
      'label'       => 'Payment Check',
      'desc'        => 'Prüft offene NOWPayments Invoices und schreibt bestätigte Deposits gut.',
      'schedule'    => '*/5 * * * *',
      'schedule_hr' => 'Alle 5 Minuten',
      'cron_cmd'    => "*/5 * * * * {$phpBin} {$cronDir} payment-check >> {$logFile} 2>&1",
    ],
  ];
  ?>

  <div class="data-table">
    <div class="dt-header" style="grid-template-columns:140px 1fr 160px 120px">
      <div>Job</div><div>Description</div><div>Schedule</div><div>Action</div>
    </div>
    <?php foreach ($jobs as $job): ?>
    <div class="dt-row" style="grid-template-columns:140px 1fr 160px 120px;align-items:start;padding:16px 18px">
      <div>
        <div class="dt-name"><?= htmlspecialchars($job['label']) ?></div>
        <div class="dt-sub" style="font-family:'DM Mono',monospace;font-size:10px"><?= htmlspecialchars($job['name']) ?></div>
      </div>
      <div class="dt-muted" style="font-size:12px;line-height:1.6"><?= htmlspecialchars($job['desc']) ?></div>
      <div>
        <div style="font-size:12px;color:#fff"><?= htmlspecialchars($job['schedule_hr']) ?></div>
        <div style="font-family:'DM Mono',monospace;font-size:10px;color:rgba(255,255,255,0.25);margin-top:3px"><?= htmlspecialchars($job['schedule']) ?></div>
      </div>
      <div>
        <form method="POST" action="/admin/crons/run">
          <input type="hidden" name="job" value="<?= htmlspecialchars($job['name']) ?>">
          <button class="btn-approve" style="font-size:12px;padding:6px 14px">&#9654; Run now</button>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Crontab Setup -->
<div class="admin-section">
  <div class="section-bar" style="padding:16px 20px">
    <h2 class="section-title">Crontab setup</h2>
  </div>
  <div style="padding:16px 20px">
    <p style="font-size:13px;color:rgba(255,255,255,0.4);margin-bottom:16px">
      Füge diese Zeilen in die Crontab ein: <code style="background:rgba(255,255,255,0.06);padding:2px 6px;border-radius:4px;font-family:'DM Mono',monospace">crontab -e</code>
    </p>

    <div class="cron-tab-group">
      <button class="cron-tab active" onclick="showCronTab('php',this)">PHP CLI</button>
      <button class="cron-tab" onclick="showCronTab('curl',this)">cURL</button>
      <button class="cron-tab" onclick="showCronTab('wget',this)">wget</button>
    </div>

    <div id="cron-php" class="cron-block">
      <?php foreach ($jobs as $job): ?>
      <div class="cron-line">
        <code><?= htmlspecialchars($job['cron_cmd']) ?></code>
        <button class="copy-btn" onclick="navigator.clipboard.writeText(this.dataset.code)" data-code="<?= htmlspecialchars($job['cron_cmd']) ?>">Copy</button>
      </div>
      <?php endforeach; ?>
      <div class="cron-copy-all">
        <button class="btn-ghost-sm" onclick="copyAll('php')">Copy all</button>
      </div>
    </div>

    <div id="cron-curl" class="cron-block" style="display:none">
      <?php foreach ($jobs as $job): ?>
      <?php $curlCmd = str_replace(
        "{$phpBin} {$cronDir} {$job['name']} >> {$logFile} 2>&1",
        "curl -s https://aidzap.com/admin/crons/run-http?job={$job['name']}&secret=" . ($_ENV['APP_SECRET'] ?? 'SECRET') . " >/dev/null",
        $job['cron_cmd']
      ) ?>
      <div class="cron-line">
        <code><?= htmlspecialchars($curlCmd) ?></code>
        <button class="copy-btn" onclick="navigator.clipboard.writeText(this.dataset.code)" data-code="<?= htmlspecialchars($curlCmd) ?>">Copy</button>
      </div>
      <?php endforeach; ?>
    </div>

    <div id="cron-wget" class="cron-block" style="display:none">
      <?php foreach ($jobs as $job): ?>
      <?php $wgetCmd = str_replace(
        "{$phpBin} {$cronDir} {$job['name']} >> {$logFile} 2>&1",
        "wget -O - -q https://aidzap.com/admin/crons/run-http?job={$job['name']}&secret=" . ($_ENV['APP_SECRET'] ?? 'SECRET') . " >/dev/null",
        $job['cron_cmd']
      ) ?>
      <div class="cron-line">
        <code><?= htmlspecialchars($wgetCmd) ?></code>
        <button class="copy-btn" onclick="navigator.clipboard.writeText(this.dataset.code)" data-code="<?= htmlspecialchars($wgetCmd) ?>">Copy</button>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Cron Log -->
<div class="admin-section">
  <div class="section-bar" style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between">
    <h2 class="section-title">Cron log</h2>
    <a href="/admin/crons?clear=1" class="btn-ghost-sm" onclick="return confirm('Clear log?')">Clear log</a>
  </div>
  <div style="padding:0 20px 20px">
    <?php
    if (isset($_GET['clear']) && file_exists($logFile)) {
        file_put_contents($logFile, '');
    }
    $logContent = file_exists($logFile) ? file_get_contents($logFile) : '';
    $logLines   = $logContent ? array_slice(array_filter(explode("\n", $logContent)), -100) : [];
    ?>
    <?php if (empty($logLines)): ?>
    <p style="color:rgba(255,255,255,0.3);font-size:13px">No log entries yet. Run a job to see output here.</p>
    <?php else: ?>
    <pre style="font-family:'DM Mono',monospace;font-size:11px;color:#3ecf8e;background:#080c10;border:0.5px solid rgba(255,255,255,0.07);border-radius:8px;padding:14px 16px;max-height:300px;overflow:auto;line-height:1.7;white-space:pre-wrap"><?= htmlspecialchars(implode("\n", array_reverse($logLines))) ?></pre>
    <?php endif; ?>
  </div>
</div>

<script>
function showCronTab(tab, btn) {
  document.querySelectorAll('.cron-block').forEach(b => b.style.display = 'none');
  document.querySelectorAll('.cron-tab').forEach(b => b.classList.remove('active'));
  document.getElementById('cron-' + tab).style.display = 'block';
  btn.classList.add('active');
}

function copyAll(tab) {
  const lines = [...document.querySelectorAll('#cron-' + tab + ' code')].map(c => c.textContent).join("\n");
  navigator.clipboard.writeText(lines).then(() => alert('All cron commands copied!'));
}
</script>
