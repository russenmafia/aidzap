<?php $active = 'billing'; ?>

<div class="page-header">
  <h1 class="page-title">Billing</h1>
</div>

<?php if (isset($_GET['paid'])): ?>
<div class="flash flash-success">Payment received! Your balance will be updated after confirmation.</div>
<?php endif; ?>
<?php if (isset($_GET['withdrawn'])): ?>
<div class="flash flash-success">Withdrawal request submitted. Processing within 24 hours.</div>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
<div class="flash flash-error"><?= htmlspecialchars($_GET['error']) ?></div>
<?php endif; ?>

<div class="wizard-grid">
<div class="wizard-main">

  <!-- Balances -->
  <div class="form-section">
    <div class="form-section-title"><span class="form-step">&#9672;</span> Your Balances</div>
    <?php if (empty($balances)): ?>
    <p style="color:rgba(255,255,255,0.35);font-size:13px">No balance yet. Make a deposit to start advertising.</p>
    <?php else: ?>
    <div style="display:flex;gap:12px;flex-wrap:wrap">
      <?php foreach ($balances as $b): ?>
      <div class="metric" style="min-width:140px">
        <div class="metric-label"><?= htmlspecialchars($b['currency']) ?></div>
        <div class="metric-val green"><?= number_format((float)$b['amount'], 8) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Deposit -->
  <div class="form-section">
    <div class="form-section-title"><span class="form-step">1</span> Deposit</div>
    <div class="form-grid">
      <div class="field">
        <label>Amount (USD)</label>
        <input type="number" id="dep-usd" min="1" step="1" value="50" placeholder="50">
        <span class="field-hint">Minimum $1 USD</span>
      </div>
      <div class="field">
        <label>Pay with</label>
        <select id="dep-currency">
          <?php foreach (['BTC','ETH','LTC','USDT','XMR','DOGE','BNB','SOL','TRX','MATIC'] as $c): ?>
          <option value="<?= $c ?>"><?= $c ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <button type="button" class="btn-submit" style="margin-top:16px" onclick="createDeposit()">
      Generate Payment Address →
    </button>

    <!-- Payment Details (nach API-Call) -->
    <div id="deposit-result" style="display:none;margin-top:20px">
      <div class="deposit-box">
        <div class="deposit-label">Send exactly</div>
        <div class="deposit-amount" id="dep-amount"></div>
        <div class="deposit-label" style="margin-top:16px">To this address</div>
        <div class="deposit-address" id="dep-address" onclick="copyAddress()"></div>
        <button type="button" class="copy-btn" onclick="copyAddress()" style="margin-top:8px">Copy Address</button>
        <div class="deposit-note">
          &#9888; Send only the exact amount. Payment expires in 20 minutes.
          Balance updates automatically after confirmation.
        </div>
      </div>
    </div>
  </div>

  <!-- Withdraw -->
  <div class="form-section">
    <div class="form-section-title"><span class="form-step">2</span> Withdraw Earnings</div>
    <form method="POST" action="/advertiser/withdraw">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Core\Auth::csrfToken()) ?>">
      <div class="form-grid">
        <div class="field">
          <label>Currency</label>
          <select name="currency">
            <option value="BTC">BTC</option>
            <option value="ETH">ETH</option>
            <option value="LTC">LTC</option>
          </select>
        </div>
        <div class="field">
          <label>Amount</label>
          <input type="number" name="amount" step="0.00000001" min="0.0001" placeholder="0.00100000">
        </div>
        <div class="field full">
          <label>Wallet Address</label>
          <input type="text" name="wallet_address" placeholder="Your BTC/ETH/LTC address">
          <span class="field-hint">Double-check your address. Withdrawals cannot be reversed.</span>
        </div>
      </div>
      <button type="submit" class="btn-submit" style="margin-top:16px;background:rgba(62,207,142,0.1);color:#3ecf8e;border:0.5px solid rgba(62,207,142,0.3)">
        Request Withdrawal →
      </button>
    </form>
  </div>

  <!-- Transaction History -->
  <div class="form-section">
    <div class="form-section-title"><span class="form-step">3</span> Transaction History</div>
    <?php if (empty($payments) && empty($invoices)): ?>
    <p style="color:rgba(255,255,255,0.35);font-size:13px">No transactions yet.</p>
    <?php else: ?>
    <div class="data-table">
      <div class="dt-header" style="grid-template-columns:120px 80px 100px 120px 90px">
        <div>Date</div><div>Type</div><div>Currency</div><div>Amount</div><div>Status</div>
      </div>
      <?php foreach ($payments as $p): ?>
      <div class="dt-row" style="grid-template-columns:120px 80px 100px 120px 90px">
        <div class="dt-muted" style="font-size:11px;font-family:'DM Mono',monospace"><?= date('d.m.Y H:i', strtotime($p['created_at'])) ?></div>
        <div><span class="badge <?= $p['type'] === 'deposit' ? 'badge-green' : 'badge-yellow' ?>"><?= htmlspecialchars($p['type']) ?></span></div>
        <div class="dt-muted"><?= htmlspecialchars($p['currency']) ?></div>
        <div class="dt-green"><?= number_format((float)$p['amount'], 8) ?></div>
        <div><span class="badge <?= $p['status'] === 'completed' ? 'badge-green' : 'badge-gray' ?>"><?= htmlspecialchars($p['status']) ?></span></div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

</div><!-- /wizard-main -->

<!-- Summary Sidebar -->
<div class="wizard-side">
  <div class="summary-card">
    <div class="summary-title">Quick Info</div>
    <div class="summary-row"><span class="summary-label">Min. deposit</span><span class="summary-val">$1 USD</span></div>
    <div class="summary-row"><span class="summary-label">Min. withdrawal</span><span class="summary-val">0.0001 BTC</span></div>
    <div class="summary-row"><span class="summary-label">Publisher share</span><span class="summary-val">80%</span></div>
    <div class="summary-row"><span class="summary-label">Platform fee</span><span class="summary-val">20%</span></div>
    <div class="summary-divider"></div>
    <div class="summary-note">
      Deposits are credited after 1 network confirmation.
      Withdrawals are processed within 24 hours.
    </div>
    <div class="summary-privacy">
      <span>&#x1F512;</span>
      No KYC required for any transaction amount.
    </div>
  </div>
</div>

</div><!-- /wizard-grid -->

<script>
async function createDeposit() {
  const usd      = document.getElementById('dep-usd').value;
  const currency = document.getElementById('dep-currency').value;
  const btn      = event.target;

  btn.textContent = 'Generating...';
  btn.disabled    = true;

  try {
    const fd = new FormData();
    fd.append('currency', currency);
    fd.append('usd_amount', usd);

    const resp = await fetch('/payment/deposit', { method: 'POST', body: fd });
    const data = await resp.json();

    if (data.error) {
      alert('Error: ' + data.error);
    } else {
      document.getElementById('dep-amount').textContent  = data.pay_amount + ' ' + data.pay_currency;
      document.getElementById('dep-address').textContent = data.pay_address;
      document.getElementById('deposit-result').style.display = 'block';
      document.getElementById('deposit-result').scrollIntoView({ behavior: 'smooth' });
    }
  } catch(e) {
    alert('Request failed. Please try again.');
  }

  btn.textContent = 'Generate Payment Address →';
  btn.disabled    = false;
}

function copyAddress() {
  const addr = document.getElementById('dep-address').textContent;
  navigator.clipboard.writeText(addr).then(() => {
    const btn = document.querySelector('.copy-btn');
    btn.textContent = 'Copied!';
    setTimeout(() => btn.textContent = 'Copy Address', 2000);
  });
}
</script>
