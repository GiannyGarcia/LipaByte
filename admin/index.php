<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$stats = admin_get_marketplace_stats();
$topCategories = admin_get_top_categories(5);

$pageTitle = 'Admin Dashboard';
$activeNav = 'admin';
$adminNav = 'dashboard';
require __DIR__ . '/../includes/header.php';
?>

<div class="container admin-layout">
    <?php require __DIR__ . '/includes/sidebar.php'; ?>

    <div>
        <div class="page-head">
            <h1>Admin Dashboard</h1>
            <p>LipaByte marketplace overview — Lipa &amp; Batangas.</p>
        </div>

        <div class="grid-2">
            <div class="card"><strong><?= $stats['users'] ?></strong><br><span class="text-muted">Total Users</span></div>
            <div class="card"><strong><?= $stats['students'] ?></strong><br><span class="text-muted">Students</span></div>
            <div class="card"><strong><?= $stats['listings'] ?></strong><br><span class="text-muted">Active Listings</span></div>
            <div class="card"><strong><?= $stats['requests'] ?></strong><br><span class="text-muted">All Requests</span></div>
            <div class="card"><strong><?= $stats['pending_requests'] ?></strong><br><span class="text-muted">Pending Requests</span></div>
            <div class="card"><strong><?= $stats['active_rentals'] ?></strong><br><span class="text-muted">Active Rentals</span></div>
        </div>

        <?php if ($topCategories): ?>
            <div class="card mt-1">
                <h2 style="margin-top:0;font-size:1rem;">Top categories</h2>
                <ul class="admin-stat-list">
                    <?php foreach ($topCategories as $cat): ?>
                        <li><span><?= e($cat['category_name']) ?></span><strong><?= (int) $cat['listing_count'] ?> listings</strong></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <p class="text-muted mt-1">
            <a href="<?= url('admin/listings.php') ?>">Manage listings</a> ·
            <a href="<?= url('admin/rentals.php') ?>">View rentals</a> ·
            <a href="<?= url('admin/users.php') ?>">Manage users</a> ·
            <a href="<?= url('home.php') ?>">View marketplace</a>
        </p>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
