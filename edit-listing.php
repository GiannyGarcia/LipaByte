<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$listingId = (int) ($_GET['id'] ?? 0);
$listing = $listingId > 0 ? get_listing_by_id($listingId) : null;
$user = current_user();
$userId = (int) $user['user_id'];
$errors = [];
$photoLimits = listing_photo_limits();

if (!$listing || (int) $listing['lender_id'] !== $userId) {
    flash('error', 'Listing not found or access denied.');
    redirect('dashboard.php?tab=lending');
}

$categories = get_active_categories();
$existingPhotos = get_listing_images($listingId);
$realPhotoCount = 0;
foreach ($existingPhotos as $photo) {
    if (!str_starts_with($photo['image_url'], 'assets/img/listings/')) {
        $realPhotoCount++;
    }
}

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? 'save';

    if ($action === 'delete_photo') {
        $result = delete_listing_photo((int) ($_POST['image_id'] ?? 0), $userId);
        flash($result['success'] ? 'success' : 'error', $result['message'] ?? ($result['errors'][0] ?? 'Action failed.'));
        redirect('edit-listing.php?id=' . $listingId);
    }

    if ($action === 'set_primary') {
        $result = set_listing_primary_photo((int) ($_POST['image_id'] ?? 0), $userId);
        flash($result['success'] ? 'success' : 'error', $result['message'] ?? ($result['errors'][0] ?? 'Action failed.'));
        redirect('edit-listing.php?id=' . $listingId);
    }

    $data = [
        'item_name' => trim($_POST['item_name'] ?? ''),
        'specifications' => trim($_POST['specifications'] ?? ''),
        'location' => trim($_POST['location'] ?? ''),
        'category_id' => (int) ($_POST['category_id'] ?? 0),
        'daily_rate' => $_POST['daily_rate'] ?? '',
        'weekly_rate' => $_POST['weekly_rate'] ?? '',
        'item_condition' => trim($_POST['item_condition'] ?? ''),
    ];
    store_old($data);

    $result = update_listing($listingId, $userId, $data);
    if (!$result['success']) {
        $errors = $result['errors'];
    } else {
        $uploadResult = save_listing_photos($listingId, $userId, $_FILES['photos'] ?? []);
        clear_old();
        if (!empty($uploadResult['errors']) && $uploadResult['saved'] === 0) {
            flash('success', $result['message']);
            flash('error', implode(' ', $uploadResult['errors']));
            redirect('edit-listing.php?id=' . $listingId);
        }
        flash('success', $uploadResult['saved'] > 0 ? 'Listing and photos updated.' : $result['message']);
        redirect('listing.php?id=' . $listingId);
    }
}

$existingPhotos = get_listing_images($listingId);
$realPhotoCount = 0;
foreach ($existingPhotos as $photo) {
    if (!str_starts_with($photo['image_url'], 'assets/img/listings/')) {
        $realPhotoCount++;
    }
}

$pageTitle = 'Edit Listing';
$activeNav = 'dashboard';
require __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="page-head">
        <h1>Edit listing</h1>
        <p>Update details for <strong><?= e($listing['item_name']) ?></strong>.</p>
    </div>

    <?php if ($errors): ?>
        <ul class="form-error-list">
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if ($existingPhotos): ?>
        <div class="card mb-1">
            <h2 style="margin-top:0;">Photos</h2>
            <div class="listing-photo-grid">
                <?php foreach ($existingPhotos as $photo): ?>
                    <?php $isPlaceholder = str_starts_with($photo['image_url'], 'assets/img/listings/'); ?>
                    <div class="listing-photo-item">
                        <img src="<?= e(public_image_url($photo['image_url'])) ?>" alt="">
                        <?php if ((int) $photo['is_primary'] === 1): ?>
                            <span class="listing-photo-badge">Cover</span>
                        <?php endif; ?>
                        <?php if (!$isPlaceholder): ?>
                            <div class="listing-photo-actions">
                                <?php if ((int) $photo['is_primary'] !== 1): ?>
                                    <form method="post" class="inline-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="set_primary">
                                        <input type="hidden" name="image_id" value="<?= (int) $photo['image_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-muted">Set cover</button>
                                    </form>
                                <?php endif; ?>
                                <form method="post" class="inline-form" onsubmit="return confirm('Remove this photo?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete_photo">
                                    <input type="hidden" name="image_id" value="<?= (int) $photo['image_id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline btn-danger">Remove</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <form method="post" class="card list-form" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">

        <div class="form-group">
            <label for="item_name">Item name</label>
            <input class="form-control" type="text" id="item_name" name="item_name" value="<?= e(old('item_name', $listing['item_name'])) ?>" required>
        </div>

        <div class="form-group">
            <label for="category_id">Category</label>
            <select class="form-select" id="category_id" name="category_id" required>
                <option value="">Select category</option>
                <?php foreach ($categories as $cat): ?>
                    <?php $selected = old('category_id', (string) $listing['category_id']) === (string) $cat['category_id']; ?>
                    <option value="<?= (int) $cat['category_id'] ?>" <?= $selected ? 'selected' : '' ?>>
                        <?= e($cat['category_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="specifications">Description &amp; specs</label>
            <textarea class="form-control" id="specifications" name="specifications" rows="5" required><?= e(old('specifications', $listing['specifications'])) ?></textarea>
        </div>

        <div class="form-group">
            <label for="location">Pickup location</label>
            <input class="form-control" type="text" id="location" name="location" value="<?= e(old('location', $listing['location'] ?? '')) ?>" required>
        </div>

        <div class="filter-row">
            <div class="form-group">
                <label for="daily_rate">Daily rate (₱)</label>
                <input class="form-control" type="number" id="daily_rate" name="daily_rate" min="1" step="1" value="<?= e(old('daily_rate', (string) $listing['daily_rate'])) ?>" required>
            </div>
            <div class="form-group">
                <label for="weekly_rate">Weekly rate (₱)</label>
                <input class="form-control" type="number" id="weekly_rate" name="weekly_rate" min="1" step="1" value="<?= e(old('weekly_rate', (string) $listing['weekly_rate'])) ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="item_condition">Condition</label>
            <select class="form-select" id="item_condition" name="item_condition" required>
                <option value="">Select condition</option>
                <?php foreach (['Like New', 'Good', 'Fair', 'Used'] as $cond): ?>
                    <?php $selected = old('item_condition', $listing['item_condition']) === $cond; ?>
                    <option value="<?= e($cond) ?>" <?= $selected ? 'selected' : '' ?>><?= e($cond) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if ($realPhotoCount < $photoLimits['max_images']): ?>
            <div class="form-group">
                <label for="photos">Add photos</label>
                <input class="form-control" type="file" id="photos" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple>
                <p class="form-hint"><?= $photoLimits['max_images'] - $realPhotoCount ?> more photo slot(s) available.</p>
            </div>
        <?php endif; ?>

        <div class="form-actions-row">
            <button type="submit" class="btn btn-primary">Save changes</button>
            <a href="<?= url('listing.php?id=' . $listingId) ?>" class="btn btn-muted">Cancel</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
