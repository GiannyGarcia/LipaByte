-- =============================================================================
-- LipaByte — Complete Marketplace Schema (InfinityFree / phpMyAdmin)
-- =============================================================================
-- HOW TO RUN:
--   1. Log in to phpMyAdmin on InfinityFree
--   2. Click your database in the LEFT sidebar (e.g. if0_42202321_lipabyte)
--   3. Open the SQL tab
--   4. Paste this ENTIRE file and click Go
--
-- DO NOT use "USE lipabyte_db" — InfinityFree assigns your own database name.
-- Selecting the database in the sidebar is enough.
--
-- WARNING: This DROPS and recreates all tables. All existing data will be lost.
-- Default logins after import:
--   Admin:   admin@lipabyte.edu.ph  / Admin@123
--   Student: juan.mitra@university.edu.ph / Student@123
--
-- OPTIONAL: Run sql/seed_sample_listings.sql next for bulk demo listings.
-- Includes Phase C tables (notifications, password reset).
-- =============================================================================

SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+08:00';
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `password_reset_tokens`;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `rental_requests`;
DROP TABLE IF EXISTS `listing_images`;
DROP TABLE IF EXISTS `listings`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `campuses`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------------------------------
-- Tables
-- -----------------------------------------------------------------------------

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(80) NOT NULL,
  `last_name` varchar(80) NOT NULL,
  `email` varchar(180) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `is_verified` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `avg_rating` decimal(3,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`),
  KEY `role` (`role`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `campuses` (
  `campus_id` int(11) NOT NULL AUTO_INCREMENT,
  `campus_name` varchar(120) NOT NULL,
  `university_name` varchar(180) NOT NULL,
  `city` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`campus_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(120) NOT NULL,
  `description` text DEFAULT NULL,
  `icon_label` varchar(40) NOT NULL DEFAULT 'chip',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`category_id`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `listings` (
  `listing_id` int(11) NOT NULL AUTO_INCREMENT,
  `lender_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `item_name` varchar(200) NOT NULL,
  `specifications` text NOT NULL,
  `daily_rate` decimal(10,2) NOT NULL,
  `weekly_rate` decimal(10,2) NOT NULL,
  `item_condition` varchar(60) NOT NULL,
  `location` varchar(120) NOT NULL DEFAULT 'Campus',
  `availability_status` enum('Available','Pending','Rented') NOT NULL DEFAULT 'Available',
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`listing_id`),
  KEY `lender_id` (`lender_id`),
  KEY `category_id` (`category_id`),
  KEY `availability_status` (`availability_status`),
  KEY `daily_rate` (`daily_rate`),
  KEY `location` (`location`),
  KEY `is_deleted` (`is_deleted`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `listings_lender_fk` FOREIGN KEY (`lender_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  CONSTRAINT `listings_category_fk` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `listing_images` (
  `image_id` int(11) NOT NULL AUTO_INCREMENT,
  `listing_id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `display_order` int(11) NOT NULL DEFAULT 1,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `uploaded_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`image_id`),
  KEY `listing_id` (`listing_id`),
  CONSTRAINT `listing_images_listing_fk` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`listing_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `rental_requests` (
  `request_id` int(11) NOT NULL AUTO_INCREMENT,
  `listing_id` int(11) NOT NULL,
  `renter_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `request_status` enum('Pending','Approved','Declined','Cancelled','Completed') NOT NULL DEFAULT 'Pending',
  `total_amount` decimal(10,2) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`request_id`),
  KEY `listing_id` (`listing_id`),
  KEY `renter_id` (`renter_id`),
  KEY `request_status` (`request_status`),
  CONSTRAINT `rental_requests_listing_fk` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`listing_id`) ON UPDATE CASCADE,
  CONSTRAINT `rental_requests_renter_fk` FOREIGN KEY (`renter_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL AUTO_INCREMENT,
  `rental_request_id` int(11) NOT NULL,
  `reviewer_id` int(11) NOT NULL,
  `reviewee_id` int(11) NOT NULL,
  `rating` tinyint(1) NOT NULL,
  `comment` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`review_id`),
  KEY `rental_request_id` (`rental_request_id`),
  KEY `reviewer_id` (`reviewer_id`),
  KEY `reviewee_id` (`reviewee_id`),
  CONSTRAINT `reviews_request_fk` FOREIGN KEY (`rental_request_id`) REFERENCES `rental_requests` (`request_id`) ON UPDATE CASCADE,
  CONSTRAINT `reviews_reviewer_fk` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  CONSTRAINT `reviews_reviewee_fk` FOREIGN KEY (`reviewee_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notifications` (
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

CREATE TABLE `password_reset_tokens` (
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
-- Seed: campuses
-- -----------------------------------------------------------------------------

INSERT INTO `campuses` (`campus_id`, `campus_name`, `university_name`, `city`, `is_active`) VALUES
(1, 'Lipa Campus', 'Batangas State University', 'Lipa City', 1),
(2, 'Pablo Borbon Campus', 'Batangas State University', 'Batangas City', 1),
(3, 'Main Campus', 'Kolehiyo ng Lungsod ng Lipa', 'Lipa City', 1);

-- -----------------------------------------------------------------------------
-- Seed: categories
-- -----------------------------------------------------------------------------

INSERT INTO `categories` (`category_id`, `category_name`, `description`, `icon_label`, `is_active`) VALUES
(1, 'IoT & Microcontrollers', 'Arduino, Raspberry Pi, sensors, dev kits', 'chip', 1),
(2, 'Mobile Testing', 'Smartphones for app testing', 'phone', 1),
(3, 'Laptops', 'Laptops for rendering and development', 'laptop', 1),
(4, 'GPUs', 'Graphics cards for ML and rendering', 'gpu', 1),
(5, 'VR/AR Gear', 'Headsets and immersive hardware', 'vr', 1),
(6, 'Cameras', 'DSLR and video equipment', 'camera', 1),
(7, 'Audio', 'Microphones and audio gear', 'audio', 1);

-- -----------------------------------------------------------------------------
-- Seed: users (all active, no verification gate)
-- Passwords: Admin@123 / Student@123
-- -----------------------------------------------------------------------------

INSERT INTO `users` (`user_id`, `first_name`, `last_name`, `email`, `password_hash`, `role`, `is_verified`, `is_active`, `avg_rating`) VALUES
(1, 'System', 'Administrator', 'admin@lipabyte.edu.ph', '$2y$10$yoczB3sNFi4tIBe6ayHiH.ZfX5zybfl6xzU5tAI6M7PnplgQlnWEG', 'admin', 1, 1, 5.00),
(2, 'Juan', 'Mitra', 'juan.mitra@university.edu.ph', '$2y$10$Svf470VtPgYsoAU/Kg5druO1RJH8qsLUSQbEIoAGwP82ExW2PHrUm', 'user', 1, 1, 4.90),
(3, 'Maria', 'Garcia', 'maria.garcia@university.edu.ph', '$2y$10$Svf470VtPgYsoAU/Kg5druO1RJH8qsLUSQbEIoAGwP82ExW2PHrUm', 'user', 1, 1, 4.75);

-- -----------------------------------------------------------------------------
-- Seed: marketplace listings (renter + lender ready)
-- -----------------------------------------------------------------------------

INSERT INTO `listings` (`listing_id`, `lender_id`, `category_id`, `item_name`, `specifications`, `daily_rate`, `weekly_rate`, `item_condition`, `location`, `availability_status`, `is_deleted`) VALUES
(1, 2, 1, 'Raspberry Pi 4 Model B Kit', '4GB RAM, 32GB microSD, power supply, GPIO header, heatsinks, case', 50.00, 300.00, 'Like New', 'Lipa City, Batangas', 'Available', 0),
(2, 2, 1, 'Arduino Uno R3 Starter Kit', 'Uno R3 board, breadboard, jumper wires, LEDs, resistors, USB cable', 35.00, 200.00, 'Good', 'Lipa City, Batangas', 'Rented', 0),
(3, 3, 3, 'MacBook Pro 14 M1 Pro', '16GB RAM, 512GB SSD, charger included. Ideal for Xcode and video editing.', 450.00, 2800.00, 'Like New', 'Batangas City, Batangas', 'Available', 0),
(4, 3, 4, 'NVIDIA RTX 3060 12GB', 'ASUS Dual, lightly used for ML coursework. Original box included.', 280.00, 1750.00, 'Good', 'Batangas City, Batangas', 'Available', 0),
(5, 2, 5, 'Meta Quest 2 128GB', 'VR headset with controllers and USB-C link cable for PC development.', 320.00, 1900.00, 'Good', 'Tanauan City, Batangas', 'Available', 0),
(6, 2, 2, 'Samsung Galaxy S21 Test Unit', 'Unlocked, Android 14, perfect for mobile app QA and responsive testing.', 120.00, 700.00, 'Good', 'Santo Tomas, Batangas', 'Available', 0),
(7, 3, 6, 'Canon EOS M50 Kit', '15-45mm lens, spare battery, 64GB SD card.', 350.00, 2100.00, 'Like New', 'Malvar, Batangas', 'Available', 0),
(8, 2, 7, 'Audio-Technica AT2020 USB Mic', 'Studio condenser mic with desk stand and pop filter.', 90.00, 550.00, 'Good', 'Lipa City, Batangas', 'Available', 0),
(9, 3, 1, 'ESP32 Dev Kit + Sensors Pack', 'ESP32-WROOM, DHT22, ultrasonic, OLED display, breadboard.', 45.00, 270.00, 'Like New', 'Mataas na Kahoy, Batangas', 'Available', 0);

-- -----------------------------------------------------------------------------
-- Seed: listing images (SVG placeholders bundled in assets/img/listings/)
-- -----------------------------------------------------------------------------

INSERT INTO `listing_images` (`listing_id`, `image_url`, `display_order`, `is_primary`) VALUES
(1, 'assets/img/listings/iot.svg', 1, 1),
(2, 'assets/img/listings/iot.svg', 1, 1),
(3, 'assets/img/listings/laptop.svg', 1, 1),
(4, 'assets/img/listings/gpu.svg', 1, 1),
(5, 'assets/img/listings/vr.svg', 1, 1),
(6, 'assets/img/listings/phone.svg', 1, 1),
(7, 'assets/img/listings/camera.svg', 1, 1),
(8, 'assets/img/listings/audio.svg', 1, 1),
(9, 'assets/img/listings/iot.svg', 1, 1);

-- -----------------------------------------------------------------------------
-- Seed: sample rental activity (Maria rents from Juan)
-- -----------------------------------------------------------------------------

INSERT INTO `rental_requests` (`request_id`, `listing_id`, `renter_id`, `start_date`, `end_date`, `request_status`, `total_amount`) VALUES
(1, 2, 3, '2026-06-12', '2026-06-18', 'Approved', 210.00),
(2, 1, 3, '2026-06-20', '2026-06-25', 'Pending', 250.00);

INSERT INTO `reviews` (`review_id`, `rental_request_id`, `reviewer_id`, `reviewee_id`, `rating`, `comment`) VALUES
(1, 1, 3, 2, 5, 'Kit was complete and worked perfectly for our capstone project.'),
(2, 1, 2, 3, 5, 'Renter returned the kit on time and in excellent condition.');
