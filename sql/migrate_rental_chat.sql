-- LipaByte — Rental chat + review uniqueness
-- Run in phpMyAdmin on your live database.

CREATE TABLE IF NOT EXISTS `rental_messages` (
  `message_id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `message_body` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`message_id`),
  KEY `request_id` (`request_id`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `rental_messages_request_fk` FOREIGN KEY (`request_id`) REFERENCES `rental_requests` (`request_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `rental_messages_sender_fk` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rental_message_reads` (
  `user_id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `last_read_message_id` int(11) NOT NULL DEFAULT 0,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`, `request_id`),
  CONSTRAINT `rental_reads_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `rental_reads_request_fk` FOREIGN KEY (`request_id`) REFERENCES `rental_requests` (`request_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Prevent duplicate reviews per user per rental (safe if index already exists).
ALTER TABLE `reviews`
  ADD UNIQUE KEY `uniq_rental_reviewer` (`rental_request_id`, `reviewer_id`);
