#!/bin/bash
# =============================================================================
# aidzap.com – Projekt-Setup Script
# Ausführen als root oder mit sudo auf dem Hetzner Server
# Usage: bash setup.sh
# =============================================================================

set -e

PROJECT_ROOT="/var/www/aidzap.com"
WEB_USER="www-data"

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

# --- Platzhalter-Dateien für leere Ordner ---
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

# --- Root .htaccess (schützt app/ etc.) ---
echo "[4/5] Erstelle .htaccess Dateien..."

cat > "$PROJECT_ROOT"/.htaccess << 'EOF'
# Schützt alle Verzeichnisse außer public/ vor direktem Zugriff
Options -Indexes
Require all denied
EOF

# public/.htaccess (Front Controller + HTTPS)
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
Header always set X-Content-Type-Options "nosniff"
Header always set X-Frame-Options "SAMEORIGIN"
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
EOF

echo "    ✓ .htaccess Dateien erstellt"

# --- Bootstrap public/index.php ---
cat > "$PROJECT_ROOT"/public/index.php << 'EOF'
<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('CONFIG_PATH', BASE_PATH . '/config');
define('STORAGE_PATH', BASE_PATH . '/storage');

// Autoloader
spl_autoload_register(function (string $class): void {
    $file = APP_PATH . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Umgebungsvariablen laden
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
if ($_ENV['APP_DEBUG'] ?? false) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
    ini_set('log_errors', '1');
    ini_set('error_log', STORAGE_PATH . '/logs/php_error.log');
}

// Session starten
session_start([
    'cookie_httponly' => true,
    'cookie_secure'   => true,
    'cookie_samesite' => 'Strict',
    'gc_maxlifetime'  => (int)($_ENV['SESSION_LIFETIME'] ?? 7200),
]);

// Router starten
require_once APP_PATH . '/Core/Router.php';

$router = new Core\Router();
require_once CONFIG_PATH . '/routes.php';
$router->dispatch();
EOF

# --- config/routes.php Placeholder ---
cat > "$PROJECT_ROOT"/config/routes.php << 'EOF'
<?php
// Routen-Definition
// $router->get('/', [Controllers\HomeController::class, 'index']);
// $router->get('/register', [Controllers\AuthController::class, 'registerForm']);
// $router->post('/register', [Controllers\AuthController::class, 'register']);
// $router->get('/login', [Controllers\AuthController::class, 'loginForm']);
// $router->post('/login', [Controllers\AuthController::class, 'login']);
// $router->get('/logout', [Controllers\AuthController::class, 'logout']);

// Temporäre Startseite bis Controller fertig sind
$router->get('/', function() {
    http_response_code(200);
    echo '<h1 style="font-family:monospace;padding:2rem">aidzap.com – Setup OK ✓</h1>';
});
EOF

# --- config/database.php ---
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

# --- app/Core/Router.php ---
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

        // Exakter Match
        if (isset($this->routes[$method][$uri])) {
            $this->call($this->routes[$method][$uri]);
            return;
        }

        // Dynamische Segmente (:id etc.)
        foreach ($this->routes[$method] ?? [] as $pattern => $handler) {
            $regex = preg_replace('#:([a-zA-Z_]+)#', '(?P<$1>[^/]+)', $pattern);
            if (preg_match('#^' . $regex . '$#', $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $this->call($handler, $params);
                return;
            }
        }

        // 404
        http_response_code(404);
        if (file_exists(APP_PATH . '/Views/errors/404.php')) {
            require APP_PATH . '/Views/errors/404.php';
        } else {
            echo '<h1>404 – Seite nicht gefunden</h1>';
        }
    }

    private function call(callable|array $handler, array $params = []): void
    {
        if (is_callable($handler)) {
            call_user_func_array($handler, $params);
        } elseif (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            $controller = new $class();
            $controller->$method(...array_values($params));
        }
    }
}
EOF

# --- app/Core/Database.php ---
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

    // Verhindert Klonen & Deserialisierung
    private function __construct() {}
    private function __clone() {}
}
EOF

echo "    ✓ Core-Dateien erstellt"

# --- Berechtigungen setzen ---
echo "[5/5] Setze Berechtigungen..."

chown -R "$WEB_USER":"$WEB_USER" "$PROJECT_ROOT"
find "$PROJECT_ROOT" -type d -exec chmod 755 {} \;
find "$PROJECT_ROOT" -type f -exec chmod 644 {} \;
chmod -R 775 "$PROJECT_ROOT"/storage
chmod 600 "$PROJECT_ROOT"/.env

echo "    ✓ Berechtigungen gesetzt"

echo ""
echo "========================================"
echo "  ✅ Setup abgeschlossen!"
echo "========================================"
echo ""
echo "  Nächste Schritte:"
echo "  1. .env ausfüllen: nano $PROJECT_ROOT/.env"
echo "  2. MySQL Datenbank anlegen (siehe unten)"
echo "  3. Apache VirtualHost konfigurieren"
echo "  4. https://aidzap.com aufrufen → 'Setup OK' sollte erscheinen"
echo ""
echo "  MySQL Setup:"
echo "  ┌─────────────────────────────────────────────────┐"
echo "  │ CREATE DATABASE aidzap CHARACTER SET utf8mb4    │"
echo "  │   COLLATE utf8mb4_unicode_ci;                   │"
echo "  │ CREATE USER 'aidzap_user'@'localhost'           │"
echo "  │   IDENTIFIED BY 'STRONG_PASSWORD';              │"
echo "  │ GRANT ALL PRIVILEGES ON aidzap.*               │"
echo "  │   TO 'aidzap_user'@'localhost';                 │"
echo "  │ FLUSH PRIVILEGES;                               │"
echo "  └─────────────────────────────────────────────────┘"
echo ""
