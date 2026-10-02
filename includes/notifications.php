<?php

declare(strict_types=1);

function notifications_table_exists(): bool
{
    static $exists = null;
    if ($exists !== null) {
        return $exists;
    }
    try {
        db()->query('SELECT 1 FROM notifications LIMIT 1');
        $exists = true;
    } catch (Throwable) {
        $exists = false;
    }
    return $exists;
}

function create_notification(int $userId, string $type, string $title, string $message, ?string $linkUrl = null): void
{
    if (!notifications_table_exists() || $userId <= 0) {
        return;
    }

    db()->prepare(
        'INSERT INTO notifications (user_id, type, title, message, link_url) VALUES (?, ?, ?, ?, ?)'
    )->execute([$userId, $type, $title, $message, $linkUrl]);
}

function get_user_notifications(int $userId, int $limit = 30): array
{
    if (!notifications_table_exists()) {
        return [];
    }

    $stmt = db()->prepare(
        'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ' . max(1, min(100, $limit))
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function count_unread_notifications(int $userId): int
{
    if (!notifications_table_exists()) {
        return 0;
    }

    $stmt = db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

function mark_notification_read(int $notificationId, int $userId): bool
{
    if (!notifications_table_exists()) {
        return false;
    }

    $stmt = db()->prepare(
        'UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?'
    );
    $stmt->execute([$notificationId, $userId]);
    return $stmt->rowCount() > 0;
}

function mark_all_notifications_read(int $userId): void
{
    if (!notifications_table_exists()) {
        return;
    }

    db()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0')->execute([$userId]);
}

function notify_rental_event(string $event, array $request, ?array $actor = null): void
{
    $itemName = $request['item_name'] ?? 'your listing';
    $listingId = (int) ($request['listing_id'] ?? 0);
    $renterId = (int) ($request['renter_id'] ?? 0);
    $lenderId = (int) ($request['lender_id'] ?? 0);
    $dashboardLending = 'dashboard.php?tab=lending';
    $dashboardRenting = 'dashboard.php?tab=renting';

    match ($event) {
        'request_new' => create_notification(
            $lenderId,
            'rental_request',
            'New rental request',
            ($actor ? user_display_name($actor) : 'A student') . ' wants to rent ' . $itemName . '.',
            $dashboardLending
        ),
        'request_approved' => create_notification(
            $renterId,
            'rental_approved',
            'Rental approved',
            'Your request for ' . $itemName . ' was approved. Coordinate pickup with the owner.',
            $dashboardRenting
        ),
        'request_declined' => create_notification(
            $renterId,
            'rental_declined',
            'Rental declined',
            'Your request for ' . $itemName . ' was declined. Browse similar gear on the marketplace.',
            'home.php'
        ),
        'request_cancelled' => create_notification(
            $lenderId,
            'rental_cancelled',
            'Request cancelled',
            'A renter cancelled their pending request for ' . $itemName . '.',
            $dashboardLending
        ),
        'request_completed' => create_notification(
            $renterId,
            'rental_completed',
            'Rental completed',
            'Your rental of ' . $itemName . ' is marked complete. Leave a review to help the community.',
            $dashboardRenting
        ),
        default => null,
    };
}
