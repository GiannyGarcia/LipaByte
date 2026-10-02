<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (is_logged_in()) {
    redirect('home.php');
}

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$errors = [];
$user = $token !== '' ? get_password_reset_user($token) : null;

if (!$user && !is_post()) {
    flash('error', 'Invalid or expired reset link.');
    redirect('auth/forgot-password.php');
}

if (is_post()) {
    verify_csrf();
    $result = reset_password_with_token(
        $token,
        $_POST['password'] ?? '',
        $_POST['confirm_password'] ?? ''
    );
    if ($result['success']) {
        flash('success', $result['message']);
        redirect('auth/login.php');
    }
    $errors = $result['errors'];
}

$pageTitle = 'Set New Password';
$authLayout = true;
require __DIR__ . '/../includes/header.php';
?>

<div class="auth-card">
    <div class="auth-brand">
        <h1>New password</h1>
        <p>Choose a new password for <?= e($user['email'] ?? 'your account') ?>.</p>
    </div>

    <?php if ($errors): ?>
        <ul class="form-error-list">
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <div class="form-group">
            <label for="password">New password</label>
            <input class="form-control" type="password" id="password" name="password" required minlength="8">
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirm password</label>
            <input class="form-control" type="password" id="confirm_password" name="confirm_password" required minlength="8">
        </div>
        <button type="submit" class="btn btn-primary btn-block">Update password</button>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
