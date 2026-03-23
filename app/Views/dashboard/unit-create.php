<?php $active = 'units'; ?>

<div class="page-header">
  <h1 class="page-title">New Ad Unit</h1>
  <a href="/publisher/units" class="btn-ghost-sm">← Back to Units</a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert-error">
  <?php foreach ($errors as $e): ?><p><?= htmlspecialchars($e) ?></p><?php endforeach; ?>
</div>
<?php endif; ?>

<form method="POST" action="/publisher/units/create" class="form-card">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

  <!-- Type Selector -->
  <div class="field full" style="margin-bottom:24px">
    <label>Ad Unit Type</label>
    <div class="type-grid">
      <?php foreach ([
        ['banner',       '&#9635;', 'Banner',       'Classic HTML/CSS display ads. No JS, no tracking.'],
        ['native',       '&#9672;', 'Native',        'Blends with your content. Title, image, description.'],
        ['sticky',       '&#8645;', 'Sticky',        'Fixed position banner. Top or bottom of viewport.'],
        ['interstitial', '&#9645;', 'Interstitial',  'Full-screen ad shown between page transitions.'],
      ] as [$val, $icon, $label, $desc]): ?>
      <label class="type-card">
        <input type="radio" name="type" value="<?= $val ?>"
               <?= ($old['type'] ?? 'banner') === $val ? 'checked' : '' ?>>
        <span class="type-inner">
          <span class="type-icon"><?= $icon ?></span>
          <span class="type-name"><?= $label ?></span>
          <span class="type-desc"><?= $desc ?></span>
        </span>
      </label>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="form-grid">

    <!-- Name -->
    <div class="field full">
      <label>Unit Name</label>
      <input type="text" name="name" value="<?= htmlspecialchars($old['name'] ?? '') ?>"
             placeholder="e.g. My Blog – Sidebar 300x250" required>
      <span class="field-hint">Internal name to identify this unit in your dashboard.</span>
    </div>

    <!-- Website URL -->
    <div class="field full">
      <label>Website URL</label>
      <input type="url" name="website_url" value="<?= htmlspecialchars($old['website_url'] ?? '') ?>"
             placeholder="https://yourdomain.com" required>
    </div>

    <!-- Category -->
    <div class="field">
      <label>Category</label>
      <select name="category_id">
        <option value="">— Select category —</option>
        <?php foreach ($categories as $cat): ?>
        <option value="<?= $cat['id'] ?>"
          <?= ($old['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($cat['name']) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Banner Size (shown only for banner + sticky) -->
    <div class="field" id="field-size">
      <label>Banner Size</label>
      <select name="size">
        <?php foreach ([
          '300x250' => 'Medium Rectangle (300×250)',
          '728x90'  => 'Leaderboard (728×90)',
          '160x600' => 'Wide Skyscraper (160×600)',
          '320x50'  => 'Mobile Banner (320×50)',
          '468x60'  => 'Full Banner (468×60)',
          '250x250' => 'Square (250×250)',
          '300x600' => 'Half Page (300×600)',
        ] as $val => $label): ?>
        <option value="<?= $val ?>" <?= ($old['size'] ?? '300x250') === $val ? 'selected' : '' ?>>
          <?= $label ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Sticky Position (shown only for sticky) -->
    <div class="field" id="field-sticky" style="display:none">
      <label>Sticky Position</label>
      <select name="sticky_position">
        <option value="bottom" <?= ($old['sticky_position'] ?? 'bottom') === 'bottom' ? 'selected' : '' ?>>Bottom of page</option>
        <option value="top"    <?= ($old['sticky_position'] ?? '') === 'top' ? 'selected' : '' ?>>Top of page</option>
      </select>
    </div>

    <!-- Floor Price -->
    <div class="field">
      <label>Floor Price (CPM)</label>
      <input type="number" name="floor_price" step="0.00000001" min="0"
             value="<?= htmlspecialchars($old['floor_price'] ?? '0') ?>"
             placeholder="0.00000000">
      <span class="field-hint">Minimum CPM in BTC. Leave 0 to accept all bids.</span>
    </div>

    <!-- Fallback HTML -->
    <div class="field full" id="field-fallback">
      <label>Fallback HTML <span style="color:rgba(255,255,255,0.25);font-weight:400">(optional)</span></label>
      <textarea name="fallback_html" rows="3"
                placeholder="HTML shown when no ad matches. Leave empty for blank."><?= htmlspecialchars($old['fallback_html'] ?? '') ?></textarea>
      <span class="field-hint">Pure HTML/CSS only. No JavaScript allowed.</span>
    </div>

  </div><!-- /form-grid -->

  <!-- Native-specific fields -->
  <div id="native-fields" style="display:none;margin-top:8px">
    <div class="native-divider">Native Ad Defaults <span>These are shown to advertisers as guidelines</span></div>
    <div class="form-grid">
      <div class="field">
        <label>Recommended title length</label>
        <input type="number" name="native_title_max" min="10" max="200"
               value="<?= htmlspecialchars($old['native_title_max'] ?? '60') ?>">
      </div>
      <div class="field">
        <label>Recommended description length</label>
        <input type="number" name="native_desc_max" min="20" max="500"
               value="<?= htmlspecialchars($old['native_desc_max'] ?? '120') ?>">
      </div>
      <div class="field full">
        <label>Native container CSS class</label>
        <input type="text" name="native_css_class"
               value="<?= htmlspecialchars($old['native_css_class'] ?? '') ?>"
               placeholder="e.g. sponsored-post, native-ad-slot">
        <span class="field-hint">The CSS class on your site where native ads will be injected.</span>
      </div>
    </div>
  </div>

  <div style="margin-top:24px;display:flex;gap:12px;align-items:center">
    <button type="submit" class="btn-submit">Create Ad Unit →</button>
    <a href="/publisher/units" class="btn-ghost-sm">Cancel</a>
  </div>

</form>

<!-- Preview box (shown after type selection) -->
<div class="embed-preview" id="embed-preview" style="display:none">
  <div class="embed-preview-title">Embed Code Preview</div>
  <div class="embed-preview-note">After creation, you'll receive your unique embed code. Here's what it will look like:</div>
  <div class="embed-code" id="embed-code-sample"></div>
</div>

<script>
const typeInputs  = document.querySelectorAll('input[name="type"]');
const fieldSize   = document.getElementById('field-size');
const fieldSticky = document.getElementById('field-sticky');
const nativeFields= document.getElementById('native-fields');
const previewBox  = document.getElementById('embed-preview');
const codeSample  = document.getElementById('embed-code-sample');

const embedSamples = {
  banner:       '&lt;iframe src="https://aidzap.com/ad/YOUR-UNIT-ID" width="300" height="250"\n  scrolling="no" frameborder="0" style="border:none"&gt;&lt;/iframe&gt;',
  native:       '&lt;div class="your-native-slot"\n  data-aidzap-unit="YOUR-UNIT-ID"\n  data-aidzap-type="native"&gt;&lt;/div&gt;\n&lt;script src="https://aidzap.com/ad/native.js"&gt;&lt;/script&gt;',
  sticky:       '&lt;script&gt;\n  (function(){var s=document.createElement("script");\n  s.src="https://aidzap.com/ad/sticky.js?unit=YOUR-UNIT-ID";\n  document.head.appendChild(s)})();\n&lt;/script&gt;',
  interstitial: '&lt;script&gt;\n  window.aidzapInterstitial = { unit: "YOUR-UNIT-ID", trigger: "pageview" };\n&lt;/script&gt;\n&lt;script src="https://aidzap.com/ad/interstitial.js"&gt;&lt;/script&gt;',
};

function updateType() {
  const val = document.querySelector('input[name="type"]:checked')?.value || 'banner';
  fieldSize.style.display   = ['banner','sticky'].includes(val) ? '' : 'none';
  fieldSticky.style.display = val === 'sticky' ? '' : 'none';
  nativeFields.style.display= val === 'native' ? '' : 'none';
  previewBox.style.display  = 'block';
  codeSample.textContent    = embedSamples[val] || '';
}

typeInputs.forEach(i => i.addEventListener('change', updateType));
updateType();
</script>
