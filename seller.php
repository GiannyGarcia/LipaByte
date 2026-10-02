<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$sellerId = (int) ($_GET['id'] ?? 0);
$profile = $sellerId > 0 ? get_lender_public_profile($sellerId) : null;

if (!$profile) {
    flash('error', 'Seller not found.');
    redirect('home.php');
}

$listings = get_lender_other_listings($sellerId, 0, 12);
$reviews = get_user_reviews_received($sellerId, 20);

$pageTitle = user_display_name($profile);
$activeNav = 'home';
require __DIR__ . '/includes/header.php';
?>

<div class="container">
    <a href="<?= url('home.php') ?>" class="back-link">← Back to marketplace</a>

    <div class="card seller-profile-card">
        <div class="profile-hero">
            <div class="profile-avatar seller-avatar-lg"><?= e(user_initials($profile)) ?></div>
            <div>
                <h1 style="margin:0;"><?= e(user_display_name($profile)) ?></h1>
                <?php if ((float) $profile['avg_rating'] > 0): ?>
                    <div class="seller-rating"><?= render_star_rating((float) $profile['avg_rating']) ?>
                        <span class="text-muted">(<?= (int) $profile['review_count'] ?> review<?= (int) $profile['review_count'] === 1 ? '' : 's' ?>)</span>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">No reviews yet</p>
                <?php endif; ?>
                <p class="text-muted mb-0">Member since <?= format_date($profile['created_at']) ?></p>
            </div>
        </div>
        <div class="stats-grid">
            <div class="stat-card">
                <strong><?= (int) $profile['listing_count'] ?></strong>
                <span>Active listings</span>
            </div>
            <div class="stat-card">
                <strong><?= number_format((float) $profile['avg_rating'], 1) ?></strong>
                <span>Avg rating</span>
            </div>
            <div class="stat-card">
                <strong><?= (int) $profile['review_count'] ?></strong>
                <span>Reviews</span>
            </div>
        </div>
    </div>

    <?php if ($listings): ?>
        <div class="card mt-1">
            <h2 style="margin-top:0;">Listings</h2>
            <div class="listing-grid listing-grid-compact">
                <?php foreach ($listings as $item): ?>
                    <a href="<?= url('listing.php?id=' . (int) $item['listing_id']) ?>" class="listing-card">
                        <div class="listing-card-image" style="background-image:url('<?= e(listing_image_url($item)) ?>')"></div>
                        <div class="listing-card-body">
                            <strong class="listing-card-price"><?= format_money((float) $item['daily_rate']) ?></strong>
                            <h3><?= e($item['item_name']) ?></h3>
                            <span class="listing-card-loc"><?= e($item['location'] ?? 'Campus') ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($reviews): ?>
        <div class="card mt-1 seller-reviews">
            <h2 style="margin-top:0;">Reviews</h2>
            <div class="review-list">
                <?php foreach ($reviews as $review): ?>
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
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
