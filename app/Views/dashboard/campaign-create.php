<?php $active = 'campaigns'; ?>

<div class="page-header">
  <h1 class="page-title">New Campaign</h1>
  <a href="/advertiser/campaigns" class="btn-ghost-sm">← Back</a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert-error">
  <?php foreach ($errors as $e): ?><p><?= htmlspecialchars($e) ?></p><?php endforeach; ?>
</div>
<?php endif; ?>

<form method="POST" action="/advertiser/campaigns/create" id="campaign-form">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

  <div class="wizard-grid">

    <!-- LEFT: Main settings -->
    <div class="wizard-main">

      <!-- Step 1: Basics -->
      <div class="form-section">
        <div class="form-section-title">
          <span class="form-step">1</span> Campaign basics
        </div>
        <div class="form-grid">
          <div class="field full">
            <label>Campaign Name</label>
            <input type="text" name="name" value="<?= htmlspecialchars($old['name'] ?? '') ?>"
                   placeholder="e.g. Crypto Tool Launch Q2" required>
          </div>
          <div class="field full">
            <label>Target URL</label>
            <input type="url" name="target_url" value="<?= htmlspecialchars($old['target_url'] ?? '') ?>"
                   placeholder="https://yoursite.com/landing" required>
            <span class="field-hint">Where visitors land after clicking your ad.</span>
          </div>
        </div>
      </div>

      <!-- Step 2: Pricing -->
      <div class="form-section">
        <div class="form-section-title">
          <span class="form-step">2</span> Pricing model
        </div>
        <div class="model-grid">
          <?php foreach ([
            ['cpd', 'CPD', 'Cost per Day', 'Pay a fixed daily rate for a share of network traffic. Simplest model.'],
            ['cpm', 'CPM', 'Cost per 1000', 'Pay per thousand impressions. Best for brand awareness.'],
            ['cpa', 'CPA', 'Cost per Action', 'Pay only when a conversion happens. Lowest risk.'],
          ] as [$val, $tag, $label, $desc]): ?>
          <label class="model-card">
            <input type="radio" name="pricing_model" value="<?= $val ?>"
                   <?= ($old['pricing_model'] ?? 'cpd') === $val ? 'checked' : '' ?>
                   onchange="updatePricingHints()">
            <span class="model-inner">
              <span class="model-tag"><?= $tag ?></span>
              <span class="model-name"><?= $label ?></span>
              <span class="model-desc"><?= $desc ?></span>
            </span>
          </label>
          <?php endforeach; ?>
        </div>

        <div class="form-grid" style="margin-top:16px">
          <div class="field">
            <label>Currency</label>
            <select name="currency">
              <?php foreach (['BTC','ETH','LTC','USDT','XMR','DOGE','BNB'] as $c): ?>
              <option value="<?= $c ?>" <?= ($old['currency'] ?? 'BTC') === $c ? 'selected' : '' ?>><?= $c ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label id="bid-label">Daily Rate</label>
            <input type="number" name="bid_amount" step="0.00000001" min="0"
                   value="<?= htmlspecialchars($old['bid_amount'] ?? '') ?>"
                   placeholder="0.00100000" required>
            <span class="field-hint" id="bid-hint">Amount per day for network share.</span>
          </div>
          <div class="field">
            <label>Daily Budget</label>
            <input type="number" name="daily_budget" step="0.00000001" min="0"
                   value="<?= htmlspecialchars($old['daily_budget'] ?? '') ?>"
                   placeholder="0.00500000" required>
          </div>
          <div class="field">
            <label>Total Budget</label>
            <input type="number" name="total_budget" step="0.00000001" min="0"
                   value="<?= htmlspecialchars($old['total_budget'] ?? '') ?>"
                   placeholder="0.05000000" required>
            <span class="field-hint">Campaign stops when this is reached.</span>
          </div>
        </div>
      </div>

      <!-- Step 3: Targeting -->
      <div class="form-section">
        <div class="form-section-title">
          <span class="form-step">3</span> Targeting
          <span class="form-section-opt">optional</span>
        </div>
        <div class="form-grid">

          <div class="field full">
            <label>Categories</label>
            <div class="checkbox-grid">
              <?php foreach ($categories as $cat): ?>
              <label class="checkbox-pill">
                <input type="checkbox" name="category_ids[]" value="<?= $cat['id'] ?>"
                  <?= in_array($cat['id'], (array)($old['category_ids'] ?? [])) ? 'checked' : '' ?>>
                <span><?= htmlspecialchars($cat['name']) ?></span>
              </label>
              <?php endforeach; ?>
            </div>
            <span class="field-hint">Leave all unchecked to target all categories.</span>
          </div>

          <!-- Countries -->
          <div class="field full">
            <label>Countries <span style="color:rgba(255,255,255,0.3);font-weight:400;font-size:11px">(leave empty = worldwide)</span></label>
            <select name="target_countries[]" multiple class="targeting-select" style="height:120px">
              <?php foreach (COUNTRIES as $code => $name): ?>
              <option value="<?= $code ?>"
                <?= in_array($code, $targetCountries) ? 'selected' : '' ?>>
                <?= htmlspecialchars($name) ?> (<?= $code ?>)
              </option>
              <?php endforeach; ?>
            </select>
            <span class="field-hint">Hold Ctrl/Cmd to select multiple. Empty = worldwide.</span>
          </div>

          <!-- Languages -->
          <div class="field full">
            <label>Languages <span style="color:rgba(255,255,255,0.3);font-weight:400;font-size:11px">(leave empty = all)</span></label>
            <select name="target_languages[]" multiple class="targeting-select" style="height:100px">
              <?php foreach (LANGUAGES as $code => $name): ?>
              <option value="<?= $code ?>"
                <?= in_array($code, $targetLanguages) ? 'selected' : '' ?>>
                <?= htmlspecialchars($name) ?> (<?= $code ?>)
              </option>
              <?php endforeach; ?>
            </select>
            <span class="field-hint">Matches browser language. Empty = all languages.</span>
          </div>

          <!-- Devices -->
          <div class="field full">
            <label>Devices <span style="color:rgba(255,255,255,0.3);font-weight:400;font-size:11px">(leave all unchecked = all)</span></label>
            <div style="display:flex;gap:12px;margin-top:8px">
              <?php foreach (['desktop' => '&#x1F5A5; Desktop', 'mobile' => '&#x1F4F1; Mobile', 'tablet' => '&#x1F4DF; Tablet'] as $val => $label): ?>
              <label class="checkbox-label" style="display:flex;align-items:center;gap:6px;cursor:pointer">
                <input type="checkbox" name="target_devices[]" value="<?= $val ?>"
                  <?= in_array($val, $targetDevices) ? 'checked' : '' ?>>
                <span><?= $label ?></span>
              </label>
              <?php endforeach; ?>
            </div>
            <span class="field-hint">Detected via User-Agent. Leave unchecked for all devices.</span>
          </div>

          <div class="field">
            <label>Start date <span style="color:rgba(255,255,255,0.25);font-weight:400">(optional)</span></label>
            <input type="datetime-local" name="starts_at"
                   value="<?= htmlspecialchars($old['starts_at'] ?? '') ?>">
          </div>
          <div class="field">
            <label>End date <span style="color:rgba(255,255,255,0.25);font-weight:400">(optional)</span></label>
            <input type="datetime-local" name="ends_at"
                   value="<?= htmlspecialchars($old['ends_at'] ?? '') ?>">
          </div>

        </div>
      </div>

      <div style="display:flex;gap:12px;align-items:center;margin-top:8px">
        <button type="submit" class="btn-submit">Create Campaign →</button>
        <a href="/advertiser/campaigns" class="btn-ghost-sm">Cancel</a>
      </div>

    </div><!-- /wizard-main -->

    <!-- RIGHT: Summary sidebar -->
    <div class="wizard-side">
      <div class="summary-card">
        <div class="summary-title">Campaign summary</div>

        <div class="summary-row">
          <span class="summary-label">Model</span>
          <span class="summary-val" id="sum-model">CPD</span>
        </div>
        <div class="summary-row">
          <span class="summary-label">Bid</span>
          <span class="summary-val" id="sum-bid">–</span>
        </div>
        <div class="summary-row">
          <span class="summary-label">Daily budget</span>
          <span class="summary-val" id="sum-daily">–</span>
        </div>
        <div class="summary-row">
          <span class="summary-label">Total budget</span>
          <span class="summary-val" id="sum-total">–</span>
        </div>
        <div class="summary-row">
          <span class="summary-label">Est. duration</span>
          <span class="summary-val" id="sum-days">–</span>
        </div>

        <div class="summary-divider"></div>

        <div class="summary-note">
          Your campaign starts as a <strong>draft</strong>. After adding banners it will be submitted for review.
        </div>

        <div class="summary-privacy">
          <span>&#x1F512;</span>
          No personal data required. Anonymous campaign creation.
        </div>
      </div>
    </div>

  </div><!-- /wizard-grid -->
</form>

<script>
const pricingHints = {
  cpd: { label: 'Daily Rate',          hint: 'Fixed amount per day for network traffic share.' },
  cpm: { label: 'CPM Bid (per 1000)',  hint: 'Amount paid per 1,000 impressions served.' },
  cpa: { label: 'CPA Bid (per action)',hint: 'Amount paid each time a conversion is recorded.' },
};

function updatePricingHints() {
  const model = document.querySelector('input[name="pricing_model"]:checked')?.value || 'cpd';
  document.getElementById('bid-label').textContent = pricingHints[model].label;
  document.getElementById('bid-hint').textContent  = pricingHints[model].hint;
  document.getElementById('sum-model').textContent = model.toUpperCase();
  updateSummary();
}

function updateSummary() {
  const bid    = parseFloat(document.querySelector('[name="bid_amount"]')?.value) || 0;
  const daily  = parseFloat(document.querySelector('[name="daily_budget"]')?.value) || 0;
  const total  = parseFloat(document.querySelector('[name="total_budget"]')?.value) || 0;
  const cur    = document.querySelector('[name="currency"]')?.value || 'BTC';
  const days   = daily > 0 ? Math.ceil(total / daily) : 0;

  document.getElementById('sum-bid').textContent   = bid   ? bid.toFixed(8) + ' ' + cur : '–';
  document.getElementById('sum-daily').textContent = daily ? daily.toFixed(8) + ' ' + cur : '–';
  document.getElementById('sum-total').textContent = total ? total.toFixed(8) + ' ' + cur : '–';
  document.getElementById('sum-days').textContent  = days  ? '~' + days + ' days' : '–';
}

document.querySelectorAll('input[name="pricing_model"]').forEach(i => i.addEventListener('change', updatePricingHints));
['bid_amount','daily_budget','total_budget','currency'].forEach(n => {
  document.querySelector('[name="' + n + '"]')?.addEventListener('input', updateSummary);
});

updatePricingHints();
</script>
