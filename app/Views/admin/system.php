<?php
$active = 'system';
$tab = (string)($_GET['tab'] ?? 'site');
if (!in_array($tab, ['site', 'formats', 'mail', 'maintenance'], true)) {
    $tab = 'site';
}

$settings = $settings ?? [];
$bannerFormats = $bannerFormats ?? [];
$csrf = (string)($csrf_token ?? '');

$mask = static function (string $value): string {
    if ($value === '') {
        return '(not set)';
    }
    $len = strlen($value);
    if ($len <= 6) {
        return str_repeat('*', $len);
    }
    return substr($value, 0, 3) . str_repeat('*', max(0, $len - 6)) . substr($value, -3);
};
?>

<div class="page-header">
  <h1 class="page-title">System Settings</h1>
</div>

<?php if (isset($_GET['saved'])): ?>
<div class="flash flash-success">Settings saved.</div>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
<div class="flash flash-error">Please check your input.</div>
<?php endif; ?>
<?php if (isset($_GET['mail_test']) && $_GET['mail_test'] === 'ok'): ?>
<div class="flash flash-success">Test email sent successfully.</div>
<?php elseif (isset($_GET['mail_test']) && $_GET['mail_test'] === 'fail'): ?>
<div class="flash flash-error">Test email failed. Check mail settings.</div>
<?php elseif (isset($_GET['mail_test']) && $_GET['mail_test'] === 'invalid'): ?>
<div class="flash flash-error">Please provide a valid test email address.</div>
<?php endif; ?>

<div style="display:flex;gap:4px;margin-bottom:24px;border-bottom:0.5px solid rgba(255,255,255,0.08);padding-bottom:0">
  <?php foreach (['site' => 'Site', 'formats' => 'Banner Formats', 'mail' => 'Mail / SMTP', 'maintenance' => 'Maintenance'] as $t => $label): ?>
  <a href="/admin/system?tab=<?= htmlspecialchars($t) ?>"
     style="padding:10px 20px;font-size:13px;text-decoration:none;border-radius:8px 8px 0 0;color:<?= $tab === $t ? '#3ecf8e' : 'rgba(255,255,255,0.4)' ?>;background:<?= $tab === $t ? 'rgba(62,207,142,0.08)' : 'transparent' ?>;border-bottom:<?= $tab === $t ? '2px solid #3ecf8e' : '2px solid transparent' ?>">
    <?= htmlspecialchars($label) ?>
  </a>
  <?php endforeach; ?>
</div>

<?php if ($tab === 'site'): ?>
<div class="admin-section">
  <div class="section-bar" style="padding:14px 20px"><h2 class="section-title">General Site Settings</h2></div>
  <div style="padding:20px">
    <form method="POST" action="/admin/system/save-settings">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="redirect_tab" value="site">

      <div class="form-grid" style="grid-template-columns:1fr 1fr;gap:16px">
        <div class="field">
          <label>Site Name</label>
          <input type="text" name="site_name" value="<?= htmlspecialchars((string)($settings['site_name'] ?? 'aidzap.com')) ?>" required>
        </div>
        <div class="field">
          <label>Site URL</label>
          <input type="text" name="site_url" value="<?= htmlspecialchars((string)($settings['site_url'] ?? 'https://aidzap.com')) ?>" required>
        </div>
        <div class="field">
          <label>Site Email</label>
          <input type="email" name="site_email" value="<?= htmlspecialchars((string)($settings['site_email'] ?? '')) ?>">
        </div>
        <div class="field">
          <label>Support Email</label>
          <input type="email" name="support_email" value="<?= htmlspecialchars((string)($settings['support_email'] ?? '')) ?>">
        </div>
      </div>

      <div style="margin-top:20px;padding:14px;background:#080c10;border-radius:10px;border:0.5px solid rgba(255,255,255,0.08)">
        <label class="checkbox-label" style="display:flex;align-items:center;gap:10px">
          <input id="ga_enabled" type="checkbox" name="ga_enabled" value="1" <?= !empty($settings['ga_enabled']) && $settings['ga_enabled'] !== '0' ? 'checked' : '' ?> style="accent-color:#3ecf8e">
          <span>Google Analytics enabled</span>
        </label>
        <div id="ga_id_wrap" style="margin-top:12px;<?= (!empty($settings['ga_enabled']) && $settings['ga_enabled'] !== '0') ? '' : 'display:none' ?>">
          <label style="display:block;font-size:12px;color:rgba(255,255,255,0.6);margin-bottom:6px">GA Measurement ID</label>
          <input type="text" name="ga_id" placeholder="G-XXXXXXXXXX" value="<?= htmlspecialchars((string)($settings['ga_id'] ?? '')) ?>" style="width:100%;background:#0d1217;border:0.5px solid rgba(255,255,255,0.1);border-radius:8px;padding:10px 12px;color:#fff">
        </div>
      </div>

      <div style="margin-top:20px">
        <button class="btn-primary" type="submit">Save Site Settings</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php if ($tab === 'formats'): ?>
<div style="display:grid;grid-template-columns:1fr;gap:20px">
  <div class="admin-section">
    <div class="section-bar" style="padding:14px 20px"><h2 class="section-title">Add New Banner Format</h2></div>
    <div style="padding:20px">
      <form method="POST" action="/admin/system/banner-formats/create" style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr auto;gap:10px;align-items:end">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <div class="field" style="margin:0">
          <label>Name</label>
          <input type="text" name="name" required placeholder="e.g. Medium Rectangle">
        </div>
        <div class="field" style="margin:0">
          <label>Width</label>
          <input type="number" name="width" min="1" value="300" required>
        </div>
        <div class="field" style="margin:0">
          <label>Height</label>
          <input type="number" name="height" min="1" value="250" required>
        </div>
        <div class="field" style="margin:0">
          <label>Sort Order</label>
          <input type="number" name="sort_order" value="0">
        </div>
        <label class="checkbox-label" style="margin-bottom:10px;display:flex;align-items:center;gap:8px">
          <input type="checkbox" name="is_active" value="1" checked style="accent-color:#3ecf8e">
          <span>Active</span>
        </label>
        <div style="grid-column:1/-1">
          <button class="btn-primary" type="submit">Add Format</button>
        </div>
      </form>
    </div>
  </div>

  <div class="admin-section">
    <div class="section-bar" style="padding:14px 20px"><h2 class="section-title">Existing Banner Formats</h2></div>
    <div style="padding:20px;overflow:auto">
      <table class="admin-table" style="width:100%">
        <thead>
          <tr>
            <th>Name</th>
            <th>Size</th>
            <th>Active</th>
            <th>Sort</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($bannerFormats)): ?>
          <tr><td colspan="5" style="opacity:.6">No banner formats found.</td></tr>
          <?php endif; ?>
          <?php foreach ($bannerFormats as $fmt): ?>
          <tr>
            <td>
              <form method="POST" action="/admin/system/banner-formats/update" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="id" value="<?= (int)$fmt['id'] ?>">
                <input type="text" name="name" value="<?= htmlspecialchars((string)$fmt['name']) ?>" required style="min-width:180px">
            </td>
            <td>
                <div style="display:flex;gap:6px;align-items:center">
                  <input type="number" name="width" min="1" value="<?= (int)$fmt['width'] ?>" style="width:90px">
                  <span>x</span>
                  <input type="number" name="height" min="1" value="<?= (int)$fmt['height'] ?>" style="width:90px">
                </div>
            </td>
            <td>
                <label class="checkbox-label" style="display:flex;align-items:center;gap:6px">
                  <input type="checkbox" name="is_active" value="1" <?= (int)$fmt['is_active'] === 1 ? 'checked' : '' ?> style="accent-color:#3ecf8e">
                  <span><?= (int)$fmt['is_active'] === 1 ? 'Yes' : 'No' ?></span>
                </label>
            </td>
            <td>
                <input type="number" name="sort_order" value="<?= (int)$fmt['sort_order'] ?>" style="width:90px">
            </td>
            <td>
                <button class="btn-ghost-sm" type="submit">Update</button>
              </form>
              <form method="POST" action="/admin/system/banner-formats/delete" style="display:inline-block;margin-top:8px" onsubmit="return confirm('Delete this format?')">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="id" value="<?= (int)$fmt['id'] ?>">
                <button class="btn-danger-sm" type="submit">Delete</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if ($tab === 'mail'): ?>
<div class="admin-section">
  <div class="section-bar" style="padding:14px 20px"><h2 class="section-title">Mail / SMTP</h2></div>
  <div style="padding:20px">
    <form method="POST" action="/admin/system/save-settings">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="redirect_tab" value="mail">

      <div style="margin-bottom:16px">
        <label class="checkbox-label" style="display:flex;align-items:center;gap:10px">
          <input id="smtp_enabled" type="checkbox" name="smtp_enabled" value="1" <?= !empty($settings['smtp_enabled']) && $settings['smtp_enabled'] !== '0' ? 'checked' : '' ?> style="accent-color:#3ecf8e">
          <span>SMTP enabled</span>
        </label>
      </div>

      <div id="smtp_fields" class="form-grid" style="grid-template-columns:1fr 1fr;gap:16px;<?= (!empty($settings['smtp_enabled']) && $settings['smtp_enabled'] !== '0') ? '' : 'opacity:.7' ?>">
        <div class="field">
          <label>SMTP Host</label>
          <input type="text" name="smtp_host" value="<?= htmlspecialchars((string)($settings['smtp_host'] ?? '')) ?>">
        </div>
        <div class="field">
          <label>SMTP Port</label>
          <input type="number" name="smtp_port" min="1" value="<?= htmlspecialchars((string)($settings['smtp_port'] ?? '587')) ?>">
        </div>
        <div class="field">
          <label>SMTP User</label>
          <input type="text" name="smtp_user" value="<?= htmlspecialchars((string)($settings['smtp_user'] ?? '')) ?>">
        </div>
        <div class="field">
          <label>SMTP Password</label>
          <input type="password" name="smtp_pass" value="<?= htmlspecialchars((string)($settings['smtp_pass'] ?? '')) ?>">
        </div>
        <div class="field">
          <label>From Email</label>
          <input type="email" name="smtp_from_email" value="<?= htmlspecialchars((string)($settings['smtp_from_email'] ?? '')) ?>">
        </div>
        <div class="field">
          <label>From Name</label>
          <input type="text" name="smtp_from_name" value="<?= htmlspecialchars((string)($settings['smtp_from_name'] ?? 'aidzap.com')) ?>">
        </div>
        <div class="field">
          <label>Encryption</label>
          <?php $enc = (string)($settings['smtp_encryption'] ?? 'tls'); ?>
          <select name="smtp_encryption">
            <option value="tls" <?= $enc === 'tls' ? 'selected' : '' ?>>TLS</option>
            <option value="ssl" <?= $enc === 'ssl' ? 'selected' : '' ?>>SSL</option>
            <option value="none" <?= $enc === 'none' ? 'selected' : '' ?>>None</option>
          </select>
        </div>
      </div>

      <div style="display:flex;gap:20px;flex-wrap:wrap;margin-top:16px">
        <label class="checkbox-label" style="display:flex;align-items:center;gap:10px">
          <input type="checkbox" name="double_optin" value="1" <?= !empty($settings['double_optin']) && $settings['double_optin'] !== '0' ? 'checked' : '' ?> style="accent-color:#3ecf8e">
          <span>Double opt-in enabled</span>
        </label>
        <label class="checkbox-label" style="display:flex;align-items:center;gap:10px">
          <input type="checkbox" name="newsletter_enabled" value="1" <?= !empty($settings['newsletter_enabled']) && $settings['newsletter_enabled'] !== '0' ? 'checked' : '' ?> style="accent-color:#3ecf8e">
          <span>Newsletter enabled</span>
        </label>
      </div>

      <div style="margin-top:20px;display:flex;gap:10px;flex-wrap:wrap">
        <button class="btn-primary" type="submit">Save Mail Settings</button>
      </div>
    </form>

    <form method="POST" action="/admin/system/mail/test" style="margin-top:14px;display:flex;gap:10px;align-items:end;flex-wrap:wrap">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <div class="field" style="margin:0;min-width:280px">
        <label>Test Email Recipient</label>
        <input type="email" name="test_email" value="<?= htmlspecialchars((string)($settings['support_email'] ?? $settings['site_email'] ?? '')) ?>" placeholder="admin@example.com">
      </div>
      <button class="btn-ghost-sm" type="submit">Send Test Email</button>
    </form>
  </div>
</div>
<?php endif; ?>

<?php if ($tab === 'maintenance'): ?>
<div class="admin-section">
  <div class="section-bar" style="padding:14px 20px"><h2 class="section-title">Maintenance & Security</h2></div>
  <div style="padding:20px">
    <form method="POST" action="/admin/system/save-settings">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="redirect_tab" value="maintenance">

      <div style="margin-bottom:16px;padding:14px;background:#080c10;border-radius:10px;border:0.5px solid rgba(255,255,255,0.08)">
        <label class="checkbox-label" style="display:flex;align-items:center;gap:10px">
          <input type="checkbox" name="maintenance_mode" value="1" <?= !empty($settings['maintenance_mode']) && $settings['maintenance_mode'] !== '0' ? 'checked' : '' ?> style="accent-color:#3ecf8e">
          <span>Maintenance mode enabled (non-admin visitors will see notice page)</span>
        </label>
      </div>

      <div class="field full">
        <label>Maintenance Notice</label>
        <textarea name="maintenance_notice" rows="4" style="width:100%" placeholder="We are back soon."><?= htmlspecialchars((string)($settings['maintenance_notice'] ?? 'We are back soon.')) ?></textarea>
      </div>

      <div style="margin-top:18px;display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div class="field">
          <label>Turnstile Site Key (.env)</label>
          <input type="text" value="<?= htmlspecialchars($mask((string)($turnstileSiteKey ?? ''))) ?>" readonly>
        </div>
        <div class="field">
          <label>Turnstile Secret Key (.env)</label>
          <input type="password" value="<?= htmlspecialchars($mask((string)($turnstileSecretKey ?? ''))) ?>" readonly>
        </div>
      </div>

      <div style="margin-top:20px">
        <button class="btn-primary" type="submit">Save Maintenance Settings</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script>
(function () {
  const gaToggle = document.getElementById('ga_enabled');
  const gaWrap = document.getElementById('ga_id_wrap');
  if (gaToggle && gaWrap) {
    gaToggle.addEventListener('change', function () {
      gaWrap.style.display = this.checked ? '' : 'none';
    });
  }

  const smtpToggle = document.getElementById('smtp_enabled');
  const smtpFields = document.getElementById('smtp_fields');
  if (smtpToggle && smtpFields) {
    smtpToggle.addEventListener('change', function () {
      smtpFields.style.opacity = this.checked ? '1' : '0.7';
    });
  }
})();
</script>
