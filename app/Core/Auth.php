<?php
declare(strict_types=1);

namespace Core;

class Auth
{
    public static function check(): bool  { return isset($_SESSION['user_id']); }
    public static function id(): ?int     { return $_SESSION['user_id'] ?? null; }

    public static function login(int $userId, bool $remember = false): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id']  = $userId;
        $_SESSION['login_at'] = time();
        if ($remember) {
            // Extend cookie lifetime via setcookie (session already started)
            $cookieParams = session_get_cookie_params();
            setcookie(
                session_name(),
                session_id(),
                [
                    'expires'  => time() + 30 * 86400,
                    'path'     => $cookieParams['path'],
                    'domain'   => $cookieParams['domain'],
                    'secure'   => true,
                    'httponly' => true,
                    'samesite' => 'Strict',
                ]
            );
        }
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time()-42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function require(): void
    {
        if (!self::check()) { header('Location: /login'); exit; }
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token']))
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }

    public static function csrfVerify(string $token): void
    {
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(419); echo 'CSRF token mismatch.'; exit;
        }
        unset($_SESSION['csrf_token']);
    }
}
