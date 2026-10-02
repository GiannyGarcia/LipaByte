<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$pdo = db();
$listing = $pdo->query(
    "SELECT listing_id, lender_id, item_name FROM listings WHERE is_deleted = 0 AND availability_status = 'Available' LIMIT 1"
)->fetch(PDO::FETCH_ASSOC);

if (!$listing) {
    echo "SKIP: no listing\n";
    exit(0);
}

$listingId = (int) $listing['listing_id'];
$lenderId = (int) $listing['lender_id'];
$name = $listing['item_name'] . ' (edited)';

$u = update_listing($listingId, $lenderId, [
    'item_name' => $name,
    'specifications' => str_repeat('Test specs with enough length. ', 2),
    'location' => 'Lipa City, Batangas',
    'category_id' => 1,
    'daily_rate' => 100,
    'weekly_rate' => 500,
    'item_condition' => 'Good',
]);
echo $u['success'] ? "Update OK\n" : 'Update FAIL: ' . implode(', ', $u['errors']) . "\n";

// Restore name
update_listing($listingId, $lenderId, [
    'item_name' => $listing['item_name'],
    'specifications' => str_repeat('Test specs with enough length. ', 2),
    'location' => 'Lipa City, Batangas',
    'category_id' => 1,
    'daily_rate' => 100,
    'weekly_rate' => 500,
    'item_condition' => 'Good',
]);

// Decline test
$renter = $pdo->prepare('SELECT user_id FROM users WHERE user_id != ? LIMIT 1');
$renter->execute([$lenderId]);
$renterId = (int) $renter->fetchColumn();
$start = date('Y-m-d', strtotime('+7 days'));
$end = date('Y-m-d', strtotime('+9 days'));
submit_rental_request($renterId, $listingId, $start, $end);
$reqId = (int) $pdo->query(
    "SELECT request_id FROM rental_requests WHERE listing_id = $listingId ORDER BY request_id DESC LIMIT 1"
)->fetchColumn();
$d = decline_rental_request($reqId, $lenderId);
echo $d['success'] ? "Decline OK\n" : 'Decline FAIL: ' . implode(', ', $d['errors']) . "\n";

$status = get_listing_by_id($listingId)['availability_status'];
echo $status === 'Available' ? "After decline Available OK\n" : "After decline got $status\n";

echo "Listing management test passed.\n";
