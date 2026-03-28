<?php $active = 'banners'; ?>

<div class="page-header">
  <h1 class="page-title">Banners</h1>
  <div style="display:flex;gap:10px;align-items:center">
    <span style="font-size:13px;color:rgba(255,255,255,0.35)"><?= htmlspecialchars($campaign['name']) ?></span>
    <a href="/advertiser/campaigns/<?= htmlspecialchars($campaign['uuid']) ?>/banners/create" class="btn-add">+ New Banner</a>
  </div>
</div>

<?php if (isset($_GET['created'])): ?>
<div class="flash flash-success">Banner submitted for review.</div>
<?php endif; ?>
<?php if (isset($_GET['saved'])): ?>
<div class="flash flash-success">Banner updated and submitted for review.</div>
<?php endif; ?>

<?php if (empty($banners)): ?>
<div class="empty-state">
  <p>No banners yet. Create your first banner to activate this campaign.</p>
  <a href="/advertiser/campaigns/<?= htmlspecialchars($campaign['uuid']) ?>/banners/create" class="btn-primary-sm">Create Banner →</a>
</div>
<?php else: ?>

<?php foreach ($banners as $b):
  [$w, $h] = explode('x', $b['size'] . 'x0');
  $scale   = min(1, 280 / max(1, (int)$w));
?>
<div class="unit-card">
  <div class="unit-header">
    <div>
      <div class="dt-name"><?= htmlspecialchars($b['name']) ?></div>
      <div class="dt-sub"><?= htmlspecialchars($b['size']) ?></div>
    </div>
    <div class="unit-meta">
      <?= statusBadge($b['status']) ?>
      <a href="/advertiser/campaigns/<?= htmlspecialchars($campaign['uuid']) ?>/banners/<?= htmlspecialchars($b['uuid']) ?>/edit"
         style="font-size:12px;color:#3ecf8e;text-decoration:none;padding:6px 12px;border:0.5px solid rgba(62,207,142,0.3);border-radius:6px">
        Edit
      </a>
      <?php if (in_array($b['status'], ['draft','rejected'])): ?>
      <form method="POST" action="/advertiser/campaigns/<?= htmlspecialchars($campaign['uuid']) ?>/banners/<?= htmlspecialchars($b['uuid']) ?>/delete" style="display:inline">
        <button class="action-btn" style="color:#e05454;border-color:rgba(224,84,84,0.25)" onclick="return confirm('Delete this banner?')">Delete</button>
      </form>
      <?php endif; ?>
    </div>
  </div>

  <div class="unit-stats">
    <div class="unit-stat"><span class="unit-stat-label">Impressions</span><span class="unit-stat-val"><?= number_format((int)$b['impressions']) ?></span></div>
    <div class="unit-stat"><span class="unit-stat-label">Clicks</span><span class="unit-stat-val"><?= number_format((int)$b['clicks']) ?></span></div>
    <div class="unit-stat"><span class="unit-stat-label">CTR</span><span class="unit-stat-val"><?= $b['impressions'] > 0 ? number_format(($b['clicks']/$b['impressions'])*100,2).'%' : '–' ?></span></div>
    <div class="unit-stat"><span class="unit-stat-label">Size</span><span class="unit-stat-val"><?= htmlspecialchars($b['size']) ?></span></div>
  </div>

  <!-- Preview -->
  <div style="padding:16px 20px;display:flex;gap:20px;align-items:flex-start;border-top:0.5px solid rgba(255,255,255,0.05)">
    <div>
      <div class="embed-label" style="margin-bottom:8px">Preview</div>
      <div style="background:#fff;border-radius:6px;overflow:hidden;width:<?= round((int)$w*$scale) ?>px;height:<?= round((int)$h*$scale) ?>px">
        <div style="width:<?= (int)$w ?>px;height:<?= (int)$h ?>px;transform:scale(<?= $scale ?>);transform-origin:top left;overflow:hidden">
          <?= $b['html'] ?>
        </div>
      </div>
    </div>
    <div style="flex:1;min-width:0">
      <div class="embed-label" style="margin-bottom:8px">HTML Source <button class="copy-btn" onclick="navigator.clipboard.writeText(this.dataset.code)" data-code="<?= htmlspecialchars($b['html']) ?>">Copy</button></div>
      <pre class="embed-box" style="max-height:100px;overflow:auto"><?= htmlspecialchars($b['html']) ?></pre>
    </div>
  </div>

</div>
<?php endforeach; ?>
<?php endif; ?>

<?php
function statusBadge(string $status): string {
    $map = ['active'=>['green','Active'],'pending_review'=>['yellow','Review'],'draft'=>['gray','Draft'],'rejected'=>['red','Rejected'],'paused'=>['gray','Paused']];
    [$c,$l] = $map[$status] ?? ['gray',ucfirst($status)];
    return "<span class='badge badge-{$c}'>{$l}</span>";
}
?>
