<?php $active = 'campaigns'; ?>

<div class="page-header">
  <h1 class="page-title">Edit Campaign</h1>
  <a href="/advertiser/campaigns" class="btn-ghost-sm">← Back</a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert-error">
  <?php foreach ($errors as $e): ?><p><?= htmlspecialchars($e) ?></p><?php endforeach; ?>
</div>
<?php endif; ?>

<form method="POST" action="/advertiser/campaigns/<?= htmlspecialchars($campaign['uuid']) ?>/edit">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

<div class="wizard-grid">
<div class="wizard-main">

  <!-- Basics -->
  <div class="form-section">
    <div class="form-section-title"><span class="form-step">1</span> Campaign basics</div>
    <div class="form-grid">
      <div class="field full">
        <label>Campaign Name</label>
        <input type="text" name="name" value="<?= htmlspecialchars($campaign['name']) ?>" required>
      </div>
      <div class="field full">
        <label>Target URL</label>
        <input type="url" name="target_url" value="<?= htmlspecialchars($campaign['target_url'] ?? '') ?>"
               placeholder="https://yoursite.com" required>
        <span class="field-hint">Where clicks will be redirected.</span>
      </div>
    </div>
  </div>

  <!-- Budget -->
  <div class="form-section">
    <div class="form-section-title"><span class="form-step">2</span> Budget & Bidding</div>
    <div class="form-grid">
      <div class="field">
        <label>Pricing Model</label>
        <select name="pricing_model" disabled style="opacity:.5">
          <option value="<?= htmlspecialchars($campaign['pricing_model']) ?>">
            <?= strtoupper(htmlspecialchars($campaign['pricing_model'])) ?>
          </option>
        </select>
        <span class="field-hint">Pricing model cannot be changed after creation.</span>
      </div>
      <div class="field">
        <label>Bid Amount (BTC)</label>
        <input type="number" name="bid_amount" step="0.00000001" min="0.00000001"
               value="<?= htmlspecialchars($campaign['bid_amount']) ?>" required>
        <span class="field-hint">
          <?= $campaign['pricing_model'] === 'cpm' ? 'Per 1000 impressions' : ($campaign['pricing_model'] === 'cpd' ? 'Per day' : 'Per action') ?>
        </span>
      </div>
      <div class="field">
        <label>Daily Budget (BTC)</label>
        <input type="number" name="daily_budget" step="0.00000001" min="0.00000001"
               value="<?= htmlspecialchars($campaign['daily_budget']) ?>" required
               oninput="updateSummary()">
        <span class="field-hint">Available balance: <?= number_format($balance, 8) ?> BTC</span>
      </div>
      <div class="field">
        <label>Total Budget (BTC) <span style="color:rgba(255,255,255,0.25);font-weight:400">(optional)</span></label>
        <input type="number" name="total_budget" step="0.00000001" min="0"
               value="<?= htmlspecialchars($campaign['total_budget'] ?? '') ?>"
               placeholder="0 = unlimited">
      </div>
    </div>
  </div>

  <!-- Schedule -->
  <div class="form-section">
    <div class="form-section-title"><span class="form-step">3</span> Schedule</div>
    <div class="form-grid">
      <div class="field">
        <label>Start Date <span style="color:rgba(255,255,255,0.25);font-weight:400">(optional)</span></label>
        <input type="date" name="starts_at"
               value="<?= $campaign['starts_at'] ? date('Y-m-d', strtotime($campaign['starts_at'])) : '' ?>">
      </div>
      <div class="field">
        <label>End Date <span style="color:rgba(255,255,255,0.25);font-weight:400">(optional)</span></label>
        <input type="date" name="ends_at"
               value="<?= $campaign['ends_at'] ? date('Y-m-d', strtotime($campaign['ends_at'])) : '' ?>">
      </div>
    </div>
  </div>

  <!-- Status -->
  <div class="form-section">
    <div class="form-section-title"><span class="form-step">4</span> Targeting
      <span class="form-section-opt">optional</span>
    </div>
    <div class="form-grid">

      <!-- Countries -->
      <div class="field full">
        <label>Countries <span style="color:rgba(255,255,255,0.3);font-weight:400;font-size:11px">(leave empty = worldwide)</span></label>
        <select name="target_countries[]" multiple class="targeting-select" style="height:120px">
          <?php foreach (COUNTRIES as $code => $cname): ?>
          <option value="<?= $code ?>"
            <?= in_array($code, $targetCountries) ? 'selected' : '' ?>>
            <?= htmlspecialchars($cname) ?> (<?= $code ?>)
          </option>
          <?php endforeach; ?>
        </select>
        <span class="field-hint">Hold Ctrl/Cmd to select multiple. Empty = worldwide.</span>
      </div>

      <!-- Languages -->
      <div class="field full">
        <label>Languages <span style="color:rgba(255,255,255,0.3);font-weight:400;font-size:11px">(leave empty = all)</span></label>
        <select name="target_languages[]" multiple class="targeting-select" style="height:100px">
          <?php foreach (LANGUAGES as $code => $lname): ?>
          <option value="<?= $code ?>"
            <?= in_array($code, $targetLanguages) ? 'selected' : '' ?>>
            <?= htmlspecialchars($lname) ?> (<?= $code ?>)
          </option>
          <?php endforeach; ?>
        </select>
        <span class="field-hint">Matches browser language. Empty = all languages.</span>
      </div>

      <!-- Devices -->
      <div class="field full">
        <label>Devices <span style="color:rgba(255,255,255,0.3);font-weight:400;font-size:11px">(leave all unchecked = all)</span></label>
        <div style="display:flex;gap:12px;margin-top:8px">
          <?php foreach (['desktop' => '&#x1F5A5; Desktop', 'mobile' => '&#x1F4F1; Mobile', 'tablet' => '&#x1F4DF; Tablet'] as $val => $dlabel): ?>
          <label class="checkbox-label" style="display:flex;align-items:center;gap:6px;cursor:pointer">
            <input type="checkbox" name="target_devices[]" value="<?= $val ?>"
              <?= in_array($val, $targetDevices) ? 'checked' : '' ?>>
            <span><?= $dlabel ?></span>
          </label>
          <?php endforeach; ?>
        </div>
        <span class="field-hint">Detected via User-Agent. Leave unchecked for all devices.</span>
      </div>

    </div>
  </div>

  <!-- Status -->
  <div class="form-section">
    <div class="form-section-title"><span class="form-step">5</span> Status</div>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
      <?php foreach ([
        'draft'  => ['gray',   'Draft'],
        'paused' => ['yellow', 'Paused'],
        'active' => ['green',  'Active'],
      ] as $val => [$color, $label]): ?>
      <label class="type-card" style="min-width:120px">
        <input type="radio" name="status" value="<?= $val ?>"
               <?= $campaign['status'] === $val ? 'checked' : '' ?>
               <?= in_array($campaign['status'], ['completed','rejected']) ? 'disabled' : '' ?>>
        <span class="type-inner" style="text-align:center;padding:12px">
          <span class="badge badge-<?= $color ?>" style="margin-bottom:4px"><?= $label ?></span>
        </span>
      </label>
      <?php endforeach; ?>
    </div>
    <?php if (in_array($campaign['status'], ['completed','rejected'])): ?>
    <p style="font-size:12px;color:rgba(255,255,255,0.3);margin-top:8px">
      This campaign is <?= htmlspecialchars($campaign['status']) ?> and cannot be reactivated.
    </p>
    <?php endif; ?>
  </div>

  <!-- Stats (readonly) -->
  <div class="form-section">
    <div class="form-section-title"><span class="form-step">&#9672;</span> Campaign Stats</div>
    <div class="unit-stats">
      <div class="unit-stat">
        <span class="unit-stat-label">Spent</span>
        <span class="unit-stat-val"><?= number_format((float)$campaign['spent'], 8) ?> BTC</span>
      </div>
      <div class="unit-stat">
        <span class="unit-stat-label">Daily budget</span>
        <span class="unit-stat-val"><?= number_format((float)$campaign['daily_budget'], 8) ?> BTC</span>
      </div>
      <div class="unit-stat">
        <span class="unit-stat-label">Created</span>
        <span class="unit-stat-val"><?= date('d.m.Y', strtotime($campaign['created_at'])) ?></span>
      </div>
      <div class="unit-stat">
        <span class="unit-stat-label">Status</span>
        <span class="unit-stat-val"><?= htmlspecialchars(ucfirst($campaign['status'])) ?></span>
      </div>
    </div>
  </div>

  <div style="display:flex;gap:12px;margin-top:8px">
    <button type="submit" class="btn-submit">Save Changes →</button>
    <a href="/advertiser/campaigns/<?= htmlspecialchars($campaign['uuid']) ?>/banners" class="btn-ghost-sm">Manage Banners</a>
    <a href="/advertiser/campaigns" class="btn-ghost-sm">Cancel</a>
  </div>

</div><!-- /wizard-main -->

<!-- Summary Sidebar -->
<div class="wizard-side">
  <div class="summary-card">
    <div class="summary-title">Budget summary</div>
    <div class="summary-row">
      <span class="summary-label">Daily budget</span>
      <span class="summary-val" id="sum-daily"><?= number_format((float)$campaign['daily_budget'], 8) ?></span>
    </div>
    <div class="summary-row">
      <span class="summary-label">Spent so far</span>
      <span class="summary-val"><?= number_format((float)$campaign['spent'], 8) ?> BTC</span>
    </div>
    <div class="summary-row">
      <span class="summary-label">Balance</span>
      <span class="summary-val <?= $balance >= (float)$campaign['daily_budget'] ? 'green' : 'red' ?>">
        <?= number_format($balance, 8) ?> BTC
      </span>
    </div>
    <div class="summary-divider"></div>
    <div class="summary-note">
      You need at least 1 day budget in your balance to activate this campaign.
    </div>
    <?php if ($balance < (float)$campaign['daily_budget']): ?>
    <a href="/advertiser/billing" class="btn-submit" style="display:block;text-align:center;margin-top:12px;font-size:13px">
      Add Funds →
    </a>
    <?php endif; ?>
  </div>
</div>

</div><!-- /wizard-grid -->
</form>

<script>
function updateSummary() {
  const daily = parseFloat(document.querySelector('[name="daily_budget"]').value) || 0;
  document.getElementById('sum-daily').textContent = daily.toFixed(8);
}
</script>
