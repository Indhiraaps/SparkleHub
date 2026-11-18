-- =====================================================================
-- Sparkle Hub - database setup
-- Creates the `cracker_shop` database with tables and demo data.
--
-- Import with phpMyAdmin (Import tab) or:
--   mysql -u root -p < database/sparkle_hub.sql
--
-- Demo admin login:  username: admin   password: admin123
-- (change it after your first login)
-- =====================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+05:30";

CREATE DATABASE IF NOT EXISTS `cracker_shop` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `cracker_shop`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `cart`, `order_items`, `orders`, `product`, `category`, `discount`, `login`, `contact_messages`;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- Product categories
-- ---------------------------------------------------------------------
CREATE TABLE `category` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `category` (`category_id`, `category_name`) VALUES
(1, 'GROUND CHAKKAR'),
(2, 'FLOWER POTS'),
(3, 'ROCKETS'),
(10, 'SPARKLERS'),
(11, 'FANCY ITEMS'),
(12, 'BOMBS'),
(13, 'CHAKKARS');

-- ---------------------------------------------------------------------
-- Products (cost = MRP, stock_status = units in stock)
-- ---------------------------------------------------------------------
CREATE TABLE `product` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_name` varchar(255) NOT NULL,
  `category` int(11) NOT NULL,
  `product_image_path` varchar(255) DEFAULT NULL,
  `cost` decimal(10,2) NOT NULL,
  `stock_status` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`product_id`),
  KEY `category` (`category`),
  CONSTRAINT `product_ibfk_1` FOREIGN KEY (`category`) REFERENCES `category` (`category_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `product` (`product_id`, `product_name`, `category`, `product_image_path`, `cost`, `stock_status`) VALUES
(24, 'Red Thunder Rocket', 3, 'images/rocket_red.jpg', 120.00, 15),
(25, 'Mega Power Rocket', 3, 'images/rocket_big.jpg', 250.00, 4),
(26, 'Jumbo Sky Rocket', 3, 'images/jumbo_rocket.jpg', 350.00, 9),
(27, 'Sky Shot Launcher', 3, 'images/sky_shot.jpg', 180.00, 0),
(31, 'Small Flower Pot', 2, 'images/flowerpot_small.jpg', 70.00, 22),
(35, 'Small Ground Chakkar', 1, 'images/ground_chakkar_small.jpg', 60.00, 37),
(36, 'Big Ground Chakkar', 1, 'images/ground_chakkar_big.jpg', 120.00, 55),
(48, 'Golden Sparkle Stick', 10, 'images/sparkler_gold.jpg', 40.00, 72),
(49, 'Multicolor Sparkler', 10, 'images/sparkler_multicolor.jpg', 55.00, 3),
(50, 'Pencil Sparkler', 10, 'images/pencil_sparkler.jpg', 30.00, 15),
(52, 'Big Flower Pot', 2, 'images/flowerpot_big.jpg', 140.00, 12),
(53, 'Blooming Flower Fountain', 2, 'images/blooming_flower.jpg', 160.00, 23),
(54, 'Night Fountain Sparkler', 2, 'images/fountain_night.jpg', 200.00, 19),
(57, 'Flying Butterfly Wheel', 13, 'images/butterfly_cracker.jpg', 90.00, 20),
(58, 'Atom Bomb Classic', 12, 'images/atom_bomb.jpg', 50.00, 27),
(59, 'Laxmi Bomb', 12, 'images/laxmi_bomb.jpg', 80.00, 36),
(60, 'Chocolate Bomb Pack', 12, 'images/chocolate_bomb.jpg', 45.00, 4),
(61, '100-Wala Garland Crackers', 12, 'images/garland_crackers.jpg', 200.00, 50),
(62, 'Kids Pop Gun', 11, 'images/kids_popgun.jpg', 60.00, 10),
(63, 'Color Twisters', 11, 'images/twisters.jpg', 90.00, 15),
(64, 'Big Whistle Rocket', 3, 'images/rocket_whistle_big.jpg', 250.00, 20),
(65, 'Rainbow Flower Fountain', 2, 'images/rainbow_flower_fountain.jpg', 220.00, 12),
(66, 'Green Sparkler', 10, 'images/sparkler_green.jpg', 30.00, 35),
(67, 'LED Flash Sparkler', 10, 'images/led_flash_sparkler.jpg', 55.00, 20),
(68, 'Color Smoke Fountain', 11, 'images/glitter_smoke_bomb.jpg', 200.00, 8),
(69, 'Deluxe Atom Bomb', 12, 'images/deluxe_atom_bomb.jpg', 70.00, 10),
(70, 'Torpedo Crackers', 12, 'images/torpedo_crackers.jpg', 120.00, 10),
(71, 'Neon Spinner', 13, 'images/neon_spinner.jpg', 70.00, 14),
(72, 'Super Ground Chakkar', 1, 'images/ground_chakkar_super.jpg', 90.00, 16),
(73, 'Magic Whirl Wind', 11, 'images/magic_whirl_wind.jpg', 140.00, 9),
(74, 'Thunder Sound Ball', 11, 'images/thunder_sound_ball.jpg', 120.00, 22),
(75, 'Mini Bullet Bomb', 12, 'images/mini_bullet_bomb.jpg', 40.00, 24),
(76, 'Jumbo Spin Wheel', 1, 'images/jumbo_spin_wheel.jpg', 150.00, 19),
(77, 'Sky Screamer Rocket', 3, 'images/sky_screamer_rocket.jpg', 320.00, 11);

-- ---------------------------------------------------------------------
-- Discount slabs: a product whose price falls in the range gets the %
-- ---------------------------------------------------------------------
CREATE TABLE `discount` (
  `discount_id` int(11) NOT NULL AUTO_INCREMENT,
  `discount_range` varchar(100) DEFAULT NULL,
  `percentage` int(11) NOT NULL,
  PRIMARY KEY (`discount_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `discount` (`discount_id`, `discount_range`, `percentage`) VALUES
(1, '0-60', 7),
(2, '61-99', 10),
(3, '100-250', 15),
(4, '251-500', 20);

-- ---------------------------------------------------------------------
-- Admin users (passwords are stored as bcrypt hashes)
-- ---------------------------------------------------------------------
CREATE TABLE `login` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- password: admin123
INSERT INTO `login` (`id`, `username`, `password`) VALUES
(1, 'admin', '$2y$12$S.RcEEdak0T9pSJtew2OO.OtbdpF7EeMv6CIPF1SBje2YhHQXKzE6');

-- ---------------------------------------------------------------------
-- Shopping cart (one row per product per visitor session)
-- ---------------------------------------------------------------------
CREATE TABLE `cart` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` varchar(255) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `total_cost` decimal(10,2) DEFAULT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `category` varchar(255) DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `session_id` (`session_id`),
  CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- Orders
-- order_status: Pending -> Processing -> Shipped -> Delivered (or Cancelled)
-- ---------------------------------------------------------------------
CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_name` varchar(255) DEFAULT NULL,
  `session_id` varchar(255) NOT NULL DEFAULT '',
  `contact_no` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `mail_id` varchar(255) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `packing_cost` decimal(10,2) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `order_date` datetime NOT NULL DEFAULT current_timestamp(),
  `order_status` varchar(50) NOT NULL DEFAULT 'Pending',
  PRIMARY KEY (`order_id`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Demo orders (fictional customers)
INSERT INTO `orders` (`order_id`, `customer_name`, `session_id`, `contact_no`, `address`, `mail_id`, `state`, `total`, `packing_cost`, `total_amount`, `order_date`, `order_status`) VALUES
(1, 'Demo Customer', 'demo', '9000000001', '12 Example Street, Coimbatore', 'demo.customer@example.com', 'Tamil Nadu', 321.80, 20.00, 341.80, '2025-11-28 15:44:08', 'Delivered'),
(2, 'Test Buyer', 'demo', '9000000002', '45 Sample Colony, Palakkad', 'test.buyer@example.com', 'Kerala', 1700.00, 20.00, 1720.00, '2025-11-29 15:13:32', 'Pending');

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `order_items` (`order_id`, `product_id`, `product_name`, `quantity`, `cost`, `total_cost`) VALUES
(1, 48, 'Golden Sparkle Stick', 1, 36.80, 36.80),
(1, 57, 'Flying Butterfly Wheel', 1, 81.00, 81.00),
(1, 36, 'Big Ground Chakkar', 2, 102.00, 204.00),
(2, 25, 'Mega Power Rocket', 8, 212.50, 1700.00);

-- ---------------------------------------------------------------------
-- Messages sent from the Contact Us page
-- ---------------------------------------------------------------------
CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
