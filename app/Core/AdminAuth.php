<?php
declare(strict_types=1);

namespace Core;

class AdminAuth
{
    public static function require(): void
    {
        Auth::require();
        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT role FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([Auth::id()]);
        $user = $stmt->fetch();

        if (!$user || $user['role'] !== 'admin') {
            http_response_code(403);
            echo '<!DOCTYPE html><html><head><style>body{font-family:monospace;background:#080c10;color:#e05454;padding:2rem}</style></head>'
               . '<body><h2>403 – Access Denied</h2><p>Admin access required.</p>'
               . '<a href="/dashboard" style="color:#3ecf8e">← Back to Dashboard</a></body></html>';
            exit;
        }
    }

    public static function isAdmin(): bool
    {
        if (!Auth::check()) return false;
        $stmt = Database::getInstance()->prepare('SELECT role FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([Auth::id()]);
        $user = $stmt->fetch();
        return $user && $user['role'] === 'admin';
    }
}
