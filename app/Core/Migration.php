<?php
declare(strict_types=1);

namespace Core;

use PDO;

class Migration
{
    private static ?PDO $db = null;

    public static function init(): void
    {
        self::$db = Database::getInstance();
        self::runPendingMigrations();
    }

    private static function runPendingMigrations(): void
    {
        $migrationsPath = BASE_PATH . '/database/migrations';
        if (!is_dir($migrationsPath)) return;

        foreach (glob($migrationsPath . '/*.sql') as $file) {
            $name = basename($file, '.sql');
            if (!self::hasRun($name)) {
                self::runMigration($name, file_get_contents($file));
                self::recordMigration($name);
            }
        }
    }

    private static function hasRun(string $name): bool
    {
        try {
            $result = self::$db->query("SELECT 1 FROM migrations WHERE name = '" . self::$db->quote($name) . "' LIMIT 1")->fetch();
            return (bool)$result;
        } catch (\Exception) {
            // Table doesn't exist yet
            self::createMigrationsTable();
            return false;
        }
    }

    private static function createMigrationsTable(): void
    {
        try {
            self::$db->exec("
                CREATE TABLE IF NOT EXISTS `migrations` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `name` VARCHAR(255) NOT NULL UNIQUE,
                    `run_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } catch (\Exception $e) {
            error_log("Failed to create migrations table: " . $e->getMessage());
        }
    }

    private static function runMigration(string $name, string $sql): void
    {
        try {
            // Split by semicolon and execute each statement
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    self::$db->exec($statement);
                }
            }
            error_log("Migration '$name' executed successfully");
        } catch (\Exception $e) {
            error_log("Migration '$name' failed: " . $e->getMessage());
        }
    }

    private static function recordMigration(string $name): void
    {
        try {
            self::$db->prepare("INSERT IGNORE INTO migrations (name) VALUES (?)")->execute([$name]);
        } catch (\Exception $e) {
            error_log("Failed to record migration: " . $e->getMessage());
        }
    }
}
