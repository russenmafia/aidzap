<?php
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

// Ad Serving (wird auch direkt über public/ad/serve.php aufgerufen)
$router->get('/ad/:uuid', function(string $uuid) {
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
