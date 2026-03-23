#!/bin/bash
# =============================================================================
# aidzap.com – Projekt-Setup Script
# Läuft als normaler User (aidzapa) ohne root/sudo
# Usage: bash ~/public_html/setup.sh
# =============================================================================

set -e

PROJECT_ROOT="/home/aidzapa/public_html"

echo "========================================"
echo "  aidzap.com – Projektstruktur Setup"
echo "========================================"

# --- Verzeichnisse anlegen ---
echo "[1/5] Erstelle Verzeichnisstruktur..."

mkdir -p "$PROJECT_ROOT"/public/assets/{css,js,img}
mkdir -p "$PROJECT_ROOT"/public/ad
mkdir -p "$PROJECT_ROOT"/app/Core
mkdir -p "$PROJECT_ROOT"/app/Controllers
mkdir -p "$PROJECT_ROOT"/app/Models
mkdir -p "$PROJECT_ROOT"/app/Services
mkdir -p "$PROJECT_ROOT"/app/Views/layouts
mkdir -p "$PROJECT_ROOT"/app/Views/home
mkdir -p "$PROJECT_ROOT"/app/Views/auth
mkdir -p "$PROJECT_ROOT"/app/Views/publisher
mkdir -p "$PROJECT_ROOT"/app/Views/advertiser
mkdir -p "$PROJECT_ROOT"/app/Views/errors
mkdir -p "$PROJECT_ROOT"/config
mkdir -p "$PROJECT_ROOT"/database/migrations
mkdir -p "$PROJECT_ROOT"/storage/logs
mkdir -p "$PROJECT_ROOT"/storage/cache
mkdir -p "$PROJECT_ROOT"/tests

echo "    ✓ Verzeichnisse angelegt"

# --- Platzhalter für leere Ordner ---
echo "[2/5] Erstelle Platzhalter-Dateien..."

touch "$PROJECT_ROOT"/storage/logs/.gitkeep
touch "$PROJECT_ROOT"/storage/cache/.gitkeep
touch "$PROJECT_ROOT"/tests/.gitkeep

echo "    ✓ Platzhalter erstellt"

# --- .env Datei ---
echo "[3/5] Erstelle .env Konfiguration..."

cat > "$PROJECT_ROOT"/.env.example << 'EOF'
APP_ENV=production
APP_DEBUG=false
APP_URL=https://aidzap.com
APP_SECRET=CHANGE_THIS_TO_RANDOM_64_CHAR_STRING

DB_HOST=localhost
DB_PORT=3306
DB_NAME=aidzap
DB_USER=aidzap_user
DB_PASS=STRONG_PASSWORD_HERE

CRYPTO_PROVIDER=nowpayments
NOWPAYMENTS_API_KEY=
NOWPAYMENTS_IPN_SECRET=

FRAUD_SCORE_THRESHOLD=0.75

SESSION_LIFETIME=7200
SESSION_SECURE=true
EOF

if [ ! -f "$PROJECT_ROOT"/.env ]; then
    cp "$PROJECT_ROOT"/.env.example "$PROJECT_ROOT"/.env
    echo "    ✓ .env erstellt (bitte Werte eintragen!)"
else
    echo "    ⚠ .env existiert bereits – nicht überschrieben"
fi

# --- .htaccess Dateien ---
echo "[4/5] Erstelle .htaccess Dateien..."

cat > "$PROJECT_ROOT"/.htaccess << 'EOF'
Options -Indexes
<FilesMatch "^\.">
    Require all denied
</FilesMatch>
EOF

cat > "$PROJECT_ROOT"/public/.htaccess << 'EOF'
Options -MultiViews -Indexes
RewriteEngine On

# HTTPS erzwingen
RewriteCond %{HTTPS} off
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# www → non-www
RewriteCond %{HTTP_HOST} ^www\.(.+)$ [NC]
RewriteRule ^ https://%1%{REQUEST_URI} [R=301,L]

# Statische Assets direkt ausliefern
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]

# Alles andere → Front Controller
RewriteRule ^ index.php [L]

# Security Headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>
EOF

echo "    ✓ .htaccess Dateien erstellt"

# --- PHP Core-Dateien ---
echo "[5/5] Erstelle PHP Core-Dateien..."

cat > "$PROJECT_ROOT"/public/index.php << 'EOF'
<?php
declare(strict_types=1);

define('BASE_PATH',   dirname(__DIR__));
define('APP_PATH',    BASE_PATH . '/app');
define('CONFIG_PATH', BASE_PATH . '/config');
define('STORAGE_PATH',BASE_PATH . '/storage');

// Autoloader
spl_autoload_register(function (string $class): void {
    $file = APP_PATH . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// .env laden
$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
        putenv(trim($key) . '=' . trim($value));
    }
}

// Fehlerbehandlung
if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
    ini_set('log_errors', '1');
    ini_set('error_log', STORAGE_PATH . '/logs/php_error.log');
}

// Session
session_start([
    'cookie_httponly' => true,
    'cookie_secure'   => true,
    'cookie_samesite' => 'Strict',
    'gc_maxlifetime'  => (int)($_ENV['SESSION_LIFETIME'] ?? 7200),
]);

// Router
require_once APP_PATH . '/Core/Router.php';
$router = new Core\Router();
require_once CONFIG_PATH . '/routes.php';
$router->dispatch();
EOF

cat > "$PROJECT_ROOT"/app/Core/Router.php << 'EOF'
<?php
declare(strict_types=1);

namespace Core;

class Router
{
    private array $routes = [];

    public function get(string $path, callable|array $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri    = rtrim($uri, '/') ?: '/';

        if (isset($this->routes[$method][$uri])) {
            $this->call($this->routes[$method][$uri]);
            return;
        }

        foreach ($this->routes[$method] ?? [] as $pattern => $handler) {
            $regex = preg_replace('#:([a-zA-Z_]+)#', '(?P<$1>[^/]+)', $pattern);
            if (preg_match('#^' . $regex . '$#', $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $this->call($handler, $params);
                return;
            }
        }

        http_response_code(404);
        $view404 = APP_PATH . '/Views/errors/404.php';
        file_exists($view404) ? require $view404 : print('<h1>404 – Nicht gefunden</h1>');
    }

    private function call(callable|array $handler, array $params = []): void
    {
        if (is_callable($handler)) {
            call_user_func_array($handler, $params);
        } elseif (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            (new $class())->$method(...array_values($params));
        }
    }
}
EOF

cat > "$PROJECT_ROOT"/app/Core/Database.php << 'EOF'
<?php
declare(strict_types=1);

namespace Core;

use PDO;

class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $cfg = require CONFIG_PATH . '/database.php';
            $dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['dbname']};charset={$cfg['charset']}";
            self::$instance = new PDO($dsn, $cfg['user'], $cfg['pass'], $cfg['options']);
        }
        return self::$instance;
    }

    private function __construct() {}
    private function __clone() {}
}
EOF

cat > "$PROJECT_ROOT"/config/database.php << 'EOF'
<?php
return [
    'host'    => $_ENV['DB_HOST'] ?? 'localhost',
    'port'    => $_ENV['DB_PORT'] ?? '3306',
    'dbname'  => $_ENV['DB_NAME'] ?? 'aidzap',
    'user'    => $_ENV['DB_USER'] ?? '',
    'pass'    => $_ENV['DB_PASS'] ?? '',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];
EOF

cat > "$PROJECT_ROOT"/config/routes.php << 'EOF'
<?php
$router->get('/', function() {
    http_response_code(200);
    echo '<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>aidzap.com</title>
<style>
  body { font-family: monospace; background: #0a0a0a; color: #00ff88;
         display: flex; align-items: center; justify-content: center;
         height: 100vh; margin: 0; }
  .box { text-align: center; }
  .logo { font-size: 2.5rem; font-weight: bold; letter-spacing: .2em; }
  .sub  { color: #666; margin-top: .5rem; font-size: .9rem; }
</style></head>
<body><div class="box">
  <div class="logo">AIDZAP</div>
  <div class="sub">Setup erfolgreich ✓ &nbsp;|&nbsp; PHP ' . PHP_VERSION . '</div>
</div></body></html>';
});
EOF

echo "    ✓ PHP Core-Dateien erstellt"

# --- Berechtigungen ---
chmod -R 755 "$PROJECT_ROOT"
chmod -R 775 "$PROJECT_ROOT"/storage
chmod 600 "$PROJECT_ROOT"/.env

echo ""
echo "========================================"
echo "  ✅ Setup abgeschlossen!"
echo "========================================"
echo ""
echo "  Projektpfad: $PROJECT_ROOT"
echo ""
echo "  Nächste Schritte:"
echo "  1. .env ausfüllen:"
echo "     nano $PROJECT_ROOT/.env"
echo ""
echo "  2. MySQL Datenbank im Hetzner KonsoleH Panel anlegen:"
echo "     Name: aidzap"
echo ""
echo "  3. https://aidzap.com aufrufen"
echo "     Grüner 'Setup erfolgreich' Screen sollte erscheinen"
echo ""
