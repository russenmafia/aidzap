<?php $active = 'quality'; ?>

<div class="page-header">
  <h1 class="page-title">Quality Score System</h1>
  <span style="font-size:12px;color:rgba(255,255,255,0.3)">Publisher quality levels &amp; dynamic referral multipliers</span>
</div>

<?php if (!empty($_GET['saved'])): ?>
<div style="margin-bottom:16px;padding:12px 16px;background:rgba(62,207,142,0.08);border:1px solid rgba(62,207,142,0.2);border-radius:8px;color:#3ecf8e;font-size:13px">
  Settings saved.
</div>
<?php endif; ?>

<?php
$db = \Core\Database::getInstance();

// ── Live stats ────────────────────────────────────────────────────────────────
$levelCounts = $db->query('
    SELECT quality_level, COUNT(*) AS cnt, AVG(revenue_share) AS avg_share
    FROM ad_units WHERE status = "active"
    GROUP BY quality_level
')->fetchAll() ?: [];

$levelRows = $db->query('
    SELECT quality_level,
           COUNT(*) AS cnt,
           ROUND(AVG(revenue_share),1) AS avg_share
    FROM ad_units WHERE status = "active"
    GROUP BY quality_level
')->fetchAll();

$recentHistory = $db->query('
    SELECT qh.*, au.name AS unit_name, u.username
    FROM quality_history qh
    JOIN ad_units au ON au.id = qh.unit_id
    JOIN users u ON u.id = au.user_id
    ORDER BY qh.changed_at DESC
    LIMIT 20
')->fetchAll();

$levelColors = ['bronze' => '#cd7f32', 'silver' => '#aaa', 'gold' => '#f5a623', 'platinum' => '#3ecf8e'];
$levelBg     = ['bronze' => 'rgba(205,127,50,0.08)', 'silver' => 'rgba(180,180,180,0.08)', 'gold' => 'rgba(245,166,35,0.08)', 'platinum' => 'rgba(62,207,142,0.08)'];
?>

<!-- Level Distribution -->
<div class="admin-metrics" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px">
  <?php foreach (['bronze','silver','gold','platinum'] as $lvl):
    $row = array_values(array_filter($levelRows, fn($r) => $r['quality_level'] === $lvl))[0] ?? null;
  ?>
  <div class="metric" style="border-color:<?= $levelColors[$lvl] ?>33">
    <div class="metric-label" style="color:<?= $levelColors[$lvl] ?>"><?= ucfirst($lvl) ?></div>
    <div class="metric-val" style="color:<?= $levelColors[$lvl] ?>"><?= $row ? (int)$row['cnt'] : 0 ?></div>
    <div class="metric-sub"><?= $row ? $row['avg_share'] . '% avg share' : 'no units' ?></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Settings Form -->
<div class="unit-card" style="margin-bottom:24px">
  <div class="unit-header">
    <div class="dt-name">&#9881; Quality &amp; Multiplier Configuration</div>
  </div>
  <div style="padding:24px">
    <form method="POST" action="/admin/quality/save">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

      <!-- CTR Thresholds -->
      <div style="margin-bottom:28px">
        <div style="font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:rgba(255,255,255,0.4);margin-bottom:14px">
          CTR Thresholds (decimal — e.g. 0.0010 = 0.10%)
        </div>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px">
          <?php foreach ([
            ['bronze_max_ctr', 'Bronze → Silver', 'Min CTR to leave bronze'],
            ['silver_max_ctr', 'Silver → Gold',   'Min CTR to reach gold'],
            ['gold_max_ctr',   'Gold → Platinum', 'Min CTR to reach platinum'],
          ] as [$field, $label, $hint]): ?>
          <div class="field">
            <label><?= $label ?></label>
            <input type="number" name="<?= $field ?>" step="0.0001" min="0" max="1"
                   value="<?= htmlspecialchars(number_format((float)$settings[$field], 4, '.', '')) ?>">
            <span class="field-hint"><?= $hint ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Revenue Shares -->
      <div style="margin-bottom:28px">
        <div style="font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:rgba(255,255,255,0.4);margin-bottom:14px">
          Publisher Revenue Shares (%)
        </div>
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px">
          <?php foreach (['bronze','silver','gold','platinum'] as $lvl): ?>
          <div class="field">
            <label style="color:<?= $levelColors[$lvl] ?>"><?= ucfirst($lvl) ?></label>
            <input type="number" name="<?= $lvl ?>_share" step="0.01" min="0" max="100"
                   value="<?= htmlspecialchars(number_format((float)$settings[$lvl . '_share'], 2, '.', '')) ?>"
                   style="border-color:<?= $levelColors[$lvl] ?>44">
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Referral Multipliers -->
      <div style="margin-bottom:28px">
        <div style="font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:rgba(255,255,255,0.4);margin-bottom:4px">
          Referral Commission Multipliers
        </div>
        <div style="font-size:11px;color:rgba(255,255,255,0.3);margin-bottom:14px">
          Applied to the configured referral % rates. 0 = no referral income. 1.5 = 150% of configured rate.
        </div>
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px">
          <?php foreach ([
            ['ref_multiplier_0',     '0 active refs',  'No qualifying referrals'],
            ['ref_multiplier_1',     '1 active ref',   '1 silver+ direct ref'],
            ['ref_multiplier_2',     '2 active refs',  '2 silver+ direct refs'],
            ['ref_multiplier_3plus', '3+ active refs', '3 or more silver+ refs'],
          ] as [$field, $label, $hint]): ?>
          <div class="field">
            <label><?= $label ?></label>
            <input type="number" name="<?= $field ?>" step="0.01" min="0" max="10"
                   value="<?= htmlspecialchars(number_format((float)$settings[$field], 2, '.', '')) ?>">
            <span class="field-hint"><?= $hint ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Advanced Settings -->
      <div style="margin-bottom:28px">
        <div style="font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:rgba(255,255,255,0.4);margin-bottom:14px">
          Advanced Settings
        </div>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px">
          <div class="field">
            <label>Min. own level for ref income</label>
            <select name="min_own_level">
              <?php foreach (['bronze','silver','gold','platinum'] as $lvl): ?>
              <option value="<?= $lvl ?>" <?= $settings['min_own_level'] === $lvl ? 'selected' : '' ?>>
                <?= ucfirst($lvl) ?>
              </option>
              <?php endforeach; ?>
            </select>
            <span class="field-hint">Publisher must reach this level to earn any referral commissions.</span>
          </div>
          <div class="field">
            <label>Concentration cap (%)</label>
            <input type="number" name="concentration_cap_pct" min="1" max="100"
                   value="<?= (int)$settings['concentration_cap_pct'] ?>">
            <span class="field-hint">Max % of ref earnings from a single user (30d).</span>
          </div>
          <div class="field">
            <label>Max fraud score (0–1)</label>
            <input type="number" name="max_fraud_score" step="0.001" min="0" max="1"
                   value="<?= htmlspecialchars(number_format((float)$settings['max_fraud_score'], 3, '.', '')) ?>">
            <span class="field-hint">Units above this avg fraud score stay at bronze.</span>
          </div>
          <div class="field">
            <label>Cooling period (days)</label>
            <input type="number" name="cooling_period_days" min="0" max="365"
                   value="<?= (int)$settings['cooling_period_days'] ?>">
            <span class="field-hint">Days to wait before a downgrade takes effect.</span>
          </div>
          <div class="field">
            <label>Activity window (days)</label>
            <input type="number" name="activity_window_days" min="1" max="365"
                   value="<?= (int)$settings['activity_window_days'] ?>">
            <span class="field-hint">Days a unit must be active before level upgrades are evaluated.</span>
          </div>
        </div>
      </div>

      <button type="submit" class="btn-submit">Save Settings →</button>
    </form>
  </div>
</div>

<!-- Recent Quality Changes -->
<div class="unit-card">
  <div class="unit-header">
    <div class="dt-name">&#128200; Recent Quality Changes</div>
  </div>
  <?php if (empty($recentHistory)): ?>
  <div style="padding:20px;color:rgba(255,255,255,0.3);font-size:13px">No quality changes recorded yet.</div>
  <?php else: ?>
  <div class="data-table">
    <div class="dt-header" style="grid-template-columns:2fr 1fr 1fr 1fr 1fr 120px">
      <div>Unit / User</div>
      <div>Change</div>
      <div>CTR (30d)</div>
      <div>Fraud</div>
      <div>Share</div>
      <div>When</div>
    </div>
    <?php foreach ($recentHistory as $h): ?>
    <div class="dt-row" style="grid-template-columns:2fr 1fr 1fr 1fr 1fr 120px">
      <div>
        <div class="dt-name"><?= htmlspecialchars($h['unit_name']) ?></div>
        <div class="dt-muted"><?= htmlspecialchars($h['username']) ?></div>
      </div>
      <div>
        <span style="color:<?= $levelColors[$h['old_level']] ?? '#888' ?>;font-size:11px"><?= htmlspecialchars($h['old_level']) ?></span>
        <span style="color:rgba(255,255,255,0.2)"> → </span>
        <span style="color:<?= $levelColors[$h['new_level']] ?? '#888' ?>;font-size:11px;font-weight:600"><?= htmlspecialchars($h['new_level']) ?></span>
      </div>
      <div class="dt-muted"><?= number_format((float)$h['ctr_30d'] * 100, 3) ?>%</div>
      <div class="dt-muted"><?= number_format((float)$h['fraud_score'], 3) ?></div>
      <div class="dt-muted"><?= number_format((float)$h['old_share'], 1) ?>% → <span style="color:#3ecf8e"><?= number_format((float)$h['new_share'], 1) ?>%</span></div>
      <div class="dt-muted" style="font-size:11px">
        <?= date('d.m H:i', strtotime($h['changed_at'])) ?>
        <div style="font-size:10px;opacity:0.6"><?= htmlspecialchars($h['reason']) ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
