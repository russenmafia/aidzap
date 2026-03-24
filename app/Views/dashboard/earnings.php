<?php $active = 'earnings'; ?>

<div class="page-header">
  <h1 class="page-title">Earnings</h1>
  <a href="/publisher/withdraw" class="btn-add">Withdraw →</a>
</div>

<!-- Metric Cards -->
<div class="metrics" style="grid-template-columns:repeat(5,1fr);margin-bottom:28px">
  <div class="metric">
    <div class="metric-label">Total earned</div>
    <div class="metric-val green"><?= number_format((float)$totals['total_earned'], 8) ?></div>
    <div class="metric-sub">BTC lifetime</div>
  </div>
  <div class="metric">
    <div class="metric-label">Pending payout</div>
    <div class="metric-val <?= $pending > 0 ? 'green' : '' ?>"><?= number_format($pending, 8) ?></div>
    <div class="metric-sub">BTC awaiting</div>
  </div>
  <div class="metric">
    <div class="metric-label">Available balance</div>
    <div class="metric-val"><?= number_format($balance, 8) ?></div>
    <div class="metric-sub">BTC withdrawable</div>
  </div>
  <div class="metric">
    <div class="metric-label">Total impressions</div>
    <div class="metric-val"><?= number_format((int)$totals['total_impressions']) ?></div>
    <div class="metric-sub">all time</div>
  </div>
  <div class="metric">
    <div class="metric-label">Total clicks</div>
    <div class="metric-val"><?= number_format((int)$totals['total_clicks']) ?></div>
    <div class="metric-sub">
      CTR: <?= $totals['total_impressions'] > 0
        ? number_format(($totals['total_clicks'] / $totals['total_impressions']) * 100, 2) . '%'
        : '–' ?>
    </div>
  </div>
</div>

<!-- 30-Day Chart -->
<div class="unit-card" style="margin-bottom:20px">
  <div class="unit-header">
    <div class="dt-name">Last 30 days</div>
  </div>
  <div style="padding:20px">
    <?php if (empty($daily)): ?>
    <p style="color:rgba(255,255,255,0.3);font-size:13px">No earnings data yet.</p>
    <?php else: ?>
    <div style="display:flex;align-items:flex-end;gap:4px;height:120px;overflow:hidden">
      <?php
      $maxVal = max(1, max(array_column($daily, 'earned')));
      // Fill missing days
      $dailyMap = [];
      foreach ($daily as $d) $dailyMap[$d['date']] = $d;
      for ($i = 29; $i >= 0; $i--):
        $date = date('Y-m-d', strtotime("-{$i} days"));
        $d    = $dailyMap[$date] ?? ['earned' => 0, 'impressions' => 0, 'clicks' => 0];
        $pct  = round(($d['earned'] / $maxVal) * 100);
      ?>
      <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;min-width:0" title="<?= $date ?>: <?= number_format((float)$d['earned'],8) ?> BTC">
        <div style="width:100%;background:<?= $d['earned'] > 0 ? 'rgba(62,207,142,0.4)' : 'rgba(255,255,255,0.04)' ?>;border-radius:3px 3px 0 0;height:<?= max(2,$pct) ?>%;transition:height .3s"></div>
      </div>
      <?php endfor; ?>
    </div>
    <div style="display:flex;justify-content:space-between;margin-top:6px;font-size:10px;color:rgba(255,255,255,0.25);font-family:'DM Mono',monospace">
      <span><?= date('d.m', strtotime('-29 days')) ?></span>
      <span><?= date('d.m', strtotime('-14 days')) ?></span>
      <span><?= date('d.m') ?></span>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Per Unit -->
<div class="unit-card" style="margin-bottom:20px">
  <div class="unit-header"><div class="dt-name">Earnings by Ad Unit</div></div>
  <?php if (empty($byUnit)): ?>
  <div style="padding:20px"><p style="color:rgba(255,255,255,0.3);font-size:13px">No ad units yet.</p></div>
  <?php else: ?>
  <div class="data-table" style="border:none;border-radius:0">
    <div class="dt-header" style="grid-template-columns:2fr 80px 80px 100px 120px 80px 90px">
      <div>Unit</div><div>Type</div><div>Size</div><div>Impressions</div><div>Earned (BTC)</div><div>CTR</div><div>Status</div>
    </div>
    <?php foreach ($byUnit as $u): ?>
    <div class="dt-row" style="grid-template-columns:2fr 80px 80px 100px 120px 80px 90px">
      <div>
        <div class="dt-name"><?= htmlspecialchars($u['name']) ?></div>
        <div class="dt-sub"><?= $u['last_activity'] ? date('d.m.Y', strtotime($u['last_activity'])) : 'No activity' ?></div>
      </div>
      <div><span class="badge badge-gray"><?= htmlspecialchars(ucfirst($u['type'] ?? 'banner')) ?></span></div>
      <div class="dt-muted"><?= htmlspecialchars($u['size'] ?? '–') ?></div>
      <div class="dt-muted"><?= number_format((int)$u['impressions']) ?></div>
      <div class="dt-green"><?= number_format((float)$u['earned'], 8) ?></div>
      <div class="dt-muted">
        <?= $u['impressions'] > 0
          ? number_format(($u['clicks'] / $u['impressions']) * 100, 2) . '%'
          : '–' ?>
      </div>
      <div><?= statusBadge($u['status']) ?></div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- Transaction History -->
<div class="unit-card">
  <div class="unit-header"><div class="dt-name">Transaction History</div></div>
  <?php if (empty($transactions)): ?>
  <div style="padding:20px"><p style="color:rgba(255,255,255,0.3);font-size:13px">No transactions yet.</p></div>
  <?php else: ?>
  <div class="data-table" style="border:none;border-radius:0">
    <div class="dt-header" style="grid-template-columns:100px 1fr 80px 80px 120px 80px">
      <div>Date</div><div>Unit</div><div>Impr.</div><div>Clicks</div><div>Earned</div><div>Status</div>
    </div>
    <?php foreach ($transactions as $t): ?>
    <div class="dt-row" style="grid-template-columns:100px 1fr 80px 80px 120px 80px">
      <div class="dt-muted" style="font-family:'DM Mono',monospace;font-size:11px"><?= date('d.m.Y', strtotime($t['date'])) ?></div>
      <div class="dt-muted"><?= htmlspecialchars($t['unit_name']) ?></div>
      <div class="dt-muted"><?= number_format((int)$t['impressions']) ?></div>
      <div class="dt-muted"><?= number_format((int)$t['clicks']) ?></div>
      <div class="dt-green"><?= number_format((float)$t['amount'], 8) ?> BTC</div>
      <div><?= $t['status'] === 'confirmed' ? '<span class="badge badge-green">Confirmed</span>' : '<span class="badge badge-yellow">Pending</span>' ?></div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php
function statusBadge(string $status): string {
    $map = ['active'=>['green','Active'],'pending_review'=>['yellow','Review'],'draft'=>['gray','Draft'],'paused'=>['gray','Paused'],'rejected'=>['red','Rejected']];
    [$c,$l] = $map[$status] ?? ['gray',ucfirst($status)];
    return "<span class='badge badge-{$c}'>{$l}</span>";
}
?>
