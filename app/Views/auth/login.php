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
  <script>const WC_PROJECT_ID = '<?= htmlspecialchars($wc_project_id ?? '') ?>';</script>
  <button type="button" class="btn-wallet-login" id="wallet-btn" onclick="handleWalletLogin()">
    <span style="color:#7f77dd;font-size:16px">&#9830;</span>
    <span id="wallet-btn-text"><?= __('auth.connect_wallet') ?></span>
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
    <!-- Honeypot -->
    <div style="display:none" aria-hidden="true">
      <input type="text" name="website" tabindex="-1" autocomplete="off">
    </div>
    <!-- Turnstile -->
    <div class="cf-turnstile" data-sitekey="<?= $_ENV['TURNSTILE_SITE_KEY'] ?? '' ?>" data-theme="dark"></div>
    <button type="submit" class="btn-submit"><?= __('auth.login_btn') ?></button>
  </form>
  <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

  <p class="auth-switch"><?= __('auth.no_account') ?> <a href="/register"><?= __('auth.create_free') ?></a></p>
</div>

<script type="module">
import { connectWallet } from '/assets/js/wallet.js';

window.handleWalletLogin = async function() {
    const btn     = document.getElementById('wallet-btn');
    const btnText = document.getElementById('wallet-btn-text');
    btnText.textContent = '<?= __('auth.connecting') ?>';
    btn.disabled = true;

    try {
        const { address, signature, nonce, message } = await connectWallet(WC_PROJECT_ID);

        const fd = new FormData();
        fd.append('address',   address);
        fd.append('signature', signature);
        fd.append('nonce',     nonce);
        fd.append('message',   message);

        const resp = await fetch('/wallet/verify', { method: 'POST', body: fd });
        const data = await resp.json();

        if (data.error) alert('Error: ' + data.error);
        else window.location.href = data.redirect ?? '/dashboard';

    } catch(e) {
        if (e.code === 4001) alert('Request rejected. Please approve the signature request.');
        else alert('Connection failed: ' + e.message);
    }

    btnText.textContent = '<?= __('auth.connect_wallet') ?>';
    btn.disabled = false;
};
</script>
