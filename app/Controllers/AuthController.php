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
            'title'         => 'Create account',
            'csrf_token'    => Auth::csrfToken(),
            'errors'        => [],
            'old'           => [],
            'ref_code'      => $refCode,
            'wc_project_id' => self::wcProjectId(),
        ], 'auth');
    }

    public function register(): void
    {
        \Core\RateLimit::check('register');
        Auth::csrfVerify($_POST['csrf_token'] ?? '');


        // Honeypot Check
        if (!empty($_POST["website"])) { http_response_code(400); exit; }

        // Turnstile Verify
        if (!$this->verifyTurnstile()) {
            View::render("auth/register", ["title"=>"Register","errors"=>["Security check failed. Please try again."],"old"=>$_POST]);
            return;
        }
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
                'title'         => 'Create account',
                'csrf_token'    => Auth::csrfToken(),
                'errors'        => $errors,
                'old'           => compact('username', 'role'),
                'wc_project_id' => self::wcProjectId(),
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
            'title'          => 'Sign in',
            'csrf_token'     => Auth::csrfToken(),
            'errors'         => [],
            'old'            => [],
            'wc_project_id'  => self::wcProjectId(),
        ], 'auth');
    }

    public function login(): void
    {
        \Core\RateLimit::check('login');
        Auth::csrfVerify($_POST['csrf_token'] ?? '');

        // Honeypot Check
        if (!empty($_POST["website"])) { http_response_code(400); exit; }

        // Turnstile Verify
        if (!$this->verifyTurnstile()) {
            View::render("auth/login", ["title"=>"Sign in","errors"=>["Security check failed. Please try again."],"old"=>$_POST,"csrf_token"=>Auth::csrfToken(),"wc_project_id"=>self::wcProjectId()]);
            return;
        }

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
                'title'         => 'Sign in',
                'csrf_token'    => Auth::csrfToken(),
                'errors'        => $errors,
                'old'           => compact('username'),
                'wc_project_id' => self::wcProjectId(),
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

    private static function wcProjectId(): string
    {
        $id = $_ENV['WALLETCONNECT_PROJECT_ID'] ?? '';
        if ($id === '' && ($_ENV['APP_DEBUG'] ?? 'false') !== 'true') {
            error_log('WALLETCONNECT_PROJECT_ID is not set. WalletConnect will not work.');
        }
        return $id;
    }
    private function verifyTurnstile(): bool
    {
        $token = $_POST['cf-turnstile-response'] ?? '';
        if (empty($token)) return false;
        $secret = $_ENV['TURNSTILE_SECRET_KEY'] ?? '';
        if (empty($secret)) return true;
        $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['secret' => $secret, 'response' => $token]));
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        curl_close($ch);
        if (!$response) return false;
        $data = json_decode($response, true);
        return $data['success'] ?? false;
    }
}