<?php

declare(strict_types=1);

function recalculate_user_rating(int $userId): void
{
    $stmt = db()->prepare('SELECT ROUND(AVG(rating), 2) FROM reviews WHERE reviewee_id = ?');
    $stmt->execute([$userId]);
    $avg = $stmt->fetchColumn();
    db()->prepare('UPDATE users SET avg_rating = ? WHERE user_id = ?')->execute([(float) ($avg ?: 0), $userId]);
}

function get_user_reviews_received(int $userId, int $limit = 10): array
{
    $stmt = db()->prepare(
        'SELECT r.*, u.first_name, u.last_name
         FROM reviews r
         JOIN users u ON u.user_id = r.reviewer_id
         WHERE r.reviewee_id = ?
         ORDER BY r.created_at DESC
         LIMIT ' . max(1, min(50, $limit))
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function get_lender_public_profile(int $userId): ?array
{
    $stmt = db()->prepare(
        'SELECT user_id, first_name, last_name, avg_rating, created_at
         FROM users WHERE user_id = ? AND is_active = 1'
    );
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if (!$row) {
        return null;
    }

    $listings = db()->prepare(
        'SELECT COUNT(*) FROM listings WHERE lender_id = ? AND is_deleted = 0'
    );
    $listings->execute([$userId]);
    $row['listing_count'] = (int) $listings->fetchColumn();

    $reviews = db()->prepare('SELECT COUNT(*) FROM reviews WHERE reviewee_id = ?');
    $reviews->execute([$userId]);
    $row['review_count'] = (int) $reviews->fetchColumn();

    return $row;
}

function get_lender_other_listings(int $lenderId, int $excludeListingId = 0, int $limit = 4): array
{
    $stmt = db()->prepare(
        'SELECT l.*, c.category_name
         FROM listings l
         JOIN categories c ON c.category_id = l.category_id
         WHERE l.lender_id = ? AND l.is_deleted = 0 AND l.listing_id != ?
         ORDER BY l.created_at DESC
         LIMIT ' . max(1, min(12, $limit))
    );
    $stmt->execute([$lenderId, $excludeListingId]);
    return $stmt->fetchAll();
}

function user_has_reviewed_rental(int $requestId, int $userId): bool
{
    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM reviews WHERE rental_request_id = ? AND reviewer_id = ?'
    );
    $stmt->execute([$requestId, $userId]);
    return (int) $stmt->fetchColumn() > 0;
}

function get_reviewable_rental(int $requestId, int $userId): ?array
{
    $stmt = db()->prepare(
        'SELECT rr.*, l.item_name, l.lender_id,
                renter.first_name AS renter_first, renter.last_name AS renter_last,
                lender.first_name AS lender_first, lender.last_name AS lender_last
         FROM rental_requests rr
         JOIN listings l ON l.listing_id = rr.listing_id
         JOIN users renter ON renter.user_id = rr.renter_id
         JOIN users lender ON lender.user_id = l.lender_id
         WHERE rr.request_id = ? AND rr.request_status = \'Completed\''
    );
    $stmt->execute([$requestId]);
    $row = $stmt->fetch();
    if (!$row) {
        return null;
    }

    if ((int) $row['renter_id'] === $userId) {
        $row['reviewee_id'] = (int) $row['lender_id'];
        $row['reviewee_name'] = trim($row['lender_first'] . ' ' . $row['lender_last']);
        $row['reviewer_role'] = 'renter';
    } elseif ((int) $row['lender_id'] === $userId) {
        $row['reviewee_id'] = (int) $row['renter_id'];
        $row['reviewee_name'] = trim($row['renter_first'] . ' ' . $row['renter_last']);
        $row['reviewer_role'] = 'lender';
    } else {
        return null;
    }

    if (user_has_reviewed_rental($requestId, $userId)) {
        return null;
    }

    return $row;
}

function get_pending_reviews_for_user(int $userId): array
{
    $stmt = db()->prepare(
        'SELECT rr.request_id, rr.start_date, rr.end_date, l.item_name, l.lender_id, rr.renter_id,
                renter.first_name AS renter_first, renter.last_name AS renter_last,
                lender.first_name AS lender_first, lender.last_name AS lender_last
         FROM rental_requests rr
         JOIN listings l ON l.listing_id = rr.listing_id
         JOIN users renter ON renter.user_id = rr.renter_id
         JOIN users lender ON lender.user_id = l.lender_id
         WHERE rr.request_status = \'Completed\'
           AND (
             (rr.renter_id = ? AND NOT EXISTS (
                SELECT 1 FROM reviews r WHERE r.rental_request_id = rr.request_id AND r.reviewer_id = ?
             ))
             OR
             (l.lender_id = ? AND NOT EXISTS (
                SELECT 1 FROM reviews r WHERE r.rental_request_id = rr.request_id AND r.reviewer_id = ?
             ))
           )
         ORDER BY rr.created_at DESC'
    );
    $stmt->execute([$userId, $userId, $userId, $userId]);
    $rows = $stmt->fetchAll();

    foreach ($rows as &$row) {
        if ((int) $row['renter_id'] === $userId) {
            $row['reviewee_id'] = (int) $row['lender_id'];
            $row['reviewee_name'] = trim($row['lender_first'] . ' ' . $row['lender_last']);
        } else {
            $row['reviewee_id'] = (int) $row['renter_id'];
            $row['reviewee_name'] = trim($row['renter_first'] . ' ' . $row['renter_last']);
        }
    }
    unset($row);

    return $rows;
}

function submit_review(int $requestId, int $reviewerId, int $rating, string $comment): array
{
    $rental = get_reviewable_rental($requestId, $reviewerId);
    if (!$rental) {
        return ['success' => false, 'errors' => ['You cannot review this rental.']];
    }

    $comment = trim($comment);
    if ($rating < 1 || $rating > 5) {
        return ['success' => false, 'errors' => ['Please select a rating from 1 to 5 stars.']];
    }
    if ($comment === '' || strlen($comment) < 5) {
        return ['success' => false, 'errors' => ['Please write a short review (at least 5 characters).']];
    }

    $stmt = db()->prepare(
        'INSERT INTO reviews (rental_request_id, reviewer_id, reviewee_id, rating, comment)
         VALUES (?, ?, ?, ?, ?)'
    );
    try {
        $stmt->execute([
            $requestId,
            $reviewerId,
            (int) $rental['reviewee_id'],
            $rating,
            $comment,
        ]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            return ['success' => false, 'errors' => ['You already submitted a review for this borrow.']];
        }
        throw $e;
    }

    recalculate_user_rating((int) $rental['reviewee_id']);

    return ['success' => true, 'message' => 'Thank you! Your review was posted.'];
}

function render_star_rating(float $rating, bool $showValue = true): string
{
    $filled = max(0, min(5, (int) round($rating)));
    $stars = str_repeat('★', $filled) . str_repeat('☆', 5 - $filled);
    $html = '<span class="star-rating" aria-label="' . e(number_format($rating, 1)) . ' out of 5">' . $stars . '</span>';
    if ($showValue && $rating > 0) {
        $html .= ' <span class="star-rating-value">' . e(number_format($rating, 1)) . '</span>';
    }
    return $html;
}

function render_star_input(string $name = 'rating', int $selected = 0): string
{
    $html = '<div class="star-input" role="radiogroup" aria-label="Rating">';
    for ($i = 5; $i >= 1; $i--) {
        $checked = $selected === $i ? ' checked' : '';
        $html .= '<label class="star-input-label"><input type="radio" name="' . e($name) . '" value="' . $i . '"' . $checked . ' required><span>★</span></label>';
    }
    $html .= '</div>';
    return $html;
}
