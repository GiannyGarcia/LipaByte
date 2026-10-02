-- Promote uploaded photos to primary cover when placeholders were removed without updating is_primary.
-- Safe to run multiple times.

UPDATE listing_images SET is_primary = 0
WHERE image_url LIKE 'assets/img/listings/%';

UPDATE listing_images li
JOIN (
    SELECT listing_id, MIN(image_id) AS image_id
    FROM listing_images
    WHERE image_url NOT LIKE 'assets/img/listings/%'
    GROUP BY listing_id
) best ON best.listing_id = li.listing_id AND best.image_id = li.image_id
SET li.is_primary = 1;

DELETE FROM listing_images
WHERE image_url LIKE 'assets/img/listings/%'
  AND listing_id IN (
    SELECT listing_id FROM (
        SELECT listing_id FROM listing_images
        WHERE image_url NOT LIKE 'assets/img/listings/%'
    ) AS uploads
  );
