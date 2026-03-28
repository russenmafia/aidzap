<?php $active = 'social_templates'; ?>
<div class="page-header">
  <h1 class="page-title">Social Media Templates</h1>
</div>

<?php if (!empty($_GET['saved'])): ?>
<div style="margin-bottom:16px;padding:12px 16px;background:rgba(62,207,142,0.08);border:0.5px solid rgba(62,207,142,0.2);border-radius:8px;color:#3ecf8e;font-size:13px">Saved!</div>
<?php endif; ?>

<!-- Create Form -->
<div class="unit-card" style="margin-bottom:24px">
  <div class="unit-header"><div class="dt-name">+ Create Template</div></div>
  <div style="padding:20px">
    <form method="POST" action="/admin/social-templates/create">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
        <div class="field">
          <label>Title</label>
          <input type="text" name="title" placeholder="e.g. Facebook — EN" required>
        </div>
        <div class="field">
          <label>Language</label>
          <select name="lang">
            <option value="en">EN</option>
            <option value="de">DE</option>
            <option value="all">All</option>
          </select>
        </div>
        <div class="field">
          <label>Platform</label>
          <select name="platform">
            <option value="all">All</option>
            <option value="facebook">Facebook</option>
            <option value="twitter">X/Twitter</option>
            <option value="whatsapp">WhatsApp</option>
            <option value="telegram">Telegram</option>
            <option value="reddit">Reddit</option>
            <option value="linkedin">LinkedIn</option>
            <option value="pinterest">Pinterest</option>
            <option value="email">Email</option>
          </select>
        </div>
        <div class="field">
          <label>Sort Order</label>
          <input type="number" name="sort_order" value="0" min="0">
        </div>
      </div>
      <div class="field" style="margin-bottom:16px">
        <label>Body</label>
        <textarea name="body" rows="4" placeholder="Use {ref_link}, {username}, {site_name}" required
                  style="width:100%;background:#080c10;border:0.5px solid rgba(255,255,255,0.1);border-radius:8px;padding:12px;color:#fff;font-family:'DM Mono',monospace;font-size:12px"></textarea>
        <span class="field-hint">Placeholders: {ref_link} {username} {site_name}</span>
      </div>
      <button type="submit" class="btn-approve">Save Template</button>
    </form>
  </div>
</div>

<!-- Templates List -->
<div class="unit-card">
  <div class="unit-header"><div class="dt-name">Manage Templates</div></div>
  <?php if (empty($templates)): ?>
  <div style="padding:20px;color:rgba(255,255,255,0.3);font-size:13px">No templates yet.</div>
  <?php else: ?>
  <div class="data-table">
    <div class="dt-header" style="grid-template-columns:2fr 80px 80px 40px 80px 3fr 180px">
      <div>Title</div><div>Language</div><div>Platform</div><div>Sort</div><div>Status</div><div>Body</div><div>Actions</div>
    </div>
    <?php foreach ($templates as $t): ?>
    <div class="dt-row" style="grid-template-columns:2fr 80px 80px 40px 80px 3fr 180px;align-items:start">
      <div class="dt-name"><?= htmlspecialchars($t['title']) ?></div>
      <div><span class="badge badge-gray"><?= strtoupper($t['lang']) ?></span></div>
      <div class="dt-muted"><?= htmlspecialchars($t['platform']) ?></div>
      <div class="dt-muted"><?= $t['sort_order'] ?></div>
      <div><span class="badge <?= $t['status']==='active' ? 'badge-green' : 'badge-gray' ?>"><?= $t['status'] ?></span></div>
      <div class="dt-muted" style="font-size:12px;word-break:break-word"><?= htmlspecialchars(mb_substr($t['body'], 0, 80)) ?>...</div>
      <div>
        <form method="POST" action="/admin/social-templates/update" style="margin-bottom:8px">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
          <input type="hidden" name="id" value="<?= $t['id'] ?>">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:4px;margin-bottom:4px">
            <input type="text" name="title" value="<?= htmlspecialchars($t['title']) ?>" style="font-size:11px;padding:4px 8px;background:#080c10;border:0.5px solid rgba(255,255,255,0.1);border-radius:4px;color:#fff">
            <input type="text" name="lang" value="<?= htmlspecialchars($t['lang']) ?>" style="font-size:11px;padding:4px 8px;background:#080c10;border:0.5px solid rgba(255,255,255,0.1);border-radius:4px;color:#fff">
            <input type="text" name="platform" value="<?= htmlspecialchars($t['platform']) ?>" style="font-size:11px;padding:4px 8px;background:#080c10;border:0.5px solid rgba(255,255,255,0.1);border-radius:4px;color:#fff">
            <input type="number" name="sort_order" value="<?= $t['sort_order'] ?>" style="font-size:11px;padding:4px 8px;background:#080c10;border:0.5px solid rgba(255,255,255,0.1);border-radius:4px;color:#fff">
            <select name="status" style="font-size:11px;padding:4px 8px;background:#080c10;border:0.5px solid rgba(255,255,255,0.1);border-radius:4px;color:#fff;grid-column:span 2">
              <option value="active" <?= $t['status']==='active' ? 'selected' : '' ?>>active</option>
              <option value="inactive" <?= $t['status']==='inactive' ? 'selected' : '' ?>>inactive</option>
            </select>
          </div>
          <textarea name="body" rows="3" style="width:100%;font-size:11px;padding:4px 8px;background:#080c10;border:0.5px solid rgba(255,255,255,0.1);border-radius:4px;color:#fff;margin-bottom:4px"><?= htmlspecialchars($t['body']) ?></textarea>
          <button type="submit" style="background:#3ecf8e;color:#000;border:none;padding:4px 12px;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;width:100%">Update</button>
        </form>
        <form method="POST" action="/admin/social-templates/delete" onsubmit="return confirm('Delete?')">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
          <input type="hidden" name="id" value="<?= $t['id'] ?>">
          <button type="submit" style="background:rgba(224,84,84,0.15);color:#e05454;border:0.5px solid rgba(224,84,84,0.3);padding:4px 12px;border-radius:4px;font-size:12px;cursor:pointer;width:100%">Delete</button>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
