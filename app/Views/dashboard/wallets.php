<?php $active = 'wallets'; ?>

<div class="page-header">
  <h1 class="page-title"><?= __('wallets.title') ?></h1>
</div>

<?php if (isset($_GET['added'])): ?><div class="flash flash-success"><?= __('wallets.flash_added') ?></div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="flash flash-success"><?= __('wallets.flash_deleted') ?></div><?php endif; ?>
<?php if (isset($_GET['default'])): ?><div class="flash flash-success"><?= __('wallets.flash_default') ?></div><?php endif; ?>
<?php if (!empty($errors)): ?>
<div class="flash flash-error"><?php foreach ($errors as $e): ?><p><?= htmlspecialchars($e) ?></p><?php endforeach; ?></div>
<?php endif; ?>

<div class="wizard-grid">
<div class="wizard-main">

  <!-- Login Wallet -->
  <?php if ($login_wallet): ?>
  <div class="form-section">
    <div class="form-section-title"><span class="form-step">&#9672;</span> Login Wallet (MetaMask)</div>
    <div style="display:flex;align-items:center;gap:12px;padding:8px 0">
      <div style="font-family:'DM Mono',monospace;font-size:13px;color:#3ecf8e;background:rgba(62,207,142,0.06);border:0.5px solid rgba(62,207,142,0.2);border-radius:8px;padding:10px 14px;flex:1;word-break:break-all">
        <?= htmlspecialchars($login_wallet) ?>
      </div>
      <span class="badge badge-green">Connected</span>
    </div>
    <p style="font-size:12px;color:rgba(255,255,255,0.3);margin-top:8px">
      This wallet is used for login. Manage it in <a href="/account/settings" style="color:#3ecf8e"><?= __('settings.title') ?></a>.
    </p>
  </div>
  <?php endif; ?>

  <!-- Payout Wallets -->
  <div class="form-section">
    <div class="form-section-title"><span class="form-step">1</span> Payout Wallets</div>

    <?php if (empty($wallets)): ?>
    <p style="color:rgba(255,255,255,0.35);font-size:13px;margin-bottom:16px"><?= __('wallets.no_wallets') ?></p>
    <?php else: ?>
    <div class="data-table" style="margin-bottom:20px">
      <div class="dt-header" style="grid-template-columns:80px 1fr 100px 80px 80px">
        <div><?= __('common.currency') ?></div><div>Address</div><div>Label</div><div>Default</div><div>Action</div>
      </div>
      <?php foreach ($wallets as $w): ?>
      <div class="dt-row" style="grid-template-columns:80px 1fr 100px 80px 80px">
        <div><span class="badge badge-gray"><?= htmlspecialchars($w['currency']) ?></span></div>
        <div class="dt-muted" style="font-family:'DM Mono',monospace;font-size:11px;word-break:break-all"><?= htmlspecialchars($w['address']) ?></div>
        <div class="dt-muted"><?= htmlspecialchars($w['label'] ?? '–') ?></div>
        <div>
          <?php if ($w['is_default']): ?>
          <span class="badge badge-green">Default</span>
          <?php else: ?>
          <form method="POST" action="/account/wallets/default" style="display:inline">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="hidden" name="wallet_id" value="<?= (int)$w['id'] ?>">
            <button class="action-btn" style="font-size:10px">Set default</button>
          </form>
          <?php endif; ?>
        </div>
        <div>
          <form method="POST" action="/account/wallets/delete" style="display:inline" onsubmit="return confirm('Remove this wallet?')">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="hidden" name="wallet_id" value="<?= (int)$w['id'] ?>">
            <button class="action-btn" style="color:#e05454;border-color:rgba(224,84,84,0.25)">Remove</button>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Add Wallet Form -->
    <div style="border-top:0.5px solid rgba(255,255,255,0.06);padding-top:20px">
      <div style="font-size:13px;font-weight:500;color:rgba(255,255,255,0.5);margin-bottom:16px"><?= __('wallets.add_title') ?></div>
      <form method="POST" action="/account/wallets/add">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <div class="form-grid">
          <div class="field">
            <label><?= __('common.currency') ?></label>
            <select name="currency">
              <?php foreach (['BTC','ETH','LTC','USDT','XMR','DOGE','BNB','SOL','TRX','MATIC'] as $c): ?>
              <option value="<?= $c ?>"><?= $c ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label>Label <span style="color:rgba(255,255,255,0.25);font-weight:400">(optional)</span></label>
            <input type="text" name="label" placeholder="e.g. My BTC wallet">
          </div>
          <div class="field full">
            <label><?= __('withdraw.wallet_address') ?></label>
            <input type="text" name="address" placeholder="bc1q... / 0x... / L..." required>
            <span class="field-hint">Double-check your address. Withdrawals cannot be reversed.</span>
          </div>
          <div class="field full">
            <label class="checkbox-label">
              <input type="checkbox" name="is_default" value="1">
              <span><?= __('wallets.set_default_label') ?></span>
            </label>
          </div>
        </div>
        <button type="submit" class="btn-submit" style="margin-top:8px"><?= __('wallets.add_btn') ?></button>
      </form>
    </div>
  </div>

</div>

<!-- Sidebar -->
<div class="wizard-side">
  <div class="summary-card">
    <div class="summary-title"><?= __('wallets.supported') ?></div>
    <?php foreach (['BTC','ETH','LTC','USDT','XMR','DOGE','BNB','SOL','TRX','MATIC'] as $c): ?>
    <div class="summary-row"><span class="summary-label"><?= $c ?></span><span class="summary-val badge badge-gray"><?= $c ?></span></div>
    <?php endforeach; ?>
    <div class="summary-divider"></div>
    <div class="summary-note">Withdrawals are processed within 24 hours. No KYC required.</div>
  </div>
</div>
</div>
