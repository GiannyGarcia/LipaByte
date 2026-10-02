<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$listingId = (int) ($_GET['id'] ?? 0);
$listing = $listingId > 0 ? get_listing_by_id($listingId) : null;

if (!$listing) {
    flash('error', 'Listing not found.');
    redirect('home.php');
}

$images = get_listing_images($listingId);
$user = current_user();
$isOwner = $user && (int) $user['user_id'] === (int) $listing['lender_user_id'];
$errors = [];
$lenderId = (int) $listing['lender_user_id'];
$sellerProfile = get_lender_public_profile($lenderId);
$sellerListings = get_lender_other_listings($lenderId, $listingId, 4);
$sellerReviews = get_user_reviews_received($lenderId, 5);
$dailyRate = (float) $listing['daily_rate'];

if (is_post() && $user) {
    verify_csrf();
    $action = $_POST['action'] ?? 'rent';

    if ($action === 'delete' && $isOwner) {
        $result = delete_listing($listingId, (int) $user['user_id']);
        if ($result['success']) {
            flash('success', $result['message']);
            redirect('dashboard.php?tab=lending');
        }
        $errors = $result['errors'];
    } elseif ($action === 'rent' && !$isOwner) {
        $result = submit_rental_request(
            (int) $user['user_id'],
            $listingId,
            $_POST['start_date'] ?? '',
            $_POST['end_date'] ?? ''
        );
        if ($result['success']) {
            flash('success', $result['message']);
            redirect('dashboard.php?tab=renting');
        }
        $errors = $result['errors'];
    }
}

$displayImages = $images ?: [['image_url' => 'assets/img/listings/' . listing_category_slug((int) $listing['category_id']) . '.svg']];
$imageUrls = array_map(static fn($img) => public_image_url($img['image_url'] ?? ''), $displayImages);

$pageTitle = $listing['item_name'];
$activeNav = 'home';
require __DIR__ . '/includes/header.php';
?>

<div class="container listing-detail-page">
    <a href="<?= url('home.php') ?>" class="back-link">← Back to marketplace</a>

    <div class="listing-detail-layout">
        <div class="listing-gallery">
            <div class="listing-gallery-main">
                <img src="<?= e($imageUrls[0]) ?>" alt="<?= e($listing['item_name']) ?>" id="mainImage">
            </div>
            <?php if (count($imageUrls) > 1): ?>
                <div class="listing-gallery-thumbs">
                    <?php foreach ($imageUrls as $i => $src): ?>
                        <button type="button" class="gallery-thumb <?= $i === 0 ? 'active' : '' ?>" data-src="<?= e($src) ?>">
                            <img src="<?= e($src) ?>" alt="">
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="listing-detail-panel">
            <div class="listing-detail-head">
                <?= status_badge($listing['availability_status']) ?>
                <span class="text-muted"><?= e($listing['category_name']) ?></span>
            </div>
            <h1><?= e($listing['item_name']) ?></h1>
            <p class="listing-detail-price"><?= format_money($dailyRate) ?> <span>/ day</span></p>
            <p class="listing-detail-weekly"><?= format_money((float) $listing['weekly_rate']) ?> / week</p>

            <div class="listing-detail-facts">
                <div><strong>Location</strong><span>📍 <?= e($listing['location'] ?? 'Campus') ?></span></div>
                <div><strong>Condition</strong><span><?= e($listing['item_condition']) ?></span></div>
            </div>

            <?php if ($sellerProfile): ?>
                <div class="seller-card card">
                    <div class="seller-card-head">
                        <div class="profile-avatar seller-avatar"><?= e(user_initials($sellerProfile)) ?></div>
                        <div>
                            <strong><?= e(user_display_name($sellerProfile)) ?></strong>
                            <?php if ((float) $sellerProfile['avg_rating'] > 0): ?>
                                <div class="seller-rating"><?= render_star_rating((float) $sellerProfile['avg_rating']) ?></div>
                            <?php else: ?>
                                <p class="text-muted mb-0" style="font-size:.85rem;">No reviews yet</p>
                            <?php endif; ?>
                            <p class="text-muted mb-0" style="font-size:.85rem;">
                                Member since <?= format_date($sellerProfile['created_at']) ?>
                                · <?= (int) $sellerProfile['listing_count'] ?> listing<?= (int) $sellerProfile['listing_count'] === 1 ? '' : 's' ?>
                            </p>
                        </div>
                    </div>
                    <a href="<?= url('seller.php?id=' . $lenderId) ?>" class="btn btn-sm btn-outline btn-block">View seller profile</a>
                </div>
            <?php endif; ?>

            <?php if ($isOwner): ?>
                <div class="owner-listing-actions card">
                    <p class="mb-0">This is your listing. Manage incoming requests in <a href="<?= url('dashboard.php?tab=lending') ?>">My Activity</a>.</p>
                    <div class="owner-listing-buttons">
                        <a href="<?= url('edit-listing.php?id=' . $listingId) ?>" class="btn btn-outline">Edit listing</a>
                        <?php if ($errors && ($_POST['action'] ?? '') === 'delete'): ?>
                            <ul class="form-error-list">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= e($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <form method="post" class="inline-form" onsubmit="return confirm('Remove this listing from the marketplace?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <button type="submit" class="btn btn-outline btn-danger">Delete listing</button>
                        </form>
                    </div>
                </div>
            <?php elseif ($listing['availability_status'] !== 'Available'): ?>
                <div class="alert alert-warning">This item is currently <?= e(strtolower($listing['availability_status'])) ?>. Check back later or browse similar listings.</div>
                <a href="<?= url('home.php?category=' . (int) $listing['category_id']) ?>" class="btn btn-muted btn-block">Browse similar items</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!$isOwner && $listing['availability_status'] === 'Available'): ?>
        <section class="listing-rent-section card">
            <?php if ($user): ?>
                <?php if ($errors): ?>
                    <ul class="form-error-list">
                        <?php foreach ($errors as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <form method="post" class="rent-form rent-form-wide" id="rentForm"
                      data-daily-rate="<?= e((string) $dailyRate) ?>"
                      data-currency="<?= e(app_config()['currency_symbol']) ?>">
                    <div class="rent-form-header">
                        <h2>Request to rent</h2>
                        <p class="text-muted mb-0">You act as the <strong>renter</strong>. The owner will approve your request.</p>
                    </div>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="rent">
                    <div class="rent-form-row">
                        <div class="rent-form-dates">
                            <div class="form-group">
                                <label for="start_date">Start date</label>
                                <input class="form-control rent-date" type="date" id="start_date" name="start_date" required min="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="form-group">
                                <label for="end_date">End date</label>
                                <input class="form-control rent-date" type="date" id="end_date" name="end_date" required min="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                        <div class="rent-price-estimate is-empty" id="rentPriceEstimate" aria-live="polite">
                            <span class="rent-price-label">Estimated total</span>
                            <div class="rent-price-values">
                                <span class="rent-price-placeholder" id="rentPricePlaceholder">Select dates to see your total</span>
                                <span class="rent-price-breakdown" id="rentPriceBreakdown" hidden></span>
                                <strong class="rent-price-total" id="rentPriceTotal" hidden></strong>
                            </div>
                        </div>
                        <div class="rent-form-submit">
                            <button type="submit" class="btn btn-primary">Send rental request</button>
                        </div>
                    </div>
                </form>
            <?php else: ?>
                <div class="rent-form-wide rent-form-guest">
                    <div class="rent-form-header">
                        <h2>Request to rent</h2>
                        <p class="text-muted mb-0">Login to send a rental request for this item.</p>
                    </div>
                    <div class="rent-form-row rent-form-row-guest">
                        <a href="<?= url('auth/login.php') ?>" class="btn btn-primary">Login to rent this item</a>
                        <p class="text-muted mb-0">New here? <a href="<?= url('auth/register.php') ?>">Create an account</a></p>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <div class="card listing-description">
        <h2>Details</h2>
        <p><?= nl2br(e($listing['specifications'])) ?></p>
    </div>

    <?php if ($sellerReviews): ?>
        <div class="card seller-reviews">
            <h2>Seller reviews</h2>
            <div class="review-list">
                <?php foreach ($sellerReviews as $review): ?>
                    <article class="review-item">
                        <div class="review-item-head">
                            <strong><?= e(trim($review['first_name'] . ' ' . $review['last_name'])) ?></strong>
                            <?= render_star_rating((float) $review['rating'], false) ?>
                            <span class="text-muted"><?= format_date($review['created_at']) ?></span>
                        </div>
                        <p class="mb-0"><?= e($review['comment']) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
            <?php if ((int) ($sellerProfile['review_count'] ?? 0) > count($sellerReviews)): ?>
                <a href="<?= url('seller.php?id=' . $lenderId) ?>" class="btn btn-sm btn-muted mt-1">View all reviews</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($sellerListings): ?>
        <div class="card mt-1">
            <h2>More from this seller</h2>
            <div class="listing-grid listing-grid-compact">
                <?php foreach ($sellerListings as $item): ?>
                    <a href="<?= url('listing.php?id=' . (int) $item['listing_id']) ?>" class="listing-card">
                        <div class="listing-card-image" style="background-image:url('<?= e(listing_image_url($item)) ?>')"></div>
                        <div class="listing-card-body">
                            <strong class="listing-card-price"><?= format_money((float) $item['daily_rate']) ?></strong>
                            <h3><?= e($item['item_name']) ?></h3>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
