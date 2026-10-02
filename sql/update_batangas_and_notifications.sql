-- =============================================================================
-- LipaByte — Live database upgrade (InfinityFree / phpMyAdmin)
-- =============================================================================
-- HOW TO RUN:
--   1. phpMyAdmin → select if0_42202321_lipabyte (left sidebar)
--   2. SQL tab → paste this ENTIRE file → Go
--
-- Safe for existing data: does NOT drop users or listings.
-- Fixes: notifications + password reset tables, Batangas listing locations.
-- =============================================================================

SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+08:00';

-- -----------------------------------------------------------------------------
-- 1. Phase C: notifications & forgot-password (fixes Notifications page)
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `notifications` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(180) NOT NULL,
  `message` text NOT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`notification_id`),
  KEY `user_id` (`user_id`),
  KEY `is_read` (`is_read`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `notifications_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `token_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`token_id`),
  KEY `user_id` (`user_id`),
  KEY `token_hash` (`token_hash`),
  KEY `expires_at` (`expires_at`),
  CONSTRAINT `password_reset_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3. Normalize all listing locations to Lipa / Batangas area
--    Distributes pickups across nearby cities/municipalities by listing_id.
-- -----------------------------------------------------------------------------

UPDATE `listings`
SET `location` = ELT(
  ((`listing_id` - 1) MOD 12) + 1,
  'Lipa City, Batangas',
  'Batangas City, Batangas',
  'Tanauan City, Batangas',
  'Santo Tomas, Batangas',
  'Malvar, Batangas',
  'Mataas na Kahoy, Batangas',
  'San Jose, Batangas',
  'Rosario, Batangas',
  'Ibaan, Batangas',
  'Padre Garcia, Batangas',
  'Balete, Batangas',
  'Talisay, Batangas'
)
WHERE `is_deleted` = 0;

-- Fix any legacy placeholder values that should not remain
UPDATE `listings`
SET `location` = 'Lipa City, Batangas'
WHERE `is_deleted` = 0
  AND (
    `location` IS NULL
    OR TRIM(`location`) = ''
    OR `location` IN ('Campus', 'UP Diliman, Quezon City', 'Quezon City', 'Manila', 'Metro Manila')
    OR `location` NOT LIKE '%Batangas%'
  );

-- -----------------------------------------------------------------------------
-- 4. Campuses (Batangas schools) — insert only if missing
-- -----------------------------------------------------------------------------

INSERT INTO `campuses` (`campus_id`, `campus_name`, `university_name`, `city`, `is_active`)
SELECT 1, 'Lipa Campus', 'Batangas State University', 'Lipa City', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `campuses` WHERE `campus_id` = 1);

INSERT INTO `campuses` (`campus_id`, `campus_name`, `university_name`, `city`, `is_active`)
SELECT 2, 'Pablo Borbon Campus', 'Batangas State University', 'Batangas City', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `campuses` WHERE `campus_id` = 2);

INSERT INTO `campuses` (`campus_id`, `campus_name`, `university_name`, `city`, `is_active`)
SELECT 3, 'Main Campus', 'Kolehiyo ng Lungsod ng Lipa', 'Lipa City', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `campuses` WHERE `campus_id` = 3);

-- Done. Refresh Notifications page and marketplace listings on your site.
