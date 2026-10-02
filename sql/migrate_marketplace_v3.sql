-- =============================================================================
-- LipaByte — Marketplace upgrade (InfinityFree / existing database)
-- =============================================================================
-- Use this if you ALREADY have tables and want to keep existing users/data.
--
-- HOW TO RUN:
--   1. In phpMyAdmin, click YOUR database in the left sidebar
--      (e.g. if0_42202321_lipabyte — NOT lipabyte_db)
--   2. SQL tab → paste this file → Go
--
-- DO NOT include "USE lipabyte_db" — it will fail on InfinityFree.
-- =============================================================================

SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+08:00';

-- 1. Remove verification gate (all users get full access on register)
UPDATE `users` SET `is_verified` = 1;

-- 2. Add location column if missing (ignore error if column already exists)
ALTER TABLE `listings` ADD COLUMN `location` varchar(120) NOT NULL DEFAULT 'Campus' AFTER `item_condition`;

-- 3. Ensure all 7 categories exist
INSERT IGNORE INTO `categories` (`category_id`, `category_name`, `description`, `icon_label`, `is_active`) VALUES
(1, 'IoT & Microcontrollers', 'Arduino, Raspberry Pi, sensors, dev kits', 'chip', 1),
(2, 'Mobile Testing', 'Smartphones for app testing', 'phone', 1),
(3, 'Laptops', 'Laptops for rendering and development', 'laptop', 1),
(4, 'GPUs', 'Graphics cards for ML and rendering', 'gpu', 1),
(5, 'VR/AR Gear', 'Headsets and immersive hardware', 'vr', 1),
(6, 'Cameras', 'DSLR and video equipment', 'camera', 1),
(7, 'Audio', 'Microphones and audio gear', 'audio', 1);

-- 4. Fix existing listing locations and images
UPDATE `listings` SET `location` = 'UP Diliman, Quezon City' WHERE `listing_id` IN (1, 2) AND (`location` IS NULL OR `location` = '' OR `location` = 'Campus');
UPDATE `listing_images` SET `image_url` = 'assets/img/listings/iot.svg' WHERE `listing_id` IN (1, 2);

-- 5. Add extra demo listings only if we still have fewer than 5 total
INSERT INTO `listings` (`lender_id`, `category_id`, `item_name`, `specifications`, `daily_rate`, `weekly_rate`, `item_condition`, `location`, `availability_status`, `is_deleted`)
SELECT 3, 3, 'MacBook Pro 14 M1 Pro', '16GB RAM, 512GB SSD, charger included.', 450.00, 2800.00, 'Like New', 'DLSU Manila', 'Available', 0
FROM DUAL WHERE (SELECT COUNT(*) FROM `listings` WHERE `is_deleted` = 0) < 5;

INSERT INTO `listings` (`lender_id`, `category_id`, `item_name`, `specifications`, `daily_rate`, `weekly_rate`, `item_condition`, `location`, `availability_status`, `is_deleted`)
SELECT 3, 4, 'NVIDIA RTX 3060 12GB', 'ASUS Dual, lightly used for ML coursework.', 280.00, 1750.00, 'Good', 'DLSU Manila', 'Available', 0
FROM DUAL WHERE (SELECT COUNT(*) FROM `listings` WHERE `is_deleted` = 0) < 5;

INSERT INTO `listings` (`lender_id`, `category_id`, `item_name`, `specifications`, `daily_rate`, `weekly_rate`, `item_condition`, `location`, `availability_status`, `is_deleted`)
SELECT 2, 5, 'Meta Quest 2 128GB', 'VR headset with controllers and USB-C link cable.', 320.00, 1900.00, 'Good', 'UP Diliman, Quezon City', 'Available', 0
FROM DUAL WHERE (SELECT COUNT(*) FROM `listings` WHERE `is_deleted` = 0) < 5;

INSERT INTO `listings` (`lender_id`, `category_id`, `item_name`, `specifications`, `daily_rate`, `weekly_rate`, `item_condition`, `location`, `availability_status`, `is_deleted`)
SELECT 2, 2, 'Samsung Galaxy S21 Test Unit', 'Unlocked, Android 14, perfect for mobile app QA.', 120.00, 700.00, 'Good', 'UP Diliman, Quezon City', 'Available', 0
FROM DUAL WHERE (SELECT COUNT(*) FROM `listings` WHERE `is_deleted` = 0) < 5;

INSERT INTO `listings` (`lender_id`, `category_id`, `item_name`, `specifications`, `daily_rate`, `weekly_rate`, `item_condition`, `location`, `availability_status`, `is_deleted`)
SELECT 3, 6, 'Canon EOS M50 Kit', '15-45mm lens, spare battery, 64GB SD card.', 350.00, 2100.00, 'Like New', 'DLSU Manila', 'Available', 0
FROM DUAL WHERE (SELECT COUNT(*) FROM `listings` WHERE `is_deleted` = 0) < 5;

INSERT INTO `listings` (`lender_id`, `category_id`, `item_name`, `specifications`, `daily_rate`, `weekly_rate`, `item_condition`, `location`, `availability_status`, `is_deleted`)
SELECT 2, 7, 'Audio-Technica AT2020 USB Mic', 'Studio condenser mic with desk stand and pop filter.', 90.00, 550.00, 'Good', 'UP Diliman, Quezon City', 'Available', 0
FROM DUAL WHERE (SELECT COUNT(*) FROM `listings` WHERE `is_deleted` = 0) < 5;

INSERT INTO `listings` (`lender_id`, `category_id`, `item_name`, `specifications`, `daily_rate`, `weekly_rate`, `item_condition`, `location`, `availability_status`, `is_deleted`)
SELECT 3, 1, 'ESP32 Dev Kit + Sensors Pack', 'ESP32-WROOM, DHT22, ultrasonic, OLED display, breadboard.', 45.00, 270.00, 'Like New', 'DLSU Manila', 'Available', 0
FROM DUAL WHERE (SELECT COUNT(*) FROM `listings` WHERE `is_deleted` = 0) < 5;

-- 6. Add images for any listing that has none
INSERT INTO `listing_images` (`listing_id`, `image_url`, `display_order`, `is_primary`)
SELECT l.`listing_id`,
  CASE l.`category_id`
    WHEN 1 THEN 'assets/img/listings/iot.svg'
    WHEN 2 THEN 'assets/img/listings/phone.svg'
    WHEN 3 THEN 'assets/img/listings/laptop.svg'
    WHEN 4 THEN 'assets/img/listings/gpu.svg'
    WHEN 5 THEN 'assets/img/listings/vr.svg'
    WHEN 6 THEN 'assets/img/listings/camera.svg'
    WHEN 7 THEN 'assets/img/listings/audio.svg'
    ELSE 'assets/img/listings/default.svg'
  END,
  1, 1
FROM `listings` l
WHERE l.`is_deleted` = 0
  AND NOT EXISTS (SELECT 1 FROM `listing_images` li WHERE li.`listing_id` = l.`listing_id`);
