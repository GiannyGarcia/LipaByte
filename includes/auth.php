<?php

declare(strict_types=1);

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    static $user = null;
    if ($user !== null) {
        return $user;
    }

    $stmt = db()->prepare(
        'SELECT * FROM users WHERE user_id = ? AND is_active = 1'
    );
    $stmt->execute([(int) $_SESSION['user_id']]);
    $user = $stmt->fetch() ?: null;

    if (!$user) {
        logout_user();
        return null;
    }

    return $user;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    $user = current_user();
    return $user && $user['role'] === 'admin';
}

function require_login(): void
{
    if (!is_logged_in()) {
        flash('info', 'Please log in or register to access the marketplace.');
        redirect('auth/login.php');
    }
}

function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        flash('error', 'Admin access only.');
        redirect('home.php');
    }
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['user_id'];
    $_SESSION['user_role'] = $user['role'];
}

function logout_user(): void
{
    unset($_SESSION['user_id'], $_SESSION['user_role']);
    session_regenerate_id(true);
}

function attempt_login(string $email, string $password): array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([strtolower(trim($email))]);
    $user = $stmt->fetch();

    if (!$user || !(int) $user['is_active']) {
        return ['success' => false, 'message' => 'Invalid email or password.'];
    }

    if (!password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'message' => 'Invalid email or password.'];
    }

    login_user($user);

    return ['success' => true, 'user' => $user];
}

function register_user(array $data): array
{
    $firstName = trim($data['first_name'] ?? '');
    $lastName = trim($data['last_name'] ?? '');
    $email = strtolower(trim($data['email'] ?? ''));
    $password = $data['password'] ?? '';

    $errors = [];

    if ($firstName === '' || strlen($firstName) < 2) {
        $errors[] = 'First name must be at least 2 characters.';
    }

    if ($lastName === '' || strlen($lastName) < 2) {
        $errors[] = 'Last name must be at least 2 characters.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } elseif (!is_valid_institutional_email($email)) {
        $errors[] = 'Use your school email in the format name@schoolname.edu.ph (e.g. 2120751@university.edu.ph).';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    if ($errors) {
        return ['success' => false, 'errors' => $errors];
    }

    $check = db()->prepare('SELECT user_id FROM users WHERE email = ?');
    $check->execute([$email]);
    if ($check->fetch()) {
        return ['success' => false, 'errors' => ['This email is already registered.']];
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = db()->prepare(
        'INSERT INTO users (first_name, last_name, email, password_hash, role, is_verified, is_active)
         VALUES (?, ?, ?, ?, \'user\', 1, 1)'
    );
    $stmt->execute([$firstName, $lastName, $email, $hash]);

    $userId = (int) db()->lastInsertId();
    $stmt = db()->prepare('SELECT * FROM users WHERE user_id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    login_user($user);

    return ['success' => true, 'user' => $user];
}

function update_user_profile(int $userId, array $data): array
{
    $firstName = trim($data['first_name'] ?? '');
    $lastName = trim($data['last_name'] ?? '');
    $errors = [];

    if ($firstName === '' || strlen($firstName) < 2) {
        $errors[] = 'First name must be at least 2 characters.';
    }

    if ($lastName === '' || strlen($lastName) < 2) {
        $errors[] = 'Last name must be at least 2 characters.';
    }

    if ($errors) {
        return ['success' => false, 'errors' => $errors];
    }

    $stmt = db()->prepare(
        'UPDATE users SET first_name = ?, last_name = ?, updated_at = NOW() WHERE user_id = ?'
    );
    $stmt->execute([$firstName, $lastName, $userId]);

    return ['success' => true];
}

function change_user_password(int $userId, string $current, string $new, string $confirm): array
{
    $errors = [];

    $stmt = db()->prepare('SELECT password_hash FROM users WHERE user_id = ?');
    $stmt->execute([$userId]);
    $hash = $stmt->fetchColumn();

    if (!$hash || !password_verify($current, $hash)) {
        $errors[] = 'Current password is incorrect.';
    }

    if (strlen($new) < 8) {
        $errors[] = 'New password must be at least 8 characters.';
    }

    if ($new !== $confirm) {
        $errors[] = 'New passwords do not match.';
    }

    if ($errors) {
        return ['success' => false, 'errors' => $errors];
    }

    $newHash = password_hash($new, PASSWORD_BCRYPT);
    $update = db()->prepare('UPDATE users SET password_hash = ?, updated_at = NOW() WHERE user_id = ?');
    $update->execute([$newHash, $userId]);

    return ['success' => true];
}

function admin_update_user(int $userId, array $data): array
{
    $role = $data['role'] ?? 'user';
    $isActive = isset($data['is_active']) ? 1 : 0;

    if (!in_array($role, ['user', 'admin'], true)) {
        return ['success' => false, 'errors' => ['Invalid role.']];
    }

    $stmt = db()->prepare(
        'UPDATE users SET role = ?, is_verified = 1, is_active = ?, updated_at = NOW() WHERE user_id = ?'
    );
    $stmt->execute([$role, $isActive, $userId]);

    return ['success' => true];
}

function admin_delete_user(int $userId): bool
{
    if ($userId === (int) (current_user()['user_id'] ?? 0)) {
        return false;
    }

    $stmt = db()->prepare('DELETE FROM users WHERE user_id = ? AND role != \'admin\'');
    $stmt->execute([$userId]);
    return $stmt->rowCount() > 0;
}

function password_reset_table_exists(): bool
{
    static $exists = null;
    if ($exists !== null) {
        return $exists;
    }
    try {
        db()->query('SELECT 1 FROM password_reset_tokens LIMIT 1');
        $exists = true;
    } catch (Throwable) {
        $exists = false;
    }
    return $exists;
}

function request_password_reset(string $email): array
{
    if (!password_reset_table_exists()) {
        return ['success' => false, 'errors' => ['Password reset is not available yet. Please contact support.']];
    }

    $email = strtolower(trim($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'errors' => ['Please enter a valid email address.']];
    }

    $stmt = db()->prepare('SELECT user_id, first_name FROM users WHERE email = ? AND is_active = 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Always return same message to avoid email enumeration
    $genericMessage = 'If that email is registered, reset instructions have been sent.';

    if (!$user) {
        return ['success' => true, 'message' => $genericMessage, 'reset_url' => null];
    }

    $token = bin2hex(random_bytes(32));
    $hash = hash('sha256', $token);
    $expires = date('Y-m-d H:i:s', time() + 3600);

    db()->prepare('DELETE FROM password_reset_tokens WHERE user_id = ?')->execute([(int) $user['user_id']]);
    db()->prepare(
        'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)'
    )->execute([(int) $user['user_id'], $hash, $expires]);

    $resetUrl = url('auth/reset-password.php?token=' . urlencode($token));
    $subject = app_config()['name'] . ' — Password reset';
    $body = "Hi {$user['first_name']},\n\nReset your password using this link (valid for 1 hour):\n{$resetUrl}\n\nIf you did not request this, ignore this email.";
    $headers = 'From: ' . app_config()['mail_from'] . "\r\nContent-Type: text/plain; charset=UTF-8";
    @mail($email, $subject, $body, $headers);

    return [
        'success' => true,
        'message' => $genericMessage,
        'reset_url' => $resetUrl,
    ];
}

function get_password_reset_user(string $token): ?array
{
    if (!password_reset_table_exists() || $token === '') {
        return null;
    }

    $hash = hash('sha256', $token);
    $stmt = db()->prepare(
        'SELECT u.user_id, u.email, u.first_name, t.token_id
         FROM password_reset_tokens t
         JOIN users u ON u.user_id = t.user_id
         WHERE t.token_hash = ? AND t.expires_at > NOW() AND u.is_active = 1'
    );
    $stmt->execute([$hash]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function reset_password_with_token(string $token, string $password, string $confirm): array
{
    $user = get_password_reset_user($token);
    if (!$user) {
        return ['success' => false, 'errors' => ['This reset link is invalid or has expired.']];
    }

    if (strlen($password) < 8) {
        return ['success' => false, 'errors' => ['Password must be at least 8 characters.']];
    }
    if ($password !== $confirm) {
        return ['success' => false, 'errors' => ['Passwords do not match.']];
    }

    $newHash = password_hash($password, PASSWORD_BCRYPT);
    db()->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?')->execute([$newHash, (int) $user['user_id']]);
    db()->prepare('DELETE FROM password_reset_tokens WHERE user_id = ?')->execute([(int) $user['user_id']]);

    return ['success' => true, 'message' => 'Your password has been updated. You can log in now.'];
}
