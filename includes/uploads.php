<?php

declare(strict_types=1);

function listing_uploads_path(): string
{
    return __DIR__ . '/../uploads/listings';
}

function public_image_url(string $path): string
{
    if ($path === '') {
        return '';
    }
    if (str_starts_with($path, 'http') || str_starts_with($path, '//')) {
        return $path;
    }
    return url(ltrim($path, '/'));
}

function listing_photo_limits(): array
{
    $config = app_config();
    return [
        'max_images' => (int) ($config['upload_max_images'] ?? 5),
        'max_bytes' => (int) ($config['upload_max_bytes'] ?? 5 * 1024 * 1024),
    ];
}

function normalize_uploaded_files(array $files): array
{
    if (!isset($files['name']) || !is_array($files['name'])) {
        if (!empty($files['tmp_name']) && is_uploaded_file($files['tmp_name'])) {
            return [$files];
        }
        return [];
    }

    $normalized = [];
    foreach ($files['name'] as $i => $name) {
        if ($name === '' || ($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $normalized[] = [
            'name' => $name,
            'type' => $files['type'][$i] ?? '',
            'tmp_name' => $files['tmp_name'][$i] ?? '',
            'error' => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$i] ?? 0,
        ];
    }
    return $normalized;
}

function validate_listing_photo(array $file, int $maxBytes): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return match ($file['error'] ?? UPLOAD_ERR_NO_FILE) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'One or more photos exceed the upload size limit.',
            UPLOAD_ERR_PARTIAL => 'A photo upload was interrupted. Please try again.',
            default => 'Photo upload failed. Please try again.',
        };
    }

    if (($file['size'] ?? 0) > $maxBytes) {
        $mb = round($maxBytes / 1048576, 1);
        return "Each photo must be {$mb} MB or smaller.";
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : '';
    if ($finfo) {
        finfo_close($finfo);
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($allowed[$mime])) {
        return 'Photos must be JPG, PNG, or WebP.';
    }

    return null;
}

function remove_listing_placeholder_images(int $listingId): void
{
    db()->prepare(
        "DELETE FROM listing_images WHERE listing_id = ? AND image_url LIKE 'assets/img/listings/%'"
    )->execute([$listingId]);
}

function count_listing_photos(int $listingId): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM listing_images WHERE listing_id = ?');
    $stmt->execute([$listingId]);
    return (int) $stmt->fetchColumn();
}

function save_listing_photos(int $listingId, int $userId, array $filesField): array
{
    $listing = get_listing_by_id($listingId);
    if (!$listing || (int) $listing['lender_id'] !== $userId) {
        return ['success' => false, 'errors' => ['Listing not found or access denied.'], 'saved' => 0];
    }

    $limits = listing_photo_limits();
    $files = normalize_uploaded_files($filesField);
    if (!$files) {
        return ['success' => true, 'saved' => 0, 'errors' => []];
    }

    $existing = count_listing_photos($listingId);

    $stmt = db()->prepare(
        "SELECT COUNT(*) FROM listing_images WHERE listing_id = ? AND image_url NOT LIKE 'assets/img/listings/%'"
    );
    $stmt->execute([$listingId]);
    $realExisting = (int) $stmt->fetchColumn();

    $slots = $limits['max_images'] - $realExisting;
    if ($slots <= 0) {
        return ['success' => false, 'errors' => ['Maximum number of photos reached.'], 'saved' => 0];
    }

    $dir = listing_uploads_path() . '/' . $listingId;
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return ['success' => false, 'errors' => ['Could not create upload folder.'], 'saved' => 0];
    }

    $errors = [];
    $saved = 0;

    $primaryStmt = db()->prepare(
        "SELECT COUNT(*) FROM listing_images
         WHERE listing_id = ? AND is_primary = 1 AND image_url NOT LIKE 'assets/img/listings/%'"
    );
    $primaryStmt->execute([$listingId]);
    $hasPrimary = (int) $primaryStmt->fetchColumn() > 0;

    $insert = db()->prepare(
        'INSERT INTO listing_images (listing_id, image_url, display_order, is_primary) VALUES (?, ?, ?, ?)'
    );

    foreach (array_slice($files, 0, $slots) as $file) {
        $error = validate_listing_photo($file, $limits['max_bytes']);
        if ($error) {
            $errors[] = $error;
            continue;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        $ext = match ($mime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        $filename = 'photo_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $dest = $dir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            $errors[] = 'Could not save one of the photos.';
            continue;
        }

        $relative = 'uploads/listings/' . $listingId . '/' . $filename;
        $order = $existing + $saved + 1;
        $isPrimary = !$hasPrimary && $saved === 0 ? 1 : 0;
        $insert->execute([$listingId, $relative, $order, $isPrimary]);
        $saved++;
        if ($isPrimary) {
            $hasPrimary = true;
        }
    }

    if ($saved > 0) {
        ensure_listing_has_primary_photo($listingId);
        remove_listing_placeholder_images($listingId);
    }

    return [
        'success' => $saved > 0 || !$errors,
        'saved' => $saved,
        'errors' => array_values(array_unique($errors)),
    ];
}

function delete_listing_photo(int $imageId, int $userId): array
{
    $stmt = db()->prepare(
        'SELECT li.*, l.lender_id FROM listing_images li
         JOIN listings l ON l.listing_id = li.listing_id
         WHERE li.image_id = ? AND l.is_deleted = 0'
    );
    $stmt->execute([$imageId]);
    $row = $stmt->fetch();
    if (!$row || (int) $row['lender_id'] !== $userId) {
        return ['success' => false, 'errors' => ['Photo not found or access denied.']];
    }

    $listingId = (int) $row['listing_id'];
    $path = $row['image_url'];
    if (str_starts_with($path, 'uploads/')) {
        $full = __DIR__ . '/../' . str_replace('/', DIRECTORY_SEPARATOR, $path);
        if (is_file($full)) {
            unlink($full);
        }
    }

    db()->prepare('DELETE FROM listing_images WHERE image_id = ?')->execute([$imageId]);

    $remaining = count_listing_photos($listingId);
    if ($remaining === 0) {
        $slug = listing_category_slug((int) get_listing_by_id($listingId)['category_id']);
        db()->prepare(
            'INSERT INTO listing_images (listing_id, image_url, display_order, is_primary) VALUES (?, ?, 1, 1)'
        )->execute([$listingId, 'assets/img/listings/' . $slug . '.svg']);
    } elseif ((int) $row['is_primary'] === 1) {
        $next = db()->prepare(
            'SELECT image_id FROM listing_images WHERE listing_id = ? ORDER BY display_order, image_id LIMIT 1'
        );
        $next->execute([$listingId]);
        $nextId = (int) $next->fetchColumn();
        if ($nextId) {
            db()->prepare('UPDATE listing_images SET is_primary = 0 WHERE listing_id = ?')->execute([$listingId]);
            db()->prepare('UPDATE listing_images SET is_primary = 1 WHERE image_id = ?')->execute([$nextId]);
        }
    }

    return ['success' => true, 'message' => 'Photo removed.'];
}

function set_listing_primary_photo(int $imageId, int $userId): array
{
    $stmt = db()->prepare(
        'SELECT li.listing_id, l.lender_id FROM listing_images li
         JOIN listings l ON l.listing_id = li.listing_id WHERE li.image_id = ?'
    );
    $stmt->execute([$imageId]);
    $row = $stmt->fetch();
    if (!$row || (int) $row['lender_id'] !== $userId) {
        return ['success' => false, 'errors' => ['Photo not found or access denied.']];
    }

    $listingId = (int) $row['listing_id'];
    db()->prepare('UPDATE listing_images SET is_primary = 0 WHERE listing_id = ?')->execute([$listingId]);
    db()->prepare('UPDATE listing_images SET is_primary = 1 WHERE image_id = ?')->execute([$imageId]);

    return ['success' => true, 'message' => 'Primary photo updated.'];
}
