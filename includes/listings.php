<?php

declare(strict_types=1);

function get_active_categories(): array
{
    return db()->query(
        'SELECT * FROM categories WHERE is_active = 1 ORDER BY category_name'
    )->fetchAll();
}

function listing_placeholder_image(int $categoryId): string
{
    $map = [
        1 => 'iot.svg',
        2 => 'phone.svg',
        3 => 'laptop.svg',
        4 => 'gpu.svg',
        5 => 'vr.svg',
        6 => 'camera.svg',
        7 => 'audio.svg',
    ];

    $file = $map[$categoryId] ?? 'default.svg';
    return asset('img/listings/' . $file);
}

function is_listing_placeholder_image(string $path): bool
{
    return str_contains($path, 'assets/img/listings/');
}

function listing_image_url(array $listing): string
{
    $listingId = (int) $listing['listing_id'];
    $categoryId = (int) $listing['category_id'];

    if (!empty($listing['primary_image']) && is_string($listing['primary_image'])) {
        $path = $listing['primary_image'];
        if (!is_listing_placeholder_image($path)) {
            return public_image_url($path);
        }
    }

    $path = fetch_best_listing_image_path($listingId);
    if ($path !== null) {
        return public_image_url($path);
    }

    return listing_placeholder_image($categoryId);
}

function fetch_best_listing_image_path(int $listingId): ?string
{
    $stmt = db()->prepare(
        "SELECT image_url FROM listing_images
         WHERE listing_id = ?
         ORDER BY
           (image_url NOT LIKE 'assets/img/listings/%') DESC,
           is_primary DESC,
           display_order ASC,
           image_id ASC
         LIMIT 1"
    );
    $stmt->execute([$listingId]);
    $path = $stmt->fetchColumn();

    return is_string($path) && $path !== '' ? $path : null;
}

function ensure_listing_has_primary_photo(int $listingId): void
{
    $stmt = db()->prepare(
        "SELECT image_id FROM listing_images
         WHERE listing_id = ? AND image_url NOT LIKE 'assets/img/listings/%'
         ORDER BY is_primary DESC, display_order ASC, image_id ASC
         LIMIT 1"
    );
    $stmt->execute([$listingId]);
    $imageId = (int) $stmt->fetchColumn();
    if ($imageId <= 0) {
        return;
    }

    db()->prepare('UPDATE listing_images SET is_primary = 0 WHERE listing_id = ?')->execute([$listingId]);
    db()->prepare('UPDATE listing_images SET is_primary = 1 WHERE image_id = ?')->execute([$imageId]);
}

function listings_filter_sql(array $filters, array &$params): string
{
    $sql = '';

    if (!empty($filters['q'])) {
        $sql .= ' AND (l.item_name LIKE ? OR l.specifications LIKE ? OR l.location LIKE ? OR c.category_name LIKE ?)';
        $term = '%' . $filters['q'] . '%';
        $params = array_merge($params, [$term, $term, $term, $term]);
    }

    if (!empty($filters['category_id'])) {
        $sql .= ' AND l.category_id = ?';
        $params[] = (int) $filters['category_id'];
    }

    if (!empty($filters['status']) && in_array($filters['status'], ['Available', 'Pending', 'Rented'], true)) {
        $sql .= ' AND l.availability_status = ?';
        $params[] = $filters['status'];
    }

    if (isset($filters['min_price']) && $filters['min_price'] !== '') {
        $sql .= ' AND l.daily_rate >= ?';
        $params[] = (float) $filters['min_price'];
    }

    if (isset($filters['max_price']) && $filters['max_price'] !== '') {
        $sql .= ' AND l.daily_rate <= ?';
        $params[] = (float) $filters['max_price'];
    }

    if (!empty($filters['location'])) {
        $sql .= ' AND l.location LIKE ?';
        $params[] = '%' . $filters['location'] . '%';
    }

    return $sql;
}

function listings_sort_sql(array $filters): string
{
    $sort = $filters['sort'] ?? 'newest';
    return match ($sort) {
        'price_low' => ' ORDER BY l.daily_rate ASC',
        'price_high' => ' ORDER BY l.daily_rate DESC',
        'name' => ' ORDER BY l.item_name ASC',
        default => ' ORDER BY l.created_at DESC',
    };
}

function count_listings(array $filters = []): int
{
    $params = [];
    $sql = 'SELECT COUNT(*)
            FROM listings l
            JOIN categories c ON c.category_id = l.category_id
            WHERE l.is_deleted = 0' . listings_filter_sql($filters, $params);

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

function search_listings(array $filters = [], ?int $limit = null, ?int $offset = null): array
{
    $params = [];
    $sql = 'SELECT l.*, c.category_name, c.icon_label,
                   u.first_name, u.last_name, u.avg_rating AS lender_rating,
                   (SELECT image_url FROM listing_images li
                    WHERE li.listing_id = l.listing_id
                    ORDER BY (li.image_url NOT LIKE \'assets/img/listings/%\') DESC,
                             li.is_primary DESC,
                             li.display_order ASC,
                             li.image_id ASC
                    LIMIT 1) AS primary_image
            FROM listings l
            JOIN categories c ON c.category_id = l.category_id
            JOIN users u ON u.user_id = l.lender_id
            WHERE l.is_deleted = 0' . listings_filter_sql($filters, $params);
    $sql .= listings_sort_sql($filters);

    if ($limit !== null) {
        $sql .= ' LIMIT ' . max(1, (int) $limit) . ' OFFSET ' . max(0, (int) ($offset ?? 0));
    }

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function calculate_rental_days(string $startDate, string $endDate): int
{
    $start = strtotime($startDate);
    $end = strtotime($endDate);
    if (!$start || !$end || $end < $start) {
        return 0;
    }
    return max(1, (int) ceil(($end - $start) / 86400) + 1);
}

function calculate_rental_total(float $dailyRate, string $startDate, string $endDate): float
{
    $days = calculate_rental_days($startDate, $endDate);
    return $days > 0 ? $days * $dailyRate : 0;
}

function get_listing_by_id(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT l.*, c.category_name, c.icon_label, c.description AS category_description,
                u.user_id AS lender_user_id, u.first_name, u.last_name, u.email AS lender_email,
                u.avg_rating AS lender_rating, u.created_at AS lender_since
         FROM listings l
         JOIN categories c ON c.category_id = l.category_id
         JOIN users u ON u.user_id = l.lender_id
         WHERE l.listing_id = ? AND l.is_deleted = 0'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function get_listing_images(int $listingId): array
{
    $stmt = db()->prepare(
        'SELECT * FROM listing_images WHERE listing_id = ? ORDER BY display_order, image_id'
    );
    $stmt->execute([$listingId]);
    return $stmt->fetchAll();
}

function validate_listing_data(array $data): array
{
    $errors = [];
    $name = trim($data['item_name'] ?? '');
    $specs = trim($data['specifications'] ?? '');
    $location = trim($data['location'] ?? '');
    $categoryId = (int) ($data['category_id'] ?? 0);
    $dailyRate = (float) ($data['daily_rate'] ?? 0);
    $weeklyRate = (float) ($data['weekly_rate'] ?? 0);
    $condition = trim($data['item_condition'] ?? '');

    if ($name === '' || strlen($name) < 3) {
        $errors[] = 'Item name must be at least 3 characters.';
    }
    if ($specs === '' || strlen($specs) < 10) {
        $errors[] = 'Please add a detailed description (at least 10 characters).';
    }
    if ($location === '') {
        $errors[] = 'Location is required (campus or city).';
    }
    if ($categoryId <= 0) {
        $errors[] = 'Please select a category.';
    }
    if ($dailyRate <= 0) {
        $errors[] = 'Daily rate must be greater than zero.';
    }
    if ($weeklyRate <= 0) {
        $weeklyRate = $dailyRate * 6;
    }
    if ($condition === '') {
        $errors[] = 'Please specify item condition.';
    }

    if ($errors) {
        return ['success' => false, 'errors' => $errors];
    }

    return [
        'success' => true,
        'data' => [
            'item_name' => $name,
            'specifications' => $specs,
            'location' => $location,
            'category_id' => $categoryId,
            'daily_rate' => $dailyRate,
            'weekly_rate' => $weeklyRate,
            'item_condition' => $condition,
        ],
    ];
}

function create_listing(int $userId, array $data): array
{
    $validated = validate_listing_data($data);
    if (!$validated['success']) {
        return $validated;
    }
    $d = $validated['data'];

    $stmt = db()->prepare(
        'INSERT INTO listings (lender_id, category_id, item_name, specifications, daily_rate, weekly_rate, item_condition, location, availability_status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, \'Available\')'
    );
    $stmt->execute([
        $userId,
        $d['category_id'],
        $d['item_name'],
        $d['specifications'],
        $d['daily_rate'],
        $d['weekly_rate'],
        $d['item_condition'],
        $d['location'],
    ]);
    $listingId = (int) db()->lastInsertId();

    $img = db()->prepare(
        'INSERT INTO listing_images (listing_id, image_url, display_order, is_primary) VALUES (?, ?, 1, 1)'
    );
    $img->execute([$listingId, 'assets/img/listings/' . listing_category_slug($d['category_id']) . '.svg']);

    return ['success' => true, 'listing_id' => $listingId];
}

function update_listing(int $listingId, int $userId, array $data): array
{
    $listing = get_listing_by_id($listingId);
    if (!$listing || (int) $listing['lender_id'] !== $userId) {
        return ['success' => false, 'errors' => ['Listing not found or access denied.']];
    }

    $validated = validate_listing_data($data);
    if (!$validated['success']) {
        return $validated;
    }
    $d = $validated['data'];

    $stmt = db()->prepare(
        'UPDATE listings SET category_id = ?, item_name = ?, specifications = ?, daily_rate = ?, weekly_rate = ?,
         item_condition = ?, location = ? WHERE listing_id = ? AND lender_id = ?'
    );
    $stmt->execute([
        $d['category_id'],
        $d['item_name'],
        $d['specifications'],
        $d['daily_rate'],
        $d['weekly_rate'],
        $d['item_condition'],
        $d['location'],
        $listingId,
        $userId,
    ]);

    return ['success' => true, 'message' => 'Listing updated successfully.'];
}

function delete_listing(int $listingId, int $userId): array
{
    $listing = get_listing_by_id($listingId);
    if (!$listing || (int) $listing['lender_id'] !== $userId) {
        return ['success' => false, 'errors' => ['Listing not found or access denied.']];
    }

    $active = db()->prepare(
        "SELECT COUNT(*) FROM rental_requests WHERE listing_id = ? AND request_status IN ('Pending', 'Approved')"
    );
    $active->execute([$listingId]);
    if ((int) $active->fetchColumn() > 0) {
        return ['success' => false, 'errors' => ['Cannot delete while there are active or pending rentals.']];
    }

    $stmt = db()->prepare(
        'UPDATE listings SET is_deleted = 1, availability_status = \'Available\' WHERE listing_id = ? AND lender_id = ?'
    );
    $stmt->execute([$listingId, $userId]);

    return ['success' => true, 'message' => 'Listing removed from the marketplace.'];
}

function get_rental_request_by_id(int $requestId): ?array
{
    $stmt = db()->prepare(
        'SELECT rr.*, l.lender_id, l.item_name, l.listing_id
         FROM rental_requests rr
         JOIN listings l ON l.listing_id = rr.listing_id
         WHERE rr.request_id = ?'
    );
    $stmt->execute([$requestId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function sync_listing_availability(int $listingId): void
{
    $pdo = db();

    $approved = $pdo->prepare(
        "SELECT COUNT(*) FROM rental_requests WHERE listing_id = ? AND request_status = 'Approved'"
    );
    $approved->execute([$listingId]);
    if ((int) $approved->fetchColumn() > 0) {
        $pdo->prepare("UPDATE listings SET availability_status = 'Rented' WHERE listing_id = ?")
            ->execute([$listingId]);
        return;
    }

    $pending = $pdo->prepare(
        "SELECT COUNT(*) FROM rental_requests WHERE listing_id = ? AND request_status = 'Pending'"
    );
    $pending->execute([$listingId]);
    if ((int) $pending->fetchColumn() > 0) {
        $pdo->prepare("UPDATE listings SET availability_status = 'Pending' WHERE listing_id = ?")
            ->execute([$listingId]);
        return;
    }

    $pdo->prepare("UPDATE listings SET availability_status = 'Available' WHERE listing_id = ?")
        ->execute([$listingId]);
}

function approve_rental_request(int $requestId, int $lenderId): array
{
    $request = get_rental_request_by_id($requestId);
    if (!$request || (int) $request['lender_id'] !== $lenderId) {
        return ['success' => false, 'errors' => ['Request not found or access denied.']];
    }
    if ($request['request_status'] !== 'Pending') {
        return ['success' => false, 'errors' => ['Only pending requests can be approved.']];
    }

    $pdo = db();
    $pdo->prepare(
        "UPDATE rental_requests SET request_status = 'Approved' WHERE request_id = ?"
    )->execute([$requestId]);

    $pdo->prepare(
        "UPDATE rental_requests SET request_status = 'Declined'
         WHERE listing_id = ? AND request_id != ? AND request_status = 'Pending'"
    )->execute([(int) $request['listing_id'], $requestId]);

    sync_listing_availability((int) $request['listing_id']);

    notify_rental_event('request_approved', $request);

    open_rental_chat($requestId);

    return ['success' => true, 'message' => 'Rental approved. Chat is open — coordinate pickup in Messages.'];
}

function decline_rental_request(int $requestId, int $lenderId): array
{
    $request = get_rental_request_by_id($requestId);
    if (!$request || (int) $request['lender_id'] !== $lenderId) {
        return ['success' => false, 'errors' => ['Request not found or access denied.']];
    }
    if ($request['request_status'] !== 'Pending') {
        return ['success' => false, 'errors' => ['Only pending requests can be declined.']];
    }

    db()->prepare(
        "UPDATE rental_requests SET request_status = 'Declined' WHERE request_id = ?"
    )->execute([$requestId]);

    sync_listing_availability((int) $request['listing_id']);

    notify_rental_event('request_declined', $request);

    return ['success' => true, 'message' => 'Rental request declined.'];
}

function cancel_rental_request(int $requestId, int $renterId): array
{
    $request = get_rental_request_by_id($requestId);
    if (!$request || (int) $request['renter_id'] !== $renterId) {
        return ['success' => false, 'errors' => ['Request not found or access denied.']];
    }
    if ($request['request_status'] !== 'Pending') {
        return ['success' => false, 'errors' => ['Only pending requests can be cancelled.']];
    }

    db()->prepare(
        "UPDATE rental_requests SET request_status = 'Cancelled' WHERE request_id = ?"
    )->execute([$requestId]);

    sync_listing_availability((int) $request['listing_id']);

    notify_rental_event('request_cancelled', $request);

    return ['success' => true, 'message' => 'Your rental request was cancelled.'];
}

function complete_rental_request(int $requestId, int $lenderId): array
{
    $request = get_rental_request_by_id($requestId);
    if (!$request || (int) $request['lender_id'] !== $lenderId) {
        return ['success' => false, 'errors' => ['Request not found or access denied.']];
    }
    if ($request['request_status'] !== 'Approved') {
        return ['success' => false, 'errors' => ['Only approved rentals can be marked complete.']];
    }

    db()->prepare(
        "UPDATE rental_requests SET request_status = 'Completed' WHERE request_id = ?"
    )->execute([$requestId]);

    sync_listing_availability((int) $request['listing_id']);

    notify_rental_event('request_completed', $request);

    notify_rental_chat_completed($requestId);

    return ['success' => true, 'message' => 'Rental marked complete. Leave reviews in Messages or My Activity.'];
}

function listing_category_slug(int $categoryId): string
{
    return match ($categoryId) {
        1 => 'iot',
        2 => 'phone',
        3 => 'laptop',
        4 => 'gpu',
        5 => 'vr',
        6 => 'camera',
        7 => 'audio',
        default => 'default',
    };
}

function get_user_rentals(int $userId): array
{
    $stmt = db()->prepare(
        'SELECT rr.*, l.item_name, l.daily_rate, l.location, l.availability_status,
                u.first_name AS lender_first, u.last_name AS lender_last
         FROM rental_requests rr
         JOIN listings l ON l.listing_id = rr.listing_id
         JOIN users u ON u.user_id = l.lender_id
         WHERE rr.renter_id = ?
         ORDER BY rr.created_at DESC'
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function get_user_listings(int $userId): array
{
    $stmt = db()->prepare(
        'SELECT l.*, c.category_name,
                (SELECT COUNT(*) FROM rental_requests rr WHERE rr.listing_id = l.listing_id) AS request_count
         FROM listings l
         JOIN categories c ON c.category_id = l.category_id
         WHERE l.lender_id = ? AND l.is_deleted = 0
         ORDER BY l.created_at DESC'
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function get_incoming_rental_requests(int $userId): array
{
    $stmt = db()->prepare(
        'SELECT rr.*, l.item_name, l.daily_rate,
                u.first_name AS renter_first, u.last_name AS renter_last
         FROM rental_requests rr
         JOIN listings l ON l.listing_id = rr.listing_id
         JOIN users u ON u.user_id = rr.renter_id
         WHERE l.lender_id = ?
         ORDER BY rr.created_at DESC'
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function submit_rental_request(int $renterId, int $listingId, string $startDate, string $endDate): array
{
    $listing = get_listing_by_id($listingId);
    if (!$listing) {
        return ['success' => false, 'errors' => ['Listing not found.']];
    }
    if ((int) $listing['lender_user_id'] === $renterId) {
        return ['success' => false, 'errors' => ['You cannot rent your own listing.']];
    }
    if ($listing['availability_status'] !== 'Available') {
        return ['success' => false, 'errors' => ['This item is not available for rent right now.']];
    }

    $existing = db()->prepare(
        "SELECT request_id FROM rental_requests WHERE listing_id = ? AND renter_id = ? AND request_status = 'Pending'"
    );
    $existing->execute([$listingId, $renterId]);
    if ($existing->fetch()) {
        return ['success' => false, 'errors' => ['You already have a pending request for this item.']];
    }

    $days = calculate_rental_days($startDate, $endDate);
    if ($days <= 0) {
        return ['success' => false, 'errors' => ['Please select valid rental dates.']];
    }
    $total = $days * (float) $listing['daily_rate'];

    $stmt = db()->prepare(
        'INSERT INTO rental_requests (listing_id, renter_id, start_date, end_date, request_status, total_amount)
         VALUES (?, ?, ?, ?, \'Pending\', ?)'
    );
    $stmt->execute([$listingId, $renterId, $startDate, $endDate, $total]);

    sync_listing_availability($listingId);

    $renter = db()->prepare('SELECT first_name, last_name FROM users WHERE user_id = ?');
    $renter->execute([$renterId]);
    notify_rental_event('request_new', [
        'item_name' => $listing['item_name'],
        'listing_id' => $listingId,
        'renter_id' => $renterId,
        'lender_id' => (int) $listing['lender_user_id'],
    ], $renter->fetch() ?: null);

    return ['success' => true, 'message' => 'Rental request sent! The owner will review your request.'];
}
