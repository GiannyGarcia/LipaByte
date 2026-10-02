<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$user = current_user();
$errors = [];
$activeTab = $_GET['tab'] ?? 'profile';

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'profile') {
        $result = update_user_profile((int) $user['user_id'], $_POST);
        if ($result['success']) {
            flash('success', 'Profile updated successfully.');
            redirect('account/settings.php?tab=profile');
        }
        $errors = $result['errors'];
        $activeTab = 'profile';
    }

    if ($action === 'password') {
        $result = change_user_password(
            (int) $user['user_id'],
            $_POST['current_password'] ?? '',
            $_POST['new_password'] ?? '',
            $_POST['new_password_confirm'] ?? ''
        );
        if ($result['success']) {
            flash('success', 'Password changed successfully.');
            redirect('account/settings.php?tab=password');
        }
        $errors = $result['errors'];
        $activeTab = 'password';
    }
}

$user = current_user();

$pageTitle = 'Account Settings';
$activeNav = 'account';
require __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="page-head">
        <h1>Account Settings</h1>
        <p>Manage your profile and security.</p>
    </div>

    <?php if ($errors): ?>
        <ul class="form-error-list">
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <div class="admin-sidebar mb-0" style="margin-bottom:1rem;">
        <a href="<?= url('account/settings.php?tab=profile') ?>" class="<?= $activeTab === 'profile' ? 'active' : '' ?>">Profile</a>
        <a href="<?= url('account/settings.php?tab=password') ?>" class="<?= $activeTab === 'password' ? 'active' : '' ?>">Password</a>
    </div>

    <?php if ($activeTab === 'password'): ?>
        <div class="card">
            <h2 class="mb-0" style="margin-top:0;">Change Password</h2>
            <form method="post" class="mt-1">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="password">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input class="form-control" type="password" id="current_password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input class="form-control" type="password" id="new_password" name="new_password" required minlength="8">
                </div>
                <div class="form-group">
                    <label for="new_password_confirm">Confirm New Password</label>
                    <input class="form-control" type="password" id="new_password_confirm" name="new_password_confirm" required minlength="8">
                </div>
                <button type="submit" class="btn btn-primary">Update Password</button>
            </form>
        </div>
    <?php else: ?>
        <div class="card">
            <h2 class="mb-0" style="margin-top:0;">Edit Profile</h2>
            <form method="post" class="mt-1">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="profile">
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input class="form-control" type="text" id="first_name" name="first_name" value="<?= e($user['first_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input class="form-control" type="text" id="last_name" name="last_name" value="<?= e($user['last_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input class="form-control" type="email" value="<?= e($user['email']) ?>" disabled>
                    <p class="form-hint">Email cannot be changed. Contact an admin if you need help.</p>
                </div>
                <button type="submit" class="btn btn-primary">Save Profile</button>
            </form>
        </div>
    <?php endif; ?>

    <p class="mt-1"><a href="<?= url('account/profile.php') ?>">← Back to Account</a></p>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
