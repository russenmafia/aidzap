<div class="auth-card">
  <h1 class="auth-title"><?= __('auth.login_title') ?></h1>
  <p class="auth-subtitle"><?= __('auth.login_subtitle') ?></p>

  <?php if (!empty($_SESSION['flash_error'])): ?>
  <div class="flash flash-error">
    <?= htmlspecialchars($_SESSION['flash_error']) ?>
    <?php unset($_SESSION['flash_error']); ?>
  </div>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
  <div class="alert alert-error">
    <?php foreach ($errors as $e): ?><p><?= htmlspecialchars($e) ?></p><?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if (!empty($success)): ?>
  <div class="alert alert-success"><p><?= htmlspecialchars($success) ?></p></div>
  <?php endif; ?>

  <!-- Wallet Login -->
  <button type="button" class="btn-wallet-login" id="wallet-btn" onclick="connectWallet()">
    <span class="wallet-icon">&#9830;</span>
    <span id="wallet-btn-text"><?= __('auth.wallet_login_btn') ?></span>
    <span class="wallet-hint"><?= __('auth.wallet_connect_hint') ?></span>
  </button>

  <div class="auth-divider"><span><?= __('auth.or_divider') ?></span></div>

  <!-- Username/Password Login -->
  <form method="POST" action="/login" class="auth-form">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
    <div class="field">
      <label><?= __('auth.username') ?></label>
      <input type="text" name="username" value="<?= htmlspecialchars($old['username'] ?? '') ?>"
             placeholder="your_username" required autofocus>
    </div>
    <div class="field">
      <label><?= __('auth.password') ?></label>
      <input type="password" name="password" placeholder="<?= __('auth.password') ?>" required>
    </div>
    <div class="field-row">
      <label class="checkbox-label">
        <input type="checkbox" name="remember" value="1">
        <span><?= __('auth.remember_me') ?></span>
      </label>
    </div>
    <button type="submit" class="btn-submit"><?= __('auth.login_btn') ?></button>
  </form>

  <p class="auth-switch"><?= __('auth.no_account') ?> <a href="/register"><?= __('auth.create_free') ?></a></p>
</div>

<script>
async function connectWallet() {
  const btn = document.getElementById('wallet-btn');
  document.getElementById('wallet-btn-text').textContent = 'Connecting...';
  btn.disabled = true;

  try {
    // MetaMask verfügbar?
    if (typeof window.ethereum === 'undefined') {
      alert('MetaMask not found. Please install MetaMask or use WalletConnect.');
      btn.disabled = false;
      document.getElementById('wallet-btn-text').textContent = 'Sign in with Wallet';
      return;
    }

    // Accounts anfordern
    const accounts = await window.ethereum.request({ method: 'eth_requestAccounts' });
    const address  = accounts[0];

    // Nonce vom Server holen
    const nonceResp = await fetch('/wallet/nonce');
    const { nonce } = await nonceResp.json();

    // SIWE Message holen
    const msgFd = new FormData();
    msgFd.append('address', address);
    msgFd.append('nonce', nonce);
    const msgResp = await fetch('/wallet/message', { method: 'POST', body: msgFd });
    const { message } = await msgResp.json();

    // Signatur anfordern
    const signature = await window.ethereum.request({
      method: 'personal_sign',
      params: [message, address],
    });

    // Verifizieren
    const verifyFd = new FormData();
    verifyFd.append('address', address);
    verifyFd.append('signature', signature);
    verifyFd.append('nonce', nonce);
    verifyFd.append('message', message);
    const verifyResp = await fetch('/wallet/verify', { method: 'POST', body: verifyFd });
    const result = await verifyResp.json();

    if (result.error) {
      alert('Error: ' + result.error);
    } else {
      window.location.href = result.redirect || '/dashboard';
    }

  } catch(e) {
    if (e.code === 4001) {
      alert('Request rejected. Please approve the signature request in MetaMask.');
    } else {
      alert('Connection failed: ' + e.message);
    }
  }

  btn.disabled = false;
  document.getElementById('wallet-btn-text').textContent = 'Sign in with Wallet';
}
</script>
