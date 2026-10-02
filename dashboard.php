<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$user = current_user();
$userId = (int) $user['user_id'];
$tab = $_GET['tab'] ?? 'renting';
if (!in_array($tab, ['renting', 'lending'], true)) {
    $tab = 'renting';
}

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $requestId = (int) ($_POST['request_id'] ?? 0);
    $returnTab = $_POST['tab'] ?? $tab;
    if (!in_array($returnTab, ['renting', 'lending'], true)) {
        $returnTab = 'renting';
    }

    $result = match ($action) {
        'approve' => approve_rental_request($requestId, $userId),
        'decline' => decline_rental_request($requestId, $userId),
        'cancel' => cancel_rental_request($requestId, $userId),
        'complete' => complete_rental_request($requestId, $userId),
        'review' => submit_review(
            $requestId,
            $userId,
            (int) ($_POST['rating'] ?? 0),
            trim($_POST['comment'] ?? '')
        ),
        default => ['success' => false, 'errors' => ['Invalid action.']],
    };

    if ($result['success']) {
        flash('success', $result['message']);
    } else {
        flash('error', $result['errors'][0] ?? 'Action failed.');
    }
    redirect('dashboard.php?tab=' . $returnTab);
}

$myRentals = get_user_rentals($userId);
$myListings = get_user_listings($userId);
$incomingRequests = get_incoming_rental_requests($userId);
$pendingReviews = get_pending_reviews_for_user($userId);

$pageTitle = 'My Activity';
$activeNav = 'dashboard';
require __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="page-head">
        <h1>My Activity</h1>
        <p>Track devices you're <strong>borrowing</strong> and devices you've <strong>listed</strong>.</p>
    </div>

    <div class="activity-tabs">
        <a href="<?= url('dashboard.php?tab=renting') ?>" class="activity-tab <?= $tab === 'renting' ? 'active' : '' ?>">
            🔍 Renting <span class="tab-count"><?= count($myRentals) ?></span>
        </a>
        <a href="<?= url('dashboard.php?tab=lending') ?>" class="activity-tab <?= $tab === 'lending' ? 'active' : '' ?>">
            📦 Listings <span class="tab-count"><?= count($myListings) ?></span>
        </a>
    </div>

    <?php if ($pendingReviews): ?>
        <div class="card mb-1">
            <h2 style="margin-top:0;">Leave a review</h2>
            <p class="text-muted">Share feedback about completed rentals to help the community.</p>
            <div class="review-pending-list">
                <?php foreach ($pendingReviews as $pending): ?>
                    <div class="review-pending-item card">
                        <p><strong><?= e($pending['item_name']) ?></strong> · Rate <?= e($pending['reviewee_name']) ?></p>
                        <form method="post" class="review-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="review">
                            <input type="hidden" name="tab" value="<?= e($tab) ?>">
                            <input type="hidden" name="request_id" value="<?= (int) $pending['request_id'] ?>">
                            <div class="form-group">
                                <label>Rating</label>
                                <?= render_star_input('rating') ?>
                            </div>
                            <div class="form-group">
                                <label for="comment-<?= (int) $pending['request_id'] ?>">Comment</label>
                                <textarea class="form-control" id="comment-<?= (int) $pending['request_id'] ?>" name="comment" rows="3" required placeholder="How was the rental experience?"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">Submit review</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($tab === 'lending'): ?>
        <div class="role-banner role-banner-lend mb-1">
            <span>📦</span>
            <div>
                <strong>Your listings</strong>
                <p>Manage your listings and incoming requests.</p>
            </div>
            <a href="<?= url('list.php') ?>" class="btn btn-primary btn-sm">+ New listing</a>
        </div>

        <?php if (!$myListings && !$incomingRequests): ?>
            <div class="card text-center">
                <p class="text-muted">You haven't listed any devices yet.</p>
                <a href="<?= url('list.php') ?>" class="btn btn-primary">List a device</a>
            </div>
        <?php endif; ?>

        <?php if ($incomingRequests): ?>
            <div class="card">
                <h2 style="margin-top:0;">Incoming requests</h2>
                <div class="activity-list">
                    <?php foreach ($incomingRequests as $req): ?>
                        <div class="activity-item">
                            <div>
                                <strong><?= e($req['item_name']) ?></strong>
                                <p class="text-muted mb-0">
                                    From <?= e(trim($req['renter_first'] . ' ' . $req['renter_last'])) ?>
                                    · <?= format_date($req['start_date']) ?> – <?= format_date($req['end_date']) ?>
                                </p>
                            </div>
                            <div class="activity-item-right">
                                <?= status_badge($req['request_status']) ?>
                                <strong><?= format_money((float) $req['total_amount']) ?></strong>
                                <div class="activity-actions">
                                    <?php if ($req['request_status'] === 'Pending'): ?>
                                        <form method="post" class="inline-form">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="tab" value="lending">
                                            <input type="hidden" name="request_id" value="<?= (int) $req['request_id'] ?>">
                                            <button type="submit" name="action" value="approve" class="btn btn-sm btn-primary">Approve</button>
                                            <button type="submit" name="action" value="decline" class="btn btn-sm btn-outline">Decline</button>
                                        </form>
                                    <?php elseif ($req['request_status'] === 'Approved'): ?>
                                        <form method="post" class="inline-form">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="tab" value="lending">
                                            <input type="hidden" name="request_id" value="<?= (int) $req['request_id'] ?>">
                                            <button type="submit" name="action" value="complete" class="btn btn-sm btn-primary">Mark complete</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if (in_array($req['request_status'], ['Approved', 'Completed'], true)): ?>
                                        <a href="<?= url('messages.php?request=' . (int) $req['request_id']) ?>" class="btn btn-sm btn-primary">Open chat</a>
                                    <?php endif; ?>
                                    <a href="<?= url('listing.php?id=' . (int) $req['listing_id']) ?>" class="btn btn-sm btn-muted">View listing</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($myListings): ?>
            <div class="card mt-1">
                <h2 style="margin-top:0;">Your listings</h2>
                <div class="listing-grid listing-grid-compact">
                    <?php foreach ($myListings as $item): ?>
                        <div class="listing-card-manage">
                            <a href="<?= url('listing.php?id=' . (int) $item['listing_id']) ?>" class="listing-card">
                                <div class="listing-card-image" style="background-image:url('<?= e(listing_image_url($item)) ?>')"></div>
                                <div class="listing-card-body">
                                    <strong class="listing-card-price"><?= format_money((float) $item['daily_rate']) ?></strong>
                                    <h3><?= e($item['item_name']) ?></h3>
                                </div>
                            </a>
                            <div class="listing-card-manage-footer">
                                <?= status_badge($item['availability_status']) ?>
                                <a href="<?= url('edit-listing.php?id=' . (int) $item['listing_id']) ?>" class="btn btn-sm btn-outline">Edit</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="role-banner role-banner-rent mb-1">
            <span>🔍</span>
            <div>
                <strong>Items you're renting</strong>
                <p>Devices you've requested or are currently borrowing from other students.</p>
            </div>
            <a href="<?= url('home.php') ?>" class="btn btn-primary btn-sm">Browse marketplace</a>
        </div>

        <?php if (!$myRentals): ?>
            <div class="card text-center">
                <p class="text-muted">No borrow activity yet. Browse the marketplace to find devices for your projects.</p>
                <a href="<?= url('home.php') ?>" class="btn btn-primary">Browse &amp; borrow</a>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="activity-list">
                    <?php foreach ($myRentals as $req): ?>
                        <div class="activity-item">
                            <div>
                                <strong><?= e($req['item_name']) ?></strong>
                                <p class="text-muted mb-0">
                                    Owner: <?= e(trim($req['lender_first'] . ' ' . $req['lender_last'])) ?>
                                    · 📍 <?= e($req['location'] ?? 'Campus') ?>
                                </p>
                                <p class="text-muted mb-0" style="font-size:.85rem;">
                                    <?= format_date($req['start_date']) ?> – <?= format_date($req['end_date']) ?>
                                </p>
                            </div>
                            <div class="activity-item-right">
                                <?= status_badge($req['request_status']) ?>
                                <strong><?= format_money((float) $req['total_amount']) ?></strong>
                                <div class="activity-actions">
                                    <?php if ($req['request_status'] === 'Pending'): ?>
                                        <form method="post" class="inline-form">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="tab" value="renting">
                                            <input type="hidden" name="request_id" value="<?= (int) $req['request_id'] ?>">
                                            <button type="submit" name="action" value="cancel" class="btn btn-sm btn-outline">Cancel request</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if (in_array($req['request_status'], ['Approved', 'Completed'], true)): ?>
                                        <a href="<?= url('messages.php?request=' . (int) $req['request_id']) ?>" class="btn btn-sm btn-primary">Open chat</a>
                                    <?php endif; ?>
                                    <a href="<?= url('listing.php?id=' . (int) $req['listing_id']) ?>" class="btn btn-sm btn-muted">View</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
