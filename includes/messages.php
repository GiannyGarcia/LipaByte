<?php

declare(strict_types=1);

function rental_messages_table_exists(): bool
{
    static $exists = null;
    if ($exists !== null) {
        return $exists;
    }
    try {
        db()->query('SELECT 1 FROM rental_messages LIMIT 1');
        $exists = true;
    } catch (Throwable $e) {
        $exists = false;
    }
    return $exists;
}

function rental_chat_reads_table_exists(): bool
{
    static $exists = null;
    if ($exists !== null) {
        return $exists;
    }
    try {
        db()->query('SELECT 1 FROM rental_message_reads LIMIT 1');
        $exists = true;
    } catch (Throwable $e) {
        $exists = false;
    }
    return $exists;
}

function get_chat_rental_context(int $requestId): ?array
{
    $stmt = db()->prepare(
        'SELECT rr.*, l.item_name, l.location, l.lender_id, l.listing_id, l.daily_rate,
                renter.first_name AS renter_first, renter.last_name AS renter_last,
                lender.first_name AS lender_first, lender.last_name AS lender_last
         FROM rental_requests rr
         JOIN listings l ON l.listing_id = rr.listing_id
         JOIN users renter ON renter.user_id = rr.renter_id
         JOIN users lender ON lender.user_id = l.lender_id
         WHERE rr.request_id = ?'
    );
    $stmt->execute([$requestId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function user_can_access_chat(int $requestId, int $userId): bool
{
    $ctx = get_chat_rental_context($requestId);
    if (!$ctx || !rental_messages_table_exists()) {
        return false;
    }
    if (!in_array($ctx['request_status'], ['Approved', 'Completed'], true)) {
        return false;
    }
    return $userId === (int) $ctx['renter_id'] || $userId === (int) $ctx['lender_id'];
}

function format_transaction_summary_message(array $ctx): string
{
    $item = $ctx['item_name'] ?? 'Device';
    $dates = format_date($ctx['start_date']) . ' – ' . format_date($ctx['end_date']);
    $total = format_money((float) $ctx['total_amount']);
    $location = $ctx['location'] ?? 'Campus';
    $renter = trim(($ctx['renter_first'] ?? '') . ' ' . ($ctx['renter_last'] ?? ''));
    $lender = trim(($ctx['lender_first'] ?? '') . ' ' . ($ctx['lender_last'] ?? ''));

    return "Borrow approved — transaction details\n"
        . "Item: {$item}\n"
        . "Dates: {$dates}\n"
        . "Total: {$total}\n"
        . "Pickup: {$location}\n"
        . "Renter: {$renter}\n"
        . "Owner: {$lender}\n\n"
        . 'Use this chat to coordinate pickup, return, and any questions.';
}

function insert_system_chat_message(int $requestId, string $body): void
{
    if (!rental_messages_table_exists() || trim($body) === '') {
        return;
    }

    $exists = db()->prepare(
        'SELECT COUNT(*) FROM rental_messages WHERE request_id = ? AND is_system = 1 AND message_body = ?'
    );
    $exists->execute([$requestId, $body]);
    if ((int) $exists->fetchColumn() > 0) {
        return;
    }

    db()->prepare(
        'INSERT INTO rental_messages (request_id, sender_id, is_system, message_body) VALUES (?, NULL, 1, ?)'
    )->execute([$requestId, $body]);
}

function ensure_chat_summary_message(int $requestId): void
{
    if (!rental_messages_table_exists()) {
        return;
    }

    $ctx = get_chat_rental_context($requestId);
    if (!$ctx || !in_array($ctx['request_status'], ['Approved', 'Completed'], true)) {
        return;
    }

    $check = db()->prepare('SELECT COUNT(*) FROM rental_messages WHERE request_id = ?');
    $check->execute([$requestId]);
    if ((int) $check->fetchColumn() === 0) {
        insert_system_chat_message($requestId, format_transaction_summary_message($ctx));
    }
}

function open_rental_chat(int $requestId): void
{
    if (!rental_messages_table_exists()) {
        return;
    }

    $ctx = get_chat_rental_context($requestId);
    if (!$ctx || !in_array($ctx['request_status'], ['Approved', 'Completed'], true)) {
        return;
    }

    ensure_chat_summary_message($requestId);

    $chatUrl = 'messages.php?request=' . $requestId;
    $item = $ctx['item_name'] ?? 'your listing';

    create_notification(
        (int) $ctx['renter_id'],
        'chat_opened',
        'Borrow approved — chat opened',
        'Your request for ' . $item . ' was approved. Open the conversation to coordinate pickup.',
        $chatUrl
    );
    create_notification(
        (int) $ctx['lender_id'],
        'chat_opened',
        'Chat opened with renter',
        'You approved a borrow request for ' . $item . '. Coordinate pickup in Messages.',
        $chatUrl
    );
}

function notify_rental_chat_completed(int $requestId): void
{
    if (!rental_messages_table_exists()) {
        return;
    }

    $body = "Transaction marked complete.\n\nBoth of you can now leave a star rating and comment about this borrow.";
    insert_system_chat_message($requestId, $body);

    $ctx = get_chat_rental_context($requestId);
    if (!$ctx) {
        return;
    }

    $chatUrl = 'messages.php?request=' . $requestId;
    $item = $ctx['item_name'] ?? 'the device';

    create_notification(
        (int) $ctx['renter_id'],
        'rental_completed',
        'Borrow complete — leave a review',
        'Your borrow of ' . $item . ' is complete. Rate the owner in Messages or My Activity.',
        $chatUrl
    );
    create_notification(
        (int) $ctx['lender_id'],
        'rental_completed',
        'Borrow complete — leave a review',
        'The borrow of ' . $item . ' is complete. Rate the borrower in Messages or My Activity.',
        $chatUrl
    );
}

function get_user_chat_threads(int $userId): array
{
    if (!rental_messages_table_exists()) {
        return [];
    }

    $unreadSql = rental_chat_reads_table_exists()
        ? '(SELECT COUNT(*) FROM rental_messages rm
            WHERE rm.request_id = rr.request_id
              AND (rm.sender_id IS NULL OR rm.sender_id != ?)
              AND rm.message_id > COALESCE(
                (SELECT last_read_message_id FROM rental_message_reads rmr
                 WHERE rmr.user_id = ? AND rmr.request_id = rr.request_id), 0
              ))'
        : '0';

    $params = rental_chat_reads_table_exists()
        ? [$userId, $userId, $userId, $userId]
        : [$userId, $userId];

    $stmt = db()->prepare(
        'SELECT rr.request_id, rr.request_status, rr.start_date, rr.end_date, rr.total_amount,
                l.item_name, l.lender_id, rr.renter_id,
                renter.first_name AS renter_first, renter.last_name AS renter_last,
                lender.first_name AS lender_first, lender.last_name AS lender_last,
                (SELECT message_body FROM rental_messages rm
                 WHERE rm.request_id = rr.request_id ORDER BY rm.message_id DESC LIMIT 1) AS last_message,
                (SELECT created_at FROM rental_messages rm
                 WHERE rm.request_id = rr.request_id ORDER BY rm.message_id DESC LIMIT 1) AS last_message_at,
                ' . $unreadSql . ' AS unread_count
         FROM rental_requests rr
         JOIN listings l ON l.listing_id = rr.listing_id
         JOIN users renter ON renter.user_id = rr.renter_id
         JOIN users lender ON lender.user_id = l.lender_id
         WHERE rr.request_status IN (\'Approved\', \'Completed\')
           AND (rr.renter_id = ? OR l.lender_id = ?)
         ORDER BY COALESCE(last_message_at, rr.created_at) DESC'
    );
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    foreach ($rows as &$row) {
        if ((int) $row['renter_id'] === $userId) {
            $row['other_name'] = trim($row['lender_first'] . ' ' . $row['lender_last']);
            $row['my_role'] = 'renter';
        } else {
            $row['other_name'] = trim($row['renter_first'] . ' ' . $row['renter_last']);
            $row['my_role'] = 'lender';
        }
    }
    unset($row);

    return $rows;
}

function get_chat_messages(int $requestId, int $userId, int $afterId = 0): array
{
    if (!user_can_access_chat($requestId, $userId)) {
        return [];
    }

    ensure_chat_summary_message($requestId);

    $sql = 'SELECT rm.*, u.first_name, u.last_name
            FROM rental_messages rm
            LEFT JOIN users u ON u.user_id = rm.sender_id
            WHERE rm.request_id = ?';
    $params = [$requestId];
    if ($afterId > 0) {
        $sql .= ' AND rm.message_id > ?';
        $params[] = $afterId;
    }
    $sql .= ' ORDER BY rm.message_id ASC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function send_chat_message(int $requestId, int $userId, string $body): array
{
    if (!rental_messages_table_exists()) {
        return ['success' => false, 'errors' => ['Messaging is not available yet. Run sql/migrate_rental_chat.sql.']];
    }
    if (!user_can_access_chat($requestId, $userId)) {
        return ['success' => false, 'errors' => ['You cannot send messages in this conversation.']];
    }

    $body = trim($body);
    if ($body === '' || strlen($body) < 1) {
        return ['success' => false, 'errors' => ['Message cannot be empty.']];
    }
    if (strlen($body) > 2000) {
        return ['success' => false, 'errors' => ['Message is too long (max 2000 characters).']];
    }

    db()->prepare(
        'INSERT INTO rental_messages (request_id, sender_id, is_system, message_body) VALUES (?, ?, 0, ?)'
    )->execute([$requestId, $userId, $body]);

    $messageId = (int) db()->lastInsertId();
    mark_chat_read($requestId, $userId, $messageId);

    $ctx = get_chat_rental_context($requestId);
    if ($ctx) {
        $recipientId = $userId === (int) $ctx['renter_id']
            ? (int) $ctx['lender_id']
            : (int) $ctx['renter_id'];
        $sender = db()->prepare('SELECT first_name, last_name FROM users WHERE user_id = ?');
        $sender->execute([$userId]);
        $senderRow = $sender->fetch() ?: ['first_name' => 'Someone', 'last_name' => ''];
        create_notification(
            $recipientId,
            'chat_message',
            'New message',
            user_display_name($senderRow) . ': ' . str_preview($body, 80),
            'messages.php?request=' . $requestId
        );
    }

    return ['success' => true, 'message_id' => $messageId];
}

function mark_chat_read(int $requestId, int $userId, int $lastMessageId): void
{
    if (!rental_messages_table_exists() || !rental_chat_reads_table_exists() || $lastMessageId <= 0) {
        return;
    }

    try {
        db()->prepare(
            'INSERT INTO rental_message_reads (user_id, request_id, last_read_message_id)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE last_read_message_id = GREATEST(last_read_message_id, VALUES(last_read_message_id))'
        )->execute([$userId, $requestId, $lastMessageId]);
    } catch (Throwable $e) {
        /* ignore read-tracking errors */
    }
}

function count_unread_chat_messages(int $userId): int
{
    if (!rental_messages_table_exists()) {
        return 0;
    }

    try {
        $threads = get_user_chat_threads($userId);
        $total = 0;
        foreach ($threads as $thread) {
            $total += (int) ($thread['unread_count'] ?? 0);
        }
        return $total;
    } catch (Throwable $e) {
        return 0;
    }
}

function chat_message_sender_label(array $message): string
{
    if ((int) ($message['is_system'] ?? 0) === 1) {
        return 'LipaByte';
    }
    return trim(($message['first_name'] ?? '') . ' ' . ($message['last_name'] ?? '')) ?: 'User';
}

function format_chat_messages_json(array $messages): array
{
    return array_map(static function (array $m): array {
        return [
            'id' => (int) $m['message_id'],
            'body' => $m['message_body'],
            'is_system' => (int) $m['is_system'] === 1,
            'sender' => chat_message_sender_label($m),
            'sender_id' => $m['sender_id'] !== null ? (int) $m['sender_id'] : null,
            'time' => $m['created_at'],
            'time_label' => format_datetime($m['created_at']),
        ];
    }, $messages);
}

function handle_chat_api(): void
{
    header('Content-Type: application/json; charset=utf-8');

    try {
        if (!is_logged_in()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $userId = (int) current_user()['user_id'];
        $requestId = (int) ($_GET['request'] ?? $_POST['request_id'] ?? $_POST['request'] ?? 0);

        if ($requestId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid conversation.']);
            return;
        }

        if (!user_can_access_chat($requestId, $userId)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Access denied.']);
            return;
        }

        if (is_post()) {
            verify_csrf_api();
            $body = trim($_POST['body'] ?? '');
            $afterId = max(0, (int) ($_POST['after'] ?? 0));
            $result = send_chat_message($requestId, $userId, $body);
            if (!$result['success']) {
                http_response_code(422);
                echo json_encode(['success' => false, 'error' => $result['errors'][0] ?? 'Could not send message.']);
                return;
            }

            $messages = get_chat_messages($requestId, $userId, $afterId);
            echo json_encode(['success' => true, 'messages' => format_chat_messages_json($messages)]);
            return;
        }

        $afterId = max(0, (int) ($_GET['after'] ?? 0));
        $messages = get_chat_messages($requestId, $userId, $afterId);
        if ($messages) {
            $lastId = (int) end($messages)['message_id'];
            mark_chat_read($requestId, $userId, $lastId);
        }

        echo json_encode([
            'success' => true,
            'messages' => format_chat_messages_json($messages),
            'poll_ms' => (int) (app_config()['chat_poll_ms'] ?? 3000),
        ]);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Server error while handling chat.']);
    }
}
