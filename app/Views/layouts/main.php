<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($title ?? 'aidzap') ?> – Privacy-First Crypto Ad Network</title>
<meta name="description" content="<?= htmlspecialchars($meta_desc ?? 'Anonymous crypto advertising network. No KYC, no cookies, no tracking. Earn BTC by placing ads. Pay with Bitcoin, Ethereum and 20+ cryptocurrencies.') ?>">
<meta name="keywords" content="crypto advertising, bitcoin ads, anonymous advertising, no KYC ads, privacy advertising, BTC publisher, crypto ad network">
<meta name="robots" content="<?= $meta_robots ?? 'index, follow' ?>">
<link rel="canonical" href="https://aidzap.com<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/') ?>">

<!-- Open Graph -->
<meta property="og:type" content="website">
<meta property="og:site_name" content="aidzap">
<meta property="og:title" content="<?= htmlspecialchars($og_title ?? $title ?? 'aidzap – Privacy-First Crypto Ad Network') ?>">
<meta property="og:description" content="<?= htmlspecialchars($og_desc ?? $meta_desc ?? 'Anonymous crypto advertising. No KYC, no cookies, no tracking. Earn BTC with your website.') ?>">
<meta property="og:url" content="https://aidzap.com<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/') ?>">
<meta property="og:image" content="https://aidzap.com/assets/img/og-image.png">

<!-- Twitter/X Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= htmlspecialchars($og_title ?? $title ?? 'aidzap') ?>">
<meta name="twitter:description" content="<?= htmlspecialchars($og_desc ?? $meta_desc ?? 'Anonymous crypto advertising. No KYC, no tracking.') ?>">
<meta name="twitter:image" content="https://aidzap.com/assets/img/og-image.png">

<!-- Schema.org -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "name": "aidzap",
  "url": "https://aidzap.com",
  "description": "Privacy-first crypto advertising network. Anonymous, no KYC, no cookies.",
  "potentialAction": {
    "@type": "SearchAction",
    "target": "https://aidzap.com/search?q={search_term_string}",
    "query-input": "required name=search_term_string"
  }
}
</script>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/main.css">
<?= $head_extra ?? '' ?>
</head>
<body>
<nav class="nav">
  <a href="/" class="logo">AID<span>ZAP</span></a>
  <ul class="nav-links">
    <li><a href="/advertiser"><?= __('nav.advertiser') ?></a></li>
    <li><a href="/publisher"><?= __('nav.publisher') ?></a></li>
    <li><a href="/publisher-quality"><?= __('nav.quality') ?></a></li>
    <li><a href="/faq"><?= __('nav.faq') ?></a></li>
  </ul>
  <div class="nav-cta">
    <div class="lang-switcher">
      <a href="/lang/en" class="<?= \Core\Lang::current() === 'en' ? 'active' : '' ?>">EN</a>
      <span>|</span>
      <a href="/lang/de" class="<?= \Core\Lang::current() === 'de' ? 'active' : '' ?>">DE</a>
    </div>
    <?php if (isset($_SESSION['user_id'])): ?>
      <a href="/dashboard" class="btn-ghost"><?= __('nav.dashboard') ?></a>
      <a href="/logout" class="btn-ghost"><?= __('nav.logout') ?></a>
    <?php else: ?>
      <a href="/login" class="btn-ghost"><?= __('nav.login') ?></a>
      <a href="/register" class="btn-primary"><?= __('nav.register') ?></a>
    <?php endif; ?>
  </div>
</nav>
<main><?= $content ?></main>
<footer class="footer">
  <div class="footer-inner">
    <span class="logo">AID<span>ZAP</span></span>
    <div class="footer-links">
      <a href="/terms"><?= __('footer.terms') ?></a>
      <a href="/privacy"><?= __('footer.privacy') ?></a>
      <a href="/impressum"><?= __('footer.impressum') ?></a>
      <a href="/faq">FAQ</a>
      <a href="/publisher-quality"><?= __('footer.quality') ?></a>
    </div>
    <span class="footer-copy"><?= __('footer.tagline') ?></span>
  </div>
</footer>
</body>
</html>
