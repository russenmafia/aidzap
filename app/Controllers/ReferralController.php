<?php
declare(strict_types=1);

namespace Controllers;

use Core\Auth;
use Core\Database;
use Core\View;
use Services\ReferralService;

class ReferralController
{
    public function index(): void
    {
        Auth::require();
        $service  = new ReferralService();
        $refCode  = $service->getRefCode(Auth::id());
        $stats    = $service->getStats(Auth::id());
        $settings = $service->getSettings();
        $refUrl   = 'https://aidzap.com/r/' . $refCode;

        // Social Share Texte
        $shareTexts = $this->getShareTexts($refUrl);

        View::render('dashboard/referrals', [
            'title'      => 'Referrals',
            'active'     => 'referrals',
            'refCode'    => $refCode,
            'refUrl'     => $refUrl,
            'stats'      => $stats,
            'settings'   => $settings,
            'shareTexts' => $shareTexts,
        ], 'dashboard');
    }

    // ── Referral Redirect ─────────────────────────────────────────────────
    public function redirect(string $code): void
    {
        // Ref-Code in Session speichern
        session_start();
        $_SESSION['ref_code'] = strtoupper($code);
        header('Location: /register'); exit;
    }

    private function getShareTexts(string $refUrl): array
    {
        return [
            'en' => "🔒 Privacy-first crypto advertising – no KYC, no cookies, no tracking!\n\nEarn BTC by placing ads on your site or advertise with crypto. Start for free:\n{$refUrl}\n\n#crypto #bitcoin #advertising #privacy",
            'de' => "🔒 Crypto-Werbung ohne KYC und ohne Tracking!\n\nVerdiene BTC mit Werbeflächen oder schalte Krypto-Ads. Kostenlos starten:\n{$refUrl}\n\n#krypto #bitcoin #werbung #datenschutz",
        ];
    }

    // ── Referral Dashboard ────────────────────────────────────────────────────
    public function dashboard(): void
    {
        Auth::require();
        $userId = (int)Auth::id();

        try {
            $service = new ReferralService();
            $refCode = $service->getRefCode($userId);
        } catch (\Exception $e) {
            error_log("ReferralController::dashboard getRefCode - " . $e->getMessage());
            $refCode = strtoupper(substr(md5((string)$userId), 0, 8));
        }

        $refLink = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'aidzap.com') . '/r/' . $refCode;

        try {
            $service = $service ?? new ReferralService();
            $stats   = $service->getStats($userId);
        } catch (\Exception $e) {
            error_log("ReferralController::dashboard getStats - " . $e->getMessage());
            $stats = [
                'counts'   => ['total' => 0, 'level1' => 0, 'level2' => 0, 'level3' => 0],
                'earnings' => ['total' => 0, 'from_earnings' => 0, 'from_spend' => 0, 'from_signup' => 0],
                'referrals'=> [],
            ];
        }

        $socialMessages = [];
        try {
            $db   = Database::getInstance();
            $stmt = $db->prepare('SELECT social_messages FROM referral_settings WHERE id = 1 LIMIT 1');
            $stmt->execute();
            $row  = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($row && !empty($row['social_messages'])) {
                $decoded = json_decode($row['social_messages'], true);
                if (is_array($decoded)) {
                    $socialMessages = $decoded;
                    foreach ($socialMessages as &$msg) {
                        $msg['text'] = str_replace('{ref_link}', $refLink, $msg['text'] ?? '');
                    }
                    unset($msg);
                }
            }
        } catch (\Exception $e) {
            error_log("ReferralController::dashboard social_messages - " . $e->getMessage());
        }

        View::render('dashboard/referrals', [
            'title'          => __('referral.page_title'),
            'active'         => 'referrals',
            'refCode'        => $refCode,
            'refLink'        => $refLink,
            'stats'          => $stats,
            'socialMessages' => $socialMessages,
        ], 'dashboard');
    }
}
