<?php $active = 'referrals'; ?>

<div class="page-header">
  <h1 class="page-title">Referral System</h1>
</div>

<?php if (isset($_GET['saved'])): ?>
<div class="flash flash-success">Settings saved.</div>
<?php endif; ?>

<?php
$db       = \Core\Database::getInstance();
$settings = [];
$totalReferrals   = 0;
$totalCommissions = 0.0;
$totalUsers       = 0;

try {
    $s = $db->query('SELECT * FROM referral_settings WHERE id = 1 LIMIT 1')->fetch();
    if ($s) $settings = $s;
} catch (\Exception $e) {
    error_log('admin/referrals settings: ' . $e->getMessage());
}
try {
    $totalReferrals = (int)$db->query('SELECT COUNT(*) FROM referrals')->fetchColumn();
} catch (\Exception $e) { /* table may not exist yet */ }
try {
    $totalCommissions = (float)$db->query('SELECT COALESCE(SUM(commission),0) FROM referral_earnings')->fetchColumn();
} catch (\Exception $e) { /* table may not exist yet */ }
try {
    $totalUsers = (int)$db->query('SELECT COUNT(*) FROM users WHERE referred_by IS NOT NULL')->fetchColumn();
} catch (\Exception $e) { /* column may not exist yet */ }

// Provide defaults for all expected settings keys
$settings = array_merge([
    'is_active'              => 1,
    'enabled'                => 1,
    'level1_pct'             => 5,
    'level2_pct'             => 3,
    'level3_pct'             => 1,
    'on_earnings'            => 1,
    'on_spend'               => 1,
    'signup_bonus_active'    => 0,
    'signup_bonus_amount'    => '0.00000000',
    'ai_banner_enabled'      => 1,
    'ai_banner_price'        => '0.00000100',
    'impression_interval_min'=> 60,
    'social_messages'        => '[]',
], $settings);
?>

<!-- Stats -->
<div class="admin-metrics" style="grid-template-columns:repeat(3,1fr);margin-bottom:24px">
  <div class="metric">
    <div class="metric-label">Total referrals</div>
    <div class="metric-val"><?= number_format($totalReferrals) ?></div>
    <div class="metric-sub">across all levels</div>
  </div>
  <div class="metric">
    <div class="metric-label">Users referred</div>
    <div class="metric-val"><?= number_format($totalUsers) ?></div>
    <div class="metric-sub">via referral link</div>
  </div>
  <div class="metric">
    <div class="metric-label">Total commissions</div>
    <div class="metric-val green"><?= number_format($totalCommissions, 8) ?></div>
    <div class="metric-sub">BTC paid out</div>
  </div>
</div>

<!-- Settings Form -->
<div class="admin-section">
  <div class="section-bar" style="padding:16px 20px"><h2 class="section-title">Configuration</h2></div>
  <div style="padding:20px">
    <form method="POST" action="/admin/referrals/save">

      <!-- Enable/Disable -->
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;padding:16px;background:#080c10;border-radius:10px;border:0.5px solid rgba(255,255,255,0.08)">
        <label class="checkbox-label" style="font-size:14px;color:#fff">
          <input type="checkbox" name="enabled" value="1" <?= $settings['is_active'] ? 'checked' : '' ?> style="accent-color:#3ecf8e;width:16px;height:16px">
          <span>Referral system enabled</span>
        </label>
      </div>

      <!-- Commission Rates -->
      <div style="margin-bottom:24px">
        <div style="font-size:13px;font-weight:500;color:rgba(255,255,255,0.5);margin-bottom:16px;letter-spacing:.06em;text-transform:uppercase">Commission Rates</div>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px">
          <?php foreach ([
            ['level1_pct', 'Level 1 (direct)', 'Direct referrals'],
            ['level2_pct', 'Level 2',           'Referrals of referrals'],
            ['level3_pct', 'Level 3',           'Third level'],
          ] as [$field, $label, $desc]): ?>
          <div class="field">
            <label><?= $label ?> (%)</label>
            <input type="number" name="<?= $field ?>" step="0.01" min="0" max="50"
                   value="<?= htmlspecialchars($settings[$field]) ?>"
                   oninput="updatePreview()">
            <span class="field-hint"><?= $desc ?></span>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Live Preview -->
        <div style="background:#080c10;border:0.5px solid rgba(255,255,255,0.08);border-radius:10px;padding:16px;margin-top:16px">
          <div style="font-size:12px;color:rgba(255,255,255,0.4);margin-bottom:10px">Example: Publisher earns 0.001 BTC</div>
          <div style="display:flex;gap:24px;font-family:'DM Mono',monospace;font-size:12px">
            <div>Level 1: <span id="prev1" style="color:#3ecf8e"></span> BTC</div>
            <div>Level 2: <span id="prev2" style="color:#3ecf8e"></span> BTC</div>
            <div>Level 3: <span id="prev3" style="color:#3ecf8e"></span> BTC</div>
          </div>
        </div>
      </div>

      <!-- Commission Types -->
      <div style="margin-bottom:24px">
        <div style="font-size:13px;font-weight:500;color:rgba(255,255,255,0.5);margin-bottom:16px;letter-spacing:.06em;text-transform:uppercase">Apply commissions on</div>
        <div style="display:flex;gap:20px">
          <label class="checkbox-label">
            <input type="checkbox" name="on_earnings" value="1" <?= $settings['on_earnings'] ? 'checked' : '' ?> style="accent-color:#3ecf8e">
            <span>Publisher earnings</span>
          </label>
          <label class="checkbox-label">
            <input type="checkbox" name="on_spend" value="1" <?= $settings['on_spend'] ? 'checked' : '' ?> style="accent-color:#3ecf8e">
            <span>Advertiser spend</span>
          </label>
        </div>
      </div>

      <!-- Signup Bonus -->
      <div style="margin-bottom:24px">
        <div style="font-size:13px;font-weight:500;color:rgba(255,255,255,0.5);margin-bottom:16px;letter-spacing:.06em;text-transform:uppercase">Signup Bonus</div>
        <div class="field" style="max-width:200px">
          <label>Bonus per new user (BTC)</label>
          <input type="number" name="signup_bonus" step="0.00000001" min="0"
                 value="<?= htmlspecialchars($settings['signup_bonus_amount']) ?>"
                 placeholder="0.00000000">
          <span class="field-hint">0 = disabled</span>
        </div>
      </div>

      <!-- AI Banner Settings -->
      <div style="margin-bottom:24px;padding-top:24px;border-top:0.5px solid rgba(255,255,255,0.06)">
        <div style="font-size:13px;font-weight:500;color:rgba(255,255,255,0.5);margin-bottom:16px;letter-spacing:.06em;text-transform:uppercase">AI Banner Generator</div>
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;padding:14px;background:#080c10;border-radius:10px;border:0.5px solid rgba(255,255,255,0.08)">
          <label class="checkbox-label" style="font-size:14px;color:#fff">
            <input type="checkbox" name="ai_banner_enabled" value="1"
                   <?= ($settings['ai_banner_enabled'] ?? 1) ? 'checked' : '' ?>
                   style="accent-color:#3ecf8e;width:16px;height:16px">
            <span>AI banner generation enabled</span>
          </label>
        </div>
        <div class="form-grid" style="max-width:400px">
          <div class="field">
            <label>Price per generation (BTC)</label>
            <input type="number" name="ai_banner_price" step="0.00000001" min="0"
                   value="<?= htmlspecialchars(number_format((float)($settings['ai_banner_price'] ?? 0.000001), 8, '.', '')) ?>"
                   placeholder="0.00000100">
            <span class="field-hint">0 = free. Recommended: 0.00000100 BTC (~$0.01)</span>
          </div>
        </div>
      </div>

      <!-- Impression Throttling -->
      <div style="margin-bottom:24px">
        <div style="font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:rgba(255,255,255,0.4);margin-bottom:14px">
          Impression Throttling
        </div>
        <div style="display:grid;grid-template-columns:1fr 2fr;gap:16px;align-items:start">
          <div class="field">
            <label>Interval (minutes)</label>
            <input type="number" name="impression_interval_min" min="1" max="1440"
                   value="<?= (int)($settings['impression_interval_min'] ?? 60) ?>">
            <span class="field-hint">
              Min. minutes between counted impressions per IP per ad unit.<br>
              Default: 60 min. Range: 1-1440 (= 24h).
            </span>
          </div>
          <div style="background:rgba(62,207,142,0.04);border:0.5px solid rgba(62,207,142,0.15);border-radius:12px;padding:16px;font-size:13px;color:rgba(255,255,255,0.5);line-height:1.6">
            <strong style="color:#3ecf8e">How it works:</strong><br>
            If the same IP visits an ad unit within this interval, the impression
            is shown but <strong style="color:#fff">not counted</strong> - no budget
            deducted, no earnings credited. This prevents artificial inflation of
            impression counts.
          </div>
        </div>
      </div>

      <!-- Social Messages Editor -->
      <div style="margin-bottom:28px">
        <div style="font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:rgba(255,255,255,0.4);margin-bottom:8px">
          Post Templates (JSON)
        </div>
        <textarea name="social_messages" rows="12"
                  style="width:100%;background:#080c10;border:0.5px solid rgba(255,255,255,0.1);border-radius:8px;padding:12px 14px;color:rgba(255,255,255,0.7);font-family:'DM Mono',monospace;font-size:11px;resize:vertical"
        ><?= htmlspecialchars($settings['social_messages'] ?? '[]') ?></textarea>
        <span class="field-hint">JSON array. Use {ref_link} as placeholder. Format: [{"title":"...","text":"...","platform":"all"}]</span>
      </div>

      <button type="submit" class="btn-approve">Save Settings →</button>
    </form>
  </div>
</div>

<!-- Top Referrers -->
<div class="admin-section">
  <div class="section-bar" style="padding:16px 20px"><h2 class="section-title">Top Referrers</h2></div>
  <?php
  $topReferrers = [];
  try {
      $topReferrers = $db->query('
        SELECT u.username,
               COUNT(r.id) AS referral_count,
               COALESCE(SUM(re.commission),0) AS total_commission
        FROM users u
        LEFT JOIN referrals r ON r.user_id = u.id
        LEFT JOIN referral_earnings re ON re.user_id = u.id
        GROUP BY u.id
        HAVING referral_count > 0
        ORDER BY total_commission DESC
        LIMIT 10
      ')->fetchAll();
  } catch (\Exception $e) { /* tables may not exist yet */ }
  ?>
  <?php if (empty($topReferrers)): ?>
  <div style="padding:20px"><p style="color:rgba(255,255,255,0.3);font-size:13px">No referrals yet.</p></div>
  <?php else: ?>
  <div class="data-table">
    <div class="dt-header" style="grid-template-columns:1fr 100px 150px">
      <div>User</div><div>Referrals</div><div>Commission (BTC)</div>
    </div>
    <?php foreach ($topReferrers as $r): ?>
    <div class="dt-row" style="grid-template-columns:1fr 100px 150px">
      <div class="dt-name"><?= htmlspecialchars($r['username']) ?></div>
      <div class="dt-muted"><?= (int)$r['referral_count'] ?></div>
      <div class="dt-green"><?= number_format((float)$r['total_commission'], 8) ?></div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<script>
function updatePreview() {
  const base = 0.001;
  ['1','2','3'].forEach(l => {
    const pct = parseFloat(document.querySelector('[name="level'+l+'_pct"]').value) || 0;
    document.getElementById('prev'+l).textContent = (base * pct / 100).toFixed(8);
  });
}
updatePreview();
</script>
