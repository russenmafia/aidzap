<?php $active = 'withdraw'; ?>

<div class="page-header">
  <h1 class="page-title"><?= __('billing.withdraw_earnings') ?></h1>
</div>

<?php if (isset($_GET['done'])): ?>
<div class="flash flash-success"><?= __('withdraw.flash_done') ?></div>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
<div class="flash flash-error"><?= htmlspecialchars($_GET['error']) ?></div>
<?php endif; ?>

<div class="wizard-grid">
<div class="wizard-main">

  <!-- Balance -->
  <div class="form-section">
    <div class="form-section-title"><span class="form-step">&#9672;</span> Available Balance</div>
    <?php if (empty($balances)): ?>
    <p style="font-size:13px;color:rgba(255,255,255,0.35)">No balance available yet. Start earning by placing ad units on your site.</p>
    <?php else: ?>
    <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:8px">
      <?php foreach ($balances as $b): ?>
      <div class="metric" style="min-width:140px">
        <div class="metric-label"><?= htmlspecialchars($b['currency']) ?></div>
        <div class="metric-val green"><?= number_format((float)$b['amount'], 8) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Withdraw Form -->
  <div class="form-section">
    <div class="form-section-title"><span class="form-step">1</span> Request Withdrawal</div>
    <form method="POST" action="/advertiser/withdraw">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Core\Auth::csrfToken()) ?>">
      <div class="form-grid">
        <div class="field">
          <label><?= __('common.currency') ?></label>
          <select name="currency" id="withdraw-currency" onchange="updateWallets()">
            <?php foreach (['BTC','ETH','LTC','USDT','XMR','DOGE'] as $c): ?>
            <option value="<?= $c ?>"><?= $c ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label><?= __('common.amount') ?></label>
          <input type="number" name="amount" step="0.00000001" min="0.0001"
                 placeholder="0.00100000" required>
          <span class="field-hint">Min: 0.0001 BTC / 0.005 ETH / 0.01 LTC</span>
        </div>
        <div class="field full">
          <label><?= __('withdraw.wallet_address') ?></label>
          <?php if (!empty($wallets)): ?>
          <select name="wallet_address" id="wallet-select">
            <option value=""><?= __('withdraw.select_wallet') ?></option>
            <?php foreach ($wallets as $w): ?>
            <option value="<?= htmlspecialchars($w['address']) ?>"
                    data-currency="<?= htmlspecialchars($w['currency']) ?>"
                    <?= $w['is_default'] ? 'data-default="1"' : '' ?>>
              <?= htmlspecialchars($w['currency']) ?>: <?= htmlspecialchars(substr($w['address'],0,20)) ?>...
              <?= $w['label'] ? '(' . htmlspecialchars($w['label']) . ')' : '' ?>
              <?= $w['is_default'] ? '★ Default' : '' ?>
            </option>
            <?php endforeach; ?>
            <option value="__manual__">✎ Enter manually</option>
          </select>
          <input type="text" id="manual-address" name="wallet_address_manual"
                 placeholder="Your wallet address" style="display:none;margin-top:8px">
          <?php else: ?>
          <input type="text" name="wallet_address" placeholder="Your BTC/ETH/LTC address" required>
          <span class="field-hint">No saved wallets. <a href="/account/wallets" style="color:#3ecf8e">Add one →</a></span>
          <?php endif; ?>
          <span class="field-hint" style="color:#e05454">Double-check your address. Withdrawals cannot be reversed.</span>
        </div>
      </div>
      <button type="submit" class="btn-submit" style="margin-top:16px;background:rgba(62,207,142,0.1);color:#3ecf8e;border:0.5px solid rgba(62,207,142,0.3)">
        Request Withdrawal →
      </button>
    </form>
  </div>

  <!-- History -->
  <div class="form-section">
    <div class="form-section-title"><span class="form-step">2</span> Withdrawal History</div>
    <?php if (empty($history)): ?>
    <p style="font-size:13px;color:rgba(255,255,255,0.35)"><?= __('withdraw.no_history') ?></p>
    <?php else: ?>
    <div class="data-table">
      <div class="dt-header" style="grid-template-columns:120px 80px 120px 1fr 90px">
        <div>Date</div><div><?= __('common.currency') ?></div><div><?= __('common.amount') ?></div><div>Address</div><div>Status</div>
      </div>
      <?php foreach ($history as $p): ?>
      <div class="dt-row" style="grid-template-columns:120px 80px 120px 1fr 90px">
        <div class="dt-muted" style="font-size:11px;font-family:'DM Mono',monospace"><?= date('d.m.Y H:i', strtotime($p['created_at'])) ?></div>
        <div class="dt-muted"><?= htmlspecialchars($p['currency']) ?></div>
        <div class="dt-green"><?= number_format((float)$p['amount'], 8) ?></div>
        <div class="dt-muted" style="font-size:11px;font-family:'DM Mono',monospace"><?= htmlspecialchars(substr($p['wallet_address'] ?? '–', 0, 24)) ?>...</div>
        <div><span class="badge <?= $p['status'] === 'completed' ? 'badge-green' : 'badge-yellow' ?>"><?= htmlspecialchars(ucfirst($p['status'])) ?></span></div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

</div>

<!-- Sidebar -->
<div class="wizard-side">
  <div class="summary-card">
    <div class="summary-title"><?= __('withdraw.info_title') ?></div>
    <div class="summary-row"><span class="summary-label">Min. BTC</span><span class="summary-val">0.0001</span></div>
    <div class="summary-row"><span class="summary-label">Min. ETH</span><span class="summary-val">0.005</span></div>
    <div class="summary-row"><span class="summary-label">Min. LTC</span><span class="summary-val">0.01</span></div>
    <div class="summary-row"><span class="summary-label"><?= __('withdraw.processing') ?></span><span class="summary-val">24 hours</span></div>
    <div class="summary-row"><span class="summary-label"><?= __('withdraw.kyc_required') ?></span><span class="summary-val" style="color:#3ecf8e"><?= __('common.never') ?></span></div>
    <div class="summary-divider"></div>
    <div class="summary-note">
      Publisher share is 80% of gross ad revenue. Pending earnings are confirmed after 30 days.
    </div>
    <div style="margin-top:12px">
      <a href="/account/wallets" class="btn-ghost-sm" style="display:block;text-align:center"><?= __('withdraw.manage_wallets') ?></a>
    </div>
  </div>
</div>
</div>

<script>
const walletSelect = document.getElementById('wallet-select');
const manualInput  = document.getElementById('manual-address');

if (walletSelect) {
  walletSelect.addEventListener('change', function() {
    if (this.value === '__manual__') {
      manualInput.style.display = 'block';
      manualInput.required = true;
      this.name = '';
      manualInput.name = 'wallet_address';
    } else {
      manualInput.style.display = 'none';
      manualInput.required = false;
      this.name = 'wallet_address';
      manualInput.name = 'wallet_address_manual';
    }
  });
}
</script>
