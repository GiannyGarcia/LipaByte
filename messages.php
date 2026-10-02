<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (isset($_GET['ajax']) && $_GET['ajax'] === 'chat') {
    handle_chat_api();
    exit;
}

require_login();

$user = current_user();
$userId = (int) $user['user_id'];
$requestId = (int) ($_GET['request'] ?? 0);

if (is_post() && ($_POST['action'] ?? '') === 'review') {
    verify_csrf();
    $result = submit_review(
        (int) ($_POST['request_id'] ?? 0),
        $userId,
        (int) ($_POST['rating'] ?? 0),
        trim($_POST['comment'] ?? '')
    );
    if ($result['success']) {
        flash('success', $result['message']);
    } else {
        flash('error', $result['errors'][0] ?? 'Review failed.');
    }
    redirect('messages.php?request=' . (int) ($_POST['request_id'] ?? 0));
}

$messagesError = null;

try {
    $threads = get_user_chat_threads($userId);
    $activeThread = null;
    $messages = [];
    $reviewable = null;

    if ($requestId > 0 && user_can_access_chat($requestId, $userId)) {
        $activeThread = get_chat_rental_context($requestId);
        $messages = get_chat_messages($requestId, $userId);
        if ($messages) {
            mark_chat_read($requestId, $userId, (int) end($messages)['message_id']);
        }
        if ($activeThread && $activeThread['request_status'] === 'Completed') {
            $reviewable = get_reviewable_rental($requestId, $userId);
        }
    } elseif ($threads) {
        redirect('messages.php?request=' . (int) $threads[0]['request_id']);
    }
} catch (Throwable $e) {
    $threads = [];
    $activeThread = null;
    $messages = [];
    $reviewable = null;
    $messagesError = 'Messages could not load. Run sql/migrate_rental_chat.sql in phpMyAdmin, then refresh.';
}

$pageTitle = 'Messages';
$activeNav = 'messages';
require __DIR__ . '/includes/header.php';
?>

<div class="container messages-page">
    <div class="page-head">
        <h1>Messages</h1>
        <p>Coordinate pickup and return with the other party. Chats open when a borrow is approved.</p>
    </div>

    <?php if ($messagesError): ?>
        <div class="alert alert-danger"><?= e($messagesError) ?></div>
    <?php endif; ?>

    <?php if (!rental_messages_table_exists()): ?>
        <div class="alert alert-warning">
            Messaging tables are not installed yet. Run <code>sql/migrate_rental_chat.sql</code> in phpMyAdmin.
        </div>
    <?php endif; ?>

    <div class="messages-layout card">
        <aside class="messages-sidebar">
            <h2 class="messages-sidebar-title">Conversations</h2>
            <?php if (!$threads): ?>
                <p class="text-muted messages-empty">No active chats yet. When a borrow is approved, the conversation appears here.</p>
            <?php else: ?>
                <ul class="messages-thread-list">
                    <?php foreach ($threads as $thread): ?>
                        <li>
                            <a href="<?= url('messages.php?request=' . (int) $thread['request_id']) ?>"
                               class="messages-thread-item <?= $requestId === (int) $thread['request_id'] ? 'active' : '' ?>">
                                <strong><?= e($thread['item_name']) ?></strong>
                                <span class="messages-thread-meta">
                                    <?= e($thread['other_name']) ?> · <?= e($thread['request_status']) ?>
                                </span>
                                <?php if (!empty($thread['last_message'])): ?>
                                    <span class="messages-thread-preview"><?= e(str_preview((string) $thread['last_message'], 60)) ?></span>
                                <?php endif; ?>
                                <?php if ((int) ($thread['unread_count'] ?? 0) > 0): ?>
                                    <span class="messages-unread-badge"><?= (int) $thread['unread_count'] ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </aside>

        <section class="messages-main">
            <?php if (!$activeThread): ?>
                <div class="messages-placeholder">
                    <p>Select a conversation or wait for an approved borrow.</p>
                </div>
            <?php else: ?>
                <?php
                $otherName = $userId === (int) $activeThread['renter_id']
                    ? trim($activeThread['lender_first'] . ' ' . $activeThread['lender_last'])
                    : trim($activeThread['renter_first'] . ' ' . $activeThread['renter_last']);
                ?>
                <div class="messages-thread-head">
                    <div>
                        <h2><?= e($activeThread['item_name']) ?></h2>
                        <p class="text-muted mb-0">With <?= e($otherName) ?> · <?= status_badge($activeThread['request_status']) ?></p>
                    </div>
                    <a href="<?= url('listing.php?id=' . (int) $activeThread['listing_id']) ?>" class="btn btn-sm btn-muted">View listing</a>
                </div>

                <div class="messages-transaction card">
                    <strong>Transaction</strong>
                    <ul>
                        <li><span>Dates</span> <?= format_date($activeThread['start_date']) ?> – <?= format_date($activeThread['end_date']) ?></li>
                        <li><span>Total</span> <?= format_money((float) $activeThread['total_amount']) ?></li>
                        <li><span>Pickup</span> <?= e($activeThread['location'] ?? 'Campus') ?></li>
                    </ul>
                </div>

                <div class="messages-feed" id="chatFeed"
                     data-request="<?= (int) $requestId ?>"
                     data-poll="<?= (int) (app_config()['chat_poll_ms'] ?? 3000) ?>"
                     data-user="<?= $userId ?>"
                     data-api="<?= e(url('messages.php?ajax=chat')) ?>">
                    <?php foreach ($messages as $msg): ?>
                        <?php
                        $isMine = !(int) $msg['is_system'] && (int) $msg['sender_id'] === $userId;
                        $isSystem = (int) $msg['is_system'] === 1;
                        ?>
                        <article class="chat-bubble <?= $isSystem ? 'chat-bubble-system' : ($isMine ? 'chat-bubble-mine' : 'chat-bubble-theirs') ?>"
                                 data-id="<?= (int) $msg['message_id'] ?>">
                            <div class="chat-bubble-meta">
                                <strong><?= e(chat_message_sender_label($msg)) ?></strong>
                                <span><?= e(format_datetime($msg['created_at'])) ?></span>
                            </div>
                            <div class="chat-bubble-body"><?= nl2br(e($msg['message_body'])) ?></div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <?php if ($reviewable): ?>
                    <div class="messages-review card">
                        <h3>Leave your review</h3>
                        <p class="text-muted">Rate <?= e($reviewable['reviewee_name']) ?> with stars and a comment for this completed borrow.</p>
                        <form method="post" class="review-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="review">
                            <input type="hidden" name="request_id" value="<?= (int) $requestId ?>">
                            <div class="form-group">
                                <label>Stars</label>
                                <?= render_star_input('rating') ?>
                            </div>
                            <div class="form-group">
                                <label for="chat-review-comment">Comment</label>
                                <textarea class="form-control" id="chat-review-comment" name="comment" rows="3" required placeholder="How was this borrow?"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">Submit review</button>
                        </form>
                    </div>
                <?php elseif ($activeThread['request_status'] === 'Completed'): ?>
                    <p class="messages-review-done text-muted">You already submitted your review for this borrow.</p>
                <?php endif; ?>

                <form class="messages-compose" id="chatCompose">
                    <?= csrf_field() ?>
                    <input type="hidden" name="request_id" value="<?= (int) $requestId ?>">
                    <textarea class="form-control" id="chatBody" name="body" rows="2" placeholder="Type a message…" required maxlength="2000"></textarea>
                    <button type="submit" class="btn btn-primary">Send</button>
                </form>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
