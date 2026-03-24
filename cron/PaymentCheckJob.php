<?php
declare(strict_types=1);

namespace Cron;

use Core\Database;

class PaymentCheckJob
{
    public function run(): string
    {
        $db     = Database::getInstance();
        $apiKey = $_ENV['NOWPAYMENTS_API_KEY'] ?? '';

        if (empty($apiKey)) {
            return 'Skipped: NOWPayments API key not configured';
        }

        // Offene Invoices prüfen (nicht älter als 2 Tage)
        $pending = $db->query("
            SELECT * FROM payment_invoices
            WHERE status IN ('waiting','confirming')
              AND created_at > DATE_SUB(NOW(), INTERVAL 2 DAY)
            LIMIT 50
        ")->fetchAll();

        $updated = 0;

        foreach ($pending as $invoice) {
            if (!$invoice['provider_id']) continue;

            $result = $this->checkStatus($invoice['provider_id'], $apiKey);
            if (!$result) continue;

            $newStatus = $result['payment_status'] ?? null;
            if (!$newStatus || $newStatus === $invoice['status']) continue;

            // Status aktualisieren
            $db->prepare("
                UPDATE payment_invoices
                SET status = ?, confirmations = ?, updated_at = NOW()
                WHERE id = ?
            ")->execute([
                $newStatus,
                $result['confirmations_count'] ?? 0,
                $invoice['id'],
            ]);

            // Bei Erfolg: Guthaben gutschreiben
            if (in_array($newStatus, ['finished','confirmed'], true)
                && !in_array($invoice['status'], ['finished','confirmed'], true)) {

                $btcAmount = (float)($result['actually_paid'] ?? $result['pay_amount'] ?? 0);
                if ($btcAmount > 0) {
                    $db->prepare("
                        INSERT INTO balances (user_id, currency, amount)
                        VALUES (?, 'BTC', ?)
                        ON DUPLICATE KEY UPDATE amount = amount + VALUES(amount)
                    ")->execute([$invoice['user_id'], $btcAmount]);

                    $db->prepare("
                        INSERT INTO payments
                            (uuid, user_id, type, currency, amount, status, provider, provider_ref, created_at)
                        VALUES (?, ?, 'deposit', 'BTC', ?, 'completed', 'nowpayments', ?, NOW())
                    ")->execute([
                        bin2hex(random_bytes(16)),
                        $invoice['user_id'],
                        $btcAmount,
                        $invoice['provider_id'],
                    ]);

                    echo "  Credited {$btcAmount} BTC to user #{$invoice['user_id']}\n";
                }
            }

            $updated++;
        }

        return "Checked " . count($pending) . " invoices, updated {$updated}";
    }

    private function checkStatus(string $paymentId, string $apiKey): ?array
    {
        $ch = curl_init("https://api.nowpayments.io/v1/payment/{$paymentId}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['x-api-key: ' . $apiKey],
            CURLOPT_TIMEOUT        => 10,
        ]);
        $result   = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$result) return null;
        return json_decode($result, true);
    }
}
