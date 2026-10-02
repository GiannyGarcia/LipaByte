<?php

declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = require __DIR__ . '/../config/database.php';

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $config['host'],
        (int) ($config['port'] ?? 3306),
        $config['dbname'],
        $config['charset']
    );

    try {
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        $localExists = is_readable(__DIR__ . '/../config/database.local.php');
        echo '<!DOCTYPE html><html><head><title>Database Error</title><style>body{font-family:sans-serif;max-width:640px;margin:3rem auto;padding:0 1rem;color:#334155}code{background:#f1f5f9;padding:.15rem .35rem;border-radius:4px}</style></head><body>';
        echo '<h1>Database connection failed</h1>';
        if (!$localExists) {
            echo '<p>Copy <code>config/database.example.php</code> to <code>config/database.local.php</code> and add your InfinityFree MySQL credentials.</p>';
        } else {
            echo '<p>Check <code>config/database.local.php</code> (InfinityFree) or <code>config/database.xampp.php</code> (localhost). Confirm the schema is imported in phpMyAdmin.</p>';
        }
        echo '</body></html>';
        exit;
    }

    return $pdo;
}
