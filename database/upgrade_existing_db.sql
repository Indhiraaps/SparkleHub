-- =====================================================================
-- Run this ONLY if you already have the old `cracker_shop` database
-- and want to keep its data. (For a fresh install, import sparkle_hub.sql.)
-- =====================================================================
USE `cracker_shop`;

-- New table for the Contact Us form
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Orders now remember which visitor placed them
ALTER TABLE `orders` MODIFY `session_id` varchar(255) NOT NULL DEFAULT '';

-- Image file was renamed from rainbow_flower_fountain.jp.jpg
UPDATE `product` SET `product_image_path` = 'images/rainbow_flower_fountain.jpg'
WHERE `product_image_path` = 'images/rainbow_flower_fountain.jp.jpg';

-- Old plain-text admin passwords are upgraded to secure hashes
-- automatically the next time that admin logs in.
