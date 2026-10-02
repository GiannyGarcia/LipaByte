<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

echo "Phase B smoke test\n";

$checks = [
    'save_listing_photos',
    'count_listings',
    'calculate_rental_total',
    'submit_review',
    'render_pagination',
    'get_lender_public_profile',
    'get_pending_reviews_for_user',
];

foreach ($checks as $fn) {
    echo function_exists($fn) ? "OK: $fn\n" : "MISSING: $fn\n";
}

$total = count_listings([]);
echo "Total listings: $total\n";

$meta = pagination_meta($total, 1, 24);
echo "Pagination pages: {$meta['total_pages']}\n";

$days = calculate_rental_days('2026-07-01', '2026-07-05');
$totalRent = calculate_rental_total(50.0, '2026-07-01', '2026-07-05');
echo "Rental calc: {$days} days, total {$totalRent}\n";
echo ($days === 5 && $totalRent === 250.0) ? "Rental calc OK\n" : "Rental calc FAIL\n";

$profile = get_lender_public_profile(2);
echo $profile ? "Seller profile OK\n" : "Seller profile FAIL\n";

$html = render_pagination(['total_pages' => 3, 'page' => 2], ['q' => 'test']);
echo str_contains($html, 'pagination') ? "Pagination HTML OK\n" : "Pagination HTML FAIL\n";

echo "Phase B smoke test done.\n";
