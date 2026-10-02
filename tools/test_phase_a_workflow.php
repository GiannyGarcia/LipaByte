<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$pdo = db();

// Find a lender with an available listing and a different renter user
$listing = $pdo->query(
    "SELECT l.listing_id, l.lender_id FROM listings l
     WHERE l.is_deleted = 0 AND l.availability_status = 'Available'
     LIMIT 1"
)->fetch(PDO::FETCH_ASSOC);

if (!$listing) {
    echo "SKIP: no available listing\n";
    exit(0);
}

$renter = $pdo->prepare(
    'SELECT user_id FROM users WHERE user_id != ? AND role = \'user\' LIMIT 1'
);
$renter->execute([(int) $listing['lender_id']]);
$renterId = (int) $renter->fetchColumn();

if (!$renterId) {
    echo "SKIP: no renter user\n";
    exit(0);
}

$listingId = (int) $listing['listing_id'];
$lenderId = (int) $listing['lender_id'];
$start = date('Y-m-d', strtotime('+3 days'));
$end = date('Y-m-d', strtotime('+5 days'));

echo "Listing $listingId, lender $lenderId, renter $renterId\n";

// Clean any existing pending between these parties on this listing
$pdo->prepare(
    "DELETE FROM rental_requests WHERE listing_id = ? AND renter_id = ? AND request_status = 'Pending'"
)->execute([$listingId, $renterId]);

$r = submit_rental_request($renterId, $listingId, $start, $end);
if (!$r['success']) {
    echo 'FAIL submit: ' . implode(', ', $r['errors']) . "\n";
    exit(1);
}
echo "Submit OK\n";

$reqId = (int) $pdo->query(
    "SELECT request_id FROM rental_requests WHERE listing_id = $listingId AND renter_id = $renterId ORDER BY request_id DESC LIMIT 1"
)->fetchColumn();

$afterSubmit = get_listing_by_id($listingId);
if ($afterSubmit['availability_status'] !== 'Pending') {
    echo 'FAIL: expected Pending after submit, got ' . $afterSubmit['availability_status'] . "\n";
    exit(1);
}
echo "Status Pending OK\n";

$a = approve_rental_request($reqId, $lenderId);
if (!$a['success']) {
    echo 'FAIL approve: ' . implode(', ', $a['errors']) . "\n";
    exit(1);
}
$afterApprove = get_listing_by_id($listingId);
if ($afterApprove['availability_status'] !== 'Rented') {
    echo 'FAIL: expected Rented after approve, got ' . $afterApprove['availability_status'] . "\n";
    exit(1);
}
echo "Approve + Rented OK\n";

$c = complete_rental_request($reqId, $lenderId);
if (!$c['success']) {
    echo 'FAIL complete: ' . implode(', ', $c['errors']) . "\n";
    exit(1);
}
$afterComplete = get_listing_by_id($listingId);
if ($afterComplete['availability_status'] !== 'Available') {
    echo 'FAIL: expected Available after complete, got ' . $afterComplete['availability_status'] . "\n";
    exit(1);
}
echo "Complete + Available OK\n";

// Test cancel flow on fresh pending
$r2 = submit_rental_request($renterId, $listingId, $start, $end);
$reqId2 = (int) $pdo->query(
    "SELECT request_id FROM rental_requests WHERE listing_id = $listingId AND renter_id = $renterId ORDER BY request_id DESC LIMIT 1"
)->fetchColumn();
$cancel = cancel_rental_request($reqId2, $renterId);
if (!$cancel['success']) {
    echo 'FAIL cancel: ' . implode(', ', $cancel['errors']) . "\n";
    exit(1);
}
$afterCancel = get_listing_by_id($listingId);
if ($afterCancel['availability_status'] !== 'Available') {
    echo 'FAIL: expected Available after cancel, got ' . $afterCancel['availability_status'] . "\n";
    exit(1);
}
echo "Cancel OK\n";

echo "Full workflow test passed.\n";
