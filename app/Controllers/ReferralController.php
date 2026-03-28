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
        $userId = Auth::id();
        $service = new ReferralService();
        $refCode = $service->getRefCode($userId);
        $refLink = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'aidzap.com') . '/r/' . $refCode;
        $stats   = $service->getStats($userId);

        $socialMessages = [];
        try {
            $db = Database::getInstance();
            $result = $db->query('SELECT social_messages FROM referral_settings WHERE id = 1 LIMIT 1')->fetch(\PDO::FETCH_ASSOC);
            $socialMessages = json_decode($result['social_messages'] ?? '[]', true) ?: [];

            foreach ($socialMessages as &$msg) {
                $msg['text'] = str_replace('{ref_link}', $refLink, $msg['text']);
            }
            unset($msg);
        } catch (\Exception $e) {
            // Tabelle existiert noch nicht - no social messages yet
            error_log("ReferralController::dashboard - referral_settings: " . $e->getMessage());
        }

        View::render('dashboard/referrals', [
            'title'          => __('referral.page_title'),
            'active'         => 'referrals',
            'refCode'        => $refCode,
            'refLink'        => $refLink,
            'stats'          => $stats,
            'socialMessages' => $socialMessages,
            'csrf_token'     => \Core\Auth::csrfToken(),
        ], 'dashboard');
    }
}
