<?php $active = 'review_campaigns'; ?>
<div class="page-header">
  <h1 class="page-title">Campaign Review</h1>
  <span style="font-size:12px;color:rgba(255,255,255,0.3)"><?= count($campaigns) ?> pending</span>
</div>

<?php if (!empty($_GET['done'])): ?>
<div style="margin-bottom:16px;padding:12px 16px;background:rgba(62,207,142,0.08);border:0.5px solid rgba(62,207,142,0.2);border-radius:8px;color:#3ecf8e;font-size:13px">
  Action completed.
</div>
<?php endif; ?>

<?php if (empty($campaigns)): ?>
<div class="unit-card" style="padding:32px;text-align:center;color:rgba(255,255,255,0.3)">
  No campaigns pending review. ✓
</div>
<?php else: ?>
<?php foreach ($campaigns as $c): ?>
<div class="unit-card" style="margin-bottom:16px">
  <div class="unit-header">
    <div>
      <div class="dt-name"><?= htmlspecialchars($c['name']) ?></div>
      <div class="dt-muted">by <?= htmlspecialchars($c['username']) ?> · <?= date('d.m.Y H:i', strtotime($c['created_at'])) ?></div>
    </div>
    <div class="unit-meta">
      <span class="badge badge-yellow">pending review</span>
    </div>
  </div>
  <div style="padding:16px 20px;display:grid;grid-template-columns:repeat(4,1fr);gap:16px;border-top:0.5px solid rgba(255,255,255,0.06)">
    <div><div class="dt-muted">Type</div><div><?= htmlspecialchars(strtoupper($c['campaign_type'] ?? $c['pricing_model'] ?? '–')) ?></div></div>
    <div><div class="dt-muted">Bid</div><div><?= number_format((float)$c['bid_amount'], 8) ?> BTC</div></div>
    <div><div class="dt-muted">Daily Budget</div><div><?= number_format((float)$c['daily_budget'], 8) ?> BTC</div></div>
    <div><div class="dt-muted">Total Budget</div><div><?= number_format((float)$c['total_budget'], 8) ?> BTC</div></div>
    <div><div class="dt-muted">Banners</div><div><?= (int)$c['banner_count'] ?></div></div>
    <div><div class="dt-muted">Starts</div><div><?= $c['starts_at'] ? date('d.m.Y', strtotime($c['starts_at'])) : 'immediately' ?></div></div>
    <div><div class="dt-muted">Ends</div><div><?= $c['ends_at'] ? date('d.m.Y', strtotime($c['ends_at'])) : 'no end date' ?></div></div>
    <div><div class="dt-muted">Target Countries</div><div><?= $c['target_countries'] ? implode(', ', json_decode($c['target_countries'], true)) : 'all' ?></div></div>
  </div>
  <div style="padding:12px 20px;display:flex;gap:12px;border-top:0.5px solid rgba(255,255,255,0.06)">
    <form method="POST" action="/admin/review/campaigns/action">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <input type="hidden" name="campaign_id" value="<?= $c['id'] ?>">
      <input type="hidden" name="action" value="approve">
      <button type="submit" style="background:#3ecf8e;color:#000;border:none;padding:8px 20px;border-radius:6px;font-weight:600;cursor:pointer">✓ Approve</button>
    </form>
    <form method="POST" action="/admin/review/campaigns/action">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <input type="hidden" name="campaign_id" value="<?= $c['id'] ?>">
      <input type="hidden" name="action" value="reject">
      <button type="submit" style="background:rgba(224,84,84,0.15);color:#e05454;border:0.5px solid rgba(224,84,84,0.3);padding:8px 20px;border-radius:6px;font-weight:600;cursor:pointer">✗ Reject</button>
    </form>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
