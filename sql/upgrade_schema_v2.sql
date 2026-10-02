-- LipaByte — full schema upgrade (v2)
-- Run in phpMyAdmin on your existing database (local or InfinityFree).
-- Safe to run on databases that still use full_name + campus_id.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+08:00";
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------------
-- 1. users: add first_name / last_name
-- ---------------------------------------------------------------------------
SET @has_first_name = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'first_name'
);

SET @sql = IF(
    @has_first_name = 0,
    'ALTER TABLE `users`
       ADD COLUMN `first_name` varchar(80) NOT NULL DEFAULT '''' AFTER `user_id`,
       ADD COLUMN `last_name` varchar(80) NOT NULL DEFAULT '''' AFTER `first_name`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------------
-- 2. users: copy full_name into first_name / last_name
-- ---------------------------------------------------------------------------
SET @has_full_name = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'full_name'
);

SET @sql = IF(
    @has_full_name > 0,
    'UPDATE `users`
     SET
       `first_name` = TRIM(SUBSTRING_INDEX(`full_name`, '' '', 1)),
       `last_name` = TRIM(SUBSTRING(`full_name`, LOCATE('' '', `full_name`) + 1))
     WHERE (`first_name` = '''' OR `last_name` = '''')
       AND `full_name` IS NOT NULL
       AND `full_name` != ''''',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE `users`
SET `last_name` = ''
WHERE `last_name` = `first_name`;

-- ---------------------------------------------------------------------------
-- 3. users: add role / is_active / updated_at if missing
-- ---------------------------------------------------------------------------
SET @has_role = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role'
);

SET @sql = IF(
    @has_role = 0,
    'ALTER TABLE `users`
       ADD COLUMN `role` enum(''user'',''admin'') NOT NULL DEFAULT ''user'' AFTER `password_hash`,
       ADD COLUMN `is_active` tinyint(1) NOT NULL DEFAULT 1 AFTER `is_verified`,
       ADD COLUMN `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() AFTER `created_at`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_is_active = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'is_active'
);

SET @sql = IF(
    @has_is_active = 0 AND @has_role > 0,
    'ALTER TABLE `users` ADD COLUMN `is_active` tinyint(1) NOT NULL DEFAULT 1 AFTER `is_verified`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_updated_at = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'updated_at'
);

SET @sql = IF(
    @has_updated_at = 0,
    'ALTER TABLE `users` ADD COLUMN `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() AFTER `created_at`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE `users` SET `role` = 'user' WHERE `role` IS NULL OR `role` = '';
UPDATE `users` SET `is_active` = 1 WHERE `is_active` IS NULL;

-- ---------------------------------------------------------------------------
-- 4. users: remove campus link and full_name
-- ---------------------------------------------------------------------------
SET @fk_name = (
    SELECT CONSTRAINT_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND REFERENCED_TABLE_NAME = 'campuses'
    LIMIT 1
);

SET @sql = IF(
    @fk_name IS NOT NULL,
    CONCAT('ALTER TABLE `users` DROP FOREIGN KEY `', @fk_name, '`'),
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_campus_id = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'campus_id'
);

SET @sql = IF(
    @has_campus_id > 0,
    'ALTER TABLE `users` DROP COLUMN `campus_id`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    @has_full_name > 0,
    'ALTER TABLE `users` DROP COLUMN `full_name`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------------
-- 5. ensure default admin account exists (Admin@123)
-- ---------------------------------------------------------------------------
INSERT INTO `users` (`first_name`, `last_name`, `email`, `password_hash`, `role`, `is_verified`, `is_active`, `avg_rating`)
SELECT
    'System',
    'Administrator',
    'admin@lipabyte.edu.ph',
    '$2y$10$yoczB3sNFi4tIBe6ayHiH.ZfX5zybfl6xzU5tAI6M7PnplgQlnWEG',
    'admin',
    1,
    1,
    5.00
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `users` WHERE `email` = 'admin@lipabyte.edu.ph'
);

UPDATE `users`
SET `password_hash` = '$2y$10$yoczB3sNFi4tIBe6ayHiH.ZfX5zybfl6xzU5tAI6M7PnplgQlnWEG'
WHERE `email` = 'admin@lipabyte.edu.ph';

SET FOREIGN_KEY_CHECKS = 1;
