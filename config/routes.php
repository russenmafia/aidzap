<?php
$router->get('/',          [\Controllers\HomeController::class, 'index']);
$router->get('/register',  [\Controllers\AuthController::class, 'registerForm']);
$router->post('/register', [\Controllers\AuthController::class, 'register']);
$router->get('/login',     [\Controllers\AuthController::class, 'loginForm']);
$router->post('/login',    [\Controllers\AuthController::class, 'login']);
$router->get('/logout',    [\Controllers\AuthController::class, 'logout']);

$router->get('/dashboard', function() {
    \Core\Auth::require();
    $stmt = \Core\Database::getInstance()->prepare('SELECT username, role FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([\Core\Auth::id()]);
    $u = $stmt->fetch();
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8">
    <style>body{font-family:monospace;background:#080c10;color:#e8e6df;padding:2rem}
    a{color:#3ecf8e}h2{color:#fff;margin-bottom:1rem}
    .box{background:#0d1117;padding:1.5rem 2rem;border-radius:12px;display:inline-block;border:0.5px solid rgba(255,255,255,0.08)}</style></head><body>
    <h2>Dashboard &#x2713;</h2>
    <div class="box">
    <p>Welcome, <strong>' . htmlspecialchars($u['username'] ?? '') . '</strong></p>
    <p style="margin-top:.5rem;color:rgba(255,255,255,0.4)">Role: ' . htmlspecialchars($u['role'] ?? '') . '</p>
    <br><a href="/logout">&#x2192; Logout</a>
    </div></body></html>';
});
