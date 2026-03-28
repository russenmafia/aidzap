<?php $active = 'referrals'; ?>
<div class="page-header">
  <h1 class="page-title">🔗 <?= __('referral.page_title') ?></h1>
</div>

<!-- Section 1: Your Referral Link -->
<div class="unit-card" style="margin-bottom:20px">
  <div class="unit-header">
    <div class="dt-name"><?= __('referral.your_link') ?></div>
  </div>
  <div style="padding:20px">
    <div style="display:flex;gap:12px;margin-bottom:12px">
      <input type="text" id="ref-link-input" readonly value="<?= htmlspecialchars($refLink) ?>"
             style="flex:1;background:#080c10;border:0.5px solid rgba(255,255,255,0.1);border-radius:8px;padding:12px 16px;color:#3ecf8e;font-family:'DM Mono',monospace;font-size:13px">
      <button onclick="copyRefLink()" 
              style="background:#3ecf8e;color:#000;border:none;padding:12px 20px;border-radius:8px;font-weight:600;cursor:pointer;white-space:nowrap">
        📋 <?= __('referral.copy') ?>
      </button>
    </div>
    <div style="font-size:12px;color:rgba(255,255,255,0.3)">
      <?= __('referral.link_hint', ['count' => $stats['counts']['total'] ?? 0]) ?>
    </div>
  </div>
</div>

<!-- Section 2: Banner Embed Codes -->
<div class="unit-card" style="margin-bottom:20px">
  <div class="unit-header">
    <div class="dt-name">🖼️ <?= __('referral.banner_codes') ?></div>
  </div>
  <div style="padding:20px">
    <p style="font-size:13px;color:rgba(255,255,255,0.5);margin-bottom:16px">
      <?= __('referral.banner_hint') ?>
    </p>
    <?php foreach (['468x60','300x250','728x90','160x600'] as $size): 
      [$w,$h] = explode('x', $size);
      $embedCode = '<iframe src="https://aidzap.com/ad/ref/' . htmlspecialchars($refCode) . '/' . $size . '" width="' . $w . '" height="' . $h . '" scrolling="no" frameborder="0" style="border:none"></iframe>';
    ?>
    <div style="margin-bottom:16px">
      <div style="font-size:11px;color:rgba(255,255,255,0.4);margin-bottom:6px"><?= $size ?></div>
      <div style="display:flex;gap:8px">
        <code style="flex:1;background:#080c10;border:0.5px solid rgba(255,255,255,0.08);border-radius:6px;padding:8px 12px;font-size:11px;color:rgba(255,255,255,0.6);font-family:'DM Mono',monospace;word-break:break-all;overflow:auto;max-height:50px">
          <?= htmlspecialchars($embedCode) ?>
        </code>
        <button onclick="copyCode(this, '<?= htmlspecialchars(addslashes($embedCode)) ?>')"
                style="background:rgba(255,255,255,0.06);border:0.5px solid rgba(255,255,255,0.1);color:#fff;padding:8px 14px;border-radius:6px;cursor:pointer;font-size:12px;white-space:nowrap">
          <?= __('referral.copy') ?>
        </button>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Section 3: Social Share Buttons -->
<div class="unit-card" style="margin-bottom:20px">
  <div class="unit-header">
    <div class="dt-name">📢 Share on Social Networks</div>
  </div>
  <div style="padding:20px">
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <?php
        $shareUrl = urlencode($refLink);
        $shareText = urlencode(__('referral.share_text'));
        $networks = [
            ['Facebook',  'https://www.facebook.com/sharer/sharer.php?u=' . $shareUrl, '#1877F2'],
            ['X/Twitter', 'https://twitter.com/intent/tweet?url=' . $shareUrl . '&text=' . $shareText, '#000'],
            ['WhatsApp',  'https://wa.me/?text=' . $shareText . '%20' . $shareUrl, '#25D366'],
            ['Telegram',  'https://t.me/share/url?url=' . $shareUrl . '&text=' . $shareText, '#229ED9'],
            ['Reddit',    'https://reddit.com/submit?url=' . $shareUrl . '&title=' . $shareText, '#FF4500'],
            ['LinkedIn',  'https://www.linkedin.com/sharing/share-offsite/?url=' . $shareUrl, '#0A66C2'],
            ['Pinterest', 'https://pinterest.com/pin/create/button/?url=' . $shareUrl . '&description=' . $shareText, '#E60023'],
            ['Email',     'mailto:?subject=' . urlencode(__('referral.email_subject')) . '&body=' . $shareText . '%20' . $shareUrl, '#555'],
        ];
        foreach ($networks as [$name, $url, $color]): ?>
      <a href="<?= $url ?>" target="_blank" rel="noopener noreferrer"
         style="background:<?= $color ?>;color:#fff;padding:10px 16px;border-radius:6px;text-decoration:none;font-size:13px;font-weight:500;white-space:nowrap;display:inline-block">
        <?= htmlspecialchars($name) ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Section 4: Ready-made Post Texts -->
<?php if (!empty($socialMessages)): ?>
<div class="unit-card" style="margin-bottom:20px">
  <div class="unit-header">
    <div class="dt-name">📝 <?= __('referral.post_texts') ?></div>
  </div>
  <div style="padding:20px">
    <?php foreach ($socialMessages as $msg): ?>
    <div style="background:rgba(255,255,255,0.02);border:0.5px solid rgba(255,255,255,0.06);border-radius:12px;padding:20px;margin-bottom:12px">
      <div style="font-size:13px;font-weight:600;margin-bottom:10px"><?= htmlspecialchars($msg['title']) ?></div>
      <textarea readonly rows="3" style="width:100%;background:#080c10;border:0.5px solid rgba(255,255,255,0.08);border-radius:8px;padding:10px 14px;color:rgba(255,255,255,0.7);font-size:13px;resize:none;font-family:'DM Mono',monospace">
<?= htmlspecialchars($msg['text']) ?></textarea>
      <div style="display:flex;gap:10px;margin-top:10px">
        <button onclick="copyCode(this, '<?= htmlspecialchars(addslashes($msg['text'])) ?>')"
                style="background:rgba(255,255,255,0.06);border:0.5px solid rgba(255,255,255,0.1);color:#fff;padding:8px 14px;border-radius:6px;cursor:pointer;font-size:12px;white-space:nowrap">
          📋 <?= __('referral.copy') ?>
        </button>
        <a href="https://www.facebook.com/sharer/sharer.php?quote=<?= urlencode($msg['text']) ?>&u=<?= $shareUrl ?>"
           target="_blank" rel="noopener noreferrer"
           style="background:rgba(255,255,255,0.06);border:0.5px solid rgba(255,255,255,0.1);color:#fff;padding:8px 14px;border-radius:6px;text-decoration:none;font-size:12px;white-space:nowrap;display:inline-block">
          📘 <?= __('referral.share_fb') ?>
        </a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- Section 5: Statistics -->
<div class="unit-card" style="margin-bottom:20px">
  <div class="unit-header">
    <div class="dt-name">📊 <?= __('referral.stats_title') ?></div>
  </div>
  <div class="metrics" style="grid-template-columns:repeat(4,1fr);padding:20px;gap:16px">
    <div class="metric">
      <div class="metric-label"><?= __('referral.level1') ?></div>
      <div class="metric-val"><?= number_format((int)($stats['counts']['level1'] ?? 0)) ?></div>
      <div class="metric-sub">direct referrals</div>
    </div>
    <div class="metric">
      <div class="metric-label"><?= __('referral.level2') ?></div>
      <div class="metric-val"><?= number_format((int)($stats['counts']['level2'] ?? 0)) ?></div>
      <div class="metric-sub">second level</div>
    </div>
    <div class="metric">
      <div class="metric-label"><?= __('referral.level3') ?></div>
      <div class="metric-val"><?= number_format((int)($stats['counts']['level3'] ?? 0)) ?></div>
      <div class="metric-sub">third level</div>
    </div>
    <div class="metric">
      <div class="metric-label" style="color:#3ecf8e"><?= __('referral.total_earned') ?></div>
      <div class="metric-val" style="color:#3ecf8e"><?= number_format((float)($stats['earnings']['total'] ?? 0), 8) ?></div>
      <div class="metric-sub">BTC total</div>
    </div>
  </div>
</div>

<script>
function copyRefLink() {
    const input = document.getElementById('ref-link-input');
    navigator.clipboard.writeText(input.value).then(() => {
        const msg = document.createElement('div');
        msg.textContent = '✓ <?= __('referral.copied') ?>';
        msg.style.cssText = 'position:fixed;top:20px;right:20px;background:#3ecf8e;color:#000;padding:12px 16px;border-radius:6px;z-index:9999;font-weight:600';
        document.body.appendChild(msg);
        setTimeout(() => msg.remove(), 2000);
    });
}
function copyCode(btn, text) {
    navigator.clipboard.writeText(text).then(() => {
        const orig = btn.textContent;
        btn.textContent = '✓ <?= __('referral.copied') ?>';
        setTimeout(() => btn.textContent = orig, 2000);
    });
}
</script>
