<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$pdo = db();
$filter = $_GET['filter'] ?? 'all';

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $userId = (int) ($_POST['user_id'] ?? 0);

    if ($action === 'update' && $userId > 0) {
        $result = admin_update_user($userId, $_POST);
        flash($result['success'] ? 'success' : 'error', $result['success'] ? 'User updated.' : ($result['errors'][0] ?? 'Update failed.'));
    }

    if ($action === 'delete' && $userId > 0) {
        if (admin_delete_user($userId)) {
            flash('success', 'User deleted.');
        } else {
            flash('error', 'Cannot delete this user.');
        }
    }

    redirect('admin/users.php?filter=' . urlencode($filter));
}

$sql = 'SELECT * FROM users u';
$params = [];

if ($filter === 'admin') {
    $sql .= ' WHERE u.role = \'admin\'';
} elseif ($filter === 'inactive') {
    $sql .= ' WHERE u.is_active = 0';
}

$sql .= ' ORDER BY u.created_at DESC';
$users = $pdo->prepare($sql);
$users->execute($params);
$users = $users->fetchAll();

$pageTitle = 'Manage Users';
$activeNav = 'admin';
$adminNav = 'users';
require __DIR__ . '/../includes/header.php';
?>

<div class="container admin-layout">
    <?php require __DIR__ . '/includes/sidebar.php'; ?>

    <div>
        <div class="page-head">
            <h1>Users</h1>
            <p>Manage roles and account access.</p>
        </div>

        <div class="admin-sidebar" style="margin-bottom:1rem;">
            <a href="<?= url('admin/users.php?filter=all') ?>" class="<?= $filter === 'all' ? 'active' : '' ?>">All</a>
            <a href="<?= url('admin/users.php?filter=admin') ?>" class="<?= $filter === 'admin' ? 'active' : '' ?>">Admins</a>
            <a href="<?= url('admin/users.php?filter=inactive') ?>" class="<?= $filter === 'inactive' ? 'active' : '' ?>">Inactive</a>
        </div>

        <div class="card table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th class="hide-mobile">Email</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$users): ?>
                    <tr><td colspan="4" class="text-muted">No users found.</td></tr>
                <?php endif; ?>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <strong><?= e(user_display_name($u)) ?></strong><br>
                            <span class="text-muted hide-desktop" style="font-size:.8rem;"><?= e($u['email']) ?></span>
                        </td>
                        <td class="hide-mobile"><?= e($u['email']) ?></td>
                        <td>
                            <?= (int) $u['is_active'] ? status_badge('active') : status_badge('inactive') ?>
                            <?= status_badge($u['role']) ?>
                        </td>
                        <td>
                            <form method="post" class="table-actions" style="align-items:center;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="user_id" value="<?= (int) $u['user_id'] ?>">

                                <label style="font-size:.75rem;display:flex;align-items:center;gap:.2rem;">
                                    <input type="checkbox" name="is_active" <?= (int) $u['is_active'] ? 'checked' : '' ?>> Active
                                </label>
                                <select name="role" class="form-select" style="width:auto;padding:.35rem .5rem;font-size:.8rem;">
                                    <option value="user" <?= $u['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                    <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                </select>
                                <button type="submit" class="btn btn-sm btn-primary">Save</button>
                            </form>
                            <?php if ($u['role'] !== 'admin' && (int) $u['user_id'] !== (int) current_user()['user_id']): ?>
                                <form method="post" style="margin-top:.35rem;" onsubmit="return confirm('Delete this user permanently?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="user_id" value="<?= (int) $u['user_id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
