<?php $active = 'stats'; ?>

<div class="page-header">
  <h1 class="page-title">System Overview</h1>
  <span style="font-size:12px;color:rgba(255,255,255,0.3);font-family:'DM Mono',monospace"><?= date('d.m.Y H:i') ?></span>
</div>

<!-- Pending Reviews Alert -->
<?php if ($stats['pending_units'] + $stats['pending_campaigns'] + $stats['pending_banners'] > 0): ?>
<div class="alert-warning">
  &#9888; Pending reviews:
  <?php if ($stats['pending_units']): ?><a href="/admin/review/units"><?= $stats['pending_units'] ?> ad units</a><?php endif; ?>
  <?php if ($stats['pending_banners']): ?><a href="/admin/review/banners"><?= $stats['pending_banners'] ?> banners</a><?php endif; ?>
</div>
<?php endif; ?>

<!-- Metric Cards -->
<div class="admin-metrics">
  <div class="metric"><div class="metric-label">Impressions today</div><div class="metric-val"><?= number_format($stats['impressions_today']) ?></div><div class="metric-sub">total: <?= number_format($stats['impressions_total']) ?></div></div>
  <div class="metric"><div class="metric-label">Clicks today</div><div class="metric-val"><?= number_format($stats['clicks_today']) ?></div><div class="metric-sub">CTR: <?= $stats['ctr_today'] ?>%</div></div>
  <div class="metric"><div class="metric-label">Revenue today</div><div class="metric-val green"><?= number_format($stats['revenue_today'], 8) ?></div><div class="metric-sub">BTC total: <?= number_format($stats['revenue_total'], 8) ?></div></div>
  <div class="metric"><div class="metric-label">Fraud today</div><div class="metric-val red"><?= number_format($stats['fraud_today']) ?></div><div class="metric-sub">blocked impressions</div></div>
  <div class="metric"><div class="metric-label">Users total</div><div class="metric-val"><?= number_format($stats['users_total']) ?></div><div class="metric-sub">+<?= $stats['users_today'] ?> today</div></div>
  <div class="metric"><div class="metric-label">Pending review</div><div class="metric-val <?= ($stats['pending_units'] + $stats['pending_banners']) > 0 ? 'red' : '' ?>"><?= $stats['pending_units'] + $stats['pending_banners'] ?></div><div class="metric-sub">units + banners</div></div>
</div>

<!-- 7-Day Chart -->
<div class="admin-section">
  <div class="section-bar"><h2 class="section-title">Last 7 days</h2></div>
  <div class="chart-wrap">
    <?php
    $maxVal = max(1, max(array_column($daily, 'cnt') ?: [1]));
    foreach ($daily as $d):
      $pct  = round(($d['cnt'] / $maxVal) * 100);
      $fpct = $d['cnt'] > 0 ? round(($d['fraud'] / $d['cnt']) * 100) : 0;
    ?>
    <div class="chart-col">
      <div class="chart-bar-wrap">
        <div class="chart-bar" style="height:<?= $pct ?>%">
          <div class="chart-bar-fraud" style="height:<?= $fpct ?>%"></div>
        </div>
      </div>
      <div class="chart-label"><?= date('d.m', strtotime($d['day'])) ?></div>
      <div class="chart-val"><?= number_format($d['cnt']) ?></div>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="chart-legend">
    <span class="legend-dot green"></span> Valid impressions
    <span class="legend-dot red" style="margin-left:16px"></span> Fraud
  </div>
</div>

<!-- Recent Fraud -->
<div class="admin-section">
  <div class="section-bar">
    <h2 class="section-title">Recent fraud signals</h2>
    <a href="/admin/fraud" class="btn-ghost-sm">View all →</a>
  </div>
  <?php if (empty($fraudLogs)): ?>
  <div class="empty-state"><p>No fraud detected yet.</p></div>
  <?php else: ?>
  <div class="data-table">
    <div class="dt-header" style="grid-template-columns:140px 1fr 80px 80px">
      <div>Time</div><div>Signals</div><div>Score</div><div>Action</div>
    </div>
    <?php foreach ($fraudLogs as $log):
      $signals = json_decode($log['signals'], true) ?? [];
      $active_signals = array_keys(array_filter($signals));
    ?>
    <div class="dt-row" style="grid-template-columns:140px 1fr 80px 80px">
      <div class="dt-muted" style="font-family:'DM Mono',monospace;font-size:11px"><?= $log['ts'] ?></div>
      <div style="display:flex;gap:6px;flex-wrap:wrap">
        <?php foreach ($active_signals as $sig): ?>
        <span class="badge badge-red" style="font-size:10px"><?= htmlspecialchars(str_replace('_',' ',$sig)) ?></span>
        <?php endforeach; ?>
      </div>
      <div class="dt-muted" style="font-family:'DM Mono',monospace"><?= number_format((float)$log['score'], 2) ?></div>
      <div><?= $log['action'] === 'block' ? '<span class="badge badge-red">block</span>' : '<span class="badge badge-yellow">flag</span>' ?></div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
