<?php
declare(strict_types=1);

namespace Controllers;

use Core\View;

class HomeController
{
    public function index(): void
    {
        View::render('home/index', [
            'title'     => 'aidzap',
            'meta_desc' => 'Privacy-first crypto advertising. No KYC, no cookies, no tracking.',
        ]);
    }
}
