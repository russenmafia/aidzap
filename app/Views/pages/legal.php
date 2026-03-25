<section class="features" style="padding-top:120px;max-width:980px;margin:0 auto;">
  <p class="section-label"><?= __('legal.label') ?></p>
  <h1 class="section-title" style="margin-bottom:12px;"><?= htmlspecialchars($page['title']) ?></h1>
  <p class="hero-sub" style="margin-bottom:22px;"><?= __('legal.last_updated') ?> <?= !empty($page['updated_at']) ? date('d.m.Y H:i', strtotime($page['updated_at'])) : '-' ?></p>

  <article class="feature-card" style="padding:28px;line-height:1.7;">
    <?= $page['content'] ?>
  </article>
</section>
