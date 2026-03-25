<?php
if (!function_exists('__')) {
    function __(string $key, array $replace = []): string {
        return \Core\Lang::get($key, $replace);
    }
}
