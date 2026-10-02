<?php

declare(strict_types=1);

function admin_get_marketplace_stats(): array
{
    $pdo = db();

    return [
        'users' => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
        'students' => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn(),
        'listings' => (int) $pdo->query('SELECT COUNT(*) FROM listings WHERE is_deleted = 0')->fetchColumn(),
        'requests' => (int) $pdo->query('SELECT COUNT(*) FROM rental_requests')->fetchColumn(),
        'pending_requests' => (int) $pdo->query("SELECT COUNT(*) FROM rental_requests WHERE request_status = 'Pending'")->fetchColumn(),
        'active_rentals' => (int) $pdo->query("SELECT COUNT(*) FROM rental_requests WHERE request_status = 'Approved'")->fetchColumn(),
        'campuses' => (int) $pdo->query('SELECT COUNT(*) FROM campuses')->fetchColumn(),
    ];
}

function admin_get_top_categories(int $limit = 5): array
{
    $stmt = db()->query(
        'SELECT c.category_name, COUNT(l.listing_id) AS listing_count
         FROM categories c
         LEFT JOIN listings l ON l.category_id = c.category_id AND l.is_deleted = 0
         GROUP BY c.category_id, c.category_name
         ORDER BY listing_count DESC
         LIMIT ' . max(1, min(10, $limit))
    );
    return $stmt->fetchAll();
}

function admin_get_all_listings(int $limit = 100): array
{
    $stmt = db()->query(
        'SELECT l.*, c.category_name, u.first_name, u.last_name, u.email
         FROM listings l
         JOIN categories c ON c.category_id = l.category_id
         JOIN users u ON u.user_id = l.lender_id
         ORDER BY l.created_at DESC
         LIMIT ' . max(1, min(500, $limit))
    );
    return $stmt->fetchAll();
}

function admin_remove_listing(int $listingId): array
{
    $stmt = db()->prepare('SELECT listing_id FROM listings WHERE listing_id = ? AND is_deleted = 0');
    $stmt->execute([$listingId]);
    if (!$stmt->fetch()) {
        return ['success' => false, 'errors' => ['Listing not found.']];
    }

    db()->prepare(
        'UPDATE listings SET is_deleted = 1, availability_status = \'Available\' WHERE listing_id = ?'
    )->execute([$listingId]);

    return ['success' => true, 'message' => 'Listing removed from marketplace.'];
}

function admin_get_all_rentals(int $limit = 100): array
{
    $stmt = db()->query(
        'SELECT rr.*, l.item_name, l.lender_id,
                renter.first_name AS renter_first, renter.last_name AS renter_last, renter.email AS renter_email,
                lender.first_name AS lender_first, lender.last_name AS lender_last
         FROM rental_requests rr
         JOIN listings l ON l.listing_id = rr.listing_id
         JOIN users renter ON renter.user_id = rr.renter_id
         JOIN users lender ON lender.user_id = l.lender_id
         ORDER BY rr.created_at DESC
         LIMIT ' . max(1, min(500, $limit))
    );
    return $stmt->fetchAll();
}
