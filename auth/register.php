<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (is_logged_in()) {
    redirect('home.php');
}

$errors = [];

if (is_post()) {
    verify_csrf();
    $data = [
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name' => trim($_POST['last_name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
    ];
    store_old($data);

    $result = register_user($data);
    if ($result['success']) {
        clear_old();
        flash('success', 'Welcome to LipaByte! Browse the marketplace or list your devices.');
        redirect('home.php');
    }
    $errors = $result['errors'];
}

$pageTitle = 'Register';
$authLayout = true;
require __DIR__ . '/../includes/header.php';
?>

<div class="auth-card">
    <div class="auth-brand">
        <div class="brand" style="justify-content:center;">
            <img src="<?= asset('img/lipabyte-logo.png') ?>" alt="<?= e(app_config()['name']) ?>" class="brand-logo" width="56" height="56">
        </div>
        <h1>Create Account</h1>
        <p>Join LipaByte — tech rentals for Batangas students</p>
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
            <label for="first_name">First Name</label>
            <input class="form-control" type="text" id="first_name" name="first_name" value="<?= old('first_name') ?>" required placeholder="Juan">
        </div>
        <div class="form-group">
            <label for="last_name">Last Name</label>
            <input class="form-control" type="text" id="last_name" name="last_name" value="<?= old('last_name') ?>" required placeholder="Mitra">
        </div>
        <div class="form-group">
            <label for="email">Institutional Email</label>
            <input class="form-control" type="email" id="email" name="email" value="<?= old('email') ?>" required placeholder="2120751@batstate-u.edu.ph">
            <p class="form-hint">Use your school email (BatStateU, KLL, etc.) ending in <strong>.edu.ph</strong>.</p>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input class="form-control" type="password" id="password" name="password" required minlength="8" placeholder="At least 8 characters">
        </div>
        <button type="submit" class="btn btn-primary btn-block">Create Account</button>
    </form>

    <p class="text-center mt-1 text-muted">
        Already registered? <a href="<?= url('auth/login.php') ?>">Login</a>
    </p>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
