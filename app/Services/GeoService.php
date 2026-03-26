<?php
declare(strict_types=1);

namespace Services;

class GeoService
{
    private static ?object $reader = null;
    private const DB_PATH = BASE_PATH . '/geoip/GeoLite2-Country.mmdb';

    public static function getCountry(string $ip): string
    {
        // Prefer Cloudflare header (zero-latency, no file I/O)
        if (!empty($_SERVER['HTTP_CF_IPCOUNTRY'])) {
            return strtoupper(substr($_SERVER['HTTP_CF_IPCOUNTRY'], 0, 2));
        }

        try {
            if (!file_exists(self::DB_PATH)) {
                return '';
            }
            if (!self::$reader) {
                self::$reader = new \GeoIp2\Database\Reader(self::DB_PATH);
            }
            $record = self::$reader->country($ip);
            return $record->country->isoCode ?? '';
        } catch (\Throwable) {
            return '';
        }
    }

    public static function getLanguage(string $acceptLanguage): string
    {
        if (empty($acceptLanguage)) {
            return '';
        }
        // Parse "de-AT,de;q=0.9,en;q=0.8" → "de"
        preg_match('/^([a-z]{2})/i', $acceptLanguage, $m);
        return strtolower($m[1] ?? '');
    }

    public static function getDevice(string $userAgent): string
    {
        $ua = strtolower($userAgent);
        if (str_contains($ua, 'tablet') || str_contains($ua, 'ipad')) {
            return 'tablet';
        }
        if (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone')) {
            return 'mobile';
        }
        return 'desktop';
    }

    /** Detect all three signals from the current request. */
    public static function detect(): array
    {
        $ip   = self::getClientIp();
        $ua   = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';

        return [
            'country'  => self::getCountry($ip),
            'language' => self::getLanguage($lang),
            'device'   => self::getDevice($ua),
        ];
    }

    private static function getClientIp(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = trim(explode(',', $_SERVER[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '0.0.0.0';
    }
}
