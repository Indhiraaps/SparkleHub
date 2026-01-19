-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 30, 2025 at 01:02 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cracker_shop`
--
CREATE DATABASE IF NOT EXISTS `cracker_shop` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `cracker_shop`;

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

DROP TABLE IF EXISTS `cart`;
CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `total_cost` decimal(10,2) DEFAULT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `category` varchar(255) DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `session_id`, `product_id`, `product_name`, `cost`, `quantity`, `total_cost`, `added_at`, `category`, `subtotal`) VALUES
(6, 'rd751ss2tgf30l1848jpkas30v', 26, 'Jumbo Sky Rocket', 350.00, 1, 350.00, '2025-11-28 00:47:57', NULL, NULL),
(8, 'q7a3t7s5jh2999abfkk5cjq1da', 49, 'Multicolor Sparkler', 50.60, 1, 50.60, '2025-11-28 01:25:58', NULL, NULL),
(10, 'lkk2jrjl2d28nbtmfi1c1s92as', 24, 'Red Thunder Rocket', 102.00, 1, 102.00, '2025-11-28 04:29:22', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

DROP TABLE IF EXISTS `category`;
CREATE TABLE `category` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`category_id`, `category_name`) VALUES
(1, 'GROUND CHAKKAR'),
(2, 'FLOWER POTS'),
(3, 'ROCKETS'),
(10, 'SPARKLERS'),
(11, 'FANCY ITEMS'),
(12, 'BOMBS'),
(13, 'CHAKKARS');

-- --------------------------------------------------------

--
-- Table structure for table `discount`
--

DROP TABLE IF EXISTS `discount`;
CREATE TABLE `discount` (
  `discount_id` int(11) NOT NULL,
  `discount_range` varchar(100) DEFAULT NULL,
  `percentage` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `discount`
--

INSERT INTO `discount` (`discount_id`, `discount_range`, `percentage`) VALUES
(1, '0-60', 7),
(2, '61-99', 10),
(3, '100-250', 15),
(4, '251-500', 20);

-- --------------------------------------------------------

--
-- Table structure for table `login`
--

DROP TABLE IF EXISTS `login`;
CREATE TABLE `login` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login`
--

INSERT INTO `login` (`id`, `username`, `password`) VALUES
(1, 'admin', '12345');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `session_id` varchar(255) NOT NULL,
  `contact_no` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `mail_id` varchar(255) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `packing_cost` decimal(10,2) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `order_date` datetime NOT NULL DEFAULT current_timestamp(),
  `order_status` varchar(50) NOT NULL DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `customer_name`, `session_id`, `contact_no`, `address`, `mail_id`, `state`, `total`, `packing_cost`, `total_amount`, `created_at`, `order_date`, `order_status`) VALUES
(17, 'Charu', '', '9012345678', 'Vkpudur', 'indhiraaps@gmail.com', 'Tamilnadu', 321.80, 20.00, 341.80, '2025-11-28 10:14:08', '2025-11-28 15:44:08', 'Pending'),
(23, 'Pravinna', '', '9367845678', 'Xys colony', 'user@gmail.com', 'Palakkad', 1700.00, 20.00, 1720.00, '2025-11-29 09:43:32', '2025-11-29 15:13:32', 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `total_price` decimal(10,2) DEFAULT NULL,
  `cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_cost` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `price`, `total_price`, `cost`, `total_cost`) VALUES
(15, 17, 28, 'Golden Sparkle Stick', 1, NULL, NULL, 36.80, 36.80),
(16, 17, 37, 'Flying Butterfly Wheel', 1, NULL, NULL, 81.00, 81.00),
(17, 17, 36, 'Big Ground Chakkar', 2, NULL, NULL, 102.00, 204.00),
(23, 23, 25, 'Mega Power Rocket', 8, NULL, NULL, 212.50, 1700.00);

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

DROP TABLE IF EXISTS `product`;
CREATE TABLE `product` (
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `category` int(11) NOT NULL,
  `product_image_path` varchar(255) DEFAULT NULL,
  `cost` decimal(10,2) NOT NULL,
  `stock_status` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product`
--

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
(65, 'Rainbow Flower Fountain', 2, 'images/rainbow_flower_fountain.jp.jpg', 220.00, 12),
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

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `discount`
--
ALTER TABLE `discount`
  ADD PRIMARY KEY (`discount_id`);

--
-- Indexes for table `login`
--
ALTER TABLE `login`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `category` (`category`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `discount`
--
ALTER TABLE `discount`
  MODIFY `discount_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `login`
--
ALTER TABLE `login`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;

--
-- Constraints for table `product`
--
ALTER TABLE `product`
  ADD CONSTRAINT `product_ibfk_1` FOREIGN KEY (`category`) REFERENCES `category` (`category_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
