<?php
declare(strict_types=1);

namespace Controllers;

use Core\Auth;
use Core\Database;
use Core\View;
use Services\PaymentService;

class WithdrawController
{
    public function index(): void
    {
        Auth::require();
        $db     = Database::getInstance();
        $userId = Auth::id();

        $balances = $db->prepare('SELECT * FROM balances WHERE user_id = ? AND amount > 0 ORDER BY amount DESC');
        $balances->execute([$userId]);
        $balances = $balances->fetchAll();

        $wallets = $db->prepare('SELECT * FROM crypto_wallets WHERE user_id = ? ORDER BY is_default DESC, currency ASC');
        $wallets->execute([$userId]);
        $wallets = $wallets->fetchAll();

        $history = $db->prepare('
            SELECT * FROM payments
            WHERE user_id = ? AND type = "withdrawal"
            ORDER BY created_at DESC LIMIT 20
        ');
        $history->execute([$userId]);
        $history = $history->fetchAll();

        View::render('dashboard/withdraw', [
            'title'    => 'Withdraw',
            'active'   => 'withdraw',
            'balances' => $balances,
            'wallets'  => $wallets,
            'history'  => $history,
        ], 'dashboard');
    }
}
