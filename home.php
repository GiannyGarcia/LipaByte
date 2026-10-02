<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$filters = [
    'q' => trim($_GET['q'] ?? ''),
    'category_id' => (int) ($_GET['category'] ?? 0) ?: null,
    'status' => trim($_GET['status'] ?? ''),
    'min_price' => $_GET['min_price'] ?? '',
    'max_price' => $_GET['max_price'] ?? '',
    'location' => trim($_GET['location'] ?? ''),
    'sort' => $_GET['sort'] ?? 'newest',
];

$perPage = (int) app_config()['listings_per_page'];
$page = max(1, (int) ($_GET['page'] ?? 1));
$totalListings = count_listings($filters);
$pagination = pagination_meta($totalListings, $page, $perPage);
[$limit, $offset] = paginate_params($pagination['page'], $perPage);

$categories = get_active_categories();
$listings = search_listings($filters, $limit, $offset);
$user = current_user();
$hasActiveFilters = $filters['q'] !== ''
    || !empty($filters['category_id'])
    || $filters['status'] !== ''
    || $filters['min_price'] !== ''
    || $filters['max_price'] !== ''
    || $filters['location'] !== ''
    || $filters['sort'] !== 'newest';

$paginationQuery = array_filter([
    'q' => $filters['q'] ?: null,
    'category' => $filters['category_id'] ?: null,
    'status' => $filters['status'] ?: null,
    'min_price' => $filters['min_price'] !== '' ? $filters['min_price'] : null,
    'max_price' => $filters['max_price'] !== '' ? $filters['max_price'] : null,
    'location' => $filters['location'] ?: null,
    'sort' => $filters['sort'] !== 'newest' ? $filters['sort'] : null,
], static fn($v) => $v !== null && $v !== '');

$pageTitle = 'Marketplace';
$activeNav = 'home';
$marketplaceLayout = true;
require __DIR__ . '/includes/header.php';
?>

<div class="marketplace-page">
    <div class="mp-topbar">
        <div class="mp-topbar-inner">
            <form method="get" action="<?= url('home.php') ?>" class="mp-search-form">
                <?php if (!empty($filters['category_id'])): ?>
                    <input type="hidden" name="category" value="<?= (int) $filters['category_id'] ?>">
                <?php endif; ?>
                <?php if ($filters['status'] !== ''): ?>
                    <input type="hidden" name="status" value="<?= e($filters['status']) ?>">
                <?php endif; ?>
                <?php if ($filters['min_price'] !== ''): ?>
                    <input type="hidden" name="min_price" value="<?= e((string) $filters['min_price']) ?>">
                <?php endif; ?>
                <?php if ($filters['max_price'] !== ''): ?>
                    <input type="hidden" name="max_price" value="<?= e((string) $filters['max_price']) ?>">
                <?php endif; ?>
                <?php if ($filters['location'] !== ''): ?>
                    <input type="hidden" name="location" value="<?= e($filters['location']) ?>">
                <?php endif; ?>
                <?php if ($filters['sort'] !== 'newest'): ?>
                    <input type="hidden" name="sort" value="<?= e($filters['sort']) ?>">
                <?php endif; ?>
                <svg class="mp-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3-3"/></svg>
                <input class="mp-search-input" type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Search devices in Lipa &amp; Batangas…" autocomplete="off">
            </form>

            <div class="mp-topbar-actions">
                <button type="button" class="mp-icon-btn" id="toggleFilters" aria-label="Filters">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
                    <?php if ($hasActiveFilters): ?><span class="mp-filter-dot"></span><?php endif; ?>
                </button>
                <a href="<?= url(is_logged_in() ? 'list.php' : 'auth/login.php') ?>" class="mp-sell-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                    <span class="hide-mobile">List</span>
                </a>
            </div>
        </div>
    </div>

    <div class="mp-subbar">
        <div class="mp-subbar-inner">
            <div class="category-pills">
                <a href="<?= url('home.php') ?>" class="category-pill <?= empty($filters['category_id']) ? 'active' : '' ?>">All</a>
                <?php foreach ($categories as $cat): ?>
                    <a href="<?= url('home.php?' . http_build_query(array_filter([
                        'q' => $filters['q'] ?: null,
                        'category' => (int) $cat['category_id'],
                        'status' => $filters['status'] ?: null,
                        'location' => $filters['location'] ?: null,
                        'sort' => $filters['sort'] !== 'newest' ? $filters['sort'] : null,
                    ]))) ?>" class="category-pill <?= ($filters['category_id'] ?? 0) === (int) $cat['category_id'] ? 'active' : '' ?>">
                        <?= e($cat['category_name']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <span class="mp-count">
                <?php if ($totalListings === 0): ?>
                    0 results
                <?php else: ?>
                    <?= (int) $pagination['from'] ?>–<?= (int) $pagination['to'] ?> of <?= $totalListings ?>
                <?php endif; ?>
            </span>
        </div>
    </div>

    <div class="mp-container">
        <div class="marketplace-how card" id="marketplaceHow">
            <div class="marketplace-how-steps" role="tablist" aria-label="How LipaByte works">
                <button type="button" class="marketplace-how-step" role="tab" aria-selected="false" id="howStep1" aria-controls="marketplaceHowDetail"
                    data-detail="Search IoT kits, laptops, GPUs, and testing devices from students in Lipa City, Batangas City, and nearby areas. Filter by category, price, and pickup location.">
                    <span>1</span> Browse devices in Lipa &amp; Batangas
                </button>
                <span class="marketplace-how-arrow" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 6l6 6-6 6"/></svg>
                </span>
                <button type="button" class="marketplace-how-step" role="tab" aria-selected="false" id="howStep2" aria-controls="marketplaceHowDetail"
                    data-detail="Open a listing, choose your dates, and send a borrow request. The owner reviews it before your reservation is confirmed.">
                    <span>2</span> Send a borrow request
                </button>
                <span class="marketplace-how-arrow" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 6l6 6-6 6"/></svg>
                </span>
                <button type="button" class="marketplace-how-step" role="tab" aria-selected="false" id="howStep3" aria-controls="marketplaceHowDetail"
                    data-detail="When the owner approves, coordinate pickup at their listed location. You'll see updates in My Activity.">
                    <span>3</span> Owner approves &amp; you pick up
                </button>
                <span class="marketplace-how-arrow" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 6l6 6-6 6"/></svg>
                </span>
                <button type="button" class="marketplace-how-step" role="tab" aria-selected="false" id="howStep4" aria-controls="marketplaceHowDetail"
                    data-detail="Return the device on time, mark the borrow complete, and leave a review to help other students choose trusted owners.">
                    <span>4</span> Return &amp; leave a review
                </button>
            </div>
            <div class="marketplace-how-detail" id="marketplaceHowDetail" role="tabpanel" aria-labelledby="howStep1" hidden>
                <p></p>
            </div>
        </div>

        <?php if (!$listings): ?>
            <div class="marketplace-empty">
                <h3>No listings found</h3>
                <p>Try different keywords or filters.</p>
                <?php if (is_logged_in()): ?>
                    <a href="<?= url('list.php') ?>" class="btn btn-primary btn-sm">List your devices</a>
                <?php else: ?>
                    <a href="<?= url('auth/register.php') ?>" class="btn btn-primary btn-sm">Register &amp; list devices</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="listing-grid">
                <?php foreach ($listings as $item): ?>
                    <?php
                    $thumbSlug = listing_category_slug((int) $item['category_id']);
                    $coverUrl = listing_image_url($item);
                    $hasRealPhoto = !empty($item['primary_image'])
                        && !is_listing_placeholder_image((string) $item['primary_image']);
                    ?>
                    <a href="<?= url('listing.php?id=' . (int) $item['listing_id']) ?>" class="listing-card">
                        <div class="listing-card-image <?= $hasRealPhoto ? 'listing-has-photo' : 'listing-thumb-' . e($thumbSlug) ?>" style="background-image:url('<?= e($coverUrl) ?>')">
                            <?php if ($item['availability_status'] !== 'Available'): ?>
                                <span class="listing-card-badge listing-status-<?= strtolower(e($item['availability_status'])) ?>">
                                    <?= e($item['availability_status']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="listing-card-body">
                            <strong class="listing-card-price"><?= format_money((float) $item['daily_rate']) ?></strong>
                            <h3><?= e($item['item_name']) ?></h3>
                            <span class="listing-card-loc"><?= e($item['location'] ?? 'Campus') ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            <?= render_pagination($pagination, $paginationQuery) ?>
        <?php endif; ?>
    </div>

    <div class="mp-drawer-backdrop" id="filterBackdrop" hidden></div>
    <aside class="mp-drawer" id="filters" aria-hidden="true">
        <div class="mp-drawer-head">
            <h2>Filters</h2>
            <button type="button" class="mp-drawer-close" id="closeFilters" aria-label="Close">&times;</button>
        </div>
        <form method="get" action="<?= url('home.php') ?>" class="mp-drawer-form">
            <div class="form-group">
                <label for="drawer-q">Search</label>
                <input class="form-control form-control-sm" type="search" id="drawer-q" name="q" value="<?= e($filters['q']) ?>" placeholder="Keywords…">
            </div>
            <div class="form-group">
                <label for="drawer-category">Category</label>
                <select class="form-select form-control-sm" id="drawer-category" name="category">
                    <option value="">All</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int) $cat['category_id'] ?>" <?= ($filters['category_id'] ?? 0) === (int) $cat['category_id'] ? 'selected' : '' ?>>
                            <?= e($cat['category_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="drawer-location">Location</label>
                <input class="form-control form-control-sm" type="text" id="drawer-location" name="location" value="<?= e($filters['location']) ?>" placeholder="Lipa City, Batangas City…">
            </div>
            <div class="form-group">
                <label for="drawer-status">Availability</label>
                <select class="form-select form-control-sm" id="drawer-status" name="status">
                    <option value="">Any</option>
                    <option value="Available" <?= $filters['status'] === 'Available' ? 'selected' : '' ?>>Available</option>
                    <option value="Pending" <?= $filters['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="Rented" <?= $filters['status'] === 'Rented' ? 'selected' : '' ?>>Rented</option>
                </select>
            </div>
            <div class="filter-row">
                <div class="form-group">
                    <label for="drawer-min">Min ₱/day</label>
                    <input class="form-control form-control-sm" type="number" id="drawer-min" name="min_price" min="0" value="<?= e((string) $filters['min_price']) ?>">
                </div>
                <div class="form-group">
                    <label for="drawer-max">Max ₱/day</label>
                    <input class="form-control form-control-sm" type="number" id="drawer-max" name="max_price" min="0" value="<?= e((string) $filters['max_price']) ?>">
                </div>
            </div>
            <div class="form-group">
                <label for="drawer-sort">Sort</label>
                <select class="form-select form-control-sm" id="drawer-sort" name="sort">
                    <option value="newest" <?= $filters['sort'] === 'newest' ? 'selected' : '' ?>>Newest</option>
                    <option value="price_low" <?= $filters['sort'] === 'price_low' ? 'selected' : '' ?>>Price: low → high</option>
                    <option value="price_high" <?= $filters['sort'] === 'price_high' ? 'selected' : '' ?>>Price: high → low</option>
                    <option value="name" <?= $filters['sort'] === 'name' ? 'selected' : '' ?>>Name A–Z</option>
                </select>
            </div>
            <div class="mp-drawer-actions">
                <button type="submit" class="btn btn-primary btn-sm">Show results</button>
                <a href="<?= url('home.php') ?>" class="btn btn-muted btn-sm">Reset</a>
            </div>
        </form>
    </aside>
</div>

<?php
$hideBottomNav = false;
require __DIR__ . '/includes/footer.php';
