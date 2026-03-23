<?php $active = 'units'; ?>

<div class="page-header">
  <h1 class="page-title">Ad Units</h1>
  <a href="/publisher/units/create" class="btn-add">+ New Unit</a>
</div>

<?php if (isset($_GET['created'])): ?>
<div class="flash flash-success">Ad unit created and submitted for review.</div>
<?php endif; ?>

<?php if (empty($units)): ?>
<div class="empty-state">
  <p>No ad units yet. Create your first unit to start monetizing your traffic.</p>
  <a href="/publisher/units/create" class="btn-primary-sm">Create Ad Unit →</a>
</div>
<?php else: ?>
<div class="data-table">
  <div class="dt-header" style="grid-template-columns:2fr 80px 90px 100px 120px 90px">
    <div>Unit</div><div>Type</div><div>Size</div><div>Impressions</div><div>Earned</div><div>Status</div>
  </div>
  <?php foreach ($units as $u): ?>
  <div class="dt-row" style="grid-template-columns:2fr 80px 90px 100px 120px 90px">
    <div>
      <div class="dt-name"><?= htmlspecialchars($u['name']) ?></div>
      <div class="dt-sub"><?= htmlspecialchars($u['website_url']) ?></div>
    </div>
    <div><span class="badge badge-gray"><?= htmlspecialchars(ucfirst($u['type'] ?? 'banner')) ?></span></div>
    <div class="dt-muted"><?= htmlspecialchars($u['size'] ?? '–') ?></div>
    <div class="dt-muted"><?= number_format((int)$u['impressions']) ?></div>
    <div class="dt-green"><?= number_format((float)$u['earned'], 8) ?> BTC</div>
    <div><?= statusBadge($u['status']) ?></div>
  </div>

  <!-- Embed code row -->
  <div class="embed-row">
    <div class="embed-label">Embed code</div>
    <div class="embed-box" onclick="this.select()" title="Click to select">
      <?php
      $type = $u['type'] ?? 'banner';
      $uuid = htmlspecialchars($u['uuid']);
      $w = explode('x', $u['size'] ?? '300x250')[0] ?? '300';
      $h = explode('x', $u['size'] ?? '300x250')[1] ?? '250';
      if ($type === 'banner' || $type === 'sticky'): ?>
&lt;iframe src="https://aidzap.com/ad/<?= $uuid ?>" width="<?= $w ?>" height="<?= $h ?>" scrolling="no" frameborder="0" style="border:none"&gt;&lt;/iframe&gt;
      <?php elseif ($type === 'native'): ?>
&lt;div data-aidzap-unit="<?= $uuid ?>" data-aidzap-type="native"&gt;&lt;/div&gt;
      <?php else: ?>
&lt;script&gt;window.aidzapUnit="<?= $uuid ?>";&lt;/script&gt;
      <?php endif; ?>
    </div>
  </div>

  <?php endforeach; ?>
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
    ];
    [$color, $label] = $map[$status] ?? ['gray', ucfirst($status)];
    return "<span class='badge badge-{$color}'>{$label}</span>";
}
?>
