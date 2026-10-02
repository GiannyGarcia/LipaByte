-- LipaByte Database Schema
-- Import this file in phpMyAdmin (InfinityFree)

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+08:00";

CREATE DATABASE IF NOT EXISTS `lipabyte_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `lipabyte_db`;

-- --------------------------------------------------------

CREATE TABLE `campuses` (
  `campus_id` int(11) NOT NULL AUTO_INCREMENT,
  `campus_name` varchar(120) NOT NULL,
  `university_name` varchar(180) NOT NULL,
  `city` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`campus_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(80) NOT NULL,
  `last_name` varchar(80) NOT NULL,
  `email` varchar(180) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `avg_rating` decimal(3,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(120) NOT NULL,
  `description` text DEFAULT NULL,
  `icon_label` varchar(40) NOT NULL DEFAULT 'chip',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`category_id`)
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
  `availability_status` enum('Available','Pending','Rented') NOT NULL DEFAULT 'Available',
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`listing_id`),
  KEY `lender_id` (`lender_id`),
  KEY `category_id` (`category_id`),
  KEY `availability_status` (`availability_status`),
  CONSTRAINT `listings_lender_fk` FOREIGN KEY (`lender_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `listings_category_fk` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`)
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
  CONSTRAINT `listing_images_listing_fk` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`listing_id`) ON DELETE CASCADE
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
  CONSTRAINT `rental_requests_listing_fk` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`listing_id`),
  CONSTRAINT `rental_requests_renter_fk` FOREIGN KEY (`renter_id`) REFERENCES `users` (`user_id`)
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
  CONSTRAINT `reviews_request_fk` FOREIGN KEY (`rental_request_id`) REFERENCES `rental_requests` (`request_id`),
  CONSTRAINT `reviews_reviewer_fk` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `reviews_reviewee_fk` FOREIGN KEY (`reviewee_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Seed data
-- --------------------------------------------------------

INSERT INTO `campuses` (`campus_id`, `campus_name`, `university_name`, `city`, `is_active`, `created_at`) VALUES
(1, 'Main Campus', 'University of the Philippines', 'Quezon City', 1, '2026-06-20 08:10:09'),
(2, 'Engineering Hub', 'De La Salle University', 'Manila', 1, '2026-06-20 08:10:09');

INSERT INTO `categories` (`category_id`, `category_name`, `description`, `icon_label`, `is_active`, `created_at`) VALUES
(1, 'IoT & Microcontrollers', 'Arduino, Raspberry Pi, sensors, dev kits', 'chip', 1, '2026-06-20 08:10:09'),
(2, 'Mobile Testing', 'Smartphones for app testing', 'phone', 1, '2026-06-20 08:10:09'),
(3, 'Laptops', 'Laptops for rendering and development', 'laptop', 1, '2026-06-20 08:10:09'),
(4, 'GPUs', 'Graphics cards for ML and rendering', 'gpu', 1, '2026-06-20 08:10:09'),
(5, 'VR/AR Gear', 'Headsets and immersive hardware', 'vr', 1, '2026-06-20 08:10:09'),
(6, 'Cameras', 'DSLR and video equipment', 'camera', 1, '2026-06-20 08:10:09'),
(7, 'Audio', 'Microphones and audio gear', 'audio', 1, '2026-06-20 08:10:09');

-- Default admin: admin@lipabyte.edu.ph / Admin@123
-- Default users: juan.mitra@university.edu.ph & maria.garcia@university.edu.ph / Student@123
INSERT INTO `users` (`user_id`, `first_name`, `last_name`, `email`, `password_hash`, `role`, `is_verified`, `is_active`, `avg_rating`, `created_at`) VALUES
(1, 'System', 'Administrator', 'admin@lipabyte.edu.ph', '$2y$10$yoczB3sNFi4tIBe6ayHiH.ZfX5zybfl6xzU5tAI6M7PnplgQlnWEG', 'admin', 1, 1, 5.00, '2026-06-20 08:10:09'),
(2, 'Juan', 'Mitra', 'juan.mitra@university.edu.ph', '$2y$10$Svf470VtPgYsoAU/Kg5druO1RJH8qsLUSQbEIoAGwP82ExW2PHrUm', 'user', 1, 1, 4.90, '2026-06-20 08:10:09'),
(3, 'Maria', 'Garcia', 'maria.garcia@university.edu.ph', '$2y$10$Svf470VtPgYsoAU/Kg5druO1RJH8qsLUSQbEIoAGwP82ExW2PHrUm', 'user', 1, 1, 4.75, '2026-06-20 08:10:09');

INSERT INTO `listings` (`listing_id`, `lender_id`, `category_id`, `item_name`, `specifications`, `daily_rate`, `weekly_rate`, `item_condition`, `availability_status`, `is_deleted`, `created_at`) VALUES
(1, 2, 1, 'Raspberry Pi 4 Model B Kit', '4GB RAM, 32GB microSD, power supply, GPIO header, heatsinks, case', 50.00, 300.00, 'Like New', 'Available', 0, '2026-06-20 08:10:09'),
(2, 2, 1, 'Arduino Uno R3 Starter Kit', 'Uno R3 board, breadboard, jumper wires, LEDs, resistors, USB cable', 35.00, 200.00, 'Good', 'Rented', 0, '2026-06-20 08:10:09');

INSERT INTO `listing_images` (`image_id`, `listing_id`, `image_url`, `display_order`, `is_primary`, `uploaded_at`) VALUES
(1, 1, '/uploads/listings/rpi4-front.jpg', 1, 1, '2026-06-20 08:10:09'),
(2, 2, '/uploads/listings/arduino-uno.jpg', 1, 1, '2026-06-20 08:10:09');

INSERT INTO `rental_requests` (`request_id`, `listing_id`, `renter_id`, `start_date`, `end_date`, `request_status`, `total_amount`, `created_at`) VALUES
(1, 2, 3, '2026-06-12', '2026-06-18', 'Approved', 210.00, '2026-06-20 08:10:09'),
(2, 1, 3, '2026-06-20', '2026-06-25', 'Pending', 250.00, '2026-06-20 08:10:09');

INSERT INTO `reviews` (`review_id`, `rental_request_id`, `reviewer_id`, `reviewee_id`, `rating`, `comment`, `created_at`) VALUES
(1, 1, 3, 2, 5, 'Kit was complete and worked perfectly for our capstone project.', '2026-06-20 08:10:09'),
(2, 1, 2, 3, 5, 'Renter returned the kit on time and in excellent condition.', '2026-06-20 08:10:09');
