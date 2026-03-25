<div class="page-header">
  <h1 class="page-title">CONTENT / Legal Pages</h1>
  <span class="metric-sub"><?= count($pages) ?> items</span>
</div>

<div class="data-table">
  <div class="dt-header" style="grid-template-columns:150px 1fr 160px 100px">
    <div>Slug</div><div>Title</div><div>Last Updated</div><div>Action</div>
  </div>

  <?php foreach ($pages as $page): ?>
  <div class="dt-row" style="grid-template-columns:150px 1fr 160px 100px">
    <div class="dt-muted"><?= htmlspecialchars($page['slug']) ?></div>
    <div class="dt-name"><?= htmlspecialchars($page['title']) ?></div>
    <div class="dt-muted"><?= !empty($page['updated_at']) ? date('d.m.Y H:i', strtotime($page['updated_at'])) : '-' ?></div>
    <div>
      <a href="/admin/legal/<?= urlencode($page['slug']) ?>" class="action-btn">Edit</a>
    </div>
  </div>
  <?php endforeach; ?>
</div>
