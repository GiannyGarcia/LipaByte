<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

if (is_post()) {
    verify_csrf();
    if (($_POST['action'] ?? '') === 'remove') {
        $result = admin_remove_listing((int) ($_POST['listing_id'] ?? 0));
        flash($result['success'] ? 'success' : 'error', $result['message'] ?? ($result['errors'][0] ?? 'Action failed.'));
    }
    redirect('admin/listings.php');
}

$listings = admin_get_all_listings(150);

$pageTitle = 'Manage Listings';
$activeNav = 'admin';
$adminNav = 'listings';
require __DIR__ . '/../includes/header.php';
?>

<div class="container admin-layout">
    <?php require __DIR__ . '/includes/sidebar.php'; ?>

    <div>
        <div class="page-head">
            <h1>Listings</h1>
            <p>Review and remove marketplace listings.</p>
        </div>

        <div class="card table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th class="hide-mobile">Owner</th>
                        <th>Status</th>
                        <th class="hide-mobile">Rate</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$listings): ?>
                    <tr><td colspan="5" class="text-muted">No listings found.</td></tr>
                <?php endif; ?>
                <?php foreach ($listings as $item): ?>
                    <tr class="<?= (int) $item['is_deleted'] === 1 ? 'text-muted' : '' ?>">
                        <td>
                            <strong><?= e($item['item_name']) ?></strong>
                            <br><span class="text-muted" style="font-size:.8rem;"><?= e($item['category_name']) ?> · <?= e($item['location'] ?? '') ?></span>
                        </td>
                        <td class="hide-mobile"><?= e(trim($item['first_name'] . ' ' . $item['last_name'])) ?></td>
                        <td><?= status_badge((int) $item['is_deleted'] === 1 ? 'inactive' : $item['availability_status']) ?></td>
                        <td class="hide-mobile"><?= format_money((float) $item['daily_rate']) ?>/day</td>
                        <td>
                            <?php if ((int) $item['is_deleted'] === 0): ?>
                                <a href="<?= url('listing.php?id=' . (int) $item['listing_id']) ?>" class="btn btn-sm btn-muted">View</a>
                                <form method="post" class="inline-form" onsubmit="return confirm('Remove this listing?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="listing_id" value="<?= (int) $item['listing_id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline btn-danger">Remove</button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted">Removed</span>
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
