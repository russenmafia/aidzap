<?php $active = 'settings'; ?>

<div class="page-header">
  <h1 class="page-title"><?= __('settings.title') ?></h1>
</div>

<?php
$successMessages = [
    'username'       => 'Username updated successfully.',
    'password'       => 'Password changed successfully.',
    'token'          => 'New API token generated.',
    'revoked'        => 'API token revoked.',
    'wallet_unlinked'=> 'Wallet login disconnected.',
];
if (!empty($success) && isset($successMessages[$success])): ?>
<div class="flash flash-success"><?= $successMessages[$success] ?></div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<div class="flash flash-error"><?php foreach ($errors as $e): ?><p><?= htmlspecialchars($e) ?></p><?php endforeach; ?></div>
<?php endif; ?>

<?php if (isset($_GET['error']) && $_GET['error'] === 'set_password_first'): ?>
<div class="flash flash-error">Please set a password before disconnecting your wallet login.</div>
<?php endif; ?>

<!-- Username -->
<div class="form-section" style="max-width:600px;margin-bottom:16px">
  <div class="form-section-title"><span class="form-step">1</span> Username</div>
  <form method="POST" action="/account/settings/username">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
    <div class="form-grid">
      <div class="field full">
        <label><?= __('settings.username') ?></label>
        <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>"
               minlength="3" maxlength="32" required>
        <span class="field-hint">3–32 characters. Letters, numbers, underscores only.</span>
      </div>
    </div>
    <button type="submit" class="btn-submit" style="margin-top:12px">Update Username →</button>
  </form>
</div>

<!-- Password -->
<div class="form-section" style="max-width:600px;margin-bottom:16px">
  <div class="form-section-title"><span class="form-step">2</span> Password</div>
  <?php if (empty($user['password_hash']) && !empty($user['wallet_address'])): ?>
  <p style="font-size:13px;color:rgba(255,255,255,0.4);margin-bottom:16px">
    You signed up with a wallet. Set a password to also enable username/password login.
  </p>
  <?php endif; ?>
  <form method="POST" action="/account/settings/password">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
    <div class="form-grid">
      <?php if (!empty($user['password_hash'])): ?>
      <div class="field full">
        <label>Current Password</label>
        <input type="password" name="current_password" placeholder="Current password" required>
      </div>
      <?php endif; ?>
      <div class="field">
        <label>New Password</label>
        <input type="password" name="new_password" placeholder="Min. 12 characters" minlength="12" required>
      </div>
      <div class="field">
        <label>Confirm Password</label>
        <input type="password" name="confirm_password" placeholder="Repeat new password" required>
      </div>
    </div>
    <button type="submit" class="btn-submit" style="margin-top:12px">Change Password →</button>
  </form>
</div>

<!-- Wallet Login -->
<div class="form-section" style="max-width:600px;margin-bottom:16px">
  <div class="form-section-title"><span class="form-step">3</span> Wallet Login</div>
  <?php if (!empty($user['wallet_address'])): ?>
  <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
    <div style="font-family:'DM Mono',monospace;font-size:12px;color:#3ecf8e;background:rgba(62,207,142,0.06);border:0.5px solid rgba(62,207,142,0.2);border-radius:8px;padding:10px 14px;flex:1;word-break:break-all">
      <?= htmlspecialchars($user['wallet_address']) ?>
    </div>
    <span class="badge badge-green">Connected</span>
  </div>
  <form method="POST" action="/account/settings/wallet/unlink">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
    <button type="submit" class="btn-reject" onclick="return confirm('Disconnect wallet login?')"><?= __('settings.disconnect_wallet') ?></button>
  </form>
  <?php else: ?>
  <script>const WC_PROJECT_ID = '<?= htmlspecialchars($wc_project_id ?? '') ?>';</script>
  <p style="font-size:13px;color:rgba(255,255,255,0.4);margin-bottom:16px"><?= __('settings.no_wallet') ?></p>
  <button type="button" class="btn-wallet-login" style="max-width:300px" onclick="linkWallet()">
    <span style="color:#7f77dd;font-size:16px">&#9830;</span>
    <span id="link-btn-text" style="font-size:13px;font-weight:500;color:#fff"><?= __('settings.connect_wallet') ?></span>
  </button>
  <?php endif; ?>
</div>

<!-- API Token -->
<div class="form-section" style="max-width:600px;margin-bottom:16px">
  <div class="form-section-title"><span class="form-step">4</span> API Token</div>
  <?php if (!empty($user['api_token'])): ?>
  <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px">
    <div class="embed-box" style="flex:1;font-size:12px"><?= htmlspecialchars($user['api_token']) ?></div>
    <button class="copy-btn" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($user['api_token']) ?>')">Copy</button>
  </div>
  <div style="display:flex;gap:8px">
    <form method="POST" action="/account/settings/token/generate" style="display:inline">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <button class="action-btn" onclick="return confirm('Regenerate token? Old token will stop working.')"><?= __('common.regenerate') ?></button>
    </form>
    <form method="POST" action="/account/settings/token/revoke" style="display:inline">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <button class="action-btn" style="color:#e05454;border-color:rgba(224,84,84,0.25)" onclick="return confirm('Revoke API token?')"><?= __('common.revoke') ?></button>
    </form>
  </div>
  <?php else: ?>
  <p style="font-size:13px;color:rgba(255,255,255,0.4);margin-bottom:16px">No API token. Generate one to access the aidzap API.</p>
  <form method="POST" action="/account/settings/token/generate">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
    <button type="submit" class="btn-submit" style="background:rgba(62,207,142,0.1);color:#3ecf8e;border:0.5px solid rgba(62,207,142,0.3)">Generate API Token →</button>
  </form>
  <?php endif; ?>
</div>

<!-- Delete Account -->
<div class="form-section" style="max-width:600px;border-color:rgba(224,84,84,0.15)">
  <div class="form-section-title" style="color:#e05454"><span class="form-step" style="background:rgba(224,84,84,0.1);border-color:rgba(224,84,84,0.3);color:#e05454">!</span> Danger Zone</div>
  <p style="font-size:13px;color:rgba(255,255,255,0.4);margin-bottom:16px">
    Permanently delete your account. All data including campaigns, earnings and wallets will be removed.
    This action cannot be undone.
  </p>
  <form method="POST" action="/account/settings/delete" onsubmit="return confirm('Are you absolutely sure? This cannot be undone.')">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
    <div class="field" style="max-width:250px;margin-bottom:12px">
      <label>Type DELETE to confirm</label>
      <input type="text" name="confirm" placeholder="DELETE" pattern="DELETE" required>
    </div>
    <button type="submit" class="btn-reject"><?= __('settings.delete_account') ?></button>
  </form>
</div>

<script type="module">
import { connectWallet } from '/assets/js/wallet.js';

window.linkWallet = async function() {
    const btnText = document.getElementById('link-btn-text');
    btnText.textContent = '<?= __('settings.connecting') ?>';

    try {
        const { address, signature, nonce, message } = await connectWallet(
            typeof WC_PROJECT_ID !== 'undefined' ? WC_PROJECT_ID : ''
        );

        const fd = new FormData();
        fd.append('address',   address);
        fd.append('signature', signature);
        fd.append('nonce',     nonce);
        fd.append('message',   message);

        const resp = await fetch('/account/settings/wallet/link', { method: 'POST', body: fd });
        const data = await resp.json();

        if (data.error) alert('Error: ' + data.error);
        else window.location.reload();

    } catch(e) {
        alert('Connection failed: ' + e.message);
    }

    btnText.textContent = '<?= __('settings.connect_wallet') ?>';
};
</script>
