<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (is_logged_in()) {
    redirect(is_admin() ? 'admin/index.php' : 'home.php');
}

$errors = [];

if (is_post()) {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    store_old(['email' => $email]);

    $result = attempt_login($email, $password);
    if ($result['success']) {
        clear_old();
        flash('success', 'Welcome back, ' . user_display_name($result['user']) . '!');
        redirect($result['user']['role'] === 'admin' ? 'admin/index.php' : 'home.php');
    }
    $errors[] = $result['message'];
}

$pageTitle = 'Login';
$authLayout = true;
require __DIR__ . '/../includes/header.php';
?>

<div class="auth-card">
    <div class="auth-brand">
        <div class="brand" style="justify-content:center;">
            <img src="<?= asset('img/lipabyte-logo.png') ?>" alt="<?= e(app_config()['name']) ?>" class="brand-logo" width="56" height="56">
        </div>
        <h1><?= e(app_config()['name']) ?></h1>
        <p><?= e(app_config()['tagline']) ?></p>
    </div>

    <?php if ($errors): ?>
        <ul class="form-error-list">
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="">
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="email">Email</label>
            <input class="form-control" type="email" id="email" name="email" value="<?= old('email') ?>" required autofocus placeholder="you@university.edu.ph">
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input class="form-control" type="password" id="password" name="password" required placeholder="Enter your password">
        </div>
        <button type="submit" class="btn btn-primary btn-block">Login</button>
    </form>

    <p class="text-center mt-1" style="font-size:.9rem;">
        <a href="<?= url('auth/forgot-password.php') ?>">Forgot password?</a>
    </p>

    <p class="text-center mt-1 text-muted">
        No account yet? <a href="<?= url('auth/register.php') ?>">Register as a student</a>
    </p>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
