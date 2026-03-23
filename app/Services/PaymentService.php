<?php
declare(strict_types=1);

namespace Services;

use Core\Database;

class PaymentService
{
    private string $apiKey;
    private string $ipnSecret;
    private string $provider;

    public function __construct()
    {
        $this->provider  = $_ENV['CRYPTO_PROVIDER'] ?? 'nowpayments';
        $this->apiKey    = $_ENV['NOWPAYMENTS_API_KEY'] ?? '';
        $this->ipnSecret = $_ENV['NOWPAYMENTS_IPN_SECRET'] ?? '';
    }

    // ── Deposit erstellen ─────────────────────────────────────────────────
    public function createDeposit(int $userId, string $payCurrency, float $usdAmount): array
    {
        if ($this->provider === 'nowpayments') {
            return $this->nowpaymentsCreateInvoice($userId, $payCurrency, $usdAmount);
        }
        return ['error' => 'Payment provider not configured.'];
    }

    // ── NOWPayments: Invoice erstellen ────────────────────────────────────
    private function nowpaymentsCreateInvoice(int $userId, string $payCurrency, float $usdAmount): array
    {
        if (empty($this->apiKey)) {
            return ['error' => 'NOWPayments API key not configured.'];
        }

        $uuid    = $this->uuid();
        $orderId = 'AZ-' . strtoupper(substr($uuid, 0, 8));

        $payload = [
            'price_amount'    => $usdAmount,
            'price_currency'  => 'usd',
            'pay_currency'    => strtolower($payCurrency),
            'order_id'        => $orderId,
            'order_description'=> 'aidzap.com deposit',
            'ipn_callback_url' => 'https://aidzap.com/payment/ipn',
            'success_url'      => 'https://aidzap.com/advertiser/billing?paid=1',
            'cancel_url'       => 'https://aidzap.com/advertiser/billing?cancelled=1',
        ];

        $response = $this->apiCall('POST', 'https://api.nowpayments.io/v1/payment', $payload);

        if (isset($response['error'])) {
            return $response;
        }

        // Invoice in DB speichern
        $db = Database::getInstance();
        $db->prepare('
            INSERT INTO payment_invoices
                (uuid, user_id, provider, provider_id, type, pay_currency,
                 pay_amount, credit_currency, credit_amount, pay_address, status)
            VALUES (?,?,?,?,?,?,?,?,?,?,?)
        ')->execute([
            $uuid,
            $userId,
            'nowpayments',
            $response['payment_id'] ?? null,
            'deposit',
            strtoupper($payCurrency),
            $response['pay_amount'] ?? 0,
            'BTC',
            0,
            $response['pay_address'] ?? null,
            $response['payment_status'] ?? 'waiting',
        ]);

        return [
            'uuid'        => $uuid,
            'pay_address' => $response['pay_address'] ?? null,
            'pay_amount'  => $response['pay_amount'] ?? 0,
            'pay_currency'=> strtoupper($payCurrency),
            'expires_at'  => $response['expiration_estimate_date'] ?? null,
            'invoice_url' => $response['invoice_url'] ?? null,
        ];
    }

    // ── IPN Webhook verarbeiten ───────────────────────────────────────────
    public function handleIpn(string $rawBody, string $signature): bool
    {
        // Signatur prüfen
        if (!empty($this->ipnSecret)) {
            $expected = hash_hmac('sha512', $rawBody, $this->ipnSecret);
            if (!hash_equals($expected, $signature)) {
                error_log('aidzap IPN: invalid signature');
                return false;
            }
        }

        $data = json_decode($rawBody, true);
        if (!$data || empty($data['payment_id'])) return false;

        $db   = Database::getInstance();
        $stmt = $db->prepare('
            SELECT * FROM payment_invoices WHERE provider_id = ? LIMIT 1
        ');
        $stmt->execute([$data['payment_id']]);
        $invoice = $stmt->fetch();

        if (!$invoice) return false;

        $newStatus = $data['payment_status'] ?? 'waiting';

        // Invoice aktualisieren
        $db->prepare('
            UPDATE payment_invoices
            SET status = ?, confirmations = ?, ipn_data = ?, updated_at = NOW()
            WHERE id = ?
        ')->execute([
            $newStatus,
            $data['confirmations_count'] ?? 0,
            json_encode($data),
            $invoice['id'],
        ]);

        // Bei Erfolg: Guthaben gutschreiben
        if (in_array($newStatus, ['finished', 'confirmed'], true)
            && $invoice['status'] !== 'finished') {

            $btcAmount = $this->convertToBtc(
                (float)($data['actually_paid'] ?? $data['pay_amount'] ?? 0),
                strtoupper($data['pay_currency'] ?? 'BTC')
            );

            // Balance erhöhen
            $db->prepare('
                INSERT INTO balances (user_id, currency, amount)
                VALUES (?, "BTC", ?)
                ON DUPLICATE KEY UPDATE amount = amount + VALUES(amount)
            ')->execute([$invoice['user_id'], $btcAmount]);

            // Payment-Log eintragen
            $db->prepare('
                INSERT INTO payments
                    (uuid, user_id, type, currency, amount, status,
                     provider, provider_ref, tx_hash, created_at)
                VALUES (?,?,?,?,?,?,?,?,?, NOW())
            ')->execute([
                $this->uuid(),
                $invoice['user_id'],
                'deposit',
                'BTC',
                $btcAmount,
                'completed',
                'nowpayments',
                $data['payment_id'],
                $data['outcome_hash'] ?? null,
            ]);
        }

        return true;
    }

    // ── Auszahlung beantragen ─────────────────────────────────────────────
    public function requestWithdrawal(int $userId, string $address, float $amount, string $currency): array
    {
        $db = Database::getInstance();

        // Balance prüfen
        $stmt = $db->prepare('SELECT amount FROM balances WHERE user_id = ? AND currency = ? LIMIT 1');
        $stmt->execute([$userId, $currency]);
        $balance = (float)($stmt->fetchColumn() ?: 0);

        if ($balance < $amount) {
            return ['error' => 'Insufficient balance.'];
        }

        // Mindestbetrag
        $minimums = ['BTC' => 0.0001, 'ETH' => 0.005, 'LTC' => 0.01];
        $min = $minimums[$currency] ?? 0.0001;
        if ($amount < $min) {
            return ['error' => "Minimum withdrawal: {$min} {$currency}"];
        }

        // Balance reservieren
        $db->prepare('
            UPDATE balances SET amount = amount - ?
            WHERE user_id = ? AND currency = ? AND amount >= ?
        ')->execute([$amount, $userId, $currency, $amount]);

        if ($db->rowCount() === 0) {
            return ['error' => 'Insufficient balance.'];
        }

        // Auszahlungs-Record anlegen
        $uuid = $this->uuid();
        $db->prepare('
            INSERT INTO payments
                (uuid, user_id, type, currency, amount, status,
                 provider, wallet_address, created_at)
            VALUES (?,?,?,?,?,"pending","manual",?, NOW())
        ')->execute([$uuid, $userId, 'withdrawal', $currency, $amount, $address]);

        return ['success' => true, 'uuid' => $uuid];
    }

    // ── Hilfsfunktionen ───────────────────────────────────────────────────
    private function apiCall(string $method, string $url, array $data = []): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'x-api-key: ' . $this->apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 15,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $result   = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) return ['error' => 'Connection error: ' . $error];

        $decoded = json_decode($result, true);
        if ($httpCode >= 400) {
            return ['error' => $decoded['message'] ?? 'API error ' . $httpCode];
        }

        return $decoded ?? [];
    }

    private function convertToBtc(float $amount, string $currency): float
    {
        if ($currency === 'BTC') return $amount;
        // Vereinfacht – in Produktion: live Exchange Rate API
        return round($amount * 0.000025, 8); // Platzhalter
    }

    private function uuid(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),
            mt_rand(0,0x0fff)|0x4000,mt_rand(0,0x3fff)|0x8000,
            mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff));
    }
}
