<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$user = current_user();
$stats = get_user_stats((int) $user['user_id']);

$pageTitle = 'Account';
$activeNav = 'account';
require __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="card">
        <div class="profile-hero">
            <div class="profile-avatar"><?= e(user_initials($user)) ?></div>
            <div>
                <h2><?= e(user_display_name($user)) ?></h2>
                <p><?= e($user['email']) ?></p>
                <div style="margin-top:.5rem;display:flex;gap:.35rem;flex-wrap:wrap;">
                    <?= status_badge($user['role']) ?>
                </div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <strong><?= $stats['active_rentals'] ?></strong>
                <span>Renting</span>
            </div>
            <div class="stat-card">
                <strong><?= $stats['listed_items'] ?></strong>
                <span>Listings</span>
            </div>
            <div class="stat-card">
                <strong><?= number_format($stats['avg_rating'], 1) ?></strong>
                <span>Rating</span>
            </div>
        </div>
    </div>

    <div class="quick-links mt-1">
        <a href="<?= url('home.php') ?>" class="quick-link">
            <strong>🔍 Browse marketplace</strong>
            <span>Find devices to borrow</span>
        </a>
        <a href="<?= url('list.php') ?>" class="quick-link">
            <strong>📦 List your devices</strong>
            <span>List equipment for other students</span>
        </a>
    </div>

    <div class="card mt-1">
        <ul class="menu-list">
            <li><a href="<?= url('dashboard.php') ?>">My Activity <span>→</span></a></li>
            <li><a href="<?= url('account/settings.php') ?>">Account Settings <span>→</span></a></li>
            <li><a href="<?= url('account/notifications.php') ?>">Notification Settings <span>→</span></a></li>
            <?php if (is_admin()): ?>
                <li><a href="<?= url('admin/index.php') ?>">Admin Panel <span>→</span></a></li>
            <?php endif; ?>
            <li>
                <a href="<?= url('auth/logout.php') ?>" data-confirm="Log out of LipaByte?">Logout <span>→</span></a>
            </li>
        </ul>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
