<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$categories = get_active_categories();
$errors = [];
$user = current_user();

if (is_post()) {
    verify_csrf();
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

    $result = create_listing((int) $user['user_id'], $data);
    if ($result['success']) {
        $listingId = (int) $result['listing_id'];
        $uploadResult = save_listing_photos($listingId, (int) $user['user_id'], $_FILES['photos'] ?? []);
        clear_old();
        if ($uploadResult['saved'] > 0) {
            flash('success', 'Your devices are now listed on the marketplace!');
        } elseif (!empty($uploadResult['errors'])) {
            flash('success', 'Listing published, but some photos could not be uploaded.');
            flash('error', implode(' ', $uploadResult['errors']));
        } else {
            flash('success', 'Your devices are now listed on the marketplace!');
        }
        redirect('listing.php?id=' . $listingId);
    }
    $errors = $result['errors'];
}

$pageTitle = 'List Devices';
$activeNav = 'list';
require __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="page-head">
        <h1>List your devices</h1>
        <p>You act as the <strong>owner/lender</strong>. Students can browse your listing and send borrow requests.</p>
    </div>

    <div class="role-banner role-banner-lend">
        <span>📦</span>
        <div>
            <strong>Lender mode</strong>
            <p>Set your daily rate, location, and item details. You keep ownership — renters borrow for a period you approve.</p>
        </div>
    </div>

    <?php if ($errors): ?>
        <ul class="form-error-list">
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" class="card list-form" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="item_name">Item name</label>
            <input class="form-control" type="text" id="item_name" name="item_name" value="<?= old('item_name') ?>" required placeholder="Raspberry Pi 4 Kit, RTX 3060, etc.">
        </div>

        <div class="form-group">
            <label for="category_id">Category</label>
            <select class="form-select" id="category_id" name="category_id" required>
                <option value="">Select category</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int) $cat['category_id'] ?>" <?= old('category_id') === (string) $cat['category_id'] ? 'selected' : '' ?>>
                        <?= e($cat['category_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="specifications">Description &amp; specs</label>
            <textarea class="form-control" id="specifications" name="specifications" rows="5" required placeholder="Include model, condition notes, what's included in the kit…"><?= old('specifications') ?></textarea>
        </div>

        <div class="form-group">
            <label for="location">Pickup location</label>
            <input class="form-control" type="text" id="location" name="location" value="<?= old('location') ?>" required placeholder="e.g. Lipa City, Batangas">
            <p class="form-hint">Where renters can pick up and return the item.</p>
        </div>

        <div class="filter-row">
            <div class="form-group">
                <label for="daily_rate">Daily rate (₱)</label>
                <input class="form-control" type="number" id="daily_rate" name="daily_rate" min="1" step="1" value="<?= old('daily_rate') ?>" required>
            </div>
            <div class="form-group">
                <label for="weekly_rate">Weekly rate (₱)</label>
                <input class="form-control" type="number" id="weekly_rate" name="weekly_rate" min="1" step="1" value="<?= old('weekly_rate') ?>" placeholder="Optional">
            </div>
        </div>

        <div class="form-group">
            <label for="item_condition">Condition</label>
            <select class="form-select" id="item_condition" name="item_condition" required>
                <option value="">Select condition</option>
                <?php foreach (['Like New', 'Good', 'Fair', 'Used'] as $cond): ?>
                    <option value="<?= e($cond) ?>" <?= old('item_condition') === $cond ? 'selected' : '' ?>><?= e($cond) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php $photoLimits = listing_photo_limits(); ?>
        <div class="form-group">
            <label for="photos">Photos</label>
            <input class="form-control" type="file" id="photos" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple>
            <p class="form-hint">Up to <?= (int) $photoLimits['max_images'] ?> photos (JPG, PNG, WebP). First photo becomes the cover image.</p>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Publish listing</button>
    </form>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
