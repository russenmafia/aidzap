<?php
use Core\Auth;
use Core\Database;
use Core\Lang;

$isLoggedIn = Auth::check();
$userLevel  = null;
$userShare  = null;
$userRefData = null;

if ($isLoggedIn) {
    $db   = Database::getInstance();
    $stmt = $db->prepare('
        SELECT quality_level, revenue_share 
        FROM ad_units 
        WHERE user_id = ? AND status = "active"
        ORDER BY FIELD(quality_level,"platinum","gold","silver","bronze")
        LIMIT 1
    ');
    $stmt->execute([Auth::id()]);
    $best = $stmt->fetch();
    $userLevel = $best['quality_level'] ?? 'bronze';
    $userShare = $best['revenue_share'] ?? 60;
}

$levels = [
    'bronze'   => ['icon'=>'🥉','color'=>'#cd7f32','share'=>$settings['bronze_share']??60,'ctr'=>'< '.round(($settings['bronze_max_ctr']??0.001)*100,2).'%'],
    'silver'   => ['icon'=>'🥈','color'=>'#aaaaaa','share'=>$settings['silver_share']??70,'ctr'=>'≥ '.round(($settings['bronze_max_ctr']??0.001)*100,2).'%'],
    'gold'     => ['icon'=>'🥇','color'=>'#f5a623','share'=>$settings['gold_share']??80,'ctr'=>'≥ '.round(($settings['silver_max_ctr']??0.003)*100,2).'%'],
    'platinum' => ['icon'=>'💎','color'=>'#3ecf8e','share'=>$settings['platinum_share']??85,'ctr'=>'≥ '.round(($settings['gold_max_ctr']??0.008)*100,2).'%'],
];
?>

<!-- Hero -->
<section style="padding:80px 0 60px;text-align:center;border-bottom:0.5px solid rgba(255,255,255,0.06)">
  <div style="max-width:680px;margin:0 auto;padding:0 24px">
    <div style="display:inline-flex;align-items:center;gap:8px;background:rgba(62,207,142,0.08);border:0.5px solid rgba(62,207,142,0.2);border-radius:100px;padding:6px 16px;font-size:12px;color:#3ecf8e;margin-bottom:24px;font-family:'DM Mono',monospace;letter-spacing:.05em">
      ⭐ <?= __('quality.badge') ?>
    </div>
    <h1 style="font-size:clamp(2rem,5vw,3rem);font-weight:700;line-height:1.15;margin-bottom:20px">
      <?= __('quality.hero_title') ?>
    </h1>
    <p style="font-size:1.1rem;color:rgba(255,255,255,0.5);line-height:1.7;margin-bottom:32px">
      <?= __('quality.hero_subtitle') ?>
    </p>
    <?php if (!$isLoggedIn): ?>
    <a href="/register" style="display:inline-flex;align-items:center;gap:8px;background:#3ecf8e;color:#0a0a0a;padding:14px 32px;border-radius:8px;font-weight:600;text-decoration:none;font-size:15px">
      <?= __('quality.cta_start') ?> 
    </a>
    <?php else: ?>
    <div style="display:inline-flex;align-items:center;gap:12px;background:rgba(255,255,255,0.04);border:0.5px solid rgba(255,255,255,0.1);border-radius:12px;padding:16px 24px">
      <span style="font-size:2rem"><?= $levels[$userLevel]['icon'] ?></span>
      <div style="text-align:left">
        <div style="font-size:11px;color:rgba(255,255,255,0.4);margin-bottom:2px"><?= __('quality.your_level') ?></div>
        <div style="font-size:1.2rem;font-weight:700;color:<?= $levels[$userLevel]['color'] ?>"><?= ucfirst($userLevel) ?></div>
        <div style="font-size:12px;color:rgba(255,255,255,0.4)"><?= $userShare ?>% <?= __('quality.revenue_share') ?></div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- <?= __('quality.levels_label') ?> -->
<section style="padding:80px 0;border-bottom:0.5px solid rgba(255,255,255,0.06)">
  <div style="max-width:960px;margin:0 auto;padding:0 24px">
    <div style="text-align:center;margin-bottom:48px">
      <div style="font-size:11px;color:rgba(255,255,255,0.4);letter-spacing:.1em;text-transform:uppercase;margin-bottom:12px"><?= __('quality.levels_label') ?></div>
      <h2 style="font-size:2rem;font-weight:700"><?= __('quality.levels_title') ?></h2>
      <p style="color:rgba(255,255,255,0.4);margin-top:12px"><?= __('quality.levels_subtitle') ?></p>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px">
      <?php foreach ($levels as $key => $l): 
        $isCurrent = $isLoggedIn && $userLevel === $key;
      ?>
      <div style="background:rgba(255,255,255,0.02);border:0.5px solid <?= $isCurrent ? $l['color'] : 'rgba(255,255,255,0.06)' ?>;border-radius:16px;padding:28px 24px;text-align:center;position:relative;<?= $isCurrent ? 'box-shadow:0 0 30px '.$l['color'].'22' : '' ?>">
        <?php if ($isCurrent): ?>
        <div style="position:absolute;top:-10px;left:50%;transform:translateX(-50%);background:<?= $l['color'] ?>;color:#000;font-size:10px;font-weight:700;padding:3px 10px;border-radius:100px;white-space:nowrap"><?= __('quality.your_level_badge') ?></div>
        <?php endif; ?>
        <div style="font-size:2.5rem;margin-bottom:12px"><?= $l['icon'] ?></div>
        <div style="font-size:1.1rem;font-weight:700;color:<?= $l['color'] ?>;margin-bottom:8px"><?= ucfirst($key) ?></div>
        <div style="font-size:2rem;font-weight:800;margin-bottom:4px"><?= $l['share'] ?>%</div>
        <div style="font-size:11px;color:rgba(255,255,255,0.4);margin-bottom:16px"><?= __('quality.revenue_share') ?></div>
        <div style="background:rgba(255,255,255,0.04);border-radius:8px;padding:10px;font-size:12px;color:rgba(255,255,255,0.5)">
          CTR <?= $l['ctr'] ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Rules -->
    <div style="margin-top:32px;display:grid;grid-template-columns:1fr 1fr;gap:16px">
      <div style="background:rgba(255,255,255,0.02);border:0.5px solid rgba(255,255,255,0.06);border-radius:12px;padding:20px">
        <div style="font-size:13px;font-weight:600;margin-bottom:8px"><?= __('quality.upgrades_title') ?></div>
        <div style="font-size:13px;color:rgba(255,255,255,0.5);line-height:1.6">
          <?= __('quality.upgrades_text', ['days' => $settings['activity_window_days']??30]) ?>
        </div>
      </div>
      <div style="background:rgba(255,255,255,0.02);border:0.5px solid rgba(255,255,255,0.06);border-radius:12px;padding:20px">
        <div style="font-size:13px;font-weight:600;margin-bottom:8px"><?= __('quality.downgrades_title') ?></div>
        <div style="font-size:13px;color:rgba(255,255,255,0.5);line-height:1.6">
          <?= __('quality.downgrades_text', ['days' => $settings['cooling_period_days']??14]) ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- <?= __('quality.ref_label') ?> -->
<section style="padding:80px 0;border-bottom:0.5px solid rgba(255,255,255,0.06)">
  <div style="max-width:960px;margin:0 auto;padding:0 24px">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:64px;align-items:center">
      <div>
        <div style="font-size:11px;color:rgba(255,255,255,0.4);letter-spacing:.1em;text-transform:uppercase;margin-bottom:12px"><?= __('quality.ref_label') ?></div>
        <h2 style="font-size:2rem;font-weight:700;margin-bottom:20px"><?= __('quality.ref_title') ?></h2>
        <p style="color:rgba(255,255,255,0.5);line-height:1.7;margin-bottom:24px">
          <?= __('quality.ref_subtitle') ?>
        </p>
        <div style="background:rgba(245,166,35,0.08);border:0.5px solid rgba(245,166,35,0.2);border-radius:12px;padding:16px 20px;font-size:13px;color:rgba(255,255,255,0.6);line-height:1.6">
          💡 <strong style="color:#fff">Important:</strong> You need to reach at least 
          <strong style="color:#f5a623"><?= ucfirst($settings['min_own_level']??'silver') ?></strong> level yourself 
          before earning any referral commissions.
        </div>
      </div>
      <div>
        <div style="display:flex;flex-direction:column;gap:12px">
          <?php
          $multipliers = [
              ['refs'=>0, 'label'=>'0 active referrals', 'mult'=>$settings['ref_multiplier_0']??0, 'color'=>'rgba(255,255,255,0.1)'],
              ['refs'=>1, 'label'=>'1 active referral',  'mult'=>$settings['ref_multiplier_1']??0.5, 'color'=>'rgba(255,255,255,0.15)'],
              ['refs'=>2, 'label'=>'2 active referrals', 'mult'=>$settings['ref_multiplier_2']??1.0, 'color'=>'rgba(62,207,142,0.15)'],
              ['refs'=>3, 'label'=>'3+ active referrals','mult'=>$settings['ref_multiplier_3plus']??1.5, 'color'=>'rgba(62,207,142,0.25)'],
          ];
          foreach ($multipliers as $m):
          ?>
          <div style="display:flex;align-items:center;justify-content:space-between;background:<?= $m['color'] ?>;border:0.5px solid rgba(255,255,255,0.06);border-radius:10px;padding:14px 18px">
            <div style="font-size:13px;color:rgba(255,255,255,0.7)"><?= $m['label'] ?></div>
            <div style="font-size:1.2rem;font-weight:700;color:<?= $m['mult']>0 ? '#3ecf8e' : 'rgba(255,255,255,0.2)' ?>">
              <?= $m['mult'] ?>×
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <div style="margin-top:12px;font-size:11px;color:rgba(255,255,255,0.3);text-align:center">
          <?= __('quality.ref_active_note') ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- <?= __('quality.tips_label') ?> -->
<section style="padding:80px 0;border-bottom:0.5px solid rgba(255,255,255,0.06)">
  <div style="max-width:960px;margin:0 auto;padding:0 24px">
    <div style="text-align:center;margin-bottom:48px">
      <div style="font-size:11px;color:rgba(255,255,255,0.4);letter-spacing:.1em;text-transform:uppercase;margin-bottom:12px"><?= __('quality.tips_label') ?></div>
      <h2 style="font-size:2rem;font-weight:700"><?= __('quality.tips_title') ?></h2>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:20px">
      <?php foreach ([
        ['icon'=>'📍','title'=>__('quality.tip1_title'),'text'=>__('quality.tip1_text')],
        ['icon'=>'🎯','title'=>__('quality.tip2_title'),'text'=>__('quality.tip2_text')],
        ['icon'=>'📱','title'=>__('quality.tip3_title'),'text'=>__('quality.tip3_text')],
        ['icon'=>'🔄','title'=>__('quality.tip4_title'),'text'=>__('quality.tip4_text')],
        ['icon'=>'⚡','title'=>__('quality.tip5_title'),'text'=>__('quality.tip5_text')],
        ['icon'=>'📊','title'=>__('quality.tip6_title'),'text'=>__('quality.tip6_text')],
      ] as $tip): ?>
      <div style="background:rgba(255,255,255,0.02);border:0.5px solid rgba(255,255,255,0.06);border-radius:12px;padding:24px">
        <div style="font-size:1.8rem;margin-bottom:12px"><?= $tip['icon'] ?></div>
        <div style="font-size:14px;font-weight:600;margin-bottom:8px"><?= $tip['title'] ?></div>
        <div style="font-size:13px;color:rgba(255,255,255,0.4);line-height:1.6"><?= $tip['text'] ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- CTA -->
<section style="padding:80px 0;text-align:center">
  <div style="max-width:560px;margin:0 auto;padding:0 24px">
    <h2 style="font-size:2rem;font-weight:700;margin-bottom:16px"><?= __('quality.cta_title') ?></h2>
    <p style="color:rgba(255,255,255,0.4);margin-bottom:32px;line-height:1.7">
      <?= __('quality.cta_subtitle') ?>
    </p>
    <?php if (!$isLoggedIn): ?>
    <a href="/register" style="display:inline-flex;align-items:center;gap:8px;background:#3ecf8e;color:#0a0a0a;padding:14px 32px;border-radius:8px;font-weight:600;text-decoration:none;font-size:15px">
      <?= __('quality.cta_register') ?>
    </a>
    <?php else: ?>
    <a href="/publisher/earnings" style="display:inline-flex;align-items:center;gap:8px;background:#3ecf8e;color:#0a0a0a;padding:14px 32px;border-radius:8px;font-weight:600;text-decoration:none;font-size:15px">
      <?= __('quality.cta_earnings') ?>
    </a>
    <?php endif; ?>
  </div>
</section>
