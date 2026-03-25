<?php
declare(strict_types=1);

namespace Controllers;

use Core\View;

class HomeController
{
    public function index(): void
    {
        View::render('home/index', [
            'title'      => 'aidzap – Privacy-First Crypto Ad Network',
            'meta_desc'  => 'Anonymous crypto advertising network. No KYC, no cookies, no tracking. Earn BTC by placing ads on your site. Pay with Bitcoin, Ethereum and 20+ cryptocurrencies.',
            'og_title'   => 'aidzap – Crypto Advertising Without Compromise',
            'og_desc'    => 'No KYC. No cookies. No tracking. Earn BTC with your website or advertise with crypto. Start in under 60 seconds.',
            'meta_robots'=> 'index, follow',
        ]);
    }
}
