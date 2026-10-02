<?php
/**
 * LipaByte — Database configuration
 *
 * InfinityFree: credentials live in config/database.local.php (upload with your site).
 * XAMPP localhost: config/database.xampp.php is used automatically when you open the site via localhost.
 */

declare(strict_types=1);

$dbConfig = [
    'host' => 'sql308.infinityfree.com',
    'port' => 3306,
    'dbname' => 'if0_42202321_lipabyte',
    'username' => 'if0_42202321',
    'password' => '',
    'charset' => 'utf8mb4',
];

function lipabyte_is_local_xampp(): bool
{
    if (PHP_SAPI === 'cli') {
        return is_readable(__DIR__ . '/database.xampp.php');
    }

    $host = strtolower($_SERVER['HTTP_HOST'] ?? '');

    return $host === 'localhost'
        || str_starts_with($host, 'localhost:')
        || str_starts_with($host, '127.0.0.1')
        || str_starts_with($host, '127.0.0.1:');
}

$xamppConfig = __DIR__ . '/database.xampp.php';
$localConfig = __DIR__ . '/database.local.php';

if (lipabyte_is_local_xampp() && is_readable($xamppConfig)) {
    $override = require $xamppConfig;
    if (is_array($override)) {
        $dbConfig = array_merge($dbConfig, $override);
    }
} elseif (is_readable($localConfig)) {
    $override = require $localConfig;
    if (is_array($override)) {
        $dbConfig = array_merge($dbConfig, $override);
    }
}

return $dbConfig;
