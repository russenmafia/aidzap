<div class="auth-card">
  <h1 class="auth-title">Create account</h1>
  <p class="auth-subtitle">No email required. No KYC. Start in 60 seconds.</p>

  <?php if (!empty($errors)): ?>
  <div class="alert alert-error">
    <?php foreach ($errors as $e): ?><p><?= htmlspecialchars($e) ?></p><?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Wallet Register -->
  <button type="button" class="btn-wallet-login" id="wallet-register-btn" onclick="registerWithWallet()">
    <span class="wallet-icon">&#9830;</span>
    <span id="wallet-register-text">Sign up with Wallet</span>
    <span class="wallet-hint">MetaMask / WalletConnect – no password needed</span>
  </button>

  <div class="auth-divider"><span>or</span></div>

  <!-- Username/Password Register -->
  <form method="POST" action="/register" class="auth-form">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

    <div class="field">
      <label>Username</label>
      <input type="text" name="username" value="<?= htmlspecialchars($old['username'] ?? '') ?>"
             placeholder="satoshi_nakamoto" minlength="3" maxlength="32" required autofocus>
      <span class="field-hint">3–32 characters. Letters, numbers, underscores.</span>
    </div>

    <div class="field">
      <label>Password</label>
      <input type="password" name="password" placeholder="Min. 12 characters" minlength="12" required>
    </div>

    <div class="field">
      <label>Confirm password</label>
      <input type="password" name="password_confirm" placeholder="Repeat password" required>
    </div>

    <div class="field">
      <label>Account type</label>
      <div class="role-toggle">
        <label class="role-option">
          <input type="radio" name="role" value="advertiser" <?= ($old['role'] ?? '') === 'advertiser' ? 'checked' : '' ?>>
          <span class="role-label">
            <span class="role-title">Advertiser</span>
            <span class="role-desc">Run campaigns</span>
          </span>
        </label>
        <label class="role-option">
          <input type="radio" name="role" value="publisher" <?= ($old['role'] ?? 'publisher') === 'publisher' ? 'checked' : '' ?>>
          <span class="role-label">
            <span class="role-title">Publisher</span>
            <span class="role-desc">Monetize traffic</span>
          </span>
        </label>
        <label class="role-option">
          <input type="radio" name="role" value="both" <?= ($old['role'] ?? '') === 'both' ? 'checked' : '' ?>>
          <span class="role-label">
            <span class="role-title">Both</span>
            <span class="role-desc">Advertise &amp; earn</span>
          </span>
        </label>
      </div>
    </div>

    <?php if (!empty($ref_code)): ?>
    <input type="hidden" name="ref_code" value="<?= htmlspecialchars($ref_code) ?>">
    <div style="padding:10px 14px;background:rgba(62,207,142,0.06);border:0.5px solid rgba(62,207,142,0.2);border-radius:8px;font-size:12px;color:rgba(62,207,142,0.8);margin-bottom:12px">
      ✓ Referred by a friend – referral bonus applied
    </div>
    <?php endif; ?>

    <button type="submit" class="btn-submit">Create account &#x2192;</button>
  </form>

  <p class="auth-switch">Already have an account? <a href="/login">Sign in</a></p>

  <div class="privacy-note">
    <span class="privacy-icon">&#x1F512;</span>
    We store no personal data. Your IP is hashed, never logged in plain text.
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

