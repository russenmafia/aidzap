<section class="hero">
  <div class="badge"><span class="badge-dot"></span><?= __('home.hero_badge') ?></div>
  <h1><?= __('home.hero_title') ?></h1>
  <p class="hero-sub"><?= __('home.hero_subtitle') ?></p>
  <div class="hero-ctas">
    <a href="/register" class="btn-hero"><?= __('home.cta_start') ?></a>
    <a href="/publisher" class="btn-hero-ghost"><?= __('home.cta_publisher_docs') ?></a>
  </div>
</section>

<div class="stats-bar">
  <div class="stat"><span class="stat-num">0</span><span class="stat-label"><?= __('home.stat_kyc_label') ?></span></div>
  <div class="stat"><span class="stat-num">20+</span><span class="stat-label"><?= __('home.stat_cryptos_label') ?></span></div>
  <div class="stat"><span class="stat-num">0.001</span><span class="stat-label"><?= __('home.stat_min_payout_label') ?></span></div>
  <div class="stat"><span class="stat-num">&#x221e;</span><span class="stat-label"><?= __('home.stat_traffic_label') ?></span></div>
</div>

<section class="features">
  <p class="section-label"><?= __('home.features_label') ?></p>
  <h2 class="section-title"><?= __('home.features_title') ?></h2>
  <div class="features-grid">
    <?php foreach ([
      ['&#x1F512;', __('home.feature_1_title'), __('home.feature_1_text')],
      ['&#x1F36A;', __('home.feature_2_title'), __('home.feature_2_text')],
      ['&#x26A1;',  __('home.feature_3_title'), __('home.feature_3_text')],
      ['&#x20BF;',  __('home.feature_4_title'), __('home.feature_4_text')],
      ['&#x1F6E1;', __('home.feature_5_title'), __('home.feature_5_text')],
      ['&#x1F4CA;', __('home.feature_6_title'), __('home.feature_6_text')],
    ] as [$icon, $ftitle, $desc]): ?>
    <div class="feature-card">
      <div class="feature-icon"><?= $icon ?></div>
      <h3 class="feature-title"><?= $ftitle ?></h3>
      <p class="feature-desc"><?= $desc ?></p>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="vs-section">
  <p class="section-label"><?= __('home.vs_label') ?></p>
  <h2 class="section-title"><?= __('home.vs_title') ?></h2>
  <div class="vs-table">
    <div class="vs-row vs-header">
      <div><?= __('home.vs_feat_header') ?></div><div class="col-us"><?= __('home.vs_col_us') ?></div><div><?= __('home.vs_col_others') ?></div>
    </div>
    <?php foreach ([
      [__('home.vs_row_1'), '&#x2713; Yes',          '&#x2717; No'],
      [__('home.vs_row_2'), '&#x2713; HTML/CSS only', '&#x2717; Required'],
      [__('home.vs_row_3'), '&#x2713; None',          '&#x2717; 10k+/mo'],
      [__('home.vs_row_4'), '&#x2713; 20+ coins',     '&#x2717; Fiat only'],
      [__('home.vs_row_5'), '&#x2713; Real-time',     '&#x2717; Basic'],
      [__('home.vs_row_6'), '&#x2713; 0.001 BTC',     '&#x2717; $100+'],
    ] as [$feat, $us, $them]): ?>
    <div class="vs-row">
      <div class="vs-feature"><?= $feat ?></div>
      <div class="col-us check"><?= $us ?></div>
      <div class="cross"><?= $them ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="cta-section">
  <h2 class="cta-title"><?= __('home.cta_title') ?></h2>
  <p class="cta-sub"><?= __('home.cta_sub') ?></p>
  <a href="/register" class="btn-hero"><?= __('home.cta_btn') ?></a>
</section>
