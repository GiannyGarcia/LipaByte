<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (is_logged_in()) {
    redirect('home.php');
}

$errors = [];
$resetUrl = null;

if (is_post()) {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    store_old(['email' => $email]);

    $result = request_password_reset($email);
    if ($result['success']) {
        clear_old();
        flash('success', $result['message']);
        if (!empty($result['reset_url']) && (app_config()['show_reset_link_local'] ?? false)) {
            $_SESSION['dev_reset_url'] = $result['reset_url'];
        }
        redirect('auth/forgot-password.php?sent=1');
    }
    $errors = $result['errors'] ?? ['Request failed.'];
}

if (!empty($_SESSION['dev_reset_url'])) {
    $resetUrl = $_SESSION['dev_reset_url'];
    unset($_SESSION['dev_reset_url']);
}

$pageTitle = 'Forgot Password';
$authLayout = true;
require __DIR__ . '/../includes/header.php';
?>

<div class="auth-card">
    <div class="auth-brand">
        <div class="brand" style="justify-content:center;">
            <img src="<?= asset('img/lipabyte-logo.png') ?>" alt="<?= e(app_config()['name']) ?>" class="brand-logo" width="56" height="56">
        </div>
        <h1>Reset password</h1>
        <p>Enter your school email and we will send reset instructions.</p>
    </div>

    <?php if ($errors): ?>
        <ul class="form-error-list">
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if (isset($_GET['sent'])): ?>
        <div class="alert alert-info">Check your inbox for a reset link. It expires in 1 hour.</div>
        <?php if ($resetUrl): ?>
            <div class="card" style="margin-bottom:1rem;padding:0.85rem;">
                <p class="text-muted mb-0" style="font-size:.85rem;"><strong>Local dev:</strong> email may not send on XAMPP. Use this link instead:</p>
                <p style="word-break:break-all;margin:.5rem 0 0;"><a href="<?= e($resetUrl) ?>"><?= e($resetUrl) ?></a></p>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <form method="post">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="email">Email</label>
                <input class="form-control" type="email" id="email" name="email" value="<?= old('email') ?>" required placeholder="you@batstate-u.edu.ph">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Send reset link</button>
        </form>
    <?php endif; ?>

    <p class="text-center mt-1 text-muted">
        <a href="<?= url('auth/login.php') ?>">← Back to login</a>
    </p>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
