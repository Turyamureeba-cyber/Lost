-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Aug 05, 2025 at 02:16 PM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `urbanpulse`
--

-- --------------------------------------------------------

--
-- Table structure for table `businesses`
--

DROP TABLE IF EXISTS `businesses`;
CREATE TABLE IF NOT EXISTS `businesses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `category_id` int NOT NULL,
  `address` text NOT NULL,
  `city` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(191) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `opening_hours` text,
  `rating` decimal(2,1) DEFAULT '0.0',
  `review_count` int DEFAULT '0',
  `featured` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `owner_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `owner_id` (`owner_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `businesses`
--

INSERT INTO `businesses` (`id`, `name`, `description`, `category_id`, `address`, `city`, `phone`, `email`, `website`, `latitude`, `longitude`, `opening_hours`, `rating`, `review_count`, `featured`, `created_at`, `updated_at`, `owner_id`) VALUES
(1, 'Rent Acar', 'This is the one', 27, 'Kigowa', 'kampala', '0754108912', 'Bwireallan98@gmail.com', '', 0.37132750, 32.62714560, '07AM-10PM', 0.0, 0, 0, '2025-07-12 13:56:36', '2025-07-12 13:56:36', 1),
(2, 'Mega Sounds', 'Party\r\nPolitics\r\nPublic Addressing', 29, 'Kigowa', 'kampala', '0754108912', 'Bwireallan98@gmail.com', '', 0.37081700, 32.61642990, 'uuuw33232', 0.0, 0, 0, '2025-07-12 15:43:25', '2025-07-19 15:26:19', 2),
(3, 'Sauna and Massage', 'Drinks\r\nFood\r\nParties\r\nCoktails\r\netc', 23, 'Ntinda, Main Town', 'kampala', '0754108912', 'Bwireallan98@gmail.com', '', 0.37081700, 32.61642990, 'jkdsjksdjk', 0.0, 0, 0, '2025-07-12 15:44:33', '2025-07-19 15:20:49', 2),
(4, 'Electrician', 'kjsdklskjd', 24, 'ntinda', 'kampala', '0754108912', 'Bwireallan98@gmail.com', '', 0.37081700, 32.61642990, 'jksdjkdsjk', 0.0, 0, 0, '2025-07-12 15:45:12', '2025-07-19 15:18:49', 2),
(5, 'I.T Consultancy', 'Web Design\r\nSystem Adminstartion\r\nNetworking\r\nComputer Repair and Maintanance\r\nComputer Lessons', 24, '', 'Kampala', '0753181844', 'jtkman@yahoo.com', '', 0.37081700, 32.61642990, '07:00AM- 12:00PM', 0.0, 0, 0, '2025-07-12 16:44:22', '2025-07-12 16:44:22', 1),
(6, 'Teen Challenge Uganda', 'Rehabilitation Center', 36, 'ntinda', 'kampala', '0754108912', 'teenchallenge@gmail.com', 'http://ww.teenchaleng_uganda.org.ug', NULL, NULL, '07:00 PM-05:00 PM', 0.0, 0, 0, '2025-07-12 16:49:29', '2025-07-12 16:49:29', 1),
(7, 'Plumbing', 'hgydygudsygfu', 22, 'Ntinda Market', 'kampala', '0754108912', 'Bwireallan98@gmail.com', '', 0.37081700, 32.61642990, 'rttfdrt', 0.0, 0, 0, '2025-07-12 16:54:58', '2025-07-19 15:19:31', 2),
(8, 'Slashers', 'This is my business', 42, 'ntinda', 'kampala', '0754108912', 'Bwireallan98@gmail.com', '', 0.37129500, 32.61713640, '24/7', 0.0, 0, 0, '2025-07-19 08:38:18', '2025-07-19 08:38:18', 2),
(9, 'Okugashura', 'Local Foot\r\nPilau\r\nKatogo\r\nLuwombo\r\nChips and Chiken', 21, 'Rwakaraba', 'Kabale, Rwakaraba', '07888888888', 'jjjjjj@gmail.com', '', 0.37129500, 32.61713640, '', 0.0, 0, 0, '2025-07-19 15:32:28', '2025-07-19 15:32:28', 1),
(10, 'Software Engineer', 'Web Design\r\nSystem Design\r\nWen Analyst\r\nSoftware Quality Assuarence', 24, 'Rwebikona', 'Mbarara', '0999999999', 'jkasjksa@hashs.com', '', NULL, NULL, '24/7', 0.0, 0, 0, '2025-07-19 15:40:17', '2025-07-19 15:40:17', 1);

-- --------------------------------------------------------

--
-- Table structure for table `business_images`
--

DROP TABLE IF EXISTS `business_images`;
CREATE TABLE IF NOT EXISTS `business_images` (
  `id` int NOT NULL AUTO_INCREMENT,
  `business_id` int NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `is_primary` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `business_id` (`business_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `business_images`
--

INSERT INTO `business_images` (`id`, `business_id`, `image_url`, `is_primary`) VALUES
(5, 6, 'business_6_1752930108.png', 0),
(6, 5, 'business_5_1752931574.jpg', 0),
(7, 3, 'business_3_1752938581.jpg', 0),
(8, 7, 'business_7_1752938667.jpg', 0),
(9, 2, 'business_2_1752938861.jpg', 0),
(10, 8, 'business_8_1752938950.jpg', 0),
(11, 9, 'business_9_1752939230.jpg', 0),
(12, 10, 'business_10_1752939704.jpg', 0);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `icon`) VALUES
(21, 'Restaurants & Cafes', 'restaurants-cafes', NULL),
(22, 'Retail Stores', 'retail-stores', NULL),
(23, 'Health & Beauty', 'health-beauty', NULL),
(24, 'Professional Services', 'professional-services', NULL),
(25, 'Home Services', 'home-services', NULL),
(26, 'Automotive', 'automotive', NULL),
(27, 'Real Estate', 'real-estate', NULL),
(28, 'Education', 'education', NULL),
(29, 'Entertainment', 'entertainment', NULL),
(30, 'Technology', 'technology', NULL),
(31, 'Travel & Hospitality', 'travel-hospitality', NULL),
(32, 'Fitness & Sports', 'fitness-sports', NULL),
(33, 'Medical & Dental', 'medical-dental', NULL),
(34, 'Arts & Crafts', 'arts-crafts', NULL),
(35, 'Financial Services', 'financial-services', NULL),
(36, 'Non-Profit Organizations', 'non-profit-organizations', NULL),
(37, 'Agriculture', 'agriculture', NULL),
(38, 'Construction', 'construction', NULL),
(39, 'Manufacturing', 'manufacturing', NULL),
(40, 'Transportation', 'transportation', NULL),
(41, 'Coding', 'Information Technology', ''),
(42, 'Compound', 'Compound Slashing', '');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` int NOT NULL AUTO_INCREMENT,
  `business_id` int NOT NULL,
  `user_id` int NOT NULL,
  `rating` int NOT NULL,
  `comment` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `business_id` (`business_id`),
  KEY `user_id` (`user_id`)
) ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) DEFAULT NULL,
  `email` varchar(191) NOT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `role` enum('admin','business_owner','user') DEFAULT 'user',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `google_id` varchar(191) DEFAULT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT '0',
  `provider` enum('local','google') DEFAULT 'local',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `google_id` (`google_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `role`, `created_at`, `google_id`, `avatar_url`, `email_verified`, `provider`) VALUES
(1, 'Joshua', 'jtkman@yahoo.com', '$2y$10$QZV7U170dgRRLtteV8GMU.q4sfGGAghTarwWm6lSHvEx37ESioivK', 'user', '2025-07-12 12:28:48', NULL, 'profile_1_1752929056.jpg', 0, 'local'),
(2, 'Allan', 'Bwireallan98@gmail.com', '$2y$10$jVZQDS/xGJ0VNAA9Jxzej.4IPWS27z8iWqbwoJbVx0390P5FtkqH6', 'user', '2025-07-12 15:42:03', NULL, 'profile_2_1752927791.jpg', 0, 'local'),
(4, 'admin', 'Bwireallan8@gmail.com', '$2y$10$PjoA8pFA5daCuz2Iie3o0eTCszYei/mIrBYUz7P6CLi0AdWrBvaJ.', 'admin', '2025-07-19 14:12:24', NULL, 'profile_default.jpg', 0, 'local');

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

DROP TABLE IF EXISTS `user_sessions`;
CREATE TABLE IF NOT EXISTS `user_sessions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `session_token` varchar(191) NOT NULL,
  `expires_at` timestamp NOT NULL,
  `device_info` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_token` (`session_token`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user_sessions`
--

INSERT INTO `user_sessions` (`id`, `user_id`, `session_token`, `expires_at`, `device_info`, `ip_address`, `created_at`) VALUES
(1, 1, '9a201b00bd6724825eed279b98261e68aa21e82b8f6281d31027d6e604f5af79', '2025-08-11 09:43:18', NULL, '::1', '2025-07-12 12:43:18'),
(2, 1, 'cdf8a1bb6dff8603593ae1160e0db075edb641419ae14ac9603acfcdead65918', '2025-08-11 11:12:03', NULL, '::1', '2025-07-12 14:12:03');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `businesses`
--
ALTER TABLE `businesses` ADD FULLTEXT KEY `name` (`name`,`description`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `businesses`
--
ALTER TABLE `businesses`
  ADD CONSTRAINT `businesses_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `businesses_ibfk_2` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `business_images`
--
ALTER TABLE `business_images`
  ADD CONSTRAINT `business_images_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
