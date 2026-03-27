<?php $active = 'units'; ?>

<div class="page-header">
  <h1 class="page-title"><?= __('publisher.units_title') ?></h1>
  <a href="/publisher/units/create" class="btn-add"><?= __('publisher.new_unit') ?></a>
</div>

<?php if (isset($_GET['created'])): ?>
<div class="flash flash-success"><?= __('publisher.created_msg') ?></div>
<?php endif; ?>

<?php if (empty($units)): ?>
<div class="empty-state">
  <p><?= __('publisher.no_units') ?></p>
  <a href="/publisher/units/create" class="btn-primary-sm"><?= __('publisher.create_first') ?></a>
</div>
<?php else: ?>

<?php foreach ($units as $u):
  $type  = $u['type'] ?? 'banner';
  $uuid  = $u['uuid'];
  $parts = explode('x', $u['size'] ?? '300x250');
  $w     = $parts[0] ?? '300';
  $h     = $parts[1] ?? '250';

  if ($type === 'native') {
      $embedCode = '<div data-aidzap-unit="' . $uuid . '" data-aidzap-type="native"></div>';
  } elseif ($type === 'interstitial') {
      $embedCode = '<script>window.aidzapUnit="' . $uuid . '";</script>' . "\n" . '<script src="https://aidzap.com/ad/interstitial.js"></script>';
  } else {
      $embedCode = '<iframe src="https://aidzap.com/ad/' . $uuid . '" width="' . $w . '" height="' . $h . '" scrolling="no" frameborder="0" style="border:none"></iframe>';
  }
?>

<div class="unit-card">

  <!-- Header Row -->
  <div class="unit-header">
    <div>
      <div class="dt-name"><?= htmlspecialchars($u['name']) ?></div>
      <div class="dt-sub"><?= htmlspecialchars($u['website_url']) ?></div>
    </div>
    <div class="unit-meta">
      <span class="badge badge-gray"><?= htmlspecialchars(ucfirst($type)) ?></span>
      <?php if ($type === 'banner' || $type === 'sticky'): ?>
      <span class="unit-size"><?= htmlspecialchars($u['size'] ?? '–') ?></span>
      <?php endif; ?>
      <?= statusBadge($u['status']) ?>
      <?php $qlevel = $u['quality_level'] ?? 'bronze'; $qshare = $u['revenue_share'] ?? 60; $qlColors = ['bronze'=>'#cd7f32','silver'=>'#aaa','gold'=>'#f5a623','platinum'=>'#3ecf8e']; $qlIcons = ['bronze'=>'🥉','silver'=>'🥈','gold'=>'🥇','platinum'=>'💎']; ?>
      <span style="font-size:11px;color:<?= $qlColors[$qlevel] ?>">  <?= $qlIcons[$qlevel] ?> <?= ucfirst($qlevel) ?> · <?= $qshare ?>%</span>
    </div>
  </div>

  <!-- Stats Row -->
  <div class="unit-stats">
    <div class="unit-stat">
      <span class="unit-stat-label"><?= __('publisher.col_impressions') ?></span>
      <span class="unit-stat-val"><?= number_format((int)$u['impressions']) ?></span>
    </div>
    <div class="unit-stat">
      <span class="unit-stat-label"><?= __('publisher.clicks') ?></span>
      <span class="unit-stat-val"><?= number_format((int)$u['clicks']) ?></span>
    </div>
    <div class="unit-stat">
      <span class="unit-stat-label"><?= __('publisher.col_earned') ?></span>
      <span class="unit-stat-val green"><?= number_format((float)$u['earned'], 8) ?> BTC</span>
    </div>
    <div class="unit-stat">
      <span class="unit-stat-label"><?= __('publisher.ctr') ?></span>
      <span class="unit-stat-val">
        <?= $u['impressions'] > 0
            ? number_format(($u['clicks'] / $u['impressions']) * 100, 2) . '%'
            : '–' ?>
      </span>
    </div>
  </div>

  <!-- Embed + Preview Row -->
  <div class="unit-bottom">

    <!-- Embed Code -->
    <div class="unit-embed-wrap">
      <div class="embed-label"><?= __('publisher.embed_code') ?>
        <button class="copy-btn" onclick="copyEmbed(this)" data-code="<?= htmlspecialchars($embedCode) ?>">Copy</button>
      </div>
      <pre class="embed-box"><?= htmlspecialchars($embedCode) ?></pre>
    </div>

    <!-- Banner Preview -->
    <?php if ($type === 'banner' || $type === 'sticky'): ?>
    <div class="unit-preview-wrap">
      <div class="embed-label"><?= __('publisher.preview') ?></div>
      <div class="banner-preview" style="width:<?= min((int)$w, 400) ?>px;height:<?= min((int)$h, 200) ?>px">
        <div class="banner-preview-inner">
          <div class="banner-preview-logo">AIDZAP</div>
          <div class="banner-preview-size"><?= htmlspecialchars($u['size'] ?? '') ?></div>
          <div class="banner-preview-status">
            <?php if ($u['status'] === 'pending_review'): ?>
            <span class="badge badge-yellow"><?= __('publisher.awaiting_review') ?></span>
            <?php elseif ($u['status'] === 'active'): ?>
            <span class="badge badge-green"><?= __('publisher.live') ?></span>
            <?php else: ?>
            <span class="badge badge-gray"><?= htmlspecialchars(ucfirst($u['status'])) ?></span>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    <?php elseif ($type === 'native'): ?>
    <div class="unit-preview-wrap">
      <div class="embed-label"><?= __('publisher.native_preview') ?></div>
      <div class="native-preview">
        <div class="native-preview-img"></div>
        <div class="native-preview-content">
          <div class="native-preview-tag">Sponsored</div>
          <div class="native-preview-title">Your ad headline will appear here</div>
          <div class="native-preview-desc">Description text from the advertiser's creative will be shown in this space.</div>
          <div class="native-preview-cta">Learn more →</div>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </div><!-- /unit-bottom -->

</div><!-- /unit-card -->

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
    ];
    [$color, $label] = $map[$status] ?? ['gray', ucfirst(str_replace('_', ' ', $status))];
    return "<span class='badge badge-{$color}'>{$label}</span>";
}
?>

<script>
function copyEmbed(btn) {
    const code = btn.getAttribute('data-code');
    navigator.clipboard.writeText(code).then(() => {
        btn.textContent = 'Copied!';
        btn.style.color = '#3ecf8e';
        setTimeout(() => { btn.textContent = 'Copy'; btn.style.color = ''; }, 2000);
    });
}
</script>
