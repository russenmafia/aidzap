<?php
declare(strict_types=1);

namespace Core {

    class Lang
    {
        private static array $strings = [];
        private static string $lang = 'en';

        public static function init(): void
        {
            $lang = $_SESSION['lang'] ?? 'en';
            if (!in_array($lang, ['en', 'de'], true)) {
                $lang = 'en';
            }
            self::$lang = $lang;
            $file = BASE_PATH . '/lang/' . $lang . '.php';
            if (file_exists($file)) {
                self::$strings = require $file;
            }
        }

        public static function get(string $key, array $replace = []): string
        {
            $text = self::$strings[$key] ?? $key;
            foreach ($replace as $k => $v) {
                $text = str_replace(':' . $k, (string)$v, $text);
            }
            return $text;
        }

        public static function current(): string
        {
            return self::$lang;
        }
    }

}

namespace {

    if (!function_exists('__')) {
        function __(string $key, array $replace = []): string
        {
            return \Core\Lang::get($key, $replace);
        }
    }

}
