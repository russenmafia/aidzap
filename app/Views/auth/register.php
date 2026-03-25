<div class="auth-card">
  <h1 class="auth-title"><?= __('auth.register_title') ?></h1>
  <p class="auth-subtitle"><?= __('auth.register_subtitle') ?></p>

  <?php if (!empty($errors)): ?>
  <div class="alert alert-error">
    <?php foreach ($errors as $e): ?><p><?= htmlspecialchars($e) ?></p><?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Wallet Register -->
  <button type="button" class="btn-wallet-login" id="wallet-register-btn" onclick="registerWithWallet()">
    <span class="wallet-icon">&#9830;</span>
    <span id="wallet-register-text"><?= __('auth.wallet_register_btn') ?></span>
    <span class="wallet-hint"><?= __('auth.wallet_register_hint') ?></span>
  </button>

  <div class="auth-divider"><span>or</span></div>

  <!-- Username/Password Register -->
  <form method="POST" action="/register" class="auth-form">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

    <div class="field">
      <label><?= __('auth.username') ?></label>
      <input type="text" name="username" value="<?= htmlspecialchars($old['username'] ?? '') ?>"
             placeholder="satoshi_nakamoto" minlength="3" maxlength="32" required autofocus>
      <span class="field-hint"><?= __('auth.username_hint') ?></span>
    </div>

    <div class="field">
      <label><?= __('auth.password') ?></label>
      <input type="password" name="password" placeholder="<?= __('auth.password_hint') ?>" minlength="12" required>
    </div>

    <div class="field">
      <label><?= __('auth.password_confirm') ?></label>
      <input type="password" name="password_confirm" placeholder="<?= __('auth.password_confirm_hint') ?>" required>
    </div>

    <div class="field">
      <label><?= __('auth.account_type') ?></label>
      <div class="role-toggle">
        <label class="role-option">
          <input type="radio" name="role" value="advertiser" <?= ($old['role'] ?? '') === 'advertiser' ? 'checked' : '' ?>>
          <span class="role-label">
            <span class="role-title"><?= __('auth.role_advertiser') ?></span>
            <span class="role-desc"><?= __('auth.role_advertiser_desc') ?></span>
          </span>
        </label>
        <label class="role-option">
          <input type="radio" name="role" value="publisher" <?= ($old['role'] ?? 'publisher') === 'publisher' ? 'checked' : '' ?>>
          <span class="role-label">
            <span class="role-title"><?= __('auth.role_publisher') ?></span>
            <span class="role-desc"><?= __('auth.role_publisher_desc') ?></span>
          </span>
        </label>
        <label class="role-option">
          <input type="radio" name="role" value="both" <?= ($old['role'] ?? '') === 'both' ? 'checked' : '' ?>>
          <span class="role-label">
            <span class="role-title"><?= __('auth.role_both') ?></span>
            <span class="role-desc"><?= __('auth.role_both_desc') ?></span>
          </span>
        </label>
      </div>
    </div>

    <?php if (!empty($ref_code)): ?>
    <input type="hidden" name="ref_code" value="<?= htmlspecialchars($ref_code) ?>">
    <div style="padding:10px 14px;background:rgba(62,207,142,0.06);border:0.5px solid rgba(62,207,142,0.2);border-radius:8px;font-size:12px;color:rgba(62,207,142,0.8);margin-bottom:12px">
      <?= __('auth.referred_by_friend') ?>
    </div>
    <?php endif; ?>

    <button type="submit" class="btn-submit"><?= __('auth.register_btn') ?></button>
  </form>

  <p class="auth-switch"><?= __('auth.already_account') ?> <a href="/login"><?= __('auth.sign_in_link') ?></a></p>

  <div class="privacy-note">
    <span class="privacy-icon">&#x1F512;</span>
    <?= __('auth.privacy_note') ?>
  </div>
</div>

<script>
async function registerWithWallet() {
  if (typeof window.ethereum === 'undefined') {
    alert('MetaMask not found. Please install MetaMask or use the form below.');
    return;
  }

  const btn = document.getElementById('wallet-register-btn');
  document.getElementById('wallet-register-text').textContent = 'Connecting...';
  btn.disabled = true;

  try {
    const accounts = await window.ethereum.request({ method: 'eth_requestAccounts' });
    const address  = accounts[0];

    // Nonce holen
    const nonceResp = await fetch('/wallet/nonce');
    const { nonce } = await nonceResp.json();

    // Message holen
    const msgFd = new FormData();
    msgFd.append('address', address);
    msgFd.append('nonce', nonce);
    const msgResp = await fetch('/wallet/message', { method: 'POST', body: msgFd });
    const { message } = await msgResp.json();

    // Signatur
    const signature = await window.ethereum.request({
      method: 'personal_sign',
      params: [message, address],
    });

    // Verifizieren + Account erstellen
    const fd = new FormData();
    fd.append('address', address);
    fd.append('signature', signature);
    fd.append('nonce', nonce);
    fd.append('message', message);

    const resp = await fetch('/wallet/verify', { method: 'POST', body: fd });
    const data = await resp.json();

    if (data.error) {
      alert('Error: ' + data.error);
    } else {
      window.location.href = data.redirect || '/dashboard';
    }

  } catch(e) {
    if (e.code === 4001) {
      alert('Request rejected. Please approve the signature in MetaMask.');
    } else {
      alert('Connection failed: ' + e.message);
    }
  }

  document.getElementById('wallet-register-text').textContent = 'Sign up with Wallet';
  btn.disabled = false;
}
</script>

