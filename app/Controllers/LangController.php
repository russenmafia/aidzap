<?php
declare(strict_types=1);

namespace Controllers;

class LangController
{
    public function switch(string $code): void
    {
        if (in_array($code, ['en', 'de'], true)) {
            $_SESSION['lang'] = $code;
        }
        $ref = $_SERVER['HTTP_REFERER'] ?? '/';
        // Prevent open redirect – only allow same-origin
        $parsed = parse_url($ref);
        if (!empty($parsed['host']) && $parsed['host'] !== ($_SERVER['HTTP_HOST'] ?? '')) {
            $ref = '/';
        }
        header('Location: ' . $ref);
        exit;
    }
}
