<?php
declare(strict_types=1);

namespace Services;

use Core\Database;

class MailService
{
    public static function send(string $to, string $subject, string $body): bool
    {
        $settings = self::loadSettings();

        $fromEmail = trim((string)($settings['smtp_from_email'] ?? ''));
        if ($fromEmail === '') {
            $fromEmail = trim((string)($settings['site_email'] ?? 'noreply@aidzap.com'));
        }

        $fromName = trim((string)($settings['smtp_from_name'] ?? 'aidzap.com'));
        $fromName = $fromName !== '' ? $fromName : 'aidzap.com';

        $headers = [
            'From: ' . $fromName . ' <' . $fromEmail . '>',
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
        ];

        // SMTP settings are persisted for future PHPMailer integration.
        // Current transport falls back to PHP mail().
        return mail($to, $subject, $body, implode("\r\n", $headers));
    }

    private static function loadSettings(): array
    {
        $settings = [];

        try {
            $rows = Database::getInstance()->query('SELECT `key`, `value` FROM site_settings')->fetchAll();
            foreach ($rows as $row) {
                $settings[(string)$row['key']] = (string)($row['value'] ?? '');
            }
        } catch (\Throwable $e) {
            error_log('MailService::loadSettings - ' . $e->getMessage());
        }

        return $settings;
    }
}
