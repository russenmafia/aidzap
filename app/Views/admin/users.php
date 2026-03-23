<?php $active = 'users'; ?>

<div class="page-header">
  <h1 class="page-title">Users</h1>
  <span class="metric-sub"><?= count($users) ?> total</span>
</div>

<?php if (isset($_GET['done'])): ?>
<div class="flash flash-success">Action completed.</div>
<?php endif; ?>

<div class="data-table">
  <div class="dt-header" style="grid-template-columns:1fr 80px 80px 60px 60px 120px 100px">
    <div>User</div><div>Role</div><div>Status</div><div>Units</div><div>Camps</div><div>Joined</div><div>Actions</div>
  </div>
  <?php foreach ($users as $u): ?>
  <div class="dt-row" style="grid-template-columns:1fr 80px 80px 60px 60px 120px 100px">
    <div>
      <div class="dt-name"><?= htmlspecialchars($u['username']) ?></div>
      <div class="dt-sub"><?= htmlspecialchars($u['uuid']) ?></div>
    </div>
    <div><span class="badge <?= $u['role'] === 'admin' ? 'badge-red' : 'badge-gray' ?>"><?= htmlspecialchars($u['role']) ?></span></div>
    <div><?= $u['status'] === 'active' ? '<span class="badge badge-green">active</span>' : '<span class="badge badge-red">' . htmlspecialchars($u['status']) . '</span>' ?></div>
    <div class="dt-muted"><?= (int)$u['unit_count'] ?></div>
    <div class="dt-muted"><?= (int)$u['campaign_count'] ?></div>
    <div class="dt-muted" style="font-size:11px"><?= date('d.m.Y', strtotime($u['created_at'])) ?></div>
    <div>
      <form method="POST" action="/admin/users/action" style="display:inline">
        <input type="hidden" name="uuid" value="<?= htmlspecialchars($u['uuid']) ?>">
        <?php if ($u['status'] === 'active'): ?>
        <button name="action" value="suspend" class="action-btn">Suspend</button>
        <?php else: ?>
        <button name="action" value="activate" class="action-btn">Activate</button>
        <?php endif; ?>
      </form>
    </div>
  </div>
  <?php endforeach; ?>
</div>
