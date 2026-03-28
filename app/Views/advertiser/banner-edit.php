<?php $active = 'banners'; ?>
<div class="page-header">
  <h1 class="page-title">Edit Banner</h1>
  <a href="/advertiser/campaigns/<?= htmlspecialchars($campaign['uuid']) ?>/banners" class="btn-ghost">&larr; Back</a>
</div>

<?php if (!empty($_GET['error'])): ?>
<div class="flash flash-error" style="margin-bottom:16px">
  Error: <?= htmlspecialchars((string)$_GET['error']) ?>
</div>
<?php endif; ?>

<div class="unit-card">
  <div class="unit-header">
    <div class="dt-name"><?= htmlspecialchars($banner['name']) ?></div>
    <div class="unit-meta">
      <span class="badge badge-gray"><?= htmlspecialchars($banner['size']) ?></span>
      <span class="badge badge-gray"><?= htmlspecialchars($banner['type'] ?? 'banner') ?></span>
    </div>
  </div>
  <div style="padding:24px">

    <div style="margin-bottom:24px">
      <div style="font-size:11px;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:.08em;margin-bottom:12px">Current Preview</div>
      <div style="background:#111;border:0.5px solid rgba(255,255,255,0.1);border-radius:8px;padding:16px;display:inline-block">
        <?= $banner['html'] ?>
      </div>
    </div>

    <?php
      $bannerType = (string)($banner['type'] ?? '');
      $isImageBanner = in_array($bannerType, ['image', 'upload'], true)
          || str_contains((string)($banner['html'] ?? ''), '/uploads/banners/');
    ?>

    <form method="POST" action="/advertiser/campaigns/<?= htmlspecialchars($campaign['uuid']) ?>/banners/<?= htmlspecialchars($banner['uuid']) ?>/edit"
          enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

      <?php if ($isImageBanner): ?>
      <div class="field" style="margin-bottom:20px">
        <label>Replace Image (<?= htmlspecialchars($banner['size']) ?>)</label>
        <input type="file" name="banner_image" accept="image/jpeg,image/png,image/gif,image/webp">
        <span class="field-hint">Leave empty to keep current image. Max 512 KB.</span>
      </div>

      <?php else: ?>
      <div class="field" style="margin-bottom:20px">
        <label>Banner HTML</label>
        <textarea name="html" rows="12"
                  style="font-family:'DM Mono',monospace;font-size:12px;width:100%;background:#080c10;border:0.5px solid rgba(255,255,255,0.1);border-radius:8px;padding:12px;color:#fff;resize:vertical"><?= htmlspecialchars($banner['html']) ?></textarea>
        <span class="field-hint">Edit HTML directly. After saving, status resets to pending review.</span>
      </div>

      <div style="margin-bottom:20px">
        <div style="font-size:11px;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:.08em;margin-bottom:12px">Live Preview</div>
        <div id="preview-box" style="background:#fff;border-radius:8px;padding:8px;display:inline-block;min-width:100px;min-height:30px"></div>
      </div>
      <?php endif; ?>

      <div style="display:flex;align-items:center;gap:12px">
        <button type="submit" class="btn-submit">Save &amp; Submit for Review &rarr;</button>
        <span style="font-size:12px;color:rgba(255,255,255,0.3)">Status will reset to "pending review"</span>
      </div>
    </form>
  </div>
</div>

<?php if (!$isImageBanner): ?>
<script>
const textarea = document.querySelector('textarea[name="html"]');
const preview = document.getElementById('preview-box');
function updatePreview() {
    preview.innerHTML = textarea.value;
}
textarea.addEventListener('input', updatePreview);
updatePreview();
</script>
<?php endif; ?>
