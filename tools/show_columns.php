<?php
require_once __DIR__ . '/../includes/bootstrap.php';
foreach (['rental_requests', 'listings'] as $t) {
    $cols = db()->query('SHOW COLUMNS FROM ' . $t)->fetchAll(PDO::FETCH_COLUMN);
    echo $t . ': ' . implode(', ', $cols) . "\n";
}
