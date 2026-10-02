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
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $name = trim($_POST['category_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $icon = trim($_POST['icon_label'] ?? 'chip');
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if ($name === '') {
        $errors[] = 'Category name is required.';
    }

    if (!$errors) {
        if ($action === 'create') {
            $stmt = $pdo->prepare(
                'INSERT INTO categories (category_name, description, icon_label, is_active) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$name, $description, $icon, $isActive]);
            flash('success', 'Category created.');
            redirect('admin/categories.php');
        }

        if ($action === 'update' && $categoryId > 0) {
            $stmt = $pdo->prepare(
                'UPDATE categories SET category_name = ?, description = ?, icon_label = ?, is_active = ? WHERE category_id = ?'
            );
            $stmt->execute([$name, $description, $icon, $isActive, $categoryId]);
            flash('success', 'Category updated.');
            redirect('admin/categories.php');
        }

        if ($action === 'delete' && $categoryId > 0) {
            try {
                $stmt = $pdo->prepare('DELETE FROM categories WHERE category_id = ?');
                $stmt->execute([$categoryId]);
                flash('success', 'Category deleted.');
            } catch (PDOException) {
                flash('error', 'Cannot delete category while listings use it.');
            }
            redirect('admin/categories.php');
        }
    }
}

$categories = $pdo->query('SELECT * FROM categories ORDER BY category_name')->fetchAll();
$editing = null;
if ($editId > 0) {
    foreach ($categories as $category) {
        if ((int) $category['category_id'] === $editId) {
            $editing = $category;
            break;
        }
    }
}

$pageTitle = 'Manage Categories';
$activeNav = 'admin';
$adminNav = 'categories';
require __DIR__ . '/../includes/header.php';
?>

<div class="container admin-layout">
    <?php require __DIR__ . '/includes/sidebar.php'; ?>

    <div>
        <div class="page-head">
            <h1>Categories</h1>
            <p>Manage hardware categories for search and filtering.</p>
        </div>

        <?php if ($errors): ?>
            <ul class="form-error-list">
                <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <div class="grid-2">
            <div class="card">
                <h2 style="margin-top:0;"><?= $editing ? 'Edit Category' : 'Add Category' ?></h2>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
                    <?php if ($editing): ?>
                        <input type="hidden" name="category_id" value="<?= (int) $editing['category_id'] ?>">
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="category_name">Category Name</label>
                        <input class="form-control" id="category_name" name="category_name" value="<?= e($editing['category_name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?= e($editing['description'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="icon_label">Icon Label</label>
                        <input class="form-control" id="icon_label" name="icon_label" value="<?= e($editing['icon_label'] ?? 'chip') ?>" placeholder="chip, phone, laptop">
                    </div>
                    <label style="display:flex;align-items:center;gap:.4rem;margin-bottom:1rem;">
                        <input type="checkbox" name="is_active" <?= !$editing || (int) $editing['is_active'] ? 'checked' : '' ?>> Active
                    </label>
                    <button type="submit" class="btn btn-primary"><?= $editing ? 'Update' : 'Create' ?> Category</button>
                    <?php if ($editing): ?>
                        <a href="<?= url('admin/categories.php') ?>" class="btn btn-muted">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="card table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Icon</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td>
                                <strong><?= e($category['category_name']) ?></strong><br>
                                <span class="text-muted" style="font-size:.8rem;"><?= e($category['description'] ?? '') ?></span>
                            </td>
                            <td><?= e($category['icon_label']) ?></td>
                            <td><?= (int) $category['is_active'] ? status_badge('active') : status_badge('inactive') ?></td>
                            <td class="table-actions">
                                <a class="btn btn-sm btn-outline" href="<?= url('admin/categories.php?edit=' . (int) $category['category_id']) ?>">Edit</a>
                                <form method="post" onsubmit="return confirm('Delete this category?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="category_id" value="<?= (int) $category['category_id'] ?>">
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
