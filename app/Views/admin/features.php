<?php $active = 'features'; ?>

<div class="page-header">
  <h1 class="page-title">Feature Flags</h1>
  <span style="font-size:12px;color:rgba(255,255,255,0.3)">Toggle ad-targeting features on/off globally</span>
</div>

<?php if (!empty($_GET['saved'])): ?>
<div class="alert-success" style="margin-bottom:16px;padding:12px 16px;background:rgba(62,207,142,0.08);border:1px solid rgba(62,207,142,0.2);border-radius:8px;color:#3ecf8e;font-size:13px">
  Feature flag updated.
</div>
<?php endif; ?>

<div class="unit-card">
  <div class="unit-header">
    <div class="dt-name">&#9881; Targeting Feature Flags</div>
  </div>

  <?php
  $labels = [
    'targeting_geo'      => ['Geo Targeting', 'Filter campaigns by visitor country using MaxMind GeoLite2 / Cloudflare CF-IPCountry.'],
    'targeting_language' => ['Language Targeting', 'Filter campaigns by browser Accept-Language header.'],
    'targeting_device'   => ['Device Targeting', 'Filter campaigns by device type (desktop / mobile / tablet) via User-Agent.'],
  ];
  ?>

  <div style="padding:0 20px 20px">
    <?php foreach ($flags as $flag):
      $key    = $flag['flag_key'];
      $active_flag = (bool)$flag['is_active'];
      [$title, $desc] = $labels[$key] ?? [$key, ''];
    ?>
    <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid rgba(255,255,255,0.06)">
      <div>
        <div style="font-size:13px;font-weight:600;color:rgba(255,255,255,0.9)"><?= htmlspecialchars($title) ?></div>
        <div style="font-size:11px;color:rgba(255,255,255,0.35);margin-top:2px"><?= htmlspecialchars($desc) ?></div>
        <div style="font-size:10px;font-family:'DM Mono',monospace;color:rgba(255,255,255,0.2);margin-top:3px"><?= htmlspecialchars($key) ?></div>
      </div>
      <form method="POST" action="/admin/features/toggle">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <input type="hidden" name="flag_key"   value="<?= htmlspecialchars($key) ?>">
        <input type="hidden" name="value"       value="<?= $active_flag ? '0' : '1' ?>">
        <button type="submit" class="btn-submit" style="
          padding:6px 20px;
          font-size:12px;
          font-weight:600;
          letter-spacing:.5px;
          background:<?= $active_flag ? 'rgba(62,207,142,0.1)' : 'rgba(255,255,255,0.04)' ?>;
          border:1px solid <?= $active_flag ? 'rgba(62,207,142,0.3)' : 'rgba(255,255,255,0.1)' ?>;
          color:<?= $active_flag ? '#3ecf8e' : 'rgba(255,255,255,0.35)' ?>">
          <?= $active_flag ? 'ON' : 'OFF' ?>
        </button>
      </form>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<div style="margin-top:20px;padding:16px;background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06);border-radius:8px;font-size:12px;color:rgba(255,255,255,0.35);line-height:1.7">
  <strong style="color:rgba(255,255,255,0.5)">Note:</strong>
  When a flag is <strong style="color:#3ecf8e">ON</strong>, campaigns with that targeting field set will only serve to matching visitors.
  When <strong style="color:rgba(255,255,255,0.4)">OFF</strong>, that targeting dimension is ignored and all visitors see all ads.
  Campaigns without a targeting value are always unaffected (serve to everyone).
</div>
