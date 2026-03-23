<div class="auth-card">
  <h1 class="auth-title">Sign in</h1>
  <p class="auth-subtitle">Welcome back. No personal data required.</p>

  <?php if (!empty($errors)): ?>
  <div class="alert alert-error">
    <?php foreach ($errors as $e): ?><p><?= htmlspecialchars($e) ?></p><?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if (!empty($success)): ?>
  <div class="alert alert-success"><p><?= htmlspecialchars($success) ?></p></div>
  <?php endif; ?>

  <form method="POST" action="/login" class="auth-form">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

    <div class="field">
      <label>Username</label>
      <input type="text" name="username" value="<?= htmlspecialchars($old['username'] ?? '') ?>"
             placeholder="your_username" required autofocus>
    </div>

    <div class="field">
      <label>Password</label>
      <input type="password" name="password" placeholder="Your password" required>
    </div>

    <div class="field-row">
      <label class="checkbox-label">
        <input type="checkbox" name="remember" value="1">
        <span>Remember me (30 days)</span>
      </label>
    </div>

    <button type="submit" class="btn-submit">Sign in &#x2192;</button>
  </form>

  <p class="auth-switch">No account yet? <a href="/register">Create one – it's free</a></p>
</div>
