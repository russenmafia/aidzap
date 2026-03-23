<?php $active = 'dashboard'; ?>

<div class="page-header">
  <h1 class="page-title">Dashboard</h1>
  <div class="tab-group">
    <button class="tab active" onclick="showTab('overview',this)">Overview</button>
    <?php if (in_array($user['role'], ['publisher','both'])): ?>
    <button class="tab" onclick="showTab('publisher',this)">Publisher</button>
    <?php endif; ?>
    <?php if (in_array($user['role'], ['advertiser','both'])): ?>
    <button class="tab" onclick="showTab('advertiser',this)">Advertiser</button>
    <?php endif; ?>
  </div>
</div>

<!-- Overview Tab -->
<div id="tab-overview" class="tab-content">
  <div class="metrics">
    <div class="metric">
      <div class="metric-label">Total earned</div>
      <div class="metric-val green"><?= number_format((float)($pubStats['total_earned'] ?? 0), 8) ?></div>
      <div class="metric-sub">BTC lifetime</div>
    </div>
    <div class="metric">
      <div class="metric-label">Impressions</div>
      <div class="metric-val"><?= number_format((int)($pubStats['total_impressions'] ?? 0)) ?></div>
      <div class="metric-sub">all time</div>
    </div>
    <div class="metric">
      <div class="metric-label">Active campaigns</div>
      <div class="metric-val"><?= (int)($advStats['active_campaigns'] ?? 0) ?></div>
      <div class="metric-sub">of <?= (int)($advStats['total_campaigns'] ?? 0) ?> total</div>
    </div>
    <div class="metric">
      <div class="metric-label">Ad balance</div>
      <div class="metric-val">
        <?php if (!empty($balances)): ?>
          <?= number_format((float)$balances[0]['amount'], 5) ?>
          <span style="font-size:13px;color:rgba(255,255,255,0.3)"><?= htmlspecialchars($balances[0]['currency']) ?></span>
        <?php else: ?>0.00000<?php endif; ?>
      </div>
      <div class="metric-sub">available</div>
    </div>
  </div>

  <!-- Quick links -->
  <div class="quick-grid">
    <?php if (in_array($user['role'], ['publisher','both'])): ?>
    <a href="/publisher/units/create" class="quick-card">
      <div class="quick-icon">+</div>
      <div class="quick-title">New Ad Unit</div>
      <div class="quick-desc">Add a banner slot to your site</div>
    </a>
    <?php endif; ?>
    <?php if (in_array($user['role'], ['advertiser','both'])): ?>
    <a href="/advertiser/campaigns/create" class="quick-card">
      <div class="quick-icon">&#9672;</div>
      <div class="quick-title">New Campaign</div>
      <div class="quick-desc">Start advertising today</div>
    </a>
    <a href="/advertiser/billing" class="quick-card">
      <div class="quick-icon">&#8593;</div>
      <div class="quick-title">Deposit</div>
      <div class="quick-desc">Top up your ad balance</div>
    </a>
    <?php endif; ?>
    <a href="/account/wallets" class="quick-card">
      <div class="quick-icon">&#9635;</div>
      <div class="quick-title">Add Wallet</div>
      <div class="quick-desc">Set payout address</div>
    </a>
  </div>
</div>

<!-- Publisher Tab -->
<?php if (in_array($user['role'], ['publisher','both'])): ?>
<div id="tab-publisher" class="tab-content" style="display:none">
  <div class="section-bar">
    <h2 class="section-title">Ad Units</h2>
    <a href="/publisher/units/create" class="btn-add">+ New Unit</a>
  </div>

  <?php if (empty($units)): ?>
  <div class="empty-state">
    <p>No ad units yet.</p>
    <a href="/publisher/units/create" class="btn-primary-sm">Create your first ad unit →</a>
  </div>
  <?php else: ?>
  <div class="data-table">
    <div class="dt-header" style="grid-template-columns:2fr 90px 100px 120px 90px">
      <div>Unit</div><div>Size</div><div>Impressions</div><div>Earned</div><div>Status</div>
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
    <h2 class="section-title">Campaigns</h2>
    <a href="/advertiser/campaigns/create" class="btn-add">+ New Campaign</a>
  </div>

  <?php if (empty($campaigns)): ?>
  <div class="empty-state">
    <p>No campaigns yet.</p>
    <a href="/advertiser/campaigns/create" class="btn-primary-sm">Create your first campaign →</a>
  </div>
  <?php else: ?>
  <div class="data-table">
    <div class="dt-header" style="grid-template-columns:2fr 80px 120px 80px 90px">
      <div>Campaign</div><div>Model</div><div>Spent</div><div>Clicks</div><div>Status</div>
    </div>
    <?php foreach ($campaigns as $c): ?>
    <div class="dt-row" style="grid-template-columns:2fr 80px 120px 80px 90px">
      <div>
        <div class="dt-name"><?= htmlspecialchars($c['name']) ?></div>
        <div class="dt-sub">Budget: <?= number_format((float)$c['total_budget'], 5) ?> <?= htmlspecialchars($c['currency']) ?></div>
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

<script>
function showTab(name, btn) {
    document.querySelectorAll('.tab-content').forEach(t => t.style.display = 'none');
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.getElementById('tab-' + name).style.display = 'block';
    btn.classList.add('active');
}
</script>
