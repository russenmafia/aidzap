<?php $active = 'dashboard'; ?>

<div class="page-header">
  <h1 class="page-title"><?= __('dashboard.title') ?></h1>
  <div class="tab-group">
    <button class="tab active" onclick="showTab('overview',this)"><?= __('dashboard.tab_overview') ?></button>
    <?php if (in_array($user['role'], ['publisher','both'])): ?>
    <button class="tab" onclick="showTab('publisher',this)"><?= __('dashboard.tab_publisher') ?></button>
    <?php endif; ?>
    <?php if (in_array($user['role'], ['advertiser','both'])): ?>
    <button class="tab" onclick="showTab('advertiser',this)"><?= __('dashboard.tab_advertiser') ?></button>
    <?php endif; ?>
  </div>
</div>

<!-- Overview Tab -->
<div id="tab-overview" class="tab-content">
  <div class="metrics">
    <div class="metric">
      <div class="metric-label"><?= __('dashboard.total_earned') ?></div>
      <div class="metric-val green"><?= number_format((float)($pubStats['total_earned'] ?? 0), 8) ?></div>
      <div class="metric-sub"><?= __('dashboard.btc_lifetime') ?></div>
    </div>
    <div class="metric">
      <div class="metric-label"><?= __('dashboard.impressions') ?></div>
      <div class="metric-val"><?= number_format((int)($pubStats['total_impressions'] ?? 0)) ?></div>
      <div class="metric-sub"><?= __('dashboard.all_time') ?></div>
    </div>
    <div class="metric">
      <div class="metric-label"><?= __('dashboard.active_campaigns') ?></div>
      <div class="metric-val"><?= (int)($advStats['active_campaigns'] ?? 0) ?></div>
      <div class="metric-sub"><?= __('dashboard.of_total', ['n' => (int)($advStats['total_campaigns'] ?? 0)]) ?></div>
    </div>
    <div class="metric">
      <div class="metric-label"><?= __('dashboard.ad_balance') ?></div>
      <div class="metric-val">
        <?php if (!empty($balances)): ?>
          <?= number_format((float)$balances[0]['amount'], 5) ?>
          <span style="font-size:13px;color:rgba(255,255,255,0.3)"><?= htmlspecialchars($balances[0]['currency']) ?></span>
        <?php else: ?>0.00000<?php endif; ?>
      </div>
      <div class="metric-sub"><?= __('dashboard.available') ?></div>
    </div>
  </div>

  <!-- Quick links -->
  <div class="quick-grid">
    <?php if (in_array($user['role'], ['publisher','both'])): ?>
    <a href="/publisher/units/create" class="quick-card">
      <div class="quick-icon">+</div>
      <div class="quick-title"><?= __('dashboard.new_ad_unit') ?></div>
      <div class="quick-desc"><?= __('dashboard.new_ad_unit_desc') ?></div>
    </a>
    <?php endif; ?>
    <?php if (in_array($user['role'], ['advertiser','both'])): ?>
    <a href="/advertiser/campaigns/create" class="quick-card">
      <div class="quick-icon">&#9672;</div>
      <div class="quick-title"><?= __('dashboard.new_campaign') ?></div>
      <div class="quick-desc"><?= __('dashboard.new_campaign_desc') ?></div>
    </a>
    <a href="/advertiser/billing" class="quick-card">
      <div class="quick-icon">&#8593;</div>
      <div class="quick-title"><?= __('dashboard.deposit') ?></div>
      <div class="quick-desc"><?= __('dashboard.deposit_desc') ?></div>
    </a>
    <?php endif; ?>
    <a href="/account/wallets" class="quick-card">
      <div class="quick-icon">&#9635;</div>
      <div class="quick-title"><?= __('dashboard.add_wallet') ?></div>
      <div class="quick-desc"><?= __('dashboard.add_wallet_desc') ?></div>
    </a>
  </div>
</div>

<!-- Publisher Tab -->
<?php if (in_array($user['role'], ['publisher','both'])): ?>
<div id="tab-publisher" class="tab-content" style="display:none">
  <div class="section-bar">
    <h2 class="section-title"><?= __('dashboard.ad_units') ?></h2>
    <a href="/publisher/units/create" class="btn-add">+ <?= __('publisher.new_unit') ?></a>
  </div>

  <?php if (empty($units)): ?>
  <div class="empty-state">
    <p><?= __('dashboard.no_units') ?></p>
    <a href="/publisher/units/create" class="btn-primary-sm"><?= __('dashboard.create_first_unit') ?></a>
  </div>
  <?php else: ?>
  <div class="data-table">
    <div class="dt-header" style="grid-template-columns:2fr 90px 100px 120px 90px">
      <div><?= __('dashboard.col_unit') ?></div><div><?= __('dashboard.col_size') ?></div><div><?= __('dashboard.col_impressions') ?></div><div><?= __('dashboard.col_earned') ?></div><div><?= __('dashboard.col_status') ?></div>
    </div>
    <?php foreach ($units as $u): ?>
    <div class="dt-row" style="grid-template-columns:2fr 90px 100px 120px 90px">
      <div>
        <div class="dt-name"><?= htmlspecialchars($u['name']) ?></div>
        <div class="dt-sub"><?= htmlspecialchars($u['website_url']) ?></div>
      </div>
      <div class="dt-muted"><?= htmlspecialchars($u['size']) ?></div>
      <div class="dt-muted"><?= number_format((int)$u['impressions']) ?></div>
      <div class="dt-green"><?= number_format((float)$u['earned'], 8) ?> BTC</div>
      <div><?= statusBadge($u['status']) ?></div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- Advertiser Tab -->
<?php if (in_array($user['role'], ['advertiser','both'])): ?>
<div id="tab-advertiser" class="tab-content" style="display:none">
  <div class="section-bar">
    <h2 class="section-title"><?= __('dashboard.campaigns') ?></h2>
    <a href="/advertiser/campaigns/create" class="btn-add">+ <?= __('campaign.new') ?></a>
  </div>

  <?php if (empty($campaigns)): ?>
  <div class="empty-state">
    <p><?= __('dashboard.no_campaigns') ?></p>
    <a href="/advertiser/campaigns/create" class="btn-primary-sm"><?= __('dashboard.create_first_camp') ?></a>
  </div>
  <?php else: ?>
  <div class="data-table">
    <div class="dt-header" style="grid-template-columns:2fr 80px 120px 80px 90px">
      <div><?= __('dashboard.col_campaign') ?></div><div><?= __('dashboard.col_model') ?></div><div><?= __('dashboard.col_spent') ?></div><div><?= __('dashboard.col_clicks') ?></div><div><?= __('dashboard.col_status') ?></div>
    </div>
    <?php foreach ($campaigns as $c): ?>
    <div class="dt-row" style="grid-template-columns:2fr 80px 120px 80px 90px">
      <div>
        <div class="dt-name"><?= htmlspecialchars($c['name']) ?></div>
        <div class="dt-sub"><?= __('dashboard.budget') ?> <?= number_format((float)$c['total_budget'], 5) ?> <?= htmlspecialchars($c['currency']) ?></div>
      </div>
      <div class="dt-muted"><?= strtoupper(htmlspecialchars($c['pricing_model'])) ?></div>
      <div class="dt-muted"><?= number_format((float)$c['spent'], 5) ?> <?= htmlspecialchars($c['currency']) ?></div>
      <div class="dt-muted">–</div>
      <div><?= statusBadge($c['status']) ?></div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php if (!empty($refData) && ($refData['reason'] ?? '') !== 'own_level_too_low'): ?>
<div class="unit-card" style="margin-bottom:20px">
  <div class="unit-header">
    <div class="dt-name">&#128279; Referral Network</div>
    <a href="/dashboard/referrals" style="font-size:12px;color:#3ecf8e;text-decoration:none">View all &#8594;</a>
  </div>
  <div style="padding:20px">
    <div class="metrics" style="grid-template-columns:repeat(3,1fr);margin-bottom:16px">
      <div class="metric">
        <div class="metric-label">Active Refs</div>
        <div class="metric-val"><?= $refData['active_refs'] ?></div>
      </div>
      <div class="metric">
        <div class="metric-label">Multiplier</div>
        <div class="metric-val <?= ($refData['multiplier'] ?? 0) > 0 ? 'green' : '' ?>">
          <?= $refData['multiplier'] ?? 0 ?>x
        </div>
      </div>
      <div class="metric">
        <div class="metric-label">Your Level</div>
        <div class="metric-val"><?= ucfirst($refData['own_level'] ?? 'bronze') ?></div>
      </div>
    </div>
    <?php
      $activeRefs = $refData['active_refs'] ?? 0;
      $next = $activeRefs >= 3 ? null : ($activeRefs >= 2 ? 3 : ($activeRefs >= 1 ? 2 : 1));
    ?>
    <?php if ($next): ?>
    <div style="font-size:12px;color:rgba(255,255,255,0.3)">
      <?= $next - $activeRefs ?> more active ref(s) needed for <?= $next ?>x multiplier
    </div>
    <?php endif; ?>
  </div>
</div>
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

<script>
function showTab(name, btn) {
    document.querySelectorAll('.tab-content').forEach(t => t.style.display = 'none');
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.getElementById('tab-' + name).style.display = 'block';
    btn.classList.add('active');
}
</script>
