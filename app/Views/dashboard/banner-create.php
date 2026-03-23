<?php $active = 'banners'; ?>

<div class="page-header">
  <h1 class="page-title">New Banner</h1>
  <a href="/advertiser/campaigns/<?= htmlspecialchars($campaign['uuid']) ?>/banners" class="btn-ghost-sm">← Back</a>
</div>

<div style="font-size:13px;color:rgba(255,255,255,0.35);margin-bottom:24px">
  Campaign: <strong style="color:#fff"><?= htmlspecialchars($campaign['name']) ?></strong>
</div>

<?php if (!empty($errors)): ?>
<div class="alert-error">
  <?php foreach ($errors as $e): ?><p><?= htmlspecialchars($e) ?></p><?php endforeach; ?>
</div>
<?php endif; ?>

<form method="POST" action="/advertiser/campaigns/<?= htmlspecialchars($campaign['uuid']) ?>/banners/create"
      enctype="multipart/form-data" id="banner-form">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
<input type="hidden" name="ai_html" id="ai_html_input">

<div class="wizard-grid">
<div class="wizard-main">

  <!-- Basics -->
  <div class="form-section">
    <div class="form-section-title"><span class="form-step">1</span> Banner basics</div>
    <div class="form-grid">
      <div class="field">
        <label>Banner Name</label>
        <input type="text" name="name" value="<?= htmlspecialchars($old['name'] ?? '') ?>"
               placeholder="e.g. 300x250 Dark v1" required>
      </div>
      <div class="field">
        <label>Size</label>
        <select name="size" id="size-select" onchange="updatePreviews()">
          <?php foreach ([
            '300x250' => 'Medium Rectangle (300×250)',
            '728x90'  => 'Leaderboard (728×90)',
            '160x600' => 'Wide Skyscraper (160×600)',
            '320x50'  => 'Mobile Banner (320×50)',
            '468x60'  => 'Full Banner (468×60)',
            '250x250' => 'Square (250×250)',
            '300x600' => 'Half Page (300×600)',
          ] as $val => $label): ?>
          <option value="<?= $val ?>" <?= ($old['size'] ?? '300x250') === $val ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </div>

  <!-- Method Selector -->
  <div class="form-section">
    <div class="form-section-title"><span class="form-step">2</span> Creation method</div>
    <div class="type-grid" style="grid-template-columns:repeat(4,1fr)">
      <?php foreach ([
        ['html',     '&lt;/&gt;', 'HTML/CSS Editor',  'Write your own code'],
        ['template', '&#9635;',   'Templates',          'Pick a ready-made design'],
        ['upload',   '&#8593;',   'Image Upload',       'JPG, PNG, GIF, WebP'],
        ['ai',       '&#9672;',   'AI Generator',       'Claude creates your banner'],
      ] as [$val, $icon, $label, $desc]): ?>
      <label class="type-card">
        <input type="radio" name="method" value="<?= $val ?>"
               <?= ($old['method'] ?? 'html') === $val ? 'checked' : '' ?>
               onchange="showMethod('<?= $val ?>')">
        <span class="type-inner">
          <span class="type-icon"><?= $icon ?></span>
          <span class="type-name"><?= $label ?></span>
          <span class="type-desc"><?= $desc ?></span>
        </span>
      </label>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- HTML Editor -->
  <div class="form-section" id="method-html">
    <div class="form-section-title"><span class="form-step">3</span> HTML/CSS Editor</div>
    <div class="field full">
      <label>Banner HTML <span style="color:rgba(255,255,255,0.25);font-weight:400">(no JavaScript allowed)</span></label>
      <textarea name="html" id="html-editor" rows="12"
                style="font-family:'DM Mono',monospace;font-size:12px;line-height:1.6;resize:vertical"
                oninput="updateHtmlPreview()"
                placeholder="<div style=&quot;width:300px;height:250px;background:#0d1117;...&quot;>..."><?= htmlspecialchars($old['html'] ?? '') ?></textarea>
      <span class="field-hint">Pure HTML/CSS only. Scripts, external resources and event handlers are automatically removed.</span>
    </div>
  </div>

  <!-- Templates -->
  <div class="form-section" id="method-template" style="display:none">
    <div class="form-section-title"><span class="form-step">3</span> Choose Template</div>
    <div class="template-grid">
      <?php foreach ($templates as $tpl): ?>
      <label class="tpl-card">
        <input type="radio" name="template_id" value="<?= $tpl['id'] ?>">
        <span class="tpl-inner">
          <span class="tpl-preview" style="background:<?= $tpl['preview_color'] ?>;border-color:<?= $tpl['accent'] ?>20">
            <span style="color:<?= $tpl['accent'] ?>;font-size:11px;font-weight:600"><?= htmlspecialchars($tpl['name']) ?></span>
          </span>
          <span class="tpl-name"><?= htmlspecialchars($tpl['name']) ?></span>
        </span>
      </label>
      <?php endforeach; ?>
    </div>
    <div class="form-grid" style="margin-top:20px">
      <div class="field full">
        <label>Headline</label>
        <input type="text" name="tpl_headline" value="<?= htmlspecialchars($old['tpl_headline'] ?? '') ?>"
               placeholder="Your main headline" oninput="updateTemplatePreview()">
      </div>
      <div class="field">
        <label>Subline</label>
        <input type="text" name="tpl_subline" value="<?= htmlspecialchars($old['tpl_subline'] ?? '') ?>"
               placeholder="Supporting text" oninput="updateTemplatePreview()">
      </div>
      <div class="field">
        <label>CTA Button</label>
        <input type="text" name="tpl_cta" value="<?= htmlspecialchars($old['tpl_cta'] ?? 'Learn More') ?>"
               placeholder="Learn More" oninput="updateTemplatePreview()">
      </div>
    </div>
  </div>

  <!-- Upload -->
  <div class="form-section" id="method-upload" style="display:none">
    <div class="form-section-title"><span class="form-step">3</span> Image Upload</div>
    <div class="upload-zone" id="upload-zone"
         ondragover="event.preventDefault();this.classList.add('drag')"
         ondragleave="this.classList.remove('drag')"
         ondrop="handleDrop(event)">
      <input type="file" name="banner_image" id="file-input" accept="image/jpeg,image/png,image/gif,image/webp"
             style="display:none" onchange="handleFileSelect(this)">
      <div class="upload-icon">&#8593;</div>
      <div class="upload-text">Drag & drop or <button type="button" onclick="document.getElementById('file-input').click()" class="upload-link">browse</button></div>
      <div class="upload-hint">JPG, PNG, GIF, WebP · Max 512 KB</div>
      <div id="upload-preview" style="display:none;margin-top:12px">
        <img id="upload-img" style="max-width:300px;max-height:200px;border-radius:6px">
        <div id="upload-filename" style="font-size:11px;color:rgba(255,255,255,0.3);margin-top:6px"></div>
      </div>
    </div>
  </div>

  <!-- AI Generator -->
  <div class="form-section" id="method-ai" style="display:none">
    <div class="form-section-title"><span class="form-step">3</span> AI Banner Generator</div>
    <div class="field full">
      <label>Describe your banner</label>
      <textarea name="ai_prompt" id="ai-prompt" rows="3"
                placeholder="e.g. Dark crypto exchange banner with Bitcoin logo, headline 'Trade BTC with 0 fees', green CTA button 'Start Now'"><?= htmlspecialchars($old['ai_prompt'] ?? '') ?></textarea>
      <span class="field-hint">Be specific about colors, text, style and your target audience.</span>
    </div>
    <button type="button" class="btn-ai-generate" onclick="generateAiBanner()" id="ai-btn">
      <span id="ai-btn-text">&#9672; Generate Banner</span>
    </button>
    <div id="ai-result" style="display:none;margin-top:16px">
      <div class="embed-label">Generated HTML</div>
      <pre class="embed-box" id="ai-html-display" style="max-height:150px;overflow:auto"></pre>
      <button type="button" class="btn-ghost-sm" onclick="regenerateAi()" style="margin-top:8px">↻ Regenerate</button>
    </div>
  </div>

  <div style="margin-top:8px;display:flex;gap:12px">
    <button type="submit" class="btn-submit">Submit for Review →</button>
    <a href="/advertiser/campaigns/<?= htmlspecialchars($campaign['uuid']) ?>/banners" class="btn-ghost-sm">Cancel</a>
  </div>

</div><!-- /wizard-main -->

<!-- Preview Sidebar -->
<div class="wizard-side">
  <div class="summary-card">
    <div class="summary-title">Live Preview</div>
    <div id="preview-container" style="background:#fff;border-radius:6px;overflow:hidden;display:inline-block;max-width:100%">
      <iframe id="preview-frame" scrolling="no" frameborder="0"
              style="display:block;border:none;width:300px;height:250px;transform-origin:top left"></iframe>
    </div>
    <div style="font-size:11px;color:rgba(255,255,255,0.25);margin-top:8px" id="preview-size">300 × 250 px</div>
    <div class="summary-divider"></div>
    <div class="summary-note">Preview updates as you type. Final banner will be reviewed before going live.</div>
  </div>
</div>

</div><!-- /wizard-grid -->
</form>

<script>
const CAMPAIGN_UUID = '<?= htmlspecialchars($campaign['uuid']) ?>';

function showMethod(method) {
  ['html','template','upload','ai'].forEach(m => {
    document.getElementById('method-' + m).style.display = m === method ? '' : 'none';
  });
  updatePreviewForMethod(method);
}

function updatePreviews() {
  const size = document.getElementById('size-select').value;
  const [w, h] = size.split('x').map(Number);
  const frame = document.getElementById('preview-frame');
  const scale = Math.min(1, 260 / w);
  frame.style.width  = w + 'px';
  frame.style.height = h + 'px';
  frame.style.transform = 'scale(' + scale + ')';
  document.getElementById('preview-container').style.width  = Math.round(w * scale) + 'px';
  document.getElementById('preview-container').style.height = Math.round(h * scale) + 'px';
  document.getElementById('preview-size').textContent = w + ' × ' + h + ' px';
}

function setPreviewHtml(html) {
  const frame = document.getElementById('preview-frame');
  const doc   = frame.contentDocument || frame.contentWindow.document;
  doc.open();
  doc.write('<!DOCTYPE html><html><head><style>*{margin:0;padding:0;box-sizing:border-box}body{overflow:hidden}</style></head><body>' + html + '</body></html>');
  doc.close();
}

function updateHtmlPreview() {
  setPreviewHtml(document.getElementById('html-editor').value);
}

function updateTemplatePreview() {
  const headline = document.querySelector('[name="tpl_headline"]')?.value || 'Your Headline';
  const subline  = document.querySelector('[name="tpl_subline"]')?.value  || 'Subline text';
  const cta      = document.querySelector('[name="tpl_cta"]')?.value      || 'Learn More';
  const tplId    = document.querySelector('[name="template_id"]:checked')?.value;
  const colors   = {1:['#0d1117','#3ecf8e'],2:['#0a2e1a','#00ff88'],3:['#1a0f00','#ff8c00'],4:['#ffffff','#000000'],5:['#0f0a1a','#7f77dd'],6:['#1a0a0a','#e05454']};
  const [bg, ac] = colors[tplId] || ['#0d1117','#3ecf8e'];
  const size     = document.getElementById('size-select').value;
  const [w, h]   = size.split('x').map(Number);
  const tc       = bg === '#ffffff' ? '#000' : '#fff';
  const fs       = Math.max(12, Math.min(22, Math.round(w/14)));
  setPreviewHtml(`<div style="width:${w}px;height:${h}px;background:${bg};display:flex;flex-direction:column;align-items:center;justify-content:center;padding:20px;box-sizing:border-box;font-family:sans-serif;text-align:center"><div style="font-size:${fs}px;font-weight:700;color:${tc};margin-bottom:8px">${headline}</div><div style="font-size:${Math.max(10,fs-4)}px;color:${tc};opacity:.7;margin-bottom:14px">${subline}</div><div style="background:${ac};color:${bg};padding:7px 18px;border-radius:4px;font-size:12px;font-weight:600">${cta}</div></div>`);
}

function updatePreviewForMethod(method) {
  if (method === 'html')     updateHtmlPreview();
  if (method === 'template') updateTemplatePreview();
}

function handleDrop(e) {
  e.preventDefault();
  document.getElementById('upload-zone').classList.remove('drag');
  const file = e.dataTransfer.files[0];
  if (file) previewFile(file);
}

function handleFileSelect(input) {
  if (input.files[0]) previewFile(input.files[0]);
}

function previewFile(file) {
  const reader = new FileReader();
  reader.onload = e => {
    document.getElementById('upload-img').src = e.target.result;
    document.getElementById('upload-filename').textContent = file.name + ' (' + Math.round(file.size/1024) + ' KB)';
    document.getElementById('upload-preview').style.display = 'block';
    setPreviewHtml('<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:cover;display:block">');
  };
  reader.readAsDataURL(file);
}

async function generateAiBanner() {
  const prompt = document.getElementById('ai-prompt').value.trim();
  const size   = document.getElementById('size-select').value;
  if (prompt.length < 5) { alert('Please describe your banner first.'); return; }

  const btn = document.getElementById('ai-btn');
  document.getElementById('ai-btn-text').textContent = '⏳ Generating...';
  btn.disabled = true;

  try {
    const fd = new FormData();
    fd.append('prompt', prompt);
    fd.append('size', size);

    const resp = await fetch('/advertiser/banners/generate-ai', { method: 'POST', body: fd });
    const data = await resp.json();

    if (data.error) {
      alert('Error: ' + data.error);
    } else {
      document.getElementById('ai-html-display').textContent = data.html;
      document.getElementById('ai_html_input').value = data.html;
      document.getElementById('ai-result').style.display = 'block';
      setPreviewHtml(data.html);
    }
  } catch(e) {
    alert('Generation failed. Please try again.');
  }

  document.getElementById('ai-btn-text').textContent = '⬦ Generate Banner';
  btn.disabled = false;
}

function regenerateAi() {
  document.getElementById('ai-result').style.display = 'none';
  document.getElementById('ai_html_input').value = '';
  generateAiBanner();
}

// Template card click updates preview
document.querySelectorAll('[name="template_id"]').forEach(r => r.addEventListener('change', updateTemplatePreview));

// Init
showMethod('<?= htmlspecialchars($old['method'] ?? 'html') ?>');
updatePreviews();
</script>
