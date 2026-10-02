<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

try {
    db()->query('SELECT 1');
    echo "DB OK\n";

    $functions = [
        'validate_listing_data',
        'update_listing',
        'delete_listing',
        'approve_rental_request',
        'decline_rental_request',
        'cancel_rental_request',
        'complete_rental_request',
        'sync_listing_availability',
    ];
    foreach ($functions as $fn) {
        echo function_exists($fn) ? "OK: $fn\n" : "MISSING: $fn\n";
    }

    $invalid = approve_rental_request(0, 0);
    echo 'Invalid approve: ' . ($invalid['success'] ? 'FAIL' : 'OK') . "\n";

    $invalidListing = update_listing(0, 0, ['item_name' => 'x']);
    echo 'Invalid update: ' . ($invalidListing['success'] ? 'FAIL' : 'OK') . "\n";
} catch (Throwable $e) {
    echo 'ERR: ' . $e->getMessage() . "\n";
    exit(1);
}

echo "Phase A smoke test passed.\n";
