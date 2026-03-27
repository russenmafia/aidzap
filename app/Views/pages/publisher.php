<?php $active = 'publisher'; ?>

<!-- Hero -->
<section style="padding:100px 0 80px;text-align:center;border-bottom:0.5px solid rgba(255,255,255,0.06)">
  <div style="max-width:720px;margin:0 auto;padding:0 24px">
    <div style="display:inline-flex;align-items:center;gap:8px;background:rgba(62,207,142,0.08);border:0.5px solid rgba(62,207,142,0.2);border-radius:100px;padding:6px 16px;font-size:12px;color:#3ecf8e;margin-bottom:24px;font-family:'DM Mono',monospace;letter-spacing:.05em">
      💰 PUBLISHER PROGRAM
    </div>
    <h1 style="font-size:clamp(2.2rem,5vw,3.5rem);font-weight:700;line-height:1.15;margin-bottom:20px">
      <?= __('publisher.hero_title') ?>
    </h1>
    <p style="font-size:1.15rem;color:rgba(255,255,255,0.5);line-height:1.7;margin-bottom:40px;max-width:580px;margin-left:auto;margin-right:auto">
      <?= __('publisher.hero_subtitle') ?>
    </p>
    <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap">
      <a href="/register" style="display:inline-flex;align-items:center;gap:8px;background:#3ecf8e;color:#0a0a0a;padding:14px 32px;border-radius:8px;font-weight:600;text-decoration:none;font-size:15px">
        <?= __('publisher.cta_primary') ?>
      </a>
      <a href="/publisher-quality" style="display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,0.06);color:#fff;padding:14px 32px;border-radius:8px;font-weight:500;text-decoration:none;font-size:15px">
        <?= __('publisher.cta_secondary') ?>
      </a>
    </div>
    <!-- Stats -->
    <div style="display:flex;gap:48px;justify-content:center;margin-top:64px;flex-wrap:wrap">
      <?php foreach ([
        ['80%',    __('publisher.stat1')],
        ['0',      __('publisher.stat2')],
        ['0.0001', __('publisher.stat3')],
      ] as [$num, $label]): ?>
      <div style="text-align:center">
        <div style="font-size:2rem;font-weight:800;color:#3ecf8e"><?= $num ?></div>
        <div style="font-size:12px;color:rgba(255,255,255,0.4);margin-top:4px;text-transform:uppercase;letter-spacing:.08em"><?= $label ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Earnings Explanation -->
<section style="padding:80px 0;border-bottom:0.5px solid rgba(255,255,255,0.06)">
  <div style="max-width:960px;margin:0 auto;padding:0 24px">
    <div style="text-align:center;margin-bottom:56px">
      <div style="font-size:11px;color:rgba(255,255,255,0.4);letter-spacing:.1em;text-transform:uppercase;margin-bottom:12px"><?= __('publisher.earnings_label') ?></div>
      <h2 style="font-size:2rem;font-weight:700"><?= __('publisher.earnings_title') ?></h2>
      <p style="color:rgba(255,255,255,0.4);margin-top:12px;max-width:560px;margin-left:auto;margin-right:auto"><?= __('publisher.earnings_subtitle') ?></p>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:20px;margin-bottom:40px">
      <?php foreach ([
        ['CPM', '💰', __('publisher.cpm_title'), __('publisher.cpm_text')],
        ['80%', '📈', __('publisher.share_title'), __('publisher.share_text')],
        ['BTC', '₿',  __('publisher.crypto_title'), __('publisher.crypto_text')],
      ] as [$badge, $icon, $title, $text]): ?>
      <div style="background:rgba(255,255,255,0.02);border:0.5px solid rgba(255,255,255,0.06);border-radius:16px;padding:28px">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
          <span style="font-size:1.8rem"><?= $icon ?></span>
          <span style="font-size:11px;font-weight:700;letter-spacing:.08em;color:#3ecf8e;background:rgba(62,207,142,0.1);padding:4px 10px;border-radius:100px"><?= $badge ?></span>
        </div>
        <div style="font-size:15px;font-weight:600;margin-bottom:10px"><?= $title ?></div>
        <div style="font-size:13px;color:rgba(255,255,255,0.4);line-height:1.7"><?= $text ?></div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- vs aads comparison -->
    <div style="background:rgba(62,207,142,0.04);border:0.5px solid rgba(62,207,142,0.15);border-radius:16px;padding:32px">
      <div style="text-align:center;margin-bottom:24px;font-size:14px;font-weight:600;color:rgba(255,255,255,0.6)"><?= __('publisher.comparison_title') ?></div>
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:0;max-width:600px;margin:0 auto">
        <div style="text-align:center;font-size:12px;color:rgba(255,255,255,0.3);padding-bottom:12px"><?= __('publisher.comp_feature') ?></div>
        <div style="text-align:center;font-size:12px;color:rgba(255,255,255,0.3);padding-bottom:12px">A-ADS</div>
        <div style="text-align:center;font-size:12px;color:#3ecf8e;padding-bottom:12px">aidzap</div>
        <?php foreach ([
          [__('publisher.comp1'), '70%', '80%'],
          [__('publisher.comp2'), __('publisher.comp_strict'), __('publisher.comp_domain')],
          [__('publisher.comp3'), __('publisher.comp_no'), __('publisher.comp_yes')],
          [__('publisher.comp4'), __('publisher.comp_no'), __('publisher.comp_yes')],
        ] as [$feature, $aads, $aidzap]): ?>
        <div style="padding:10px 8px;border-top:0.5px solid rgba(255,255,255,0.06);font-size:12px;color:rgba(255,255,255,0.5)"><?= $feature ?></div>
        <div style="padding:10px 8px;border-top:0.5px solid rgba(255,255,255,0.06);text-align:center;font-size:12px;color:rgba(255,100,100,0.7)"><?= $aads ?></div>
        <div style="padding:10px 8px;border-top:0.5px solid rgba(255,255,255,0.06);text-align:center;font-size:12px;color:#3ecf8e;font-weight:600"><?= $aidzap ?></div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<!-- Quality Program -->
<section style="padding:80px 0;border-bottom:0.5px solid rgba(255,255,255,0.06)">
  <div style="max-width:960px;margin:0 auto;padding:0 24px">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:64px;align-items:center">
      <div>
        <div style="font-size:11px;color:rgba(255,255,255,0.4);letter-spacing:.1em;text-transform:uppercase;margin-bottom:12px"><?= __('publisher.quality_label') ?></div>
        <h2 style="font-size:2rem;font-weight:700;margin-bottom:20px"><?= __('publisher.quality_title') ?></h2>
        <p style="color:rgba(255,255,255,0.5);line-height:1.7;margin-bottom:24px"><?= __('publisher.quality_text') ?></p>
        <a href="/publisher-quality" style="display:inline-flex;align-items:center;gap:8px;color:#3ecf8e;text-decoration:none;font-size:14px;font-weight:500">
          <?= __('publisher.quality_link') ?> →
        </a>
      </div>
      <div style="display:flex;flex-direction:column;gap:12px">
        <?php foreach ([
          ['🥉', 'Bronze', '60%'],
          ['🥈', 'Silver', '70%'],
          ['🥇', 'Gold',   '80%'],
          ['💎', 'Platinum','85%'],
        ] as [$icon, $level, $share]): ?>
        <div style="display:flex;align-items:center;justify-content:space-between;background:rgba(255,255,255,0.02);border:0.5px solid rgba(255,255,255,0.06);border-radius:10px;padding:14px 18px">
          <div style="display:flex;align-items:center;gap:10px">
            <span style="font-size:1.3rem"><?= $icon ?></span>
            <span style="font-size:14px;font-weight:500"><?= $level ?></span>
          </div>
          <span style="font-size:1.1rem;font-weight:700;color:#3ecf8e"><?= $share ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<!-- How it works -->
<section style="padding:80px 0;border-bottom:0.5px solid rgba(255,255,255,0.06)">
  <div style="max-width:760px;margin:0 auto;padding:0 24px">
    <div style="text-align:center;margin-bottom:56px">
      <div style="font-size:11px;color:rgba(255,255,255,0.4);letter-spacing:.1em;text-transform:uppercase;margin-bottom:12px"><?= __('publisher.how_label') ?></div>
      <h2 style="font-size:2rem;font-weight:700"><?= __('publisher.how_title') ?></h2>
    </div>
    <div style="display:flex;flex-direction:column;gap:0">
      <?php foreach ([
        ['01', __('publisher.step1_title'), __('publisher.step1_text')],
        ['02', __('publisher.step2_title'), __('publisher.step2_text')],
        ['03', __('publisher.step3_title'), __('publisher.step3_text')],
        ['04', __('publisher.step4_title'), __('publisher.step4_text')],
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

<!-- CTA -->
<section style="padding:80px 0;text-align:center">
  <div style="max-width:560px;margin:0 auto;padding:0 24px">
    <h2 style="font-size:2rem;font-weight:700;margin-bottom:16px"><?= __('publisher.cta_title') ?></h2>
    <p style="color:rgba(255,255,255,0.4);margin-bottom:32px;line-height:1.7"><?= __('publisher.cta_subtitle') ?></p>
    <a href="/register" style="display:inline-flex;align-items:center;gap:8px;background:#3ecf8e;color:#0a0a0a;padding:14px 32px;border-radius:8px;font-weight:600;text-decoration:none;font-size:15px">
      <?= __('publisher.cta_btn') ?>
    </a>
  </div>
</section>
