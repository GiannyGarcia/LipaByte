<?php

declare(strict_types=1);

/**
 * Export marketplace listings to ragbot/knowledge/11-listings-snapshot.txt
 *
 * Usage:
 *   c:\xampp\php\php.exe tools/export_listings_snapshot.php
 *   c:\xampp\php\php.exe tools/export_listings_snapshot.php --live
 *
 * --live uses config/database.local.php (InfinityFree)
 * default uses config/database.xampp.php when available
 */

$root = dirname(__DIR__);
$useLive = in_array('--live', $argv ?? [], true);

$xampp = $root . '/config/database.xampp.php';
$local = $root . '/config/database.local.php';

if ($useLive) {
    if (!is_readable($local)) {
        fwrite(STDERR, "Missing config/database.local.php for --live export.\n");
        exit(1);
    }
    $config = require $local;
    $sourceLabel = 'InfinityFree (database.local.php)';
} elseif (is_readable($xampp)) {
    $config = require $xampp;
    $sourceLabel = 'XAMPP (database.xampp.php)';
} elseif (is_readable($local)) {
    $config = require $local;
    $sourceLabel = 'database.local.php';
} else {
    fwrite(STDERR, "No database config found.\n");
    exit(1);
}

$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
    $config['host'],
    (int) ($config['port'] ?? 3306),
    $config['dbname'],
    $config['charset'] ?? 'utf8mb4'
);

try {
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Throwable $e) {
    fwrite(STDERR, 'Database connection failed: ' . $e->getMessage() . "\n");
    exit(1);
}

$sql = 'SELECT l.listing_id, l.item_name, l.daily_rate, l.weekly_rate, l.location,
               l.availability_status, l.item_condition, c.category_name
        FROM listings l
        JOIN categories c ON c.category_id = l.category_id
        WHERE l.is_deleted = 0
        ORDER BY c.category_name ASC, l.daily_rate ASC, l.item_name ASC';

$rows = $pdo->query($sql)->fetchAll();
$generatedAt = date('Y-m-d H:i:s T');
$outPath = $root . '/ragbot/knowledge/11-listings-snapshot.txt';

$lines = [];
$lines[] = 'LipaByte Marketplace Listings Snapshot';
$lines[] = '======================================';
$lines[] = '';
$lines[] = 'Generated at: ' . $generatedAt;
$lines[] = 'Source: ' . $sourceLabel;
$lines[] = 'Total listings: ' . count($rows);
$lines[] = 'Currency: Philippine Peso (PHP)';
$lines[] = '';
$lines[] = 'How to read each line:';
$lines[] = 'ID | Item name | Category | Daily rate | Weekly rate | Location | Status | Condition';
$lines[] = '';
$lines[] = 'Assistant rules:';
$lines[] = '- Quote only listings shown below.';
$lines[] = '- If an item is missing, say it is not in this snapshot and suggest Marketplace.';
$lines[] = '- Do not invent prices.';
$lines[] = '';

$byCategory = [];
foreach ($rows as $row) {
    $cat = (string) $row['category_name'];
    $byCategory[$cat][] = $row;
}

foreach ($byCategory as $category => $items) {
    $lines[] = 'CATEGORY: ' . $category;
    $lines[] = str_repeat('-', 10 + strlen($category));
    foreach ($items as $row) {
        $daily = number_format((float) $row['daily_rate'], 2, '.', '');
        $weekly = $row['weekly_rate'] !== null && $row['weekly_rate'] !== ''
            ? number_format((float) $row['weekly_rate'], 2, '.', '')
            : 'n/a';
        $lines[] = sprintf(
            '#%d | %s | %s | PHP %s/day | weekly PHP %s | %s | %s | %s',
            (int) $row['listing_id'],
            trim((string) $row['item_name']),
            $category,
            $daily,
            $weekly,
            trim((string) ($row['location'] ?? 'Campus')),
            trim((string) $row['availability_status']),
            trim((string) ($row['item_condition'] ?? 'n/a'))
        );
    }
    $lines[] = '';
}

$contents = implode("\n", $lines) . "\n";
if (file_put_contents($outPath, $contents) === false) {
    fwrite(STDERR, "Could not write $outPath\n");
    exit(1);
}

fwrite(STDOUT, "Wrote " . count($rows) . " listings to:\n$outPath\n");
exit(0);
