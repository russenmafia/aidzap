<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($title ?? 'aidzap') ?> – aidzap</title>
<meta name="robots" content="noindex, nofollow">
<meta name="description" content="<?= htmlspecialchars($meta_desc ?? 'Sign in to aidzap – Privacy-first crypto ad network.') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/main.css">
<link rel="stylesheet" href="/assets/css/auth.css">
<link rel="preconnect" href="https://cdn.jsdelivr.net">
</head>
<body class="auth-body">
<div class="auth-wrap">
  <a href="/" class="auth-logo">AID<span>ZAP</span></a>
  <?= $content ?>
</div>
</body>
</html>
