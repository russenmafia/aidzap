<?php
declare(strict_types=1);

namespace Services;

use Core\Database;

class FeatureFlag
{
    private static array $cache = [];

    public static function isActive(string $key): bool
    {
        if (!isset(self::$cache[$key])) {
            $db   = Database::getInstance();
            $stmt = $db->prepare('SELECT is_active FROM feature_flags WHERE flag_key = ? LIMIT 1');
            $stmt->execute([$key]);
            $row = $stmt->fetch();
            self::$cache[$key] = (bool)($row['is_active'] ?? false);
        }
        return self::$cache[$key];
    }

    public static function set(string $key, bool $value): void
    {
        $db = Database::getInstance();
        $db->prepare('UPDATE feature_flags SET is_active = ? WHERE flag_key = ?')
           ->execute([(int)$value, $key]);
        self::$cache[$key] = $value;
    }

    public static function all(): array
    {
        $db   = Database::getInstance();
        $stmt = $db->query('SELECT flag_key, is_active FROM feature_flags ORDER BY flag_key');
        return $stmt->fetchAll();
    }
}
