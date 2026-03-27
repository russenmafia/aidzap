<?php $active = 'finance'; ?>
<?php
// ── Pre-compute KPIs ─────────────────────────────────────────────────────────
$margin_today   = (float)$today['revenue'] - $payouts_today - $ref_today;
$margin_pct     = (float)$today['revenue'] > 0
    ? round($margin_today / (float)$today['revenue'] * 100, 1)
    : 0;
$revenue_per_imp = (int)$today['impressions'] > 0
    ? (float)$today['revenue'] / (int)$today['impressions']
    : 0.0;
$cost_per_imp    = (int)$today['impressions'] > 0
    ? ($payouts_today + $ref_today) / (int)$today['impressions']
    : 0.0;
$net_per_imp     = $revenue_per_imp - $cost_per_imp;

$margin_monthly  = (float)$monthly['revenue'] - $payouts_monthly - $ref_monthly;
$margin_pct_m    = (float)$monthly['revenue'] > 0
    ? round($margin_monthly / (float)$monthly['revenue'] * 100, 1)
    : 0;
?>

<div class="page-header">
  <h1 class="page-title">Finance Dashboard</h1>
  <span style="font-size:12px;color:rgba(255,255,255,0.3);font-family:'DM Mono',monospace"><?= date('d.m.Y H:i') ?></span>
</div>

<!-- ── Per-Impression KPI Box ─────────────────────────────────────────────── -->
<div class="admin-section" style="margin-bottom:24px">
  <div class="section-bar"><h2 class="section-title">Today – Per Impression Metrics</h2></div>
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;padding:16px 0">
    <div style="background:rgba(255,255,255,0.04);border-radius:10px;padding:20px;text-align:center">
      <div style="font-size:11px;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px">Revenue / Impression</div>
      <div style="font-size:20px;font-family:'DM Mono',monospace;color:rgba(255,255,255,0.85)"><?= number_format($revenue_per_imp, 8) ?> <span style="font-size:11px;opacity:.5">BTC</span></div>
    </div>
    <div style="background:rgba(255,255,255,0.04);border-radius:10px;padding:20px;text-align:center">
      <div style="font-size:11px;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px">Cost / Impression</div>
      <div style="font-size:20px;font-family:'DM Mono',monospace;color:rgba(224,84,84,0.9)"><?= number_format($cost_per_imp, 8) ?> <span style="font-size:11px;opacity:.5">BTC</span></div>
    </div>
    <div style="background:rgba(255,255,255,0.04);border-radius:10px;padding:20px;text-align:center;border:1px solid <?= $net_per_imp >= 0 ? 'rgba(62,207,142,0.3)' : 'rgba(224,84,84,0.3)' ?>">
      <div style="font-size:11px;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px">Net / Impression</div>
      <div style="font-size:20px;font-weight:600;font-family:'DM Mono',monospace;color:<?= $net_per_imp >= 0 ? '#3ecf8e' : '#e05454' ?>"><?= number_format($net_per_imp, 8) ?> <span style="font-size:11px;opacity:.5">BTC</span></div>
    </div>
  </div>
</div>

<!-- ── Today's Summary ────────────────────────────────────────────────────── -->
<div class="section-bar" style="margin-bottom:12px"><h2 class="section-title">Today</h2></div>
<div class="admin-metrics" style="margin-bottom:32px">
  <div class="metric">
    <div class="metric-label">Advertiser Revenue</div>
    <div class="metric-val green"><?= number_format((float)$today['revenue'], 8) ?></div>
    <div class="metric-sub"><?= number_format((int)$today['impressions']) ?> impr · <?= number_format((int)$today['clicks']) ?> clicks</div>
  </div>
  <div class="metric">
    <div class="metric-label">Publisher Payouts</div>
    <div class="metric-val red"><?= number_format($payouts_today, 8) ?></div>
    <div class="metric-sub">BTC paid out</div>
  </div>
  <div class="metric">
    <div class="metric-label">Referral Payouts</div>
    <div class="metric-val" style="color:#f5a623"><?= number_format($ref_today, 8) ?></div>
    <div class="metric-sub">BTC commissions</div>
  </div>
  <div class="metric">
    <div class="metric-label">Net Margin</div>
    <div class="metric-val <?= $margin_today >= 0 ? 'green' : 'red' ?>"><?= number_format($margin_today, 8) ?></div>
    <div class="metric-sub"><?= $margin_pct ?>% of revenue</div>
  </div>
</div>

<!-- ── 30-Day Summary ─────────────────────────────────────────────────────── -->
<div class="section-bar" style="margin-bottom:12px"><h2 class="section-title">Last 30 Days</h2></div>
<div class="admin-metrics" style="margin-bottom:32px">
  <div class="metric">
    <div class="metric-label">Advertiser Revenue</div>
    <div class="metric-val green"><?= number_format((float)$monthly['revenue'], 8) ?></div>
    <div class="metric-sub"><?= number_format((int)$monthly['impressions']) ?> impr · <?= number_format((int)$monthly['clicks']) ?> clicks</div>
  </div>
  <div class="metric">
    <div class="metric-label">Publisher Payouts</div>
    <div class="metric-val red"><?= number_format($payouts_monthly, 8) ?></div>
    <div class="metric-sub">BTC paid out</div>
  </div>
  <div class="metric">
    <div class="metric-label">Referral Payouts</div>
    <div class="metric-val" style="color:#f5a623"><?= number_format($ref_monthly, 8) ?></div>
    <div class="metric-sub">BTC commissions</div>
  </div>
  <div class="metric">
    <div class="metric-label">Net Margin</div>
    <div class="metric-val <?= $margin_monthly >= 0 ? 'green' : 'red' ?>"><?= number_format($margin_monthly, 8) ?></div>
    <div class="metric-sub"><?= $margin_pct_m ?>% of revenue</div>
  </div>
</div>

<!-- ── Margin per Impression by Pricing Model ─────────────────────────────── -->
<div class="admin-section" style="margin-bottom:24px">
  <div class="section-bar"><h2 class="section-title">Margin per Impression – by Pricing Model (30d)</h2></div>
  <?php if (empty($perImpression)): ?>
  <div class="empty-state"><p>No impression data for the last 30 days.</p></div>
  <?php else: ?>
  <div class="data-table">
    <div class="dt-header" style="grid-template-columns:120px 1fr 1fr 1fr 1fr 1fr">
      <div>Model</div>
      <div>Impressions</div>
      <div>Avg Rev / Imp</div>
      <div>Publisher Share</div>
      <div>Ref Share</div>
      <div>Net / Imp</div>
    </div>
    <?php foreach ($perImpression as $row):
        $impr    = max(1, (int)$row['impressions']);
        $rev     = (float)$row['total_revenue'];
        $pub     = (float)$row['total_publisher_payout'];
        $ref     = (float)$row['total_ref_payout'];
        $net_imp = ($rev - $pub - $ref) / $impr;
        $avg_rev = (float)$row['avg_revenue_per_imp'];
    ?>
    <div class="dt-row" style="grid-template-columns:120px 1fr 1fr 1fr 1fr 1fr">
      <div><span class="badge badge-<?= $row['pricing_model'] === 'cpm' ? 'blue' : 'yellow' ?>"><?= htmlspecialchars(strtoupper($row['pricing_model'])) ?></span></div>
      <div class="dt-muted"><?= number_format($impr) ?></div>
      <div style="font-family:'DM Mono',monospace;font-size:12px"><?= number_format($avg_rev, 8) ?></div>
      <div style="font-family:'DM Mono',monospace;font-size:12px;color:#e05454"><?= number_format($pub / $impr, 8) ?></div>
      <div style="font-family:'DM Mono',monospace;font-size:12px;color:#f5a623"><?= number_format($ref / $impr, 8) ?></div>
      <div style="font-family:'DM Mono',monospace;font-size:12px;font-weight:600;color:<?= $net_imp >= 0 ? '#3ecf8e' : '#e05454' ?>"><?= number_format($net_imp, 8) ?></div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- ── Revenue Share Distribution ────────────────────────────────────────── -->
<div class="admin-section" style="margin-bottom:24px">
  <div class="section-bar"><h2 class="section-title">Revenue Share Distribution (by Quality Level)</h2></div>
  <?php if (empty($revenueShare)): ?>
  <div class="empty-state"><p>No ad units found.</p></div>
  <?php else: ?>
  <div class="data-table">
    <div class="dt-header" style="grid-template-columns:130px 80px 120px 1fr">
      <div>Level</div>
      <div>Units</div>
      <div>Revenue Share</div>
      <div>Total Paid (30d)</div>
    </div>
    <?php
    $levelColors = [
        'platinum' => '#3ecf8e',
        'gold'     => '#f5a623',
        'silver'   => '#aaaaaa',
        'bronze'   => '#cd7f32',
    ];
    foreach ($revenueShare as $row):
        $lvl   = $row['quality_level'] ?? 'bronze';
        $color = $levelColors[$lvl] ?? '#888';
    ?>
    <div class="dt-row" style="grid-template-columns:130px 80px 120px 1fr">
      <div><span style="color:<?= $color ?>;font-weight:600;text-transform:capitalize"><?= htmlspecialchars($lvl) ?></span></div>
      <div class="dt-muted"><?= number_format((int)$row['unit_count']) ?></div>
      <div><span style="font-family:'DM Mono',monospace;color:<?= $color ?>"><?= number_format((float)$row['revenue_share'], 1) ?>%</span></div>
      <div style="font-family:'DM Mono',monospace;font-size:12px;color:#3ecf8e"><?= number_format((float)$row['total_paid'], 8) ?> BTC</div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- ── Referral Cost Breakdown ────────────────────────────────────────────── -->
<div class="admin-section" style="margin-bottom:24px">
  <div class="section-bar"><h2 class="section-title">Referral Cost Breakdown (30d)</h2></div>
  <?php if (empty($refByLevel)): ?>
  <div class="empty-state"><p>No referral commissions in the last 30 days.</p></div>
  <?php else: ?>
  <div class="data-table">
    <div class="dt-header" style="grid-template-columns:80px 1fr 1fr 1fr">
      <div>Level</div>
      <div>Transactions</div>
      <div>Avg %</div>
      <div>Total Commission (30d)</div>
    </div>
    <?php foreach ($refByLevel as $row): ?>
    <div class="dt-row" style="grid-template-columns:80px 1fr 1fr 1fr">
      <div><span class="badge badge-blue"><?= htmlspecialchars((string)$row['level']) ?></span></div>
      <div class="dt-muted"><?= number_format((int)$row['transactions']) ?></div>
      <div style="font-family:'DM Mono',monospace;color:#f5a623"><?= number_format((float)$row['avg_pct'], 2) ?>%</div>
      <div style="font-family:'DM Mono',monospace;font-size:12px;color:#e05454"><?= number_format((float)$row['total_commission'], 8) ?> BTC</div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- ── Recent Transactions ────────────────────────────────────────────────── -->
<div class="admin-section">
  <div class="section-bar"><h2 class="section-title">Recent Revenue Transactions</h2></div>
  <?php if (empty($recentTransactions)): ?>
  <div class="empty-state"><p>No transactions yet.</p></div>
  <?php else: ?>
  <div class="data-table">
    <div class="dt-header" style="grid-template-columns:160px 1fr 1fr 1fr">
      <div>Time</div>
      <div>Campaign</div>
      <div>Ad Unit</div>
      <div>Amount</div>
    </div>
    <?php foreach ($recentTransactions as $tx): ?>
    <div class="dt-row" style="grid-template-columns:160px 1fr 1fr 1fr">
      <div class="dt-muted" style="font-family:'DM Mono',monospace;font-size:11px"><?= htmlspecialchars($tx['created_at']) ?></div>
      <div><?= htmlspecialchars($tx['campaign_name']) ?></div>
      <div class="dt-muted"><?= htmlspecialchars($tx['unit_name']) ?></div>
      <div style="font-family:'DM Mono',monospace;font-size:12px;color:#3ecf8e"><?= number_format((float)$tx['amount'], 8) ?> BTC</div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
