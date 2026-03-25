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
<meta property="og:image" content="https://aidzap.com/assets/og-image.png">

<!-- Twitter/X Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= htmlspecialchars($og_title ?? $title ?? 'aidzap') ?>">
<meta name="twitter:description" content="<?= htmlspecialchars($og_desc ?? $meta_desc ?? 'Anonymous crypto advertising. No KYC, no tracking.') ?>">
<meta name="twitter:image" content="https://aidzap.com/assets/og-image.png">

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
