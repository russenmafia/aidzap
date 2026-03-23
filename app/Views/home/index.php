<section class="hero">
  <div class="badge"><span class="badge-dot"></span>No KYC. No Cookies. No Tracking.</div>
  <h1>Crypto advertising<br><strong>built for privacy</strong></h1>
  <p class="hero-sub">The ad network that respects both advertiser and visitor. Pure HTML/CSS banners. Anonymous accounts. Crypto payouts from day one.</p>
  <div class="hero-ctas">
    <a href="/register" class="btn-hero">Start for free →</a>
    <a href="/publisher" class="btn-hero-ghost">Publisher docs</a>
  </div>
</section>

<div class="stats-bar">
  <div class="stat"><span class="stat-num">0</span><span class="stat-label">KYC Requirements</span></div>
  <div class="stat"><span class="stat-num">20+</span><span class="stat-label">Cryptocurrencies</span></div>
  <div class="stat"><span class="stat-num">0.001</span><span class="stat-label">Min. BTC Payout</span></div>
  <div class="stat"><span class="stat-num">&#x221e;</span><span class="stat-label">Min. Traffic Needed</span></div>
</div>

<section class="features">
  <p class="section-label">Why aidzap</p>
  <h2 class="section-title">Built different, by design</h2>
  <div class="features-grid">
    <?php foreach ([
      ['&#x1F512;', 'Zero KYC',              'No name, no address, no ID. Create an account with just a username and password.'],
      ['&#x1F36A;', 'No cookies, no JS',     'Every banner is pure HTML/CSS. No invasive scripts, no fingerprinting, no third-party calls.'],
      ['&#x26A1;',  'Start instantly',       'No traffic minimums. Your first blog qualifies. No waiting period, no approval queue.'],
      ['&#x20BF;',  'Crypto-native payouts', 'BTC, ETH, LTC and 20+ more. Withdraw from 0.001 BTC. Direct to your wallet.'],
      ['&#x1F6E1;', 'AI fraud detection',   'Real-time bot scoring on every impression. Datacenter IPs, behavioral anomalies, frequency caps.'],
      ['&#x1F4CA;', 'CPD / CPM / CPA',       'Pay per day, per thousand impressions, or per action. Simple models for every budget.'],
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
  <p class="section-label">Comparison</p>
  <h2 class="section-title">aidzap vs. the alternatives</h2>
  <div class="vs-table">
    <div class="vs-row vs-header">
      <div>Feature</div><div class="col-us">AIDZAP</div><div>Others</div>
    </div>
    <?php foreach ([
      ['Anonymous registration (no KYC)',  '&#x2713; Yes',          '&#x2717; No'],
      ['No JS tracking / cookies',         '&#x2713; HTML/CSS only', '&#x2717; Required'],
      ['Traffic minimum for publishers',   '&#x2713; None',          '&#x2717; 10k+/mo'],
      ['Crypto payouts',                   '&#x2713; 20+ coins',     '&#x2717; Fiat only'],
      ['AI bot / fraud detection',         '&#x2713; Real-time',     '&#x2717; Basic'],
      ['Minimum payout',                   '&#x2713; 0.001 BTC',     '&#x2717; $100+'],
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
  <h2 class="cta-title">Ready to advertise<br>without compromise?</h2>
  <p class="cta-sub">No personal data required. Start in under 60 seconds.</p>
  <a href="/register" class="btn-hero">Create free account &#x2192;</a>
</section>
