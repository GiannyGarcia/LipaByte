<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$filter = $_GET['status'] ?? '';
$rentals = admin_get_all_rentals(150);
if ($filter !== '' && in_array($filter, ['Pending', 'Approved', 'Declined', 'Cancelled', 'Completed'], true)) {
    $rentals = array_values(array_filter($rentals, static fn($r) => $r['request_status'] === $filter));
}

$pageTitle = 'Rental Requests';
$activeNav = 'admin';
$adminNav = 'rentals';
require __DIR__ . '/../includes/header.php';
?>

<div class="container admin-layout">
    <?php require __DIR__ . '/includes/sidebar.php'; ?>

    <div>
        <div class="page-head">
            <h1>Rental requests</h1>
            <p>Monitor rental activity across the marketplace.</p>
        </div>

        <div class="admin-sidebar" style="margin-bottom:1rem;">
            <a href="<?= url('admin/rentals.php') ?>" class="<?= $filter === '' ? 'active' : '' ?>">All</a>
            <?php foreach (['Pending', 'Approved', 'Completed', 'Declined', 'Cancelled'] as $status): ?>
                <a href="<?= url('admin/rentals.php?status=' . $status) ?>" class="<?= $filter === $status ? 'active' : '' ?>"><?= e($status) ?></a>
            <?php endforeach; ?>
        </div>

        <div class="card table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th class="hide-mobile">Renter</th>
                        <th class="hide-mobile">Owner</th>
                        <th>Dates</th>
                        <th>Status</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$rentals): ?>
                    <tr><td colspan="6" class="text-muted">No rental requests found.</td></tr>
                <?php endif; ?>
                <?php foreach ($rentals as $req): ?>
                    <tr>
                        <td>
                            <a href="<?= url('listing.php?id=' . (int) $req['listing_id']) ?>"><?= e($req['item_name']) ?></a>
                        </td>
                        <td class="hide-mobile"><?= e(trim($req['renter_first'] . ' ' . $req['renter_last'])) ?></td>
                        <td class="hide-mobile"><?= e(trim($req['lender_first'] . ' ' . $req['lender_last'])) ?></td>
                        <td style="font-size:.85rem;"><?= format_date($req['start_date']) ?> – <?= format_date($req['end_date']) ?></td>
                        <td><?= status_badge($req['request_status']) ?></td>
                        <td><?= format_money((float) $req['total_amount']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
