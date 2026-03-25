<?php
declare(strict_types=1);

namespace Controllers;

use Core\Auth;
use Services\WalletAuthService;

class WalletAuthController
{
    // ── Nonce anfordern ───────────────────────────────────────────────────
    public function nonce(): void
    {
        \Core\RateLimit::check('wallet_nonce');
        header('Content-Type: application/json');
        $ip      = $_SERVER['REMOTE_ADDR'] ?? '';
        $ipHash  = hash('sha256', $ip . ($_ENV['APP_SECRET'] ?? ''));
        $service = new WalletAuthService();
        $nonce   = $service->generateNonce($ipHash);
        echo json_encode(['nonce' => $nonce]);
    }

    // ── SIWE Message abrufen ──────────────────────────────────────────────
    public function message(): void
    {
        header('Content-Type: application/json');
        $address = trim($_POST['address'] ?? '');
        $nonce   = trim($_POST['nonce'] ?? '');

        if (!preg_match('/^0x[0-9a-fA-F]{40}$/', $address) || empty($nonce)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid address or nonce']); return;
        }

        $service = new WalletAuthService();
        $message = $service->buildMessage($address, $nonce);
        echo json_encode(['message' => $message]);
    }

    // ── Signatur verifizieren + einloggen ─────────────────────────────────
    public function verify(): void
    {
        \Core\RateLimit::check('wallet_login');
        header('Content-Type: application/json');

        $address   = strtolower(trim($_POST['address'] ?? ''));
        $signature = trim($_POST['signature'] ?? '');
        $nonce     = trim($_POST['nonce'] ?? '');
        $message   = trim($_POST['message'] ?? '');
        file_put_contents(BASE_PATH . '/storage/logs/siwe_debug.log', json_encode(['msg_raw' => $_POST['message'] ?? '', 'msg_len' => strlen($_POST['message'] ?? ''), 'has_newline' => strpos($_POST['message'] ?? '', "
") !== false]) . "
", FILE_APPEND);

        if (!preg_match('/^0x[0-9a-fA-F]{40}$/', $address)
            || empty($signature) || empty($nonce) || empty($message)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing parameters']); return;
        }

        $service = new WalletAuthService();

        // Nonce validieren
        if (!$service->validateAndConsumeNonce($nonce)) {
            echo json_encode(['error' => 'Invalid or expired nonce']); return;
        }

        // Signatur prüfen
        if (!$service->verifySignature($message, $signature, $address)) {
            echo json_encode(['error' => 'Signature verification failed']); return;
        }

        // User finden oder erstellen
        $result = $service->findOrCreateUser($address);
        $user   = $result['user'];

        // Session starten
        Auth::login((int)$user['id']);

        echo json_encode([
            'success' => true,
            'created' => $result['created'],
            'redirect'=> '/dashboard',
        ]);
    }

    // ── Wallet mit bestehendem Account verknüpfen ─────────────────────────
    public function link(): void
    {
        Auth::require();
        header('Content-Type: application/json');

        $address   = strtolower(trim($_POST['address'] ?? ''));
        $signature = trim($_POST['signature'] ?? '');
        $nonce     = trim($_POST['nonce'] ?? '');
        $message   = trim($_POST['message'] ?? '');
        file_put_contents(BASE_PATH . '/storage/logs/siwe_debug.log', json_encode(['msg_raw' => $_POST['message'] ?? '', 'msg_len' => strlen($_POST['message'] ?? ''), 'has_newline' => strpos($_POST['message'] ?? '', "
") !== false]) . "
", FILE_APPEND);

        $service = new WalletAuthService();

        if (!$service->validateAndConsumeNonce($nonce)
            || !$service->verifySignature($message, $signature, $address)) {
            echo json_encode(['error' => 'Verification failed']); return;
        }

        $db = \Core\Database::getInstance();
        $db->prepare('UPDATE users SET wallet_address = ?, wallet_chain = "ethereum" WHERE id = ?')
           ->execute([$address, Auth::id()]);

        echo json_encode(['success' => true]);
    }
}
// TEMP DEBUG - remove after fix
