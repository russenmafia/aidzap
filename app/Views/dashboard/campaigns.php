<?php $active = 'campaigns'; ?>

<div class="page-header">
  <h1 class="page-title"><?= __('campaign.title') ?></h1>
  <a href="/advertiser/campaigns/create" class="btn-add"><?= __('campaign.new') ?></a>
</div>

<?php if (isset($_GET['created'])): ?>
<div class="flash flash-success"><?= __('campaign.created_msg') ?></div>
<?php endif; ?>
<?php if (isset($_GET['updated'])): ?>
<div class="flash flash-success"><?= __('campaign.updated_msg') ?></div>
<?php endif; ?>
<?php if (isset($_GET['toggled'])): ?>
<div class="flash flash-success"><?= __('campaign.toggled_msg') ?></div>
<?php endif; ?>

<?php if (!empty($balance) && $balance > 0): ?>
<div style="font-size:12px;color:rgba(255,255,255,0.3);margin-bottom:16px">
  <?= __('campaign.balance') ?> <strong style="color:#3ecf8e"><?= number_format($balance, 8) ?> BTC</strong>
</div>
<?php endif; ?>

<?php if (empty($campaigns)): ?>
<div class="empty-state">
  <p><?= __('campaign.no_campaigns') ?></p>
  <a href="/advertiser/campaigns/create" class="btn-primary-sm"><?= __('campaign.create_first') ?></a>
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
      <span class="unit-stat-label"><?= __('campaign.daily_budget') ?></span>
      <span class="unit-stat-val"><?= number_format((float)$c['daily_budget'], 5) ?></span>
    </div>
    <div class="unit-stat">
      <span class="unit-stat-label"><?= __('campaign.total_budget') ?></span>
      <span class="unit-stat-val"><?= number_format($total, 5) ?></span>
    </div>
    <div class="unit-stat">
      <span class="unit-stat-label"><?= __('campaign.spent') ?></span>
      <span class="unit-stat-val green"><?= number_format($spent, 5) ?></span>
    </div>
    <div class="unit-stat">
      <span class="unit-stat-label"><?= __('campaign.banners') ?></span>
      <span class="unit-stat-val"><?= (int)$c['banner_count'] ?></span>
    </div>
  </div>

  <!-- Budget progress bar -->
  <div class="budget-bar-wrap">
    <div class="budget-bar-track">
      <div class="budget-bar-fill" style="width:<?= $pct ?>%"></div>
    </div>
    <span class="budget-bar-pct"><?= __('campaign.pct_spent', ['n' => $pct]) ?></span>
  </div>

  <div class="campaign-actions">
    <a href="/advertiser/campaigns/<?= $c['uuid'] ?>/banners" class="action-btn"><?= __('campaign.manage_banners') ?></a>
    <a href="/advertiser/campaigns/<?= $c['uuid'] ?>/edit" class="action-btn"><?= __('campaign.edit') ?></a>
    <?php if (in_array($c['status'], ['active','paused','draft'])): ?>
    <form method="POST" action="/advertiser/campaigns/<?= $c['uuid'] ?>/toggle" style="display:inline">
      <button class="action-btn <?= $c['status'] === 'active' ? '' : 'style="color:#3ecf8e"' ?>">
        <?= $c['status'] === 'active' ? __('campaign.pause') : __('campaign.activate') ?>
      </button>
    </form>
    <?php endif; ?>
  </div>

</div>
<?php endforeach; ?>
<?php endif; ?>

<?php
function statusBadge(string $status): string {
    $map = [
        'active'         => ['green',  __('status.active')],
        'pending_review' => ['yellow', __('status.pending_review')],
        'draft'          => ['gray',   __('status.draft')],
        'paused'         => ['gray',   __('status.paused')],
        'rejected'       => ['red',    __('status.rejected')],
        'completed'      => ['blue',   __('status.completed')],
    ];
    [$color, $label] = $map[$status] ?? ['gray', ucfirst(str_replace('_', ' ', $status))];
    return "<span class='badge badge-{$color}'>{$label}</span>";
}
?>
