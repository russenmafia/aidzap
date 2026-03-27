<?php
// Language switcher
$router->get('/lang/:code', [\Controllers\LangController::class, 'switch']);

$router->get('/',          [\Controllers\HomeController::class, 'index']);
$router->get('/register',  [\Controllers\AuthController::class, 'registerForm']);
$router->post('/register', [\Controllers\AuthController::class, 'register']);
$router->get('/login',     [\Controllers\AuthController::class, 'loginForm']);
$router->post('/login',    [\Controllers\AuthController::class, 'login']);
$router->get('/logout',    [\Controllers\AuthController::class, 'logout']);

// Dashboard
$router->get('/dashboard',  [\Controllers\DashboardController::class, 'index']);

// Publisher
$router->get('/publisher/units',         [\Controllers\AdUnitController::class, 'index']);
$router->get('/publisher/units/create',  [\Controllers\AdUnitController::class, 'createForm']);
$router->post('/publisher/units/create', [\Controllers\AdUnitController::class, 'create']);

// Advertiser
$router->get('/advertiser/campaigns',         [\Controllers\CampaignController::class, 'index']);
$router->get('/advertiser/campaigns/create',  [\Controllers\CampaignController::class, 'createForm']);
$router->post('/advertiser/campaigns/create', [\Controllers\CampaignController::class, 'create']);

// Placeholders
foreach (['/publisher/earnings','/publisher/withdraw','/advertiser/banners',
          '/advertiser/billing','/account/wallets','/account/settings'] as $route) {
    $router->get($route, function() use ($route) {
        \Core\Auth::require();
        echo '<p style="font-family:monospace;padding:2rem;color:#3ecf8e">Coming soon: ' . htmlspecialchars($route) . '</p>';
    });
}

// Ad Click Tracking
$router->get('/ad/click', function() {
    require_once APP_PATH . '/../public/ad/click.php';
});

// Ad Serving (wird auch direkt über public/ad/serve.php aufgerufen)
$router->get('/ad/:uuid', function(string $uuid) {
    $_GET['unit'] = $uuid;
    require_once APP_PATH . '/../public/ad/serve.php';
});

// Admin
$router->get('/admin',                        [\Controllers\AdminController::class, 'index']);
$router->get('/admin/users',                  [\Controllers\AdminController::class, 'users']);
$router->post('/admin/users/action',          [\Controllers\AdminController::class, 'userAction']);
$router->get('/admin/review/units',           [\Controllers\AdminController::class, 'reviewUnits']);
$router->post('/admin/review/units/action',   [\Controllers\AdminController::class, 'reviewUnitAction']);
$router->get('/admin/review/banners',         [\Controllers\AdminController::class, 'reviewBanners']);
$router->post('/admin/review/banners/action', [\Controllers\AdminController::class, 'reviewBannerAction']);
$router->get('/admin/fraud',                  [\Controllers\AdminController::class, 'fraud']);
$router->post('/admin/fraud/unblacklist',     [\Controllers\AdminController::class, 'unblacklist']);

// Legal Pages Admin
$router->get('/admin/legal',                  [\Controllers\AdminController::class, 'legalPages']);
$router->get('/admin/legal/:slug',            [\Controllers\AdminController::class, 'legalPageEdit']);
$router->post('/admin/legal/:slug',           [\Controllers\AdminController::class, 'legalPageUpdate']);

// FAQ Admin
$router->get('/admin/faq',                    [\Controllers\AdminController::class, 'faqIndex']);
$router->get('/admin/faq/add',                [\Controllers\AdminController::class, 'faqAdd']);
$router->post('/admin/faq/add',               [\Controllers\AdminController::class, 'faqStore']);
$router->get('/admin/faq/:id',                [\Controllers\AdminController::class, 'faqEdit']);
$router->post('/admin/faq/:id',               [\Controllers\AdminController::class, 'faqUpdate']);
$router->post('/admin/faq/:id/delete',        [\Controllers\AdminController::class, 'faqDelete']);

// Public Pages
$router->get('/publisher', [\Controllers\PageController::class, 'publisher']);
$router->get('/advertiser', [\Controllers\PageController::class, 'advertiser']);
$router->get('/terms',                        [\Controllers\PageController::class, 'legal']);
$router->get('/privacy',                      [\Controllers\PageController::class, 'legal']);
$router->get('/impressum',                    [\Controllers\PageController::class, 'legal']);
$router->get('/publisher-quality', [\Controllers\PageController::class, 'publisherQuality']);
$router->get('/faq',                          [\Controllers\PageController::class, 'faq']);

// Banner Routes
$router->get('/advertiser/campaigns/:uuid/banners',              [\Controllers\BannerController::class, 'index']);
$router->get('/advertiser/campaigns/:uuid/banners/create',       [\Controllers\BannerController::class, 'createForm']);
$router->post('/advertiser/campaigns/:uuid/banners/create',      [\Controllers\BannerController::class, 'create']);
$router->post('/advertiser/campaigns/:uuid/banners/:buuid/delete', [\Controllers\BannerController::class, 'delete']);
$router->post('/advertiser/banners/generate-ai',                 [\Controllers\BannerController::class, 'generateAi']);

// Payment
$router->get('/advertiser/billing',  [\Controllers\PaymentController::class, 'billing']);
$router->post('/payment/deposit',    [\Controllers\PaymentController::class, 'createDeposit']);
$router->post('/advertiser/withdraw',[\Controllers\PaymentController::class, 'withdraw']);
$router->post('/payment/ipn',        [\Controllers\PaymentController::class, 'ipn']);

// Wallet Auth
$router->get('/wallet/nonce',        [\Controllers\WalletAuthController::class, 'nonce']);
$router->post('/wallet/message',     [\Controllers\WalletAuthController::class, 'message']);
$router->post('/wallet/verify',      [\Controllers\WalletAuthController::class, 'verify']);
$router->post('/wallet/link',        [\Controllers\WalletAuthController::class, 'link']);

// Cron Admin
$router->get('/admin/crons',          [\Controllers\AdminController::class, 'crons']);
$router->post('/admin/crons/run',     [\Controllers\AdminController::class, 'runCron']);
$router->get('/admin/crons/run-http', [\Controllers\AdminController::class, 'runCronHttp']);

// Feature Flags Admin
$router->get('/admin/features',         [\Controllers\AdminController::class, 'features']);
$router->post('/admin/features/toggle', [\Controllers\AdminController::class, 'toggleFeature']);

// Quality Score Admin
$router->get('/admin/quality',          [\Controllers\AdminController::class, 'quality']);
$router->post('/admin/quality/save',    [\Controllers\AdminController::class, 'saveQuality']);

// Account
$router->get('/account/wallets',                   [\Controllers\AccountController::class, 'wallets']);
$router->post('/account/wallets/add',              [\Controllers\AccountController::class, 'addWallet']);
$router->post('/account/wallets/delete',           [\Controllers\AccountController::class, 'deleteWallet']);
$router->post('/account/wallets/default',          [\Controllers\AccountController::class, 'setDefaultWallet']);
$router->get('/account/settings',                  [\Controllers\AccountController::class, 'settings']);
$router->post('/account/settings/username',        [\Controllers\AccountController::class, 'updateUsername']);
$router->post('/account/settings/password',        [\Controllers\AccountController::class, 'updatePassword']);
$router->post('/account/settings/token/generate',  [\Controllers\AccountController::class, 'generateApiToken']);
$router->post('/account/settings/token/revoke',    [\Controllers\AccountController::class, 'revokeApiToken']);
$router->post('/account/settings/wallet/link',     [\Controllers\AccountController::class, 'linkWallet']);
$router->post('/account/settings/wallet/unlink',   [\Controllers\AccountController::class, 'unlinkWallet']);
$router->post('/account/settings/delete',          [\Controllers\AccountController::class, 'deleteAccount']);

// Publisher Earnings
$router->get('/publisher/earnings', [\Controllers\EarningsController::class, 'index']);

// Banners Overview + Withdraw
$router->get('/advertiser/banners',  [\Controllers\BannersOverviewController::class, 'index']);
$router->get('/publisher/withdraw',  [\Controllers\WithdrawController::class, 'index']);

// Referrals
$router->get('/referrals',           [\Controllers\ReferralController::class, 'index']);
$router->get('/r/:code',             [\Controllers\ReferralController::class, 'redirect']);
$router->get('/admin/referrals',     [\Controllers\AdminReferralController::class, 'index']);
$router->post('/admin/referrals/save', [\Controllers\AdminReferralController::class, 'save']);

// Campaign Edit
$router->get('/advertiser/campaigns/:uuid/edit',  [\Controllers\CampaignController::class, 'editForm']);
$router->post('/advertiser/campaigns/:uuid/edit', [\Controllers\CampaignController::class, 'edit']);
$router->post('/advertiser/campaigns/:uuid/toggle', [\Controllers\CampaignController::class, 'toggleStatus']);

// SEO
$router->get('/sitemap.xml', function() {
    header('Content-Type: application/xml; charset=utf-8');
    readfile(BASE_PATH . '/public/sitemap.xml');
});
$router->get('/robots.txt', function() {
    header('Content-Type: text/plain');
    readfile(BASE_PATH . '/public/robots.txt');
});

// Finance Dashboard
$router->get('/admin/finance', [\Controllers\AdminController::class, 'finance']);

// System Overview
$router->get('/admin/system',              [\Controllers\AdminController::class, 'system']);
$router->post('/admin/system/clear-cache', [\Controllers\AdminController::class, 'clearCache']);
