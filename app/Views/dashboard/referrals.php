<?php $active = 'referrals'; ?>

<div class="page-header">
  <h1 class="page-title">Referrals</h1>
</div>

<!-- Stats -->
<div class="metrics" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px">
  <div class="metric">
    <div class="metric-label">Total referrals</div>
    <div class="metric-val"><?= (int)$stats['counts']['total'] ?></div>
    <div class="metric-sub">all levels</div>
  </div>
  <div class="metric">
    <div class="metric-label">Direct (L1)</div>
    <div class="metric-val"><?= (int)$stats['counts']['level1'] ?></div>
    <div class="metric-sub"><?= number_format((float)$settings['level1_pct'], 1) ?>% commission</div>
  </div>
  <div class="metric">
    <div class="metric-label">Total earned</div>
    <div class="metric-val green"><?= number_format((float)$stats['earnings']['total'], 8) ?></div>
    <div class="metric-sub">BTC commissions</div>
  </div>
  <div class="metric">
    <div class="metric-label">From earnings</div>
    <div class="metric-val"><?= number_format((float)$stats['earnings']['from_earnings'], 8) ?></div>
    <div class="metric-sub">publisher referrals</div>
  </div>
</div>

<!-- Ref Link -->
<div class="unit-card" style="margin-bottom:20px">
  <div class="unit-header"><div class="dt-name">Your Referral Link</div></div>
  <div style="padding:20px">
    <div style="display:flex;gap:10px;align-items:center;margin-bottom:16px">
      <div class="embed-box" style="flex:1;font-size:13px;color:#fff"><?= htmlspecialchars($refUrl) ?></div>
      <button class="copy-btn" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($refUrl) ?>').then(()=>{this.textContent='Copied!';setTimeout(()=>this.textContent='Copy',2000)})">Copy</button>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <div style="font-size:12px;color:rgba(255,255,255,0.3)">Commission structure:</div>
      <span class="badge badge-green">L1: <?= htmlspecialchars($settings['level1_pct']) ?>%</span>
      <span class="badge badge-gray">L2: <?= htmlspecialchars($settings['level2_pct']) ?>%</span>
      <span class="badge badge-gray">L3: <?= htmlspecialchars($settings['level3_pct']) ?>%</span>
    </div>
  </div>
</div>

<!-- Social Share -->
<div class="unit-card" style="margin-bottom:20px">
  <div class="unit-header"><div class="dt-name">Share on Social Media</div></div>
  <div style="padding:20px">

    <!-- Language Tabs -->
    <div class="cron-tab-group" style="margin-bottom:16px">
      <button class="cron-tab active" onclick="setLang('en',this)">English</button>
      <button class="cron-tab" onclick="setLang('de',this)">Deutsch</button>
    </div>

    <!-- Message Box -->
    <textarea id="share-text" rows="5"
              style="width:100%;background:#080c10;border:0.5px solid rgba(255,255,255,0.12);border-radius:8px;padding:12px;font-family:'DM Mono',monospace;font-size:12px;color:#fff;line-height:1.7;resize:vertical"
    ><?= htmlspecialchars($shareTexts['en']) ?></textarea>

    <!-- Share Buttons -->
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:14px">

      <button class="share-btn share-facebook" onclick="shareOn('facebook')">
        <span class="share-icon">f</span> Facebook
      </button>
      <button class="share-btn share-twitter" onclick="shareOn('twitter')">
        <span class="share-icon">𝕏</span> Twitter/X
      </button>
      <button class="share-btn share-telegram" onclick="shareOn('telegram')">
        <span class="share-icon">✈</span> Telegram
      </button>
      <button class="share-btn share-whatsapp" onclick="shareOn('whatsapp')">
        <span class="share-icon">●</span> WhatsApp
      </button>
      <button class="share-btn share-reddit" onclick="shareOn('reddit')">
        <span class="share-icon">●</span> Reddit
      </button>
      <button class="share-btn share-linkedin" onclick="shareOn('linkedin')">
        <span class="share-icon">in</span> LinkedIn
      </button>

      <button class="copy-btn" style="margin-left:auto" onclick="copyShareText()">Copy text</button>
    </div>

    <p style="font-size:11px;color:rgba(255,255,255,0.2);margin-top:12px">
      You can edit the text above before sharing. Your referral link is automatically included.
    </p>
  </div>
</div>

<!-- Referral List -->
<div class="unit-card">
  <div class="unit-header"><div class="dt-name">Your Referrals</div></div>
  <?php if (empty($stats['referrals'])): ?>
  <div style="padding:20px"><p style="font-size:13px;color:rgba(255,255,255,0.3)">No referrals yet. Share your link to start earning commissions.</p></div>
  <?php else: ?>
  <div class="data-table" style="border:none;border-radius:0">
    <div class="dt-header" style="grid-template-columns:1fr 60px 120px">
      <div>User</div><div>Level</div><div>Joined</div>
    </div>
    <?php foreach ($stats['referrals'] as $r): ?>
    <div class="dt-row" style="grid-template-columns:1fr 60px 120px">
      <div class="dt-name"><?= htmlspecialchars($r['username']) ?></div>
      <div><span class="badge badge-gray">L<?= (int)$r['level'] ?></span></div>
      <div class="dt-muted" style="font-size:11px"><?= date('d.m.Y', strtotime($r['user_joined'])) ?></div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<script>
const shareTexts = <?= json_encode($shareTexts) ?>;
const refUrl     = '<?= htmlspecialchars($refUrl) ?>';

function setLang(lang, btn) {
  document.querySelectorAll('.cron-tab').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('share-text').value = shareTexts[lang] || shareTexts['en'];
}

function getShareText() {
  return document.getElementById('share-text').value;
}

function copyShareText() {
  navigator.clipboard.writeText(getShareText()).then(() => {
    const btn = event.target;
    btn.textContent = 'Copied!';
    setTimeout(() => btn.textContent = 'Copy text', 2000);
  });
}

const shareUrls = {
  facebook: text => `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(refUrl)}&quote=${encodeURIComponent(text)}`,
  twitter:  text => `https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}`,
  telegram: text => `https://t.me/share/url?url=${encodeURIComponent(refUrl)}&text=${encodeURIComponent(text)}`,
  whatsapp: text => `https://wa.me/?text=${encodeURIComponent(text)}`,
  reddit:   text => `https://www.reddit.com/submit?url=${encodeURIComponent(refUrl)}&title=${encodeURIComponent(text.split('\n')[0])}`,
  linkedin: text => `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(refUrl)}`,
};

function shareOn(platform) {
  const text = getShareText();
  const url  = shareUrls[platform](text);
  window.open(url, '_blank', 'width=600,height=500,noopener');
}
</script>
