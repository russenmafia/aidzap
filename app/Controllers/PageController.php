<?php
declare(strict_types=1);

namespace Controllers;

use Core\Database;
use Core\View;

class PageController
{
    public function legal(): void
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $slug = trim($path, '/');

        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM legal_pages WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        $page = $stmt->fetch();

        if (!$page) {
            http_response_code(404);
            die('Not found');
        }

        View::render('pages/legal', [
            'title' => $page['title'],
            'page' => $page,
        ]);
    }

    public function faq(): void
    {
        $db = Database::getInstance();
        $stmt = $db->query('SELECT * FROM faq_items WHERE is_active = 1 ORDER BY sort_order ASC, id ASC');
        $items = $stmt->fetchAll();

        View::render('pages/faq', [
            'title' => 'FAQ',
            'items' => $items,
        ]);
    }

    public function publisherQuality(): void
    {
        $db = Database::getInstance();
        $settings = $db->query('SELECT * FROM quality_settings LIMIT 1')->fetch() ?: [];
        View::render('pages/publisher-quality', [
            'title'    => 'Publisher Quality Program – aidzap.com',
            'settings' => $settings,
        ]);
    }

    public function advertiser(): void
    {
        View::render('pages/advertiser', [
            'title' => 'Advertiser – aidzap.com',
        ]);
    }

    public function publisher(): void
    {
        View::render('pages/publisher', [
            'title' => 'Publisher – aidzap.com',
        ]);
    }
}