<?php $active = 'advertiser'; ?>

<!-- Hero -->
<section style="padding:100px 0 80px;text-align:center;border-bottom:0.5px solid rgba(255,255,255,0.06)">
  <div style="max-width:720px;margin:0 auto;padding:0 24px">
    <div style="display:inline-flex;align-items:center;gap:8px;background:rgba(62,207,142,0.08);border:0.5px solid rgba(62,207,142,0.2);border-radius:100px;padding:6px 16px;font-size:12px;color:#3ecf8e;margin-bottom:24px;font-family:'DM Mono',monospace;letter-spacing:.05em">
      ⚡ CRYPTO ADVERTISING
    </div>
    <h1 style="font-size:clamp(2.2rem,5vw,3.5rem);font-weight:700;line-height:1.15;margin-bottom:20px">
      <?= __('advertiser.hero_title') ?>
    </h1>
    <p style="font-size:1.15rem;color:rgba(255,255,255,0.5);line-height:1.7;margin-bottom:40px;max-width:580px;margin-left:auto;margin-right:auto">
      <?= __('advertiser.hero_subtitle') ?>
    </p>
    <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap">
      <a href="/register" style="display:inline-flex;align-items:center;gap:8px;background:#3ecf8e;color:#0a0a0a;padding:14px 32px;border-radius:8px;font-weight:600;text-decoration:none;font-size:15px">
        <?= __('advertiser.cta_primary') ?>
      </a>
      <a href="/faq" style="display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,0.06);color:#fff;padding:14px 32px;border-radius:8px;font-weight:500;text-decoration:none;font-size:15px">
        <?= __('advertiser.cta_secondary') ?>
      </a>
    </div>
    <!-- Stats -->
    <div style="display:flex;gap:48px;justify-content:center;margin-top:64px;flex-wrap:wrap">
      <?php foreach ([
        ['20+', __('advertiser.stat1')],
        ['0', __('advertiser.stat2')],
        ['100%', __('advertiser.stat3')],
      ] as [$num, $label]): ?>
      <div style="text-align:center">
        <div style="font-size:2rem;font-weight:800;color:#3ecf8e"><?= $num ?></div>
        <div style="font-size:12px;color:rgba(255,255,255,0.4);margin-top:4px;text-transform:uppercase;letter-spacing:.08em"><?= $label ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Features -->
<section style="padding:80px 0;border-bottom:0.5px solid rgba(255,255,255,0.06)">
  <div style="max-width:960px;margin:0 auto;padding:0 24px">
    <div style="text-align:center;margin-bottom:56px">
      <div style="font-size:11px;color:rgba(255,255,255,0.4);letter-spacing:.1em;text-transform:uppercase;margin-bottom:12px"><?= __('advertiser.features_label') ?></div>
      <h2 style="font-size:2rem;font-weight:700"><?= __('advertiser.features_title') ?></h2>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:24px">
      <?php foreach ([
        ['🔒', __('advertiser.feat1_title'), __('advertiser.feat1_text')],
        ['₿',  __('advertiser.feat2_title'), __('advertiser.feat2_text')],
        ['🎯', __('advertiser.feat3_title'), __('advertiser.feat3_text')],
        ['🤖', __('advertiser.feat4_title'), __('advertiser.feat4_text')],
        ['📊', __('advertiser.feat5_title'), __('advertiser.feat5_text')],
        ['⚡', __('advertiser.feat6_title'), __('advertiser.feat6_text')],
      ] as [$icon, $title, $text]): ?>
      <div style="background:rgba(255,255,255,0.02);border:0.5px solid rgba(255,255,255,0.06);border-radius:16px;padding:28px">
        <div style="font-size:2rem;margin-bottom:16px"><?= $icon ?></div>
        <div style="font-size:15px;font-weight:600;margin-bottom:10px"><?= $title ?></div>
        <div style="font-size:13px;color:rgba(255,255,255,0.4);line-height:1.7"><?= $text ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- How it works -->
<section style="padding:80px 0;border-bottom:0.5px solid rgba(255,255,255,0.06)">
  <div style="max-width:760px;margin:0 auto;padding:0 24px">
    <div style="text-align:center;margin-bottom:56px">
      <div style="font-size:11px;color:rgba(255,255,255,0.4);letter-spacing:.1em;text-transform:uppercase;margin-bottom:12px"><?= __('advertiser.how_label') ?></div>
      <h2 style="font-size:2rem;font-weight:700"><?= __('advertiser.how_title') ?></h2>
    </div>
    <div style="display:flex;flex-direction:column;gap:0">
      <?php foreach ([
        ['01', __('advertiser.step1_title'), __('advertiser.step1_text')],
        ['02', __('advertiser.step2_title'), __('advertiser.step2_text')],
        ['03', __('advertiser.step3_title'), __('advertiser.step3_text')],
        ['04', __('advertiser.step4_title'), __('advertiser.step4_text')],
      ] as $i => [$num, $title, $text]): ?>
      <div style="display:flex;gap:24px;position:relative;padding-bottom:<?= $i < 3 ? '40px' : '0' ?>">
        <div style="flex-shrink:0;display:flex;flex-direction:column;align-items:center">
          <div style="width:48px;height:48px;border-radius:50%;background:rgba(62,207,142,0.1);border:0.5px solid rgba(62,207,142,0.3);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#3ecf8e;font-family:'DM Mono',monospace">
            <?= $num ?>
          </div>
          <?php if ($i < 3): ?>
          <div style="width:1px;flex:1;background:rgba(255,255,255,0.06);margin-top:8px"></div>
          <?php endif; ?>
        </div>
        <div style="padding-top:10px">
          <div style="font-size:16px;font-weight:600;margin-bottom:8px"><?= $title ?></div>
          <div style="font-size:13px;color:rgba(255,255,255,0.4);line-height:1.7"><?= $text ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Pricing -->
<section style="padding:80px 0;border-bottom:0.5px solid rgba(255,255,255,0.06)">
  <div style="max-width:960px;margin:0 auto;padding:0 24px">
    <div style="text-align:center;margin-bottom:56px">
      <div style="font-size:11px;color:rgba(255,255,255,0.4);letter-spacing:.1em;text-transform:uppercase;margin-bottom:12px"><?= __('advertiser.pricing_label') ?></div>
      <h2 style="font-size:2rem;font-weight:700"><?= __('advertiser.pricing_title') ?></h2>
      <p style="color:rgba(255,255,255,0.4);margin-top:12px"><?= __('advertiser.pricing_subtitle') ?></p>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:20px">
      <?php foreach ([
        ['CPD', __('advertiser.cpd_title'), __('advertiser.cpd_text'), false],
        ['CPM', __('advertiser.cpm_title'), __('advertiser.cpm_text'), true],
        ['CPA', __('advertiser.cpa_title'), __('advertiser.cpa_text'), false],
      ] as [$type, $title, $text, $featured]): ?>
      <div style="background:<?= $featured ? 'rgba(62,207,142,0.06)' : 'rgba(255,255,255,0.02)' ?>;border:0.5px solid <?= $featured ? 'rgba(62,207,142,0.3)' : 'rgba(255,255,255,0.06)' ?>;border-radius:16px;padding:32px;text-align:center">
        <div style="font-size:11px;font-weight:700;letter-spacing:.1em;color:<?= $featured ? '#3ecf8e' : 'rgba(255,255,255,0.4)' ?>;margin-bottom:16px"><?= $type ?></div>
        <div style="font-size:1.2rem;font-weight:700;margin-bottom:12px"><?= $title ?></div>
        <div style="font-size:13px;color:rgba(255,255,255,0.4);line-height:1.7"><?= $text ?></div>
        <?php if ($featured): ?><div style="margin-top:16px;font-size:11px;color:#3ecf8e">★ <?= __('advertiser.popular') ?></div><?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <div style="margin-top:24px;text-align:center;font-size:13px;color:rgba(255,255,255,0.3)">
      <?= __('advertiser.pricing_note') ?>
    </div>
  </div>
</section>

<!-- CTA -->
<section style="padding:80px 0;text-align:center">
  <div style="max-width:560px;margin:0 auto;padding:0 24px">
    <h2 style="font-size:2rem;font-weight:700;margin-bottom:16px"><?= __('advertiser.cta_title') ?></h2>
    <p style="color:rgba(255,255,255,0.4);margin-bottom:32px;line-height:1.7"><?= __('advertiser.cta_subtitle') ?></p>
    <a href="/register" style="display:inline-flex;align-items:center;gap:8px;background:#3ecf8e;color:#0a0a0a;padding:14px 32px;border-radius:8px;font-weight:600;text-decoration:none;font-size:15px">
      <?= __('advertiser.cta_btn') ?>
    </a>
  </div>
</section>
