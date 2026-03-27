<?php
$user   = $user   ?? ['username' => 'admin', 'role' => 'admin'];
$active = $active ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($title ?? 'Admin') ?> – aidzap admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/main.css">
<link rel="stylesheet" href="/assets/css/dashboard.css">
<link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="dash-body">
<div class="dash-wrap">

  <aside class="sidebar">
    <a href="/admin" class="sb-logo">AID<span style="color:#e05454">ZAP</span> <span style="font-size:10px;color:rgba(255,255,255,0.3);letter-spacing:.1em">ADMIN</span></a>
    <nav class="sb-nav">
      <div class="sb-section"><?= __('admin.overview') ?></div>
      <a href="/admin" class="sb-item <?= $active === 'stats' ? 'active' : '' ?>">
        <span class="sb-icon">&#9672;</span> <?= __('admin.stats') ?>
      </a>
      <a href="/admin/users" class="sb-item <?= $active === 'users' ? 'active' : '' ?>">
        <span class="sb-icon">&#9635;</span> <?= __('admin.users') ?>
      </a>
      <a href="/admin/system" class="sb-item <?= $active === 'system' ? 'active' : '' ?>">
        <span class="sb-icon">&#9881;</span> <?= __('admin.system') ?>
      </a>

      <div class="sb-section"><?= __('admin.review') ?></div>
      <a href="/admin/review/units" class="sb-item <?= $active === 'review' && str_contains($_SERVER['REQUEST_URI'],'units') ? 'active' : '' ?>">
        <span class="sb-icon">&#9635;</span> <?= __('admin.ad_units') ?>
        <?php
        $pendingUnits = \Core\Database::getInstance()->query("SELECT COUNT(*) FROM ad_units WHERE status='pending_review'")->fetchColumn();
        if ($pendingUnits > 0): ?>
        <span class="sb-badge"><?= $pendingUnits ?></span>
        <?php endif; ?>
      </a>
      <a href="/admin/review/banners" class="sb-item <?= $active === 'review' && str_contains($_SERVER['REQUEST_URI'],'banners') ? 'active' : '' ?>">
        <span class="sb-icon">&#9672;</span> <?= __('admin.banners') ?>
        <?php
        $pendingBanners = \Core\Database::getInstance()->query("SELECT COUNT(*) FROM ad_banners WHERE status='pending_review'")->fetchColumn();
        if ($pendingBanners > 0): ?>
        <span class="sb-badge"><?= $pendingBanners ?></span>
        <?php endif; ?>
      </a>

      <div class="sb-section"><?= __('admin.content') ?></div>
      <a href="/admin/legal" class="sb-item <?= in_array($active, ['legal'], true) ? 'active' : '' ?>">
        <span class="sb-icon">&#128196;</span> <?= __('admin.legal_pages') ?>
      </a>
      <a href="/admin/faq" class="sb-item <?= $active === 'faq' ? 'active' : '' ?>">
        <span class="sb-icon">&#10067;</span> <?= __('admin.faq') ?>
      </a>

      <div class="sb-section"><?= __('admin.security') ?></div>
      <a href="/admin/fraud" class="sb-item <?= $active === 'fraud' ? 'active' : '' ?>">
        <span class="sb-icon">&#9645;</span> <?= __('admin.fraud_logs') ?>
      </a>
      <a href="/admin/crons" class="sb-item <?= $active === 'crons' ? 'active' : '' ?>">
        <span class="sb-icon">&#9680;</span> <?= __('admin.cron_jobs') ?>
      </a>
            <a href="/admin/referrals" class="sb-item <?= $active === 'referrals' ? 'active' : '' ?>">
        <span class="sb-icon">&#128279;</span> Referrals
      </a>
      <a href="/admin/features" class="sb-item <?= $active === 'features' ? 'active' : '' ?>">
        <span class="sb-icon">&#9881;</span> Feature Flags
      </a>
      <a href="/admin/quality" class="sb-item <?= $active === 'quality' ? 'active' : '' ?>">
        <span class="sb-icon">&#11088;</span> Quality Score
      </a>

      <div class="sb-section"><?= __('admin.navigation') ?></div>
      <a href="/dashboard" class="sb-item">
        <span class="sb-icon">&#8592;</span> <?= __('admin.back_dashboard') ?>
      </a>
    </nav>

    <div class="sb-lang" style="padding:8px 16px;display:flex;gap:10px;font-size:11px;opacity:0.5;">
      <a href="/lang/en" style="color:inherit;text-decoration:none;<?= \Core\Lang::current()==='en'?'font-weight:700;opacity:1;':'' ?>">EN</a>
      <span>|</span>
      <a href="/lang/de" style="color:inherit;text-decoration:none;<?= \Core\Lang::current()==='de'?'font-weight:700;opacity:1;':'' ?>">DE</a>
    </div>
    <div class="sb-user">
      <div class="sb-avatar" style="background:rgba(224,84,84,0.15);border-color:rgba(224,84,84,0.3);color:#e05454">
        <?= strtoupper(substr($_SESSION['username'] ?? 'A', 0, 2)) ?>
      </div>
      <div>
        <div class="sb-username">Admin</div>
        <div class="sb-role">System access</div>
      </div>
      <a href="/logout" class="sb-logout" title="Logout">&#x2192;</a>
    </div>
  </aside>

  <main class="dash-main">
    <?php if (!empty($flash)): ?>
    <div class="flash flash-<?= $flash['type'] ?? 'info' ?>"><?= htmlspecialchars($flash['msg']) ?></div>
    <?php endif; ?>
    <?= $content ?>
  </main>

</div>
</body>
</html>
