<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$pdo = db();
$errors = [];
$editId = (int) ($_GET['edit'] ?? 0);

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $campusId = (int) ($_POST['campus_id'] ?? 0);
    $name = trim($_POST['campus_name'] ?? '');
    $university = trim($_POST['university_name'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if ($name === '' || $university === '' || $city === '') {
        $errors[] = 'All campus fields are required.';
    }

    if (!$errors) {
        if ($action === 'create') {
            $stmt = $pdo->prepare(
                'INSERT INTO campuses (campus_name, university_name, city, is_active) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$name, $university, $city, $isActive]);
            flash('success', 'Campus created.');
            redirect('admin/campuses.php');
        }

        if ($action === 'update' && $campusId > 0) {
            $stmt = $pdo->prepare(
                'UPDATE campuses SET campus_name = ?, university_name = ?, city = ?, is_active = ? WHERE campus_id = ?'
            );
            $stmt->execute([$name, $university, $city, $isActive, $campusId]);
            flash('success', 'Campus updated.');
            redirect('admin/campuses.php');
        }

        if ($action === 'delete' && $campusId > 0) {
            try {
                $stmt = $pdo->prepare('DELETE FROM campuses WHERE campus_id = ?');
                $stmt->execute([$campusId]);
                flash('success', 'Campus deleted.');
            } catch (PDOException) {
                flash('error', 'Cannot delete campus while users are assigned to it.');
            }
            redirect('admin/campuses.php');
        }
    }
}

$campuses = $pdo->query('SELECT * FROM campuses ORDER BY university_name, campus_name')->fetchAll();
$editing = null;
if ($editId > 0) {
    foreach ($campuses as $campus) {
        if ((int) $campus['campus_id'] === $editId) {
            $editing = $campus;
            break;
        }
    }
}

$pageTitle = 'Manage Campuses';
$activeNav = 'admin';
$adminNav = 'campuses';
require __DIR__ . '/../includes/header.php';
?>

<div class="container admin-layout">
    <?php require __DIR__ . '/includes/sidebar.php'; ?>

    <div>
        <div class="page-head">
            <h1>Campuses</h1>
            <p>Manage university locations for the campus community.</p>
        </div>

        <?php if ($errors): ?>
            <ul class="form-error-list">
                <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <div class="grid-2">
            <div class="card">
                <h2 style="margin-top:0;"><?= $editing ? 'Edit Campus' : 'Add Campus' ?></h2>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
                    <?php if ($editing): ?>
                        <input type="hidden" name="campus_id" value="<?= (int) $editing['campus_id'] ?>">
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="campus_name">Campus Name</label>
                        <input class="form-control" id="campus_name" name="campus_name" value="<?= e($editing['campus_name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="university_name">University Name</label>
                        <input class="form-control" id="university_name" name="university_name" value="<?= e($editing['university_name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="city">City</label>
                        <input class="form-control" id="city" name="city" value="<?= e($editing['city'] ?? '') ?>" required>
                    </div>
                    <label style="display:flex;align-items:center;gap:.4rem;margin-bottom:1rem;">
                        <input type="checkbox" name="is_active" <?= !$editing || (int) $editing['is_active'] ? 'checked' : '' ?>> Active
                    </label>
                    <button type="submit" class="btn btn-primary"><?= $editing ? 'Update' : 'Create' ?> Campus</button>
                    <?php if ($editing): ?>
                        <a href="<?= url('admin/campuses.php') ?>" class="btn btn-muted">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="card table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Campus</th>
                            <th>City</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($campuses as $campus): ?>
                        <tr>
                            <td>
                                <strong><?= e($campus['campus_name']) ?></strong><br>
                                <span class="text-muted" style="font-size:.8rem;"><?= e($campus['university_name']) ?></span>
                            </td>
                            <td><?= e($campus['city']) ?></td>
                            <td><?= (int) $campus['is_active'] ? status_badge('active') : status_badge('inactive') ?></td>
                            <td class="table-actions">
                                <a class="btn btn-sm btn-outline" href="<?= url('admin/campuses.php?edit=' . (int) $campus['campus_id']) ?>">Edit</a>
                                <form method="post" onsubmit="return confirm('Delete this campus?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="campus_id" value="<?= (int) $campus['campus_id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
