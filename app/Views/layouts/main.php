<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($title ?? 'aidzap') ?> – Privacy-First Ad Network</title>
<meta name="description" content="<?= htmlspecialchars($meta_desc ?? 'Anonymous crypto advertising. No KYC, no cookies, no tracking.') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>
<nav class="nav">
  <a href="/" class="logo">AID<span>ZAP</span></a>
  <ul class="nav-links">
    <li><a href="/advertiser">Advertiser</a></li>
    <li><a href="/publisher">Publisher</a></li>
    <li><a href="/pricing">Pricing</a></li>
  </ul>
  <div class="nav-cta">
    <?php if (isset($_SESSION['user_id'])): ?>
      <a href="/dashboard" class="btn-ghost">Dashboard</a>
      <a href="/logout" class="btn-ghost">Logout</a>
    <?php else: ?>
      <a href="/login" class="btn-ghost">Login</a>
      <a href="/register" class="btn-primary">Get started</a>
    <?php endif; ?>
  </div>
</nav>
<main><?= $content ?></main>
<footer class="footer">
  <div class="footer-inner">
    <span class="logo">AID<span>ZAP</span></span>
    <span class="footer-copy">Privacy-first advertising. No KYC. No tracking.</span>
  </div>
</footer>
</body>
</html>
