<?php
declare(strict_types=1);

namespace Controllers;

use Core\Auth;
use Core\Database;
use Core\View;

class AccountController
{
    // ── Wallets ───────────────────────────────────────────────────────────
    public function wallets(): void
    {
        Auth::require();
        $db = Database::getInstance();

        $wallets = $db->prepare('SELECT * FROM crypto_wallets WHERE user_id = ? ORDER BY is_default DESC, created_at DESC');
        $wallets->execute([Auth::id()]);

        $user = $db->prepare('SELECT wallet_address, wallet_chain FROM users WHERE id = ? LIMIT 1');
        $user->execute([Auth::id()]);
        $user = $user->fetch();

        View::render('dashboard/wallets', [
            'title'      => 'Wallets',
            'active'     => 'wallets',
            'wallets'    => $wallets->fetchAll(),
            'login_wallet' => $user['wallet_address'] ?? null,
            'csrf_token' => Auth::csrfToken(),
            'errors'     => [],
        ], 'dashboard');
    }

    public function addWallet(): void
    {
        Auth::require();
        Auth::csrfVerify($_POST['csrf_token'] ?? '');

        $currency = strtoupper(trim($_POST['currency'] ?? ''));
        $address  = trim($_POST['address'] ?? '');
        $label    = trim($_POST['label'] ?? '');
        $isDefault= isset($_POST['is_default']);
        $errors   = [];

        $validCurrencies = ['BTC','ETH','LTC','USDT','XMR','DOGE','BNB','SOL','TRX','MATIC'];
        if (!in_array($currency, $validCurrencies, true)) $errors[] = 'Invalid currency.';
        if (strlen($address) < 10) $errors[] = 'Invalid wallet address.';

        if (empty($errors)) {
            $db = Database::getInstance();

            if ($isDefault) {
                $db->prepare('UPDATE crypto_wallets SET is_default = 0 WHERE user_id = ? AND currency = ?')
                   ->execute([Auth::id(), $currency]);
            }

            $db->prepare('
                INSERT INTO crypto_wallets (user_id, currency, address, label, is_default)
                VALUES (?,?,?,?,?)
                ON DUPLICATE KEY UPDATE label = VALUES(label), is_default = VALUES(is_default)
            ')->execute([Auth::id(), $currency, $address, $label ?: null, $isDefault ? 1 : 0]);
        }

        header('Location: /account/wallets' . (empty($errors) ? '?added=1' : '?error=1')); exit;
    }

    public function deleteWallet(): void
    {
        Auth::require();
        Auth::csrfVerify($_POST['csrf_token'] ?? '');

        $id = (int)($_POST['wallet_id'] ?? 0);
        if ($id) {
            Database::getInstance()->prepare('DELETE FROM crypto_wallets WHERE id = ? AND user_id = ?')
                ->execute([$id, Auth::id()]);
        }
        header('Location: /account/wallets?deleted=1'); exit;
    }

    public function setDefaultWallet(): void
    {
        Auth::require();
        Auth::csrfVerify($_POST['csrf_token'] ?? '');

        $id = (int)($_POST['wallet_id'] ?? 0);
        if ($id) {
            $db = Database::getInstance();
            $stmt = $db->prepare('SELECT currency FROM crypto_wallets WHERE id = ? AND user_id = ? LIMIT 1');
            $stmt->execute([$id, Auth::id()]);
            $wallet = $stmt->fetch();
            if ($wallet) {
                $db->prepare('UPDATE crypto_wallets SET is_default = 0 WHERE user_id = ? AND currency = ?')
                   ->execute([Auth::id(), $wallet['currency']]);
                $db->prepare('UPDATE crypto_wallets SET is_default = 1 WHERE id = ?')
                   ->execute([$id]);
            }
        }
        header('Location: /account/wallets?default=1'); exit;
    }

    // ── Settings ──────────────────────────────────────────────────────────
    public function settings(): void
    {
        Auth::require();
        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([Auth::id()]);
        $user = $stmt->fetch();

        View::render('dashboard/settings', [
            'title'         => 'Settings',
            'active'        => 'settings',
            'user'          => $user,
            'csrf_token'    => Auth::csrfToken(),
            'errors'        => [],
            'success'       => $_GET['saved'] ?? null,
            'wc_project_id' => $_ENV['WALLETCONNECT_PROJECT_ID'] ?? '',
        ], 'dashboard');
    }

    public function updateUsername(): void
    {
        Auth::require();
        Auth::csrfVerify($_POST['csrf_token'] ?? '');

        $username = trim($_POST['username'] ?? '');
        $errors   = [];

        if (!preg_match('/^[a-zA-Z0-9_]{3,32}$/', $username)) {
            $errors[] = 'Username must be 3–32 characters (letters, numbers, underscores).';
        }

        if (empty($errors)) {
            $db   = Database::getInstance();
            $stmt = $db->prepare('SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1');
            $stmt->execute([$username, Auth::id()]);
            if ($stmt->fetch()) $errors[] = 'Username already taken.';
        }

        if (empty($errors)) {
            Database::getInstance()->prepare('UPDATE users SET username = ? WHERE id = ?')
                ->execute([$username, Auth::id()]);
            header('Location: /account/settings?saved=username'); exit;
        }

        $this->settingsWithErrors($errors);
    }

    public function updatePassword(): void
    {
        Auth::require();
        Auth::csrfVerify($_POST['csrf_token'] ?? '');

        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $errors  = [];

        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT password_hash, wallet_address FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([Auth::id()]);
        $user = $stmt->fetch();

        // Wallet-only User hat kein Passwort
        if (empty($user['password_hash']) && !empty($user['wallet_address'])) {
            if (strlen($new) < 12) {
                $errors[] = 'Password must be at least 12 characters.';
            } elseif ($new !== $confirm) {
                $errors[] = 'Passwords do not match.';
            }
        } else {
            if (!password_verify($current, $user['password_hash'])) $errors[] = 'Current password is incorrect.';
            if (strlen($new) < 12) $errors[] = 'New password must be at least 12 characters.';
            if ($new !== $confirm) $errors[] = 'Passwords do not match.';
        }

        if (empty($errors)) {
            $hash = password_hash($new, PASSWORD_ARGON2ID);
            $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, Auth::id()]);
            header('Location: /account/settings?saved=password'); exit;
        }

        $this->settingsWithErrors($errors);
    }

    public function generateApiToken(): void
    {
        Auth::require();
        Auth::csrfVerify($_POST['csrf_token'] ?? '');

        $token = bin2hex(random_bytes(32));
        Database::getInstance()->prepare('UPDATE users SET api_token = ? WHERE id = ?')
            ->execute([$token, Auth::id()]);

        header('Location: /account/settings?saved=token'); exit;
    }

    public function revokeApiToken(): void
    {
        Auth::require();
        Auth::csrfVerify($_POST['csrf_token'] ?? '');

        Database::getInstance()->prepare('UPDATE users SET api_token = NULL WHERE id = ?')
            ->execute([Auth::id()]);

        header('Location: /account/settings?saved=revoked'); exit;
    }

    public function linkWallet(): void
    {
        Auth::require();
        header('Content-Type: application/json');

        $address   = strtolower(trim($_POST['address'] ?? ''));
        $signature = trim($_POST['signature'] ?? '');
        $nonce     = trim($_POST['nonce'] ?? '');
        $message   = trim($_POST['message'] ?? '');

        $service = new \Services\WalletAuthService();

        if (!$service->validateAndConsumeNonce($nonce)
            || !$service->verifySignature($message, $signature, $address)) {
            echo json_encode(['error' => 'Verification failed']); return;
        }

        // Prüfen ob Adresse schon von anderem User verwendet
        $stmt = Database::getInstance()->prepare('SELECT id FROM users WHERE wallet_address = ? AND id != ? LIMIT 1');
        $stmt->execute([$address, Auth::id()]);
        if ($stmt->fetch()) {
            echo json_encode(['error' => 'This wallet is already linked to another account']); return;
        }

        Database::getInstance()->prepare('UPDATE users SET wallet_address = ?, wallet_chain = "ethereum" WHERE id = ?')
            ->execute([$address, Auth::id()]);

        echo json_encode(['success' => true]);
    }

    public function unlinkWallet(): void
    {
        Auth::require();
        Auth::csrfVerify($_POST['csrf_token'] ?? '');

        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([Auth::id()]);
        $user = $stmt->fetch();

        if (empty($user['password_hash'])) {
            header('Location: /account/settings?error=set_password_first'); exit;
        }

        $db->prepare('UPDATE users SET wallet_address = NULL, wallet_chain = NULL WHERE id = ?')
           ->execute([Auth::id()]);

        header('Location: /account/settings?saved=wallet_unlinked'); exit;
    }

    public function deleteAccount(): void
    {
        Auth::require();
        Auth::csrfVerify($_POST['csrf_token'] ?? '');

        $confirm = $_POST['confirm'] ?? '';
        if ($confirm !== 'DELETE') {
            header('Location: /account/settings?error=confirm'); exit;
        }

        $userId = Auth::id();
        Auth::logout();

        Database::getInstance()->prepare('UPDATE users SET status = "banned", username = CONCAT("deleted_", id) WHERE id = ?')
            ->execute([$userId]);

        header('Location: /?deleted=1'); exit;
    }

    private function settingsWithErrors(array $errors): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([Auth::id()]);
        $user = $stmt->fetch();

        View::render('dashboard/settings', [
            'title'         => 'Settings',
            'active'        => 'settings',
            'user'          => $user,
            'csrf_token'    => Auth::csrfToken(),
            'errors'        => $errors,
            'success'       => null,
            'wc_project_id' => $_ENV['WALLETCONNECT_PROJECT_ID'] ?? '',
        ], 'dashboard');
    }
}
