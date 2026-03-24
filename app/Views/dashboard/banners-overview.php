<?php $active = 'banners'; ?>

<div class="page-header">
  <h1 class="page-title">Banners</h1>
</div>

<?php if (empty($campaigns)): ?>
<div class="empty-state">
  <p>No campaigns yet. Create a campaign first, then add banners to it.</p>
  <a href="/advertiser/campaigns/create" class="btn-primary-sm">Create Campaign →</a>
</div>
<?php else: ?>

<?php foreach ($campaigns as $c): ?>
<div class="unit-card" style="margin-bottom:16px">
  <div class="unit-header">
    <div>
      <div class="dt-name"><?= htmlspecialchars($c['name']) ?></div>
      <div class="dt-sub">
        <?= strtoupper(htmlspecialchars($c['pricing_model'])) ?>
        &nbsp;·&nbsp; <?= htmlspecialchars($c['currency']) ?>
        &nbsp;·&nbsp; <?= (int)$c['banner_count'] ?> banner<?= $c['banner_count'] != 1 ? 's' : '' ?>
      </div>
    </div>
    <div class="unit-meta">
      <?= statusBadge($c['status']) ?>
      <a href="/advertiser/campaigns/<?= htmlspecialchars($c['uuid']) ?>/banners/create" class="btn-add" style="font-size:12px;padding:6px 14px">+ Add Banner</a>
      <a href="/advertiser/campaigns/<?= htmlspecialchars($c['uuid']) ?>/banners" class="action-btn">View all →</a>
    </div>
  </div>

  <?php if (!empty($c['banners'])): ?>
  <div style="padding:14px 20px;display:flex;gap:12px;flex-wrap:wrap;border-top:0.5px solid rgba(255,255,255,0.05)">
    <?php foreach ($c['banners'] as $b):
      [$w, $h] = explode('x', $b['size'] . 'x0');
      $scale   = min(1, 120 / max(1, (int)$w));
    ?>
    <div style="text-align:center">
      <div style="background:#fff;border-radius:4px;overflow:hidden;width:<?= round((int)$w*$scale) ?>px;height:<?= round((int)$h*$scale) ?>px;margin-bottom:4px">
        <div style="width:<?= (int)$w ?>px;height:<?= (int)$h ?>px;transform:scale(<?= $scale ?>);transform-origin:top left">
          <?= $b['html'] ?>
        </div>
      </div>
      <div style="font-size:10px;color:rgba(255,255,255,0.3)"><?= htmlspecialchars($b['size']) ?></div>
      <div><?= statusBadge($b['status']) ?></div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div style="padding:16px 20px;border-top:0.5px solid rgba(255,255,255,0.05)">
    <p style="font-size:13px;color:rgba(255,255,255,0.3)">No banners yet for this campaign.</p>
  </div>
  <?php endif; ?>

</div>
<?php endforeach; ?>
<?php endif; ?>

<?php
function statusBadge(string $status): string {
    $map = ['active'=>['green','Active'],'pending_review'=>['yellow','Review'],'draft'=>['gray','Draft'],'paused'=>['gray','Paused'],'rejected'=>['red','Rejected'],'completed'=>['blue','Done']];
    [$c,$l] = $map[$status] ?? ['gray',ucfirst($status)];
    return "<span class='badge badge-{$c}'>{$l}</span>";
}
?>
