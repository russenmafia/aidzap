<?php
declare(strict_types=1);

namespace Controllers;

use Core\Auth;
use Core\Database;
use Core\View;
use Services\PaymentService;

class PaymentController
{
    // ── Billing Dashboard ─────────────────────────────────────────────────
    public function billing(): void
    {
        Auth::require();
        $db     = Database::getInstance();
        $userId = Auth::id();

        $balances = $db->prepare('SELECT * FROM balances WHERE user_id = ? ORDER BY amount DESC')->execute([$userId]) ? [] : [];
        $stmt = $db->prepare('SELECT * FROM balances WHERE user_id = ? ORDER BY amount DESC');
        $stmt->execute([$userId]);
        $balances = $stmt->fetchAll();

        $stmt = $db->prepare('
            SELECT * FROM payment_invoices
            WHERE user_id = ?
            ORDER BY created_at DESC LIMIT 20
        ');
        $stmt->execute([$userId]);
        $invoices = $stmt->fetchAll();

        $stmt = $db->prepare('
            SELECT * FROM payments
            WHERE user_id = ?
            ORDER BY created_at DESC LIMIT 20
        ');
        $stmt->execute([$userId]);
        $payments = $stmt->fetchAll();

        View::render('dashboard/billing', [
            'title'    => 'Billing',
            'active'   => 'billing',
            'balances' => $balances,
            'invoices' => $invoices,
            'payments' => $payments,
        ], 'dashboard');
    }

    // ── Deposit erstellen ─────────────────────────────────────────────────
    public function createDeposit(): void
    {
        Auth::require();
        header('Content-Type: application/json');

        $currency  = strtoupper(trim($_POST['currency'] ?? 'BTC'));
        $usdAmount = (float)($_POST['usd_amount'] ?? 0);

        if ($usdAmount < 1) {
            echo json_encode(['error' => 'Minimum deposit: $1 USD']); return;
        }

        $service = new PaymentService();
        $result  = $service->createDeposit(Auth::id(), $currency, $usdAmount);

        echo json_encode($result);
    }

    // ── Auszahlung beantragen ─────────────────────────────────────────────
    public function withdraw(): void
    {
        Auth::require();
        \Core\Auth::csrfVerify($_POST['csrf_token'] ?? '');

        $address  = trim($_POST['wallet_address'] ?? '');
        $amount   = (float)($_POST['amount'] ?? 0);
        $currency = strtoupper(trim($_POST['currency'] ?? 'BTC'));

        if (empty($address) || $amount <= 0) {
            header('Location: /advertiser/billing?error=invalid'); exit;
        }

        $service = new PaymentService();
        $result  = $service->requestWithdrawal(Auth::id(), $address, $amount, $currency);

        if (isset($result['error'])) {
            header('Location: /advertiser/billing?error=' . urlencode($result['error'])); exit;
        }

        header('Location: /advertiser/billing?withdrawn=1'); exit;
    }

    // ── IPN Webhook ───────────────────────────────────────────────────────
    public function ipn(): void
    {
        $rawBody   = file_get_contents('php://input');
        $signature = $_SERVER['HTTP_X_NOWPAYMENTS_SIG'] ?? '';

        $service = new PaymentService();
        $success = $service->handleIpn($rawBody, $signature);

        http_response_code($success ? 200 : 400);
        echo $success ? 'OK' : 'ERROR';
    }
}
