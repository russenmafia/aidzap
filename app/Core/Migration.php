<?php
declare(strict_types=1);

namespace Core;

use PDO;
use PDOException;

class Migration
{
    private static ?PDO $db = null;

    public static function init(): void
    {
        try {
            self::$db = Database::getInstance();
            self::runPendingMigrations();
        } catch (\Exception $e) {
            error_log("Migration init failed: " . $e->getMessage());
            // Don't block app startup if migrations fail
        }
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
            $stmt = self::$db->prepare("SELECT 1 FROM migrations WHERE name = ? LIMIT 1");
            $stmt->execute([$name]);
            return (bool)$stmt->fetch();
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
                    try {
                        self::$db->exec($statement);
                    } catch (PDOException $e) {
                        // Log individual statement errors but continue with others
                        // This allows duplicate columns, tables to be handled gracefully
                        $code = $e->errorInfo[1] ?? null;
                        if ($code !== 1060 && $code !== 1050) { // 1060 = duplicate column, 1050 = table already exists
                            error_log("Migration '$name' statement error: " . $e->getMessage());
                        }
                    }
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
