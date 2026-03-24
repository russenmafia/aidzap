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
}
