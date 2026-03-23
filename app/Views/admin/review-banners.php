<?php $active = 'review'; ?>

<div class="page-header">
  <h1 class="page-title">Banner Review</h1>
  <span class="metric-sub"><?= count($banners) ?> pending</span>
</div>

<?php if (isset($_GET['done'])): ?>
<div class="flash flash-success">Banner <?= htmlspecialchars($_GET['done']) ?>d.</div>
<?php endif; ?>

<?php if (empty($banners)): ?>
<div class="empty-state"><p>No banners pending review. ✓</p></div>
<?php else: ?>
<?php foreach ($banners as $b):
  [$w, $h] = explode('x', $b['size'] . 'x0');
?>
<div class="unit-card">
  <div class="unit-header">
    <div>
      <div class="dt-name"><?= htmlspecialchars($b['name']) ?></div>
      <div class="dt-sub">
        by <strong><?= htmlspecialchars($b['username']) ?></strong>
        &nbsp;·&nbsp; Campaign: <?= htmlspecialchars($b['campaign_name']) ?>
        &nbsp;·&nbsp; <a href="<?= htmlspecialchars($b['target_url']) ?>" target="_blank" style="color:#3ecf8e"><?= htmlspecialchars($b['target_url']) ?> ↗</a>
      </div>
    </div>
    <div class="unit-meta">
      <span class="badge badge-gray"><?= htmlspecialchars($b['size']) ?></span>
      <span class="badge badge-yellow">Review</span>
    </div>
  </div>

  <!-- Banner Preview -->
  <div style="padding:16px 20px;border-bottom:0.5px solid rgba(255,255,255,0.05)">
    <div class="embed-label">Banner Preview</div>
    <div style="background:#fff;display:inline-block;border-radius:4px;overflow:hidden;max-width:100%">
      <div style="width:<?= min((int)$w, 600) ?>px;height:<?= min((int)$h, 300) ?>px;overflow:hidden;transform-origin:top left;<?= (int)$w > 600 ? 'transform:scale('.round(600/(int)$w,2).')' : '' ?>">
        <?= $b['html'] ?>
      </div>
    </div>
  </div>

  <!-- Raw HTML -->
  <div style="padding:12px 20px;border-bottom:0.5px solid rgba(255,255,255,0.05)">
    <div class="embed-label">HTML Source</div>
    <pre class="embed-box" style="max-height:120px;overflow:auto"><?= htmlspecialchars($b['html']) ?></pre>
  </div>

  <!-- Actions -->
  <div class="review-actions">
    <form method="POST" action="/admin/review/banners/action" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
      <input type="hidden" name="uuid" value="<?= htmlspecialchars($b['uuid']) ?>">
      <button name="action" value="approve" class="btn-approve">✓ Approve</button>
      <input type="text" name="reason" placeholder="Reject reason (optional)" style="padding:6px 12px;background:#080c10;border:0.5px solid rgba(255,255,255,0.12);border-radius:6px;color:#fff;font-size:12px;font-family:inherit;width:220px">
      <button name="action" value="reject" class="btn-reject">✗ Reject</button>
    </form>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
