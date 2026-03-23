<?php $active = 'fraud'; ?>

<div class="page-header">
  <h1 class="page-title">Fraud & Security</h1>
</div>

<?php if (isset($_GET['done'])): ?>
<div class="flash flash-success">IP removed from blacklist.</div>
<?php endif; ?>

<!-- IP Blacklist -->
<div class="admin-section">
  <div class="section-bar">
    <h2 class="section-title">IP Blacklist (<?= count($blacklist) ?>)</h2>
  </div>
  <?php if (empty($blacklist)): ?>
  <div class="empty-state"><p>No IPs blacklisted.</p></div>
  <?php else: ?>
  <div class="data-table">
    <div class="dt-header" style="grid-template-columns:1fr 1fr 80px 100px 80px">
      <div>IP Hash</div><div>Reason</div><div>Auto</div><div>Expires</div><div>Action</div>
    </div>
    <?php foreach ($blacklist as $b): ?>
    <div class="dt-row" style="grid-template-columns:1fr 1fr 80px 100px 80px">
      <div class="dt-muted" style="font-family:'DM Mono',monospace;font-size:11px"><?= htmlspecialchars(substr($b['ip_hash'],0,16)) ?>…</div>
      <div class="dt-muted"><?= htmlspecialchars($b['reason'] ?? '–') ?></div>
      <div><?= $b['auto_banned'] ? '<span class="badge badge-yellow">auto</span>' : '<span class="badge badge-gray">manual</span>' ?></div>
      <div class="dt-muted" style="font-size:11px"><?= $b['exp'] ?? 'permanent' ?></div>
      <div>
        <form method="POST" action="/admin/fraud/unblacklist">
          <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
          <button class="action-btn">Remove</button>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- Fraud Logs -->
<div class="admin-section">
  <div class="section-bar"><h2 class="section-title">Fraud Log (last 100)</h2></div>
  <?php if (empty($logs)): ?>
  <div class="empty-state"><p>No fraud logs yet.</p></div>
  <?php else: ?>
  <div class="data-table">
    <div class="dt-header" style="grid-template-columns:130px 60px 1fr 80px">
      <div>Time</div><div>Score</div><div>Signals</div><div>Action</div>
    </div>
    <?php foreach ($logs as $log):
      $signals = json_decode($log['signals'], true) ?? [];
      $active_signals = array_keys(array_filter($signals));
    ?>
    <div class="dt-row" style="grid-template-columns:130px 60px 1fr 80px">
      <div class="dt-muted" style="font-family:'DM Mono',monospace;font-size:11px"><?= $log['ts'] ?></div>
      <div style="font-family:'DM Mono',monospace;color:<?= (float)$log['score'] >= 0.75 ? '#e05454' : '#fac775' ?>;font-size:12px"><?= number_format((float)$log['score'],2) ?></div>
      <div style="display:flex;gap:4px;flex-wrap:wrap">
        <?php foreach ($active_signals as $sig): ?>
        <span class="badge badge-red" style="font-size:10px;padding:2px 6px"><?= htmlspecialchars(str_replace('_',' ',$sig)) ?></span>
        <?php endforeach; ?>
      </div>
      <div><?= $log['action'] === 'block' ? '<span class="badge badge-red">block</span>' : '<span class="badge badge-yellow">flag</span>' ?></div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
