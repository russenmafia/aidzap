<div class="auth-card">
  <h1 class="auth-title"><?= __('auth.register_title') ?></h1>
  <p class="auth-subtitle"><?= __('auth.register_subtitle') ?></p>

  <?php if (!empty($errors)): ?>
  <div class="alert alert-error">
    <?php foreach ($errors as $e): ?><p><?= htmlspecialchars($e) ?></p><?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Wallet Register -->
  <script>const WC_PROJECT_ID = '<?= htmlspecialchars($wc_project_id ?? '') ?>';</script>
  <button type="button" class="btn-wallet-login" id="wallet-register-btn" onclick="handleWalletRegister()">
    <span style="color:#7f77dd;font-size:16px">&#9830;</span>
    <span id="wallet-register-text"><?= __('auth.connect_wallet') ?></span>
  </button>
  <div id="wallet-confirm" style="display:none;font-size:12px;color:#3ecf8e;margin-top:6px;text-align:center"></div>

  <div class="auth-divider"><span><?= __('auth.or_divider') ?></span></div>

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

    <!-- Hidden wallet fields (populated by handleWalletRegister) -->
    <input type="hidden" id="reg-wallet-address"   name="wallet_address">
    <input type="hidden" id="reg-wallet-signature" name="wallet_signature">
    <input type="hidden" id="reg-wallet-nonce"     name="wallet_nonce">
    <input type="hidden" id="reg-wallet-message"   name="wallet_message">

    <!-- Honeypot -->
    <div style="display:none" aria-hidden="true">
      <input type="text" name="website" tabindex="-1" autocomplete="off">
    </div>
    <!-- Turnstile -->
    <div class="cf-turnstile" data-sitekey="<?= $_ENV['TURNSTILE_SITE_KEY'] ?? '' ?>" data-theme="dark"></div>
    <button type="submit" class="btn-submit"><?= __('auth.register_btn') ?></button>
  </form>
  <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

  <p class="auth-switch"><?= __('auth.already_account') ?> <a href="/login"><?= __('auth.sign_in_link') ?></a></p>

  <div class="privacy-note">
    <span class="privacy-icon">&#x1F512;</span>
    <?= __('auth.privacy_note') ?>
  </div>
</div>

<script type="module">
import { connectWallet } from '/assets/js/wallet.js';

window.handleWalletRegister = async function() {
    const btn     = document.getElementById('wallet-register-btn');
    const btnText = document.getElementById('wallet-register-text');
    btnText.textContent = '<?= __('auth.connecting') ?>';
    btn.disabled = true;

    try {
        const { address, signature, nonce, message } = await connectWallet(WC_PROJECT_ID);

        document.getElementById('reg-wallet-address').value   = address;
        document.getElementById('reg-wallet-signature').value = signature;
        document.getElementById('reg-wallet-nonce').value     = nonce;
        document.getElementById('reg-wallet-message').value   = message;

        const confirm = document.getElementById('wallet-confirm');
        confirm.textContent = address.slice(0,6) + '...' + address.slice(-4) + ' ✓';
        confirm.style.display = 'block';

    } catch(e) {
        if (e.code === 4001) alert('Request rejected. Please approve the signature request.');
        else alert('Connection failed: ' + e.message);
    }

    btnText.textContent = '<?= __('auth.connect_wallet') ?>';
    btn.disabled = false;
};
</script>

