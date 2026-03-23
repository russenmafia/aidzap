<?php $active = 'campaigns'; ?>

<div class="page-header">
  <h1 class="page-title">Campaigns</h1>
  <a href="/advertiser/campaigns/create" class="btn-add">+ New Campaign</a>
</div>

<?php if (isset($_GET['created'])): ?>
<div class="flash flash-success">Campaign created as draft. Add a banner to submit for review.</div>
<?php endif; ?>

<?php if (empty($campaigns)): ?>
<div class="empty-state">
  <p>No campaigns yet. Create your first campaign to start advertising.</p>
  <a href="/advertiser/campaigns/create" class="btn-primary-sm">Create Campaign →</a>
</div>
<?php else: ?>

<?php foreach ($campaigns as $c):
  $spent   = (float)$c['spent'];
  $total   = (float)$c['total_budget'];
  $pct     = $total > 0 ? min(100, round($spent / $total * 100)) : 0;
?>
<div class="unit-card">
  <div class="unit-header">
    <div>
      <div class="dt-name"><?= htmlspecialchars($c['name']) ?></div>
      <div class="dt-sub"><?= htmlspecialchars($c['target_url'] ?? '') ?></div>
    </div>
    <div class="unit-meta">
      <span class="badge badge-gray"><?= strtoupper(htmlspecialchars($c['pricing_model'])) ?></span>
      <span class="unit-size"><?= htmlspecialchars($c['currency']) ?></span>
      <?= statusBadge($c['status']) ?>
    </div>
  </div>

  <div class="unit-stats">
    <div class="unit-stat">
      <span class="unit-stat-label">Daily budget</span>
      <span class="unit-stat-val"><?= number_format((float)$c['daily_budget'], 5) ?></span>
    </div>
    <div class="unit-stat">
      <span class="unit-stat-label">Total budget</span>
      <span class="unit-stat-val"><?= number_format($total, 5) ?></span>
    </div>
    <div class="unit-stat">
      <span class="unit-stat-label">Spent</span>
      <span class="unit-stat-val green"><?= number_format($spent, 5) ?></span>
    </div>
    <div class="unit-stat">
      <span class="unit-stat-label">Banners</span>
      <span class="unit-stat-val"><?= (int)$c['banner_count'] ?></span>
    </div>
  </div>

  <!-- Budget progress bar -->
  <div class="budget-bar-wrap">
    <div class="budget-bar-track">
      <div class="budget-bar-fill" style="width:<?= $pct ?>%"></div>
    </div>
    <span class="budget-bar-pct"><?= $pct ?>% spent</span>
  </div>

  <div class="campaign-actions">
    <a href="/advertiser/campaigns/<?= $c['uuid'] ?>/banners" class="action-btn">Manage Banners</a>
    <?php if ($c['status'] === 'active'): ?>
    <a href="/advertiser/campaigns/<?= $c['uuid'] ?>/pause" class="action-btn">Pause</a>
    <?php elseif ($c['status'] === 'paused'): ?>
    <a href="/advertiser/campaigns/<?= $c['uuid'] ?>/resume" class="action-btn">Resume</a>
    <?php endif; ?>
  </div>

</div>
<?php endforeach; ?>
<?php endif; ?>

<?php
function statusBadge(string $status): string {
    $map = [
        'active'         => ['green',  'Active'],
        'pending_review' => ['yellow', 'Review'],
        'draft'          => ['gray',   'Draft'],
        'paused'         => ['gray',   'Paused'],
        'rejected'       => ['red',    'Rejected'],
        'completed'      => ['blue',   'Done'],
    ];
    [$color, $label] = $map[$status] ?? ['gray', ucfirst($status)];
    return "<span class='badge badge-{$color}'>{$label}</span>";
}
?>
