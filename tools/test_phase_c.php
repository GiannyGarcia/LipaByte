<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

echo "Phase C smoke test\n";

$checks = [
    'create_notification',
    'count_unread_notifications',
    'request_password_reset',
    'admin_get_marketplace_stats',
    'admin_get_all_listings',
    'notify_rental_event',
];

foreach ($checks as $fn) {
    echo function_exists($fn) ? "OK: $fn\n" : "MISSING: $fn\n";
}

$stats = admin_get_marketplace_stats();
echo "Listings: {$stats['listings']}, Pending: {$stats['pending_requests']}\n";

create_notification(2, 'test', 'Test', 'Phase C notification test', 'home.php');
$count = count_unread_notifications(2);
echo $count > 0 ? "Notification create OK\n" : "Notification create check\n";

echo "Phase C smoke test done.\n";
