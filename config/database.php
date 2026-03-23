<?php
return [
    'host'    => $_ENV['DB_HOST'] ?? 'localhost',
    'port'    => $_ENV['DB_PORT'] ?? '3306',
    'dbname'  => $_ENV['DB_NAME'] ?? 'aidzap',
    'user'    => $_ENV['DB_USER'] ?? '',
    'pass'    => $_ENV['DB_PASS'] ?? '',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];
