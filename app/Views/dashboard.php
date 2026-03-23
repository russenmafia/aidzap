<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($title ?? 'Dashboard') ?> – aidzap</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/main.css">
<link rel="stylesheet" href="/assets/css/dashboard.css">
</head>
<body class="dash-body">

<div class="dash-wrap">

  <!-- Sidebar -->
  <aside class="sidebar">
    <a href="/" class="sb-logo">AID<span>ZAP</span></a>

    <nav class="sb-nav">
      <div class="sb-section">Overview</div>
      <a href="/dashboard" class="sb-item <?= $active === 'dashboard' ? 'active' : '' ?>">
        <span class="sb-icon">&#9672;</span> Dashboard
      </a>

      <?php if (in_array($user['role'] ?? '', ['publisher','both'])): ?>
      <div class="sb-section">Publisher</div>
      <a href="/publisher/units" class="sb-item <?= $active === 'units' ? 'active' : '' ?>">
        <span class="sb-icon">&#9635;</span> Ad Units
      </a>
      <a href="/publisher/earnings" class="sb-item <?= $active === 'earnings' ? 'active' : '' ?>">
        <span class="sb-icon">&#9672;</span> Earnings
      </a>
      <a href="/publisher/withdraw" class="sb-item <?= $active === 'withdraw' ? 'active' : '' ?>">
        <span class="sb-icon">&#8593;</span> Withdraw
      </a>
      <?php endif; ?>

      <?php if (in_array($user['role'] ?? '', ['advertiser','both'])): ?>
      <div class="sb-section">Advertiser</div>
      <a href="/advertiser/campaigns" class="sb-item <?= $active === 'campaigns' ? 'active' : '' ?>">
        <span class="sb-icon">&#9672;</span> Campaigns
      </a>
      <a href="/advertiser/banners" class="sb-item <?= $active === 'banners' ? 'active' : '' ?>">
        <span class="sb-icon">&#9635;</span> Banners
      </a>
      <a href="/advertiser/billing" class="sb-item <?= $active === 'billing' ? 'active' : '' ?>">
        <span class="sb-icon">+</span> Billing
      </a>
      <?php endif; ?>

      <div class="sb-section">Account</div>
      <a href="/account/wallets" class="sb-item <?= $active === 'wallets' ? 'active' : '' ?>">
        <span class="sb-icon">&#9635;</span> Wallets
      </a>
      <a href="/account/settings" class="sb-item <?= $active === 'settings' ? 'active' : '' ?>">
        <span class="sb-icon">&#9672;</span> Settings
      </a>
    </nav>

    <div class="sb-user">
      <div class="sb-avatar"><?= strtoupper(substr($user['username'] ?? 'U', 0, 2)) ?></div>
      <div>
        <div class="sb-username"><?= htmlspecialchars($user['username'] ?? '') ?></div>
        <div class="sb-role"><?= htmlspecialchars(ucfirst($user['role'] ?? '')) ?></div>
      </div>
      <a href="/logout" class="sb-logout" title="Logout">&#x2192;</a>
    </div>
  </aside>

  <!-- Main -->
  <main class="dash-main">
    <?php if (!empty($flash)): ?>
    <div class="flash flash-<?= $flash['type'] ?? 'info' ?>"><?= htmlspecialchars($flash['msg']) ?></div>
    <?php endif; ?>
    <?= $content ?>
  </main>

</div>
</body>
</html>
