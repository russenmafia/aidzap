<?php
declare(strict_types=1);

namespace Core;

use PDO;

class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $cfg = require CONFIG_PATH . '/database.php';
            $dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['dbname']};charset={$cfg['charset']}";
            self::$instance = new PDO($dsn, $cfg['user'], $cfg['pass'], $cfg['options']);
        }
        return self::$instance;
    }

    private function __construct() {}
    private function __clone() {}
}
