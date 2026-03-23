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
