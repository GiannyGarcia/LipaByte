<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$user = current_user();
$userId = (int) $user['user_id'];

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'read_all') {
        mark_all_notifications_read($userId);
        flash('success', 'All notifications marked as read.');
    } elseif ($action === 'read_one') {
        mark_notification_read((int) ($_POST['notification_id'] ?? 0), $userId);
    }
    redirect('account/notifications.php');
}

$notifications = get_user_notifications($userId, 50);
$unreadCount = count_unread_notifications($userId);

$pageTitle = 'Notifications';
$activeNav = 'account';
require __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="page-head">
        <h1>Notifications</h1>
        <p>Updates about your rentals and listing activity in Lipa &amp; Batangas.</p>
    </div>

    <?php if (!notifications_table_exists()): ?>
        <div class="card">
            <p class="text-muted">Notifications require a database update. Run <code>sql/migrate_phase_c.sql</code> in phpMyAdmin.</p>
        </div>
    <?php else: ?>
        <?php if ($unreadCount > 0): ?>
            <form method="post" class="mb-1">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="read_all">
                <button type="submit" class="btn btn-sm btn-muted">Mark all as read (<?= $unreadCount ?>)</button>
            </form>
        <?php endif; ?>

        <?php if (!$notifications): ?>
            <div class="card text-center">
                <p class="text-muted mb-0">No notifications yet. Activity on your rentals and listings will appear here.</p>
            </div>
        <?php else: ?>
            <div class="notification-list">
                <?php foreach ($notifications as $note): ?>
                    <article class="notification-item card <?= (int) $note['is_read'] === 0 ? 'notification-unread' : '' ?>">
                        <div class="notification-item-body">
                            <strong><?= e($note['title']) ?></strong>
                            <p class="mb-0 text-muted"><?= e($note['message']) ?></p>
                            <span class="notification-time"><?= format_date($note['created_at']) ?></span>
                        </div>
                        <div class="notification-item-actions">
                            <?php if (!empty($note['link_url'])): ?>
                                <a href="<?= url($note['link_url']) ?>" class="btn btn-sm btn-outline">View</a>
                            <?php endif; ?>
                            <?php if ((int) $note['is_read'] === 0): ?>
                                <form method="post" class="inline-form">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="read_one">
                                    <input type="hidden" name="notification_id" value="<?= (int) $note['notification_id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-muted">Mark read</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <p class="mt-1"><a href="<?= url('account/profile.php') ?>">← Back to Account</a></p>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
