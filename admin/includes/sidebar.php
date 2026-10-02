<?php
/** @var string $adminNav */
$adminNav = $adminNav ?? 'dashboard';
?>
<aside class="admin-sidebar">
    <a href="<?= url('admin/index.php') ?>" class="<?= $adminNav === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
    <a href="<?= url('admin/users.php') ?>" class="<?= $adminNav === 'users' ? 'active' : '' ?>">Users</a>
    <a href="<?= url('admin/listings.php') ?>" class="<?= $adminNav === 'listings' ? 'active' : '' ?>">Listings</a>
    <a href="<?= url('admin/rentals.php') ?>" class="<?= $adminNav === 'rentals' ? 'active' : '' ?>">Rentals</a>
    <a href="<?= url('admin/campuses.php') ?>" class="<?= $adminNav === 'campuses' ? 'active' : '' ?>">Campuses</a>
    <a href="<?= url('admin/categories.php') ?>" class="<?= $adminNav === 'categories' ? 'active' : '' ?>">Categories</a>
    <a href="<?= url('home.php') ?>">← Back to App</a>
</aside>
