<?php $active = 'review'; ?>

<div class="page-header">
  <h1 class="page-title">Ad Unit Review</h1>
  <span class="metric-sub"><?= count($units) ?> pending</span>
</div>

<?php if (isset($_GET['done'])): ?>
<div class="flash flash-success">Unit <?= htmlspecialchars($_GET['done']) ?>d.</div>
<?php endif; ?>

<?php if (empty($units)): ?>
<div class="empty-state"><p>No units pending review. ✓</p></div>
<?php else: ?>
<?php foreach ($units as $u): ?>
<div class="unit-card">
  <div class="unit-header">
    <div>
      <div class="dt-name"><?= htmlspecialchars($u['name']) ?></div>
      <div class="dt-sub">by <strong><?= htmlspecialchars($u['username']) ?></strong> &nbsp;·&nbsp; <?= htmlspecialchars($u['website_url']) ?></div>
    </div>
    <div class="unit-meta">
      <span class="badge badge-gray"><?= htmlspecialchars(ucfirst($u['type'] ?? 'banner')) ?></span>
      <span class="badge badge-gray"><?= htmlspecialchars($u['size'] ?? '') ?></span>
      <span class="badge badge-yellow">Review</span>
    </div>
  </div>
  <div class="review-actions">
    <form method="POST" action="/admin/review/units/action" style="display:flex;gap:8px;align-items:center">
      <input type="hidden" name="uuid" value="<?= htmlspecialchars($u['uuid']) ?>">
      <button name="action" value="approve" class="btn-approve">✓ Approve</button>
      <button name="action" value="reject"  class="btn-reject">✗ Reject</button>
    </form>
    <a href="<?= htmlspecialchars($u['website_url']) ?>" target="_blank" class="btn-ghost-sm">Visit Site ↗</a>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
