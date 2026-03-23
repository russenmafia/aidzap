<div class="auth-card">
  <h1 class="auth-title">Create account</h1>
  <p class="auth-subtitle">No email required. No KYC. Just a username and password.</p>

  <?php if (!empty($errors)): ?>
  <div class="alert alert-error">
    <?php foreach ($errors as $e): ?><p><?= htmlspecialchars($e) ?></p><?php endforeach; ?>
  </div>
  <?php endif; ?>

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

    <button type="submit" class="btn-submit">Create account &#x2192;</button>
  </form>

  <p class="auth-switch">Already have an account? <a href="/login">Sign in</a></p>

  <div class="privacy-note">
    <span class="privacy-icon">&#x1F512;</span>
    We store no personal data. Your IP is hashed, never logged in plain text.
  </div>
</div>
