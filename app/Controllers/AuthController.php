<?php
declare(strict_types=1);

namespace Controllers;

use Core\View;
use Core\Database;
use Core\Auth;

class AuthController
{
    public function registerForm(): void
    {
        if (Auth::check()) { header('Location: /dashboard'); exit; }

        // Ref-Code aus URL, Session oder Cookie
        $refCode = $_GET['ref'] ?? $_SESSION['ref_code'] ?? $_COOKIE['ref_code'] ?? null;
        if ($refCode) $_SESSION['ref_code'] = $refCode;

        View::render('auth/register', [
            'title'      => 'Create account',
            'csrf_token' => Auth::csrfToken(),
            'errors'     => [],
            'old'        => [],
            'ref_code'   => $refCode,
        ], 'auth');
    }

    public function register(): void
    {
        Auth::csrfVerify($_POST['csrf_token'] ?? '');

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['password_confirm'] ?? '';
        $role     = $_POST['role'] ?? 'publisher';
        $errors   = [];

        if (!preg_match('/^[a-zA-Z0-9_]{3,32}$/', $username))
            $errors[] = 'Username must be 3–32 characters (letters, numbers, underscores).';
        if (strlen($password) < 12)
            $errors[] = 'Password must be at least 12 characters.';
        if ($password !== $confirm)
            $errors[] = 'Passwords do not match.';
        if (!in_array($role, ['advertiser', 'publisher', 'both'], true))
            $errors[] = 'Invalid account type.';

        if (empty($errors)) {
            $stmt = Database::getInstance()->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
            $stmt->execute([$username]);
            if ($stmt->fetch()) $errors[] = 'This username is already taken.';
        }

        if (!empty($errors)) {
            View::render('auth/register', [
                'title'      => 'Create account',
                'csrf_token' => Auth::csrfToken(),
                'errors'     => $errors,
                'old'        => compact('username', 'role'),
            ], 'auth');
            return;
        }

        $db   = Database::getInstance();
        $uuid = $this->uuid();
        $hash = password_hash($password, PASSWORD_ARGON2ID);
        $db->prepare('INSERT INTO users (uuid, username, password_hash, role) VALUES (?,?,?,?)')
           ->execute([$uuid, $username, $hash, $role]);

        $userId = (int)$db->lastInsertId();

        // Referral verarbeiten
        $refCode = $_SESSION['ref_code'] ?? $_COOKIE['ref_code'] ?? null;
        if ($refCode) {
            (new \Services\ReferralService())->processSignup($userId, $refCode);
            unset($_SESSION['ref_code']);
        }

        Auth::login($userId);
        header('Location: /dashboard'); exit;
    }

    public function loginForm(): void
    {
        if (Auth::check()) { header('Location: /dashboard'); exit; }
        View::render('auth/login', [
            'title'      => 'Sign in',
            'csrf_token' => Auth::csrfToken(),
            'errors'     => [],
            'old'        => [],
        ], 'auth');
    }

    public function login(): void
    {
        Auth::csrfVerify($_POST['csrf_token'] ?? '');

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = Database::getInstance()->prepare(
            'SELECT id, password_hash, status FROM users WHERE username = ? LIMIT 1'
        );
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        $errors = [];
        if (!$user || !password_verify($password, $user['password_hash']))
            $errors[] = 'Invalid username or password.';
        elseif ($user['status'] !== 'active')
            $errors[] = 'Your account has been suspended.';

        if (!empty($errors)) {
            View::render('auth/login', [
                'title'      => 'Sign in',
                'csrf_token' => Auth::csrfToken(),
                'errors'     => $errors,
                'old'        => compact('username'),
            ], 'auth');
            return;
        }

        Auth::login((int)$user['id'], isset($_POST['remember']));

        $ip = inet_pton($_SERVER['REMOTE_ADDR'] ?? '') ?: null;
        Database::getInstance()
            ->prepare('UPDATE users SET last_login_at = NOW(), last_login_ip = ? WHERE id = ?')
            ->execute([$ip, $user['id']]);

        header('Location: /dashboard'); exit;
    }

    public function logout(): void
    {
        Auth::logout();
        header('Location: /'); exit;
    }

    private function uuid(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff),
            mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000,
            mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff));
    }
}
