-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 22, 2025 at 07:44 PM
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
-- Database: `inventory_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `app_users`
--

CREATE TABLE `app_users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `alt_email` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'sales',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `website` varchar(255) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `job_title` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `app_users`
--

INSERT INTO `app_users` (`id`, `name`, `email`, `alt_email`, `email_verified_at`, `password`, `remember_token`, `role`, `is_active`, `website`, `photo`, `bio`, `job_title`, `created_at`, `updated_at`, `deleted_at`, `last_login_at`) VALUES
(1, 'Jethro k. Mandalones', 'jethrom@gmail.com', NULL, NULL, '$2y$12$ey9Mury8kX90c.z8sBx./.vwISFJTH.MiUrDnQBFrCjQPvQ5zhgte', '5b2uwSsVzhRkYnSQwEM0wsUdfFNLe3Po41otcrLUBnZTUCOK1aR9Ob2fYfEt', 'masters admin', 1, NULL, NULL, NULL, NULL, '2025-11-14 04:27:06', '2025-11-22 09:56:21', NULL, '2025-11-22 09:56:21'),
(2, 'Jeankyla Cortuna', 'jeank@gmail.com', NULL, NULL, '$2y$12$5MnDqMemZ39pVnPZASkS9ebIVueHU9yK4ZVpfuCbNuMLQ3n7O.OKy', NULL, 'production manager', 1, NULL, NULL, NULL, NULL, '2025-11-14 05:52:47', '2025-11-14 12:12:58', NULL, '2025-11-14 12:12:58'),
(3, 'Jeankyla Cortuna', 'jeankk@gmail.com', NULL, NULL, '$2y$12$uSgWg8DNIvFBywlj7E/mVOak9FzWpillIoGqOB4NR3eRA7Y8BDJum', NULL, 'production manager', 0, NULL, NULL, NULL, NULL, '2025-11-14 11:31:32', '2025-11-16 06:11:28', NULL, NULL),
(4, 'Jethro k. Mandalones', 'danicab@gmail.com', NULL, NULL, '$2y$12$uAShhKEVPVFaJWz01GwGc.E.m6UvUXUEd2u0C4wkTZMPDwMUDtV7.', NULL, 'sales', 1, NULL, NULL, NULL, NULL, '2025-11-15 05:27:01', '2025-11-15 05:27:01', NULL, NULL),
(5, 'Elizabeth Guran', 'elizabethg@gmail.com', NULL, NULL, '$2y$12$Ye0eyKqCjxP1lhCbx75Ru.0v2/nZbwYZo8WW.C0k/To2IAOE7UFVa', NULL, 'sales', 1, NULL, NULL, NULL, NULL, '2025-11-15 07:37:05', '2025-11-16 06:12:54', NULL, '2025-11-16 06:12:54'),
(6, 'Jimboy Arvesu', 'jimboya@gmail.com', NULL, NULL, '$2y$12$lZqco.sMCcYLA7BNw8pXs.xyX7kEO00jvb0N9Nh3uKze11zMxazZi', NULL, 'inventory', 1, NULL, NULL, NULL, NULL, '2025-11-15 07:42:21', '2025-11-15 07:42:32', NULL, '2025-11-15 07:42:32'),
(7, 'Jimboy Arvesu', 'jimboyaa@gmail.com', NULL, NULL, '$2y$12$rPM2EefKnV/ge.E26gCkdeV7jTeSV3.6iYRrN22uPdP9j5QN9aCeG', NULL, 'production manager', 1, NULL, NULL, NULL, NULL, '2025-11-16 05:46:16', '2025-11-16 05:46:16', NULL, NULL),
(8, 'Danica Ballesteros', 'danicabbb@gmail.com', NULL, NULL, '$2y$12$BqgR2UWP4D1HhpGK577MTOvNL126TdPkPj5L1Qn/s/s7tHzduTwI6', NULL, 'production manager', 1, NULL, NULL, NULL, NULL, '2025-11-16 06:14:58', '2025-11-20 04:08:08', NULL, '2025-11-16 06:15:07'),
(9, 'Ecco Nonee', 'ecconn@gmail.com', NULL, NULL, '$2y$12$OjJ958xemznYIb3c9yj7duBtP4TOhr7Gv4X1eQe62bMAkPW2SCSA6', NULL, 'masters admin', 1, NULL, NULL, NULL, NULL, '2025-11-20 03:44:31', '2025-11-20 04:09:29', NULL, '2025-11-20 03:44:47');

-- --------------------------------------------------------

--
-- Table structure for table `batches`
--

CREATE TABLE `batches` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `batch_code` varchar(100) NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `production_id` bigint(20) UNSIGNED DEFAULT NULL,
  `produced_at` datetime NOT NULL,
  `expiry_date` datetime NOT NULL,
  `shelf_life_days` int(11) NOT NULL DEFAULT 0,
  `qty_total` int(11) NOT NULL DEFAULT 0,
  `qty_available` int(11) NOT NULL DEFAULT 0,
  `qty_reserved` int(11) NOT NULL DEFAULT 0,
  `status` varchar(40) NOT NULL DEFAULT 'CREATED',
  `dispatch_sequence` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `batch_allocations`
--

CREATE TABLE `batch_allocations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sale_id` bigint(20) UNSIGNED DEFAULT NULL,
  `order_item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `production_id` bigint(20) UNSIGNED NOT NULL,
  `mode` enum('kg','pack','bag') NOT NULL,
  `quantity_value` decimal(10,3) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `batch_allocations`
--

INSERT INTO `batch_allocations` (`id`, `sale_id`, `order_item_id`, `production_id`, `mode`, `quantity_value`, `created_at`, `updated_at`, `deleted_at`) VALUES
(15, 34, NULL, 5, 'pack', 20.000, '2025-11-14 09:58:17', '2025-11-14 09:58:17', NULL),
(16, 35, NULL, 5, 'bag', 50.000, '2025-11-14 10:29:20', '2025-11-14 10:29:34', '2025-11-14 10:29:34'),
(17, 36, NULL, 6, 'pack', 30.000, '2025-11-15 08:03:35', '2025-11-15 08:03:59', '2025-11-15 08:03:59'),
(18, 37, NULL, 6, 'bag', 50.000, '2025-11-15 08:04:35', '2025-11-15 08:05:07', '2025-11-15 08:05:07'),
(19, 38, NULL, 6, 'pack', 30.000, '2025-11-15 08:53:29', '2025-11-19 08:08:43', '2025-11-19 08:08:43'),
(20, 39, NULL, 7, 'pack', 45.000, '2025-11-15 09:05:04', '2025-11-16 06:01:04', '2025-11-16 06:01:04'),
(21, 40, NULL, 8, 'pack', 3.000, '2025-11-15 09:06:54', '2025-11-16 06:01:05', '2025-11-16 06:01:05'),
(22, 41, NULL, 4, 'pack', 10.000, '2025-11-16 05:59:46', '2025-11-19 07:45:39', '2025-11-19 07:45:39'),
(23, 42, NULL, 13, 'pack', 50.000, '2025-11-20 03:54:52', '2025-11-20 03:58:13', '2025-11-20 03:58:13'),
(24, 43, NULL, 13, 'pack', 3.000, '2025-11-21 00:11:08', '2025-11-21 00:11:08', NULL),
(25, 44, NULL, 8, 'pack', 2.000, '2025-11-21 23:26:11', '2025-11-21 23:26:11', NULL),
(26, 45, NULL, 24, 'pack', 2.000, '2025-11-21 23:46:23', '2025-11-21 23:46:23', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `position` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'active',
  `avatar_path` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `user_id`, `first_name`, `last_name`, `position`, `email`, `username`, `password`, `remember_token`, `email_verified_at`, `status`, `avatar_path`, `phone`, `hire_date`, `created_at`, `updated_at`) VALUES
(1, 1, 'Jethro', 'k. Mandalones', 'Masters Admin', 'jethrom@gmail.com', 'jethrom', NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2025-11-14 04:27:06', '2025-11-14 04:27:06'),
(3, 3, 'Jeankyla', 'Cortuna', 'Production Manager', 'jeankk@gmail.com', 'jeank', NULL, NULL, NULL, 'inactive', 'avatars/5xgK6zPFLfNMXBStxSxX346bFnfE6gkl5u1BTWiB.jpg', NULL, NULL, '2025-11-14 11:31:32', '2025-11-21 03:48:54'),
(4, 4, 'Jethro', 'k. Mandalones', NULL, 'danicab@gmail.com', 'danicab', NULL, NULL, NULL, 'active', 'avatars/2rFXfk8Ebh028jZjB56IoCjkFW7dJoSXd5Awu751.jpg', NULL, NULL, '2025-11-15 05:27:01', '2025-11-21 03:39:19'),
(5, 5, 'Elizabeth', 'Guran', NULL, 'elizabethg@gmail.com', 'elizabethg', NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2025-11-15 07:37:05', '2025-11-15 07:37:05'),
(6, 6, 'Jimboy', 'Arvesu', NULL, 'jimboya@gmail.com', 'jimboya', NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2025-11-15 07:42:21', '2025-11-15 07:42:21'),
(7, 7, 'Jimboy', 'Arvesu', NULL, 'jimboyaa@gmail.com', 'jimboyaa', NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2025-11-16 05:46:16', '2025-11-16 05:46:16'),
(8, 8, 'Danica', 'Ballesteros', NULL, 'danicabbb@gmail.com', 'danicabb', NULL, NULL, NULL, 'active', 'avatars/AbJxZAgpMubIkEe94mYTBoEDZProe6gMtpSKAsHi.jpg', NULL, NULL, '2025-11-16 06:14:59', '2025-11-21 03:38:41'),
(9, 9, 'Ecco', 'None', NULL, 'ecconn@gmail.com', 'ecconone', NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2025-11-20 03:44:31', '2025-11-20 03:44:31');

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `batch_number` varchar(100) DEFAULT NULL,
  `production_date` date DEFAULT NULL,
  `expiration_date` date DEFAULT NULL,
  `quantity` decimal(14,3) NOT NULL DEFAULT 0.000,
  `stock_status` enum('in_stock','out_of_stock','low_stock') NOT NULL DEFAULT 'in_stock',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_ledgers`
--

CREATE TABLE `inventory_ledgers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `inventory_id` bigint(20) UNSIGNED DEFAULT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `material_id` bigint(20) UNSIGNED DEFAULT NULL,
  `production_id` bigint(20) UNSIGNED DEFAULT NULL,
  `sale_id` bigint(20) UNSIGNED DEFAULT NULL,
  `movement_type` enum('IN','OUT','ADJUST','RESERVE','RELEASE') NOT NULL,
  `reason` varchar(150) DEFAULT NULL,
  `quantity_kg` decimal(14,3) NOT NULL DEFAULT 0.000,
  `before_qty_kg` decimal(14,3) NOT NULL DEFAULT 0.000,
  `after_qty_kg` decimal(14,3) NOT NULL DEFAULT 0.000,
  `unit_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_cost` decimal(14,2) NOT NULL DEFAULT 0.00,
  `notes` varchar(255) DEFAULT NULL,
  `movement_date` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoice_sequences`
--

CREATE TABLE `invoice_sequences` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `date_key` date NOT NULL,
  `last_seq` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoice_sequences`
--

INSERT INTO `invoice_sequences` (`id`, `date_key`, `last_seq`, `created_at`, `updated_at`) VALUES
(1, '2025-11-14', 35, '2025-11-14 05:11:11', '2025-11-14 10:29:20'),
(2, '2025-11-15', 5, '2025-11-15 08:03:35', '2025-11-15 09:06:54'),
(3, '2025-11-16', 1, '2025-11-16 05:59:43', '2025-11-16 05:59:43'),
(4, '2025-11-20', 1, '2025-11-20 03:54:49', '2025-11-20 03:54:49'),
(5, '2025-11-21', 1, '2025-11-21 00:11:04', '2025-11-21 00:11:04'),
(6, '2025-11-22', 2, '2025-11-21 23:26:08', '2025-11-21 23:46:23');

-- --------------------------------------------------------

--
-- Table structure for table `login_activities`
--

CREATE TABLE `login_activities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `email` varchar(191) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `login_at` timestamp NULL DEFAULT NULL,
  `logout_at` timestamp NULL DEFAULT NULL,
  `succeeded` tinyint(1) NOT NULL DEFAULT 1,
  `reason` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `login_activities`
--

INSERT INTO `login_activities` (`id`, `user_id`, `email`, `ip_address`, `user_agent`, `login_at`, `logout_at`, `succeeded`, `reason`, `created_at`, `updated_at`) VALUES
(1, 2, 'jeank@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 12:12:58', '2025-11-14 12:13:05', 1, NULL, '2025-11-14 12:12:58', '2025-11-14 12:13:05'),
(2, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 12:13:14', '2025-11-14 12:19:09', 1, NULL, '2025-11-14 12:13:14', '2025-11-14 12:19:09'),
(3, 3, 'jeankk@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 12:19:19', NULL, 0, 'invalid_credentials', '2025-11-14 12:19:19', '2025-11-14 12:19:19'),
(4, 3, 'jeankk@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 12:19:26', NULL, 0, 'invalid_credentials', '2025-11-14 12:19:26', '2025-11-14 12:19:26'),
(5, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 12:19:42', '2025-11-14 12:23:49', 1, NULL, '2025-11-14 12:19:42', '2025-11-14 12:23:49'),
(6, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 02:35:14', '2025-11-15 02:47:12', 1, NULL, '2025-11-15 02:35:14', '2025-11-15 02:47:12'),
(7, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 04:38:38', '2025-11-15 05:01:39', 1, NULL, '2025-11-15 04:38:38', '2025-11-15 05:01:39'),
(8, 4, 'danicab@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 05:27:16', NULL, 0, 'invalid_credentials', '2025-11-15 05:27:16', '2025-11-15 05:27:16'),
(9, 4, 'danicab@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 05:27:29', NULL, 0, 'invalid_credentials', '2025-11-15 05:27:29', '2025-11-15 05:27:29'),
(10, 4, 'danicab@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 05:32:30', NULL, 0, 'invalid_credentials', '2025-11-15 05:32:30', '2025-11-15 05:32:30'),
(11, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 05:35:15', '2025-11-15 05:35:38', 1, NULL, '2025-11-15 05:35:15', '2025-11-15 05:35:38'),
(12, 4, 'danicab@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 05:35:46', NULL, 0, 'invalid_credentials', '2025-11-15 05:35:46', '2025-11-15 05:35:46'),
(13, 5, 'elizabethg@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 07:37:20', '2025-11-15 07:37:30', 1, NULL, '2025-11-15 07:37:20', '2025-11-15 07:37:30'),
(14, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 07:37:44', '2025-11-15 07:41:09', 1, NULL, '2025-11-15 07:37:44', '2025-11-15 07:41:09'),
(15, 6, 'jimboya@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 07:42:32', '2025-11-15 07:44:28', 1, NULL, '2025-11-15 07:42:32', '2025-11-15 07:44:28'),
(16, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 07:44:36', '2025-11-15 09:15:49', 1, NULL, '2025-11-15 07:44:36', '2025-11-15 09:15:49'),
(17, 5, 'elizabethg@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 09:16:10', '2025-11-15 09:16:41', 1, NULL, '2025-11-15 09:16:10', '2025-11-15 09:16:41'),
(18, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 09:16:49', '2025-11-15 11:25:43', 1, NULL, '2025-11-15 09:16:49', '2025-11-15 11:25:43'),
(19, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 11:32:29', NULL, 1, NULL, '2025-11-15 11:32:29', '2025-11-15 11:32:29'),
(20, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 04:49:44', '2025-11-16 05:37:36', 1, NULL, '2025-11-16 04:49:44', '2025-11-16 05:37:36'),
(21, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 05:46:37', '2025-11-16 06:11:56', 1, NULL, '2025-11-16 05:46:37', '2025-11-16 06:11:56'),
(22, 3, 'jeankk@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 06:12:06', NULL, 0, 'invalid_credentials', '2025-11-16 06:12:06', '2025-11-16 06:12:06'),
(23, 5, 'elizabethg@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 06:12:54', '2025-11-16 06:13:24', 1, NULL, '2025-11-16 06:12:54', '2025-11-16 06:13:24'),
(24, 8, 'danicabbb@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 06:15:07', '2025-11-16 06:16:02', 1, NULL, '2025-11-16 06:15:07', '2025-11-16 06:16:02'),
(25, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 06:16:44', NULL, 1, NULL, '2025-11-16 06:16:44', '2025-11-16 06:16:44'),
(26, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 10:17:54', NULL, 1, NULL, '2025-11-16 10:17:54', '2025-11-16 10:17:54'),
(27, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 19:15:39', '2025-11-16 20:09:35', 1, NULL, '2025-11-16 19:15:39', '2025-11-16 20:09:35'),
(28, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 20:11:04', NULL, 1, NULL, '2025-11-16 20:11:04', '2025-11-16 20:11:04'),
(29, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-19 07:09:19', NULL, 1, NULL, '2025-11-19 07:09:19', '2025-11-19 07:09:19'),
(30, 9, 'ecconn@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-20 03:44:47', '2025-11-20 04:10:32', 1, NULL, '2025-11-20 03:44:47', '2025-11-20 04:10:32'),
(31, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-21 00:10:22', NULL, 1, NULL, '2025-11-21 00:10:22', '2025-11-21 00:10:22'),
(32, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-21 22:50:12', NULL, 1, NULL, '2025-11-21 22:50:12', '2025-11-21 22:50:12'),
(33, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-22 04:30:45', NULL, 1, NULL, '2025-11-22 04:30:45', '2025-11-22 04:30:45'),
(34, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-22 09:56:21', NULL, 1, NULL, '2025-11-22 09:56:21', '2025-11-22 09:56:21');

-- --------------------------------------------------------

--
-- Table structure for table `materials`
--

CREATE TABLE `materials` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `material_name` varchar(255) NOT NULL,
  `category` varchar(255) DEFAULT NULL,
  `unit` varchar(20) NOT NULL DEFAULT 'kg',
  `sku` varchar(64) DEFAULT NULL,
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `quantity_kg` decimal(14,3) NOT NULL DEFAULT 0.000,
  `min_stock_kg` decimal(14,3) DEFAULT NULL,
  `stock_status` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `materials`
--

INSERT INTO `materials` (`id`, `material_name`, `category`, `unit`, `sku`, `unit_price`, `quantity_kg`, `min_stock_kg`, `stock_status`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Sugar', 'Spices & Seasonings', 'kg', NULL, 30.00, 300.000, 300.000, 'low', '2025-11-14 05:57:14', '2025-11-14 05:57:14', NULL),
(2, 'Salt', 'Salt', 'kg', NULL, 40.00, 500.000, 100.000, 'in_stock', '2025-11-14 05:57:43', '2025-11-14 05:57:43', NULL),
(3, 'MDM', 'Meat Cuts & Trimmings', 'kg', NULL, 400.00, 200.000, 200.000, 'low', '2025-11-16 06:09:11', '2025-11-16 06:09:11', NULL),
(4, 'Flour', 'Packaging Films & Bags', 'kg', NULL, 50.00, 500.000, 500.000, 'low', '2025-11-20 04:05:11', '2025-11-20 04:05:11', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_sequences`
--

CREATE TABLE `order_sequences` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `date_key` date NOT NULL,
  `last_seq` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `productions`
--

CREATE TABLE `productions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `parent_product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `product_name_snapshot` varchar(255) DEFAULT NULL,
  `batch_number` varchar(255) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `forecasted_demand` decimal(10,2) NOT NULL DEFAULT 0.00,
  `current_inventory` int(11) DEFAULT NULL,
  `unit_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `unit_price_pack` decimal(10,2) DEFAULT NULL,
  `unit_price_bag` decimal(10,2) DEFAULT NULL,
  `available_pack` int(11) NOT NULL DEFAULT 0,
  `available_bag` int(11) NOT NULL DEFAULT 0,
  `remarks` varchar(500) DEFAULT NULL,
  `image_disk` varchar(40) DEFAULT 'public',
  `image_path` varchar(255) DEFAULT NULL,
  `image_medium_path` varchar(255) DEFAULT NULL,
  `image_thumb_path` varchar(255) DEFAULT NULL,
  `production_date` date DEFAULT NULL,
  `expiration_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `archived_reason` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `productions`
--

INSERT INTO `productions` (`id`, `product_id`, `parent_product_id`, `product_name_snapshot`, `batch_number`, `quantity`, `forecasted_demand`, `current_inventory`, `unit_cost`, `unit_price_pack`, `unit_price_bag`, `available_pack`, `available_bag`, `remarks`, `image_disk`, `image_path`, `image_medium_path`, `image_thumb_path`, `production_date`, `expiration_date`, `created_at`, `updated_at`, `deleted_at`, `archived_at`, `archived_reason`) VALUES
(4, 1, 1, 'Regular', '1', 200, 3000.00, 200, 0.00, 27.00, 457.00, 100, 100, '', 'public', NULL, NULL, NULL, '2025-11-14', '2025-12-15', '2025-11-14 09:57:06', '2025-11-19 08:08:49', NULL, NULL, NULL),
(5, 1, 1, 'Garlic', '2', 900, 2000.00, 900, 0.00, 27.00, 457.00, 380, 500, '', 'public', NULL, NULL, NULL, '2025-11-14', '2025-12-15', '2025-11-14 09:57:44', '2025-11-16 05:13:25', NULL, NULL, NULL),
(6, 2, 2, 'Pork', '1', 700, 3000.00, 700, 0.00, 27.00, 457.00, 400, 300, '', 'public', NULL, NULL, NULL, '2025-11-15', '2025-12-16', '2025-11-15 08:03:09', '2025-11-19 08:08:48', NULL, NULL, NULL),
(7, 3, 3, 'Pork', '1', 500, 3000.00, 500, 0.00, 27.00, 540.00, 300, 200, '', 'public', NULL, NULL, NULL, '2025-11-15', '2025-12-16', '2025-11-15 09:04:27', '2025-11-19 08:08:51', NULL, NULL, NULL),
(8, 2, 2, 'Chicken', '2', 30, 2000.00, 30, 0.00, 27.00, 540.00, 18, 10, '', 'public', NULL, NULL, NULL, '2025-11-15', '2025-11-19', '2025-11-15 09:06:20', '2025-11-21 23:26:11', NULL, NULL, NULL),
(9, 2, 2, 'Pork', '3', 430, 2000.00, 430, 0.00, 30.00, 600.00, 400, 30, '', 'public', NULL, NULL, NULL, '2025-11-15', '2025-11-18', '2025-11-15 09:09:20', '2025-11-15 09:09:20', NULL, NULL, NULL),
(10, 2, 2, 'Pork', '4', 0, 3000.00, 0, 0.00, 0.00, 0.00, 0, 0, '', 'public', NULL, NULL, NULL, '2025-11-15', '2025-11-18', '2025-11-15 11:12:18', '2025-11-16 05:13:23', NULL, NULL, NULL),
(11, 1, 1, 'Garlic', '3', 500, 3000.00, 500, 0.00, 27.00, 457.00, 300, 200, '', 'public', NULL, NULL, NULL, '2025-11-16', '2025-12-16', '2025-11-16 05:55:12', '2025-11-16 05:56:57', NULL, NULL, NULL),
(12, 1, 1, 'Garlic', '4', 420, 3000.00, 420, 0.00, 47.00, 400.00, 400, 20, '', 'public', NULL, NULL, NULL, '2025-11-20', '2025-12-20', '2025-11-20 03:49:33', '2025-11-20 03:49:33', NULL, NULL, NULL),
(13, 2, 2, 'Pork', '5', 600, 3000.00, 600, 0.00, 40.00, 400.00, 297, 300, '', 'public', NULL, NULL, NULL, '2025-11-15', '2025-11-24', '2025-11-20 03:50:30', '2025-11-21 00:11:08', NULL, NULL, NULL),
(14, 2, 2, 'Pork', '6', 657, 2000.00, 657, 0.00, 27.00, 457.00, 457, 200, '', 'public', NULL, NULL, NULL, '2025-11-15', '2025-11-24', '2025-11-21 02:52:30', '2025-11-21 02:52:30', NULL, NULL, NULL),
(15, 2, 2, 'Pork', '7', 347, 2000.00, 347, 0.00, 27.00, 457.00, 47, 300, 'hii', 'public', NULL, NULL, NULL, '2025-11-15', '2025-11-24', '2025-11-21 02:53:25', '2025-11-21 02:53:25', NULL, NULL, NULL),
(16, 2, 2, 'Pork', '8', 53, 1000.00, 53, 0.00, 27.00, 457.00, 30, 23, '', 'public', NULL, NULL, NULL, '2025-11-15', '2025-11-24', '2025-11-21 03:02:08', '2025-11-21 03:02:08', NULL, NULL, NULL),
(17, 2, 2, 'Pork', '9', 40, 1000.00, 40, 0.00, 27.00, 457.00, 30, 10, '', 'public', NULL, NULL, NULL, '2025-11-15', '2025-11-24', '2025-11-21 03:13:49', '2025-11-21 03:13:49', NULL, NULL, NULL),
(18, 2, 2, 'Pork', '10', 330, 1000.00, 330, 0.00, 27.00, 457.00, 300, 30, '', 'public', NULL, NULL, NULL, '2025-11-15', '2025-11-24', '2025-11-21 03:22:57', '2025-11-21 03:22:57', NULL, NULL, NULL),
(19, 2, 2, 'Pork', '11', 320, 1000.00, 320, 0.00, 27.00, 457.00, 300, 20, '', 'public', NULL, NULL, NULL, '2025-11-15', '2025-11-24', '2025-11-21 03:36:59', '2025-11-21 03:36:59', NULL, NULL, NULL),
(20, 2, 2, 'Pork', '12', 757, 2000.00, 757, 0.00, 200.00, 457.00, 457, 300, '', 'public', NULL, NULL, NULL, '2025-11-15', '2025-11-24', '2025-11-21 03:46:51', '2025-11-21 03:46:51', NULL, NULL, NULL),
(21, 2, 2, 'Pork', '13', 506, 3000.00, 506, 0.00, 27.00, 457.00, 467, 39, '', 'public', NULL, NULL, NULL, '2025-11-15', '2025-11-24', '2025-11-21 03:57:36', '2025-11-21 03:57:36', NULL, NULL, NULL),
(22, 2, 2, 'Pork', '14', 60, 2000.00, 60, 0.00, 27.00, 457.00, 30, 30, '', 'public', NULL, NULL, NULL, '2025-11-15', '2025-11-24', '2025-11-21 04:14:45', '2025-11-21 04:14:45', NULL, NULL, NULL),
(23, 3, 3, 'Pork', '2', 75, 2000.00, 75, 0.00, 27.00, 457.00, 45, 30, '', 'public', NULL, NULL, NULL, '2025-11-15', '2025-12-16', '2025-11-21 04:16:54', '2025-11-21 04:16:54', NULL, NULL, NULL),
(24, 3, 3, 'Pork', '3', 40, 3000.00, 40, 0.00, 47.00, 457.00, 28, 10, '', 'public', NULL, NULL, NULL, '2025-11-21', '2025-12-16', '2025-11-21 04:18:05', '2025-11-21 23:46:23', NULL, NULL, NULL),
(25, 3, 3, 'Embutido', '4', 75, 3000.00, 75, 0.00, 27.00, 457.00, 45, 30, '', 'public', NULL, NULL, NULL, '2025-11-21', '2025-12-16', '2025-11-21 23:44:36', '2025-11-22 10:25:21', NULL, NULL, NULL),
(26, 3, 3, 'Chicken', '5', 8, 200.00, 8, 0.00, 27.00, 457.00, 5, 3, '', 'public', NULL, NULL, NULL, '2025-11-21', '2025-12-16', '2025-11-21 23:45:40', '2025-11-21 23:45:40', NULL, NULL, NULL),
(27, 4, 4, 'Pork', '1', 97, 3000.00, 97, 0.00, 27.00, 500.00, 57, 40, '', 'public', NULL, NULL, NULL, '2025-11-22', '2025-12-22', '2025-11-22 00:05:23', '2025-11-22 10:25:20', NULL, NULL, NULL),
(28, 2, 2, 'Pork', '15', 40, 1000.00, 40, 0.00, 27.00, 457.00, 10, 30, '', 'public', NULL, NULL, NULL, '2025-11-15', '2025-11-24', '2025-11-22 00:12:12', '2025-11-22 00:12:12', NULL, NULL, NULL),
(29, 2, 2, 'Pork', '16', 60, 300.00, 60, 0.00, 27.00, 457.00, 40, 20, '', 'public', NULL, NULL, NULL, '2025-11-15', '2025-11-24', '2025-11-22 00:22:54', '2025-11-22 00:22:54', NULL, NULL, NULL),
(30, 2, 2, 'Pork', '17', 79, 3000.00, 79, 0.00, 27.00, 349.00, 30, 49, '', 'public', NULL, NULL, NULL, '2025-11-15', '2025-11-24', '2025-11-22 01:17:01', '2025-11-22 01:17:01', NULL, NULL, NULL),
(31, 2, 2, 'pork', '18', 40, 3000.00, 40, 0.00, 27.00, 457.00, 20, 20, '', 'public', NULL, NULL, NULL, '2025-11-15', '2025-11-24', '2025-11-22 01:35:55', '2025-11-22 01:35:55', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `product_code` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `batch_number` varchar(255) DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `image_disk` varchar(40) DEFAULT 'public',
  `image_path` varchar(255) DEFAULT NULL,
  `image_medium_path` varchar(255) DEFAULT NULL,
  `image_thumb_path` varchar(255) DEFAULT NULL,
  `production_date` date DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `current_inventory` decimal(11,3) DEFAULT NULL,
  `unit_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `last_cost_date` date DEFAULT NULL,
  `default_price` decimal(10,2) DEFAULT NULL,
  `forecasted_demand` int(11) NOT NULL DEFAULT 0,
  `image_url` varchar(255) DEFAULT NULL,
  `card_image_url` varchar(255) DEFAULT NULL,
  `card_image_srcset` text DEFAULT NULL,
  `stock_status` enum('in_stock','out_of_stock','low_stock') NOT NULL DEFAULT 'in_stock',
  `status` varchar(50) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `shelf_life_days` int(10) UNSIGNED DEFAULT NULL,
  `temp_requirements` varchar(255) DEFAULT NULL,
  `storage_zone` varchar(255) DEFAULT NULL,
  `yield_rate` decimal(8,3) DEFAULT NULL,
  `standard_batch_size` decimal(11,3) DEFAULT NULL,
  `lead_time_days` int(11) DEFAULT NULL,
  `min_run_qty` decimal(11,3) DEFAULT NULL,
  `max_run_qty` decimal(11,3) DEFAULT NULL,
  `line_constraints` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`line_constraints`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `parent_id`, `name`, `product_name`, `product_code`, `price`, `batch_number`, `category`, `image_disk`, `image_path`, `image_medium_path`, `image_thumb_path`, `production_date`, `quantity`, `current_inventory`, `unit_cost`, `last_cost_date`, `default_price`, `forecasted_demand`, `image_url`, `card_image_url`, `card_image_srcset`, `stock_status`, `status`, `unit`, `created_at`, `updated_at`, `deleted_at`, `shelf_life_days`, `temp_requirements`, `storage_zone`, `yield_rate`, `standard_batch_size`, `lead_time_days`, `min_run_qty`, `max_run_qty`, `line_constraints`) VALUES
(1, NULL, NULL, 'Skinless longganisa', NULL, NULL, NULL, 'Garlic', 'public', NULL, NULL, NULL, '2025-11-20', 1980, NULL, 0.00, NULL, NULL, 2000, NULL, NULL, NULL, 'in_stock', NULL, NULL, '2025-11-14 04:56:06', '2025-11-20 03:49:35', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, NULL, NULL, 'Bologna', NULL, NULL, NULL, 'pork', 'public', 'products/dbXTX1S0rEHB7imscoAoXxyYmVYZAwFwRyNnNz7K.jpg', NULL, NULL, '2025-11-15', 5039, NULL, 0.00, NULL, NULL, 3000, 'http://localhost/storage/products/dbXTX1S0rEHB7imscoAoXxyYmVYZAwFwRyNnNz7K.jpg', 'http://localhost/storage/products/dbXTX1S0rEHB7imscoAoXxyYmVYZAwFwRyNnNz7K.jpg', NULL, 'in_stock', NULL, NULL, '2025-11-15 08:03:09', '2025-11-22 01:35:55', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, NULL, NULL, 'Embutido', NULL, NULL, NULL, 'Chicken', 'public', 'products/sml2ZZbcYZCYy7iPPvPatDGdLo0tS2G8PjmlGOM5.webp', NULL, NULL, '2025-11-21', 694, NULL, 0.00, NULL, NULL, 200, 'http://localhost/storage/products/sml2ZZbcYZCYy7iPPvPatDGdLo0tS2G8PjmlGOM5.webp', NULL, NULL, 'in_stock', NULL, NULL, '2025-11-15 09:04:27', '2025-11-22 10:25:22', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, NULL, NULL, 'Salami', NULL, NULL, NULL, 'Pork', 'public', NULL, NULL, NULL, '2025-11-22', 97, NULL, 0.00, NULL, NULL, 3000, NULL, NULL, NULL, 'in_stock', NULL, NULL, '2025-11-22 00:05:22', '2025-11-22 10:25:20', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product_recipes`
--

CREATE TABLE `product_recipes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `material_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ingredient_id` bigint(20) UNSIGNED DEFAULT NULL,
  `qty` decimal(10,3) NOT NULL DEFAULT 0.000,
  `unit` varchar(50) NOT NULL DEFAULT 'kg',
  `unit_price_snapshot` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_recipes`
--

INSERT INTO `product_recipes` (`id`, `product_id`, `material_id`, `ingredient_id`, `qty`, `unit`, `unit_price_snapshot`, `created_at`, `updated_at`) VALUES
(1, 1, 2, NULL, 2.000, 'kg', 40.00, '2025-11-14 05:58:16', '2025-11-14 05:58:16'),
(2, 1, 1, NULL, 3.000, 'kg', 30.00, '2025-11-14 05:58:24', '2025-11-14 05:58:24'),
(3, 2, 2, NULL, 1.000, 'kg', 40.00, '2025-11-16 06:07:27', '2025-11-16 06:07:27'),
(4, 2, 1, NULL, 1.000, 'kg', 30.00, '2025-11-16 06:07:27', '2025-11-16 06:07:27'),
(6, 2, 3, NULL, 1.000, 'kg', 400.00, '2025-11-20 04:02:36', '2025-11-20 04:02:36');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `order_date` date DEFAULT NULL,
  `production_date` date DEFAULT NULL,
  `expiration_date` date DEFAULT NULL,
  `production_id` bigint(20) UNSIGNED DEFAULT NULL,
  `invoice_number` varchar(50) DEFAULT NULL,
  `order_number` varchar(50) DEFAULT NULL,
  `product` varchar(150) DEFAULT NULL,
  `type_label` varchar(255) DEFAULT NULL,
  `product_name` varchar(150) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `quantity_kg` decimal(10,3) DEFAULT NULL,
  `unit_type` enum('kg','pack','bag') DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `total_price` decimal(12,2) DEFAULT NULL,
  `total` decimal(12,2) DEFAULT NULL,
  `status` enum('Pending','Completed','Cancelled','Paid') NOT NULL DEFAULT 'Completed',
  `customer_name` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `product_id`, `unit_price`, `order_date`, `production_date`, `expiration_date`, `production_id`, `invoice_number`, `order_number`, `product`, `type_label`, `product_name`, `date`, `quantity`, `quantity_kg`, `unit_type`, `price`, `total_price`, `total`, `status`, `customer_name`, `notes`, `created_at`, `updated_at`, `deleted_at`) VALUES
(33, 1, 27.00, '2025-11-14', NULL, NULL, NULL, 'INV-20251114-033', 'INV-20251114-033', 'Skinless longganisa', 'Garlic', NULL, '2025-11-14 00:00:00', 14, 14.000, 'pack', 27.00, 378.00, 378.00, 'Paid', NULL, NULL, '2025-11-14 09:51:29', '2025-11-14 09:55:08', '2025-11-14 09:55:08'),
(34, 1, 27.00, '2025-11-14', NULL, NULL, 5, 'INV-20251114-034', 'INV-20251114-034', 'Skinless longganisa', 'Garlic', NULL, '2025-11-14 00:00:00', 20, 20.000, 'pack', 27.00, 540.00, 540.00, 'Paid', NULL, NULL, '2025-11-14 09:58:17', '2025-11-14 09:58:17', NULL),
(35, 1, 457.00, '2025-11-14', NULL, NULL, 5, 'INV-20251114-035', 'INV-20251114-035', 'Skinless longganisa', 'Garlic', NULL, '2025-11-14 00:00:00', 50, 50.000, 'bag', 457.00, 22850.00, 22850.00, 'Paid', NULL, NULL, '2025-11-14 10:29:20', '2025-11-14 10:29:34', '2025-11-14 10:29:34'),
(36, 2, 27.00, '2025-11-15', NULL, NULL, 6, 'INV-20251115-001', 'INV-20251115-001', 'Bologna', 'Pork', NULL, '2025-11-15 00:00:00', 30, 30.000, 'pack', 27.00, 810.00, 810.00, 'Paid', NULL, NULL, '2025-11-15 08:03:35', '2025-11-15 08:03:59', '2025-11-15 08:03:59'),
(37, 2, 457.00, '2025-11-15', NULL, NULL, 6, 'INV-20251115-002', 'INV-20251115-002', 'Bologna', 'Pork', NULL, '2025-11-15 00:00:00', 50, 50.000, 'bag', 457.00, 22850.00, 22850.00, 'Paid', NULL, NULL, '2025-11-15 08:04:35', '2025-11-15 08:05:07', '2025-11-15 08:05:07'),
(38, 2, 27.00, '2025-11-15', NULL, NULL, 6, 'INV-20251115-003', 'INV-20251115-003', 'Bologna', 'Pork', NULL, '2025-11-15 00:00:00', 30, 30.000, 'pack', 27.00, 810.00, 810.00, 'Paid', NULL, NULL, '2025-11-15 08:53:29', '2025-11-19 08:08:42', '2025-11-19 08:08:42'),
(39, 3, 27.00, '2025-11-15', NULL, NULL, 7, 'INV-20251115-004', 'INV-20251115-004', 'Embutido', 'Pork', NULL, '2025-11-15 00:00:00', 45, 45.000, 'pack', 27.00, 1215.00, 1215.00, 'Paid', NULL, NULL, '2025-11-15 09:05:04', '2025-11-16 06:01:04', '2025-11-16 06:01:04'),
(40, 2, 27.00, '2025-11-15', NULL, NULL, 8, 'INV-20251115-005', 'INV-20251115-005', 'Bologna', 'Chicken', NULL, '2025-11-15 00:00:00', 3, 3.000, 'pack', 27.00, 81.00, 81.00, 'Pending', NULL, NULL, '2025-11-15 09:06:54', '2025-11-16 06:01:05', '2025-11-16 06:01:05'),
(41, 1, 27.00, '2025-11-16', NULL, NULL, 4, 'INV-20251116-001', 'INV-20251116-001', 'Skinless longganisa', 'Regular', NULL, '2025-11-16 00:00:00', 10, 10.000, 'pack', 27.00, 270.00, 270.00, 'Paid', NULL, NULL, '2025-11-16 05:59:46', '2025-11-19 07:45:38', '2025-11-19 07:45:38'),
(42, 2, 40.00, '2025-11-20', NULL, NULL, 13, 'INV-20251120-001', 'INV-20251120-001', 'Bologna', 'Pork', NULL, '2025-11-20 00:00:00', 50, 50.000, 'pack', 40.00, 2000.00, 2000.00, 'Paid', NULL, NULL, '2025-11-20 03:54:52', '2025-11-20 03:58:13', '2025-11-20 03:58:13'),
(43, 2, 40.00, '2025-11-21', NULL, NULL, 13, 'INV-20251121-001', 'INV-20251121-001', 'Bologna', 'Pork', NULL, '2025-11-21 00:00:00', 3, 3.000, 'pack', 40.00, 120.00, 120.00, 'Paid', NULL, NULL, '2025-11-21 00:11:08', '2025-11-21 00:11:08', NULL),
(44, 2, 27.00, '2025-11-22', NULL, NULL, 8, 'INV-20251122-001', 'INV-20251122-001', 'Bologna', 'Chicken', NULL, '2025-11-22 00:00:00', 2, 2.000, 'pack', 27.00, 54.00, 54.00, 'Pending', NULL, NULL, '2025-11-21 23:26:11', '2025-11-21 23:26:11', NULL),
(45, 3, 47.00, '2025-11-22', NULL, NULL, 24, 'INV-20251122-002', 'INV-20251122-002', 'Embutido', 'Pork', NULL, '2025-11-22 00:00:00', 2, 2.000, 'pack', 47.00, 94.00, 94.00, 'Paid', NULL, NULL, '2025-11-21 23:46:23', '2025-11-21 23:46:23', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sale_audits`
--

CREATE TABLE `sale_audits` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sale_id` bigint(20) UNSIGNED DEFAULT NULL,
  `order_item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `message` text NOT NULL,
  `at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sale_audits`
--

INSERT INTO `sale_audits` (`id`, `sale_id`, `order_item_id`, `message`, `at`, `created_at`, `updated_at`) VALUES
(27, 33, NULL, 'Deducted 14 pack(s) from batch 2 (Production #3).', '2025-11-14 17:51:29', '2025-11-14 09:51:29', '2025-11-14 09:51:29'),
(29, 33, NULL, 'Returned 14 pack(s) to batch 2 (Production #3).', '2025-11-14 17:55:08', '2025-11-14 09:55:08', '2025-11-14 09:55:08'),
(30, 34, NULL, 'Deducted 20 pack(s) from batch 2 (Production #5).', '2025-11-14 17:58:17', '2025-11-14 09:58:17', '2025-11-14 09:58:17'),
(31, 35, NULL, 'Deducted 50 bag(s) from batch 2 (Production #5).', '2025-11-14 18:29:20', '2025-11-14 10:29:20', '2025-11-14 10:29:20'),
(32, 35, NULL, 'Returned 50 bag(s) to batch 2 (Production #5).', '2025-11-14 18:29:34', '2025-11-14 10:29:34', '2025-11-14 10:29:34'),
(33, 36, NULL, 'Deducted 30 pack(s) from batch 1 (Production #6).', '2025-11-15 16:03:35', '2025-11-15 08:03:35', '2025-11-15 08:03:35'),
(34, 36, NULL, 'Returned 30 pack(s) to batch 1 (Production #6).', '2025-11-15 16:03:59', '2025-11-15 08:03:59', '2025-11-15 08:03:59'),
(35, 37, NULL, 'Deducted 50 bag(s) from batch 1 (Production #6).', '2025-11-15 16:04:35', '2025-11-15 08:04:35', '2025-11-15 08:04:35'),
(36, 37, NULL, 'Returned 50 bag(s) to batch 1 (Production #6).', '2025-11-15 16:05:07', '2025-11-15 08:05:07', '2025-11-15 08:05:07'),
(37, 38, NULL, 'Deducted 30 pack(s) from batch 1 (Production #6).', '2025-11-15 16:53:29', '2025-11-15 08:53:29', '2025-11-15 08:53:29'),
(38, 39, NULL, 'Deducted 45 pack(s) from batch 1 (Production #7).', '2025-11-15 17:05:05', '2025-11-15 09:05:05', '2025-11-15 09:05:05'),
(39, 40, NULL, 'Deducted 3 pack(s) from batch 2 (Production #8).', '2025-11-15 17:06:54', '2025-11-15 09:06:54', '2025-11-15 09:06:54'),
(40, 41, NULL, 'Deducted 10 pack(s) from batch 1 (Production #4).', '2025-11-16 13:59:46', '2025-11-16 05:59:46', '2025-11-16 05:59:46'),
(41, 39, NULL, 'Returned 45 pack(s) to batch 1 (Production #7).', '2025-11-16 14:01:04', '2025-11-16 06:01:04', '2025-11-16 06:01:04'),
(42, 40, NULL, 'Returned 3 pack(s) to batch 2 (Production #8).', '2025-11-16 14:01:05', '2025-11-16 06:01:05', '2025-11-16 06:01:05'),
(43, 41, NULL, 'Returned 10 pack(s) to batch 1 (Production #4).', '2025-11-19 15:45:39', '2025-11-19 07:45:39', '2025-11-19 07:45:39'),
(44, 38, NULL, 'Returned 30 pack(s) to batch 1 (Production #6).', '2025-11-19 16:08:43', '2025-11-19 08:08:43', '2025-11-19 08:08:43'),
(45, 42, NULL, 'Deducted 50 pack(s) from batch 5 (Production #13).', '2025-11-20 11:54:52', '2025-11-20 03:54:52', '2025-11-20 03:54:52'),
(46, 42, NULL, 'Returned 50 pack(s) to batch 5 (Production #13).', '2025-11-20 11:58:13', '2025-11-20 03:58:13', '2025-11-20 03:58:13'),
(47, 43, NULL, 'Deducted 3 pack(s) from batch 5 (Production #13).', '2025-11-21 08:11:08', '2025-11-21 00:11:08', '2025-11-21 00:11:08'),
(48, 44, NULL, 'Deducted 2 pack(s) from batch 2 (Production #8).', '2025-11-22 07:26:11', '2025-11-21 23:26:11', '2025-11-21 23:26:11'),
(49, 45, NULL, 'Deducted 2 pack(s) from batch 3 (Production #24).', '2025-11-22 07:46:23', '2025-11-21 23:46:23', '2025-11-21 23:46:23');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `users`
-- (See below for the actual view)
--
CREATE TABLE `users` (
`id` bigint(20) unsigned
,`name` varchar(255)
,`email` varchar(255)
,`alt_email` varchar(255)
,`email_verified_at` timestamp
,`password` varchar(255)
,`remember_token` varchar(100)
,`role` varchar(50)
,`is_active` tinyint(1)
,`website` varchar(255)
,`photo` varchar(255)
,`bio` text
,`job_title` varchar(255)
,`created_at` timestamp
,`updated_at` timestamp
,`deleted_at` timestamp
,`last_login_at` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `user_settings`
--

CREATE TABLE `user_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `appearance` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`appearance`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_settings`
--

INSERT INTO `user_settings` (`id`, `user_id`, `appearance`, `created_at`, `updated_at`) VALUES
(1, 1, '{\"theme\":\"light\",\"accent\":\"#3b82f6\",\"font_style\":\"default\"}', '2025-11-15 08:05:53', '2025-11-22 00:03:04'),
(2, 9, '{\"theme\":\"light\",\"accent\":\"#3b82f6\",\"font_style\":\"default\"}', '2025-11-20 04:09:47', '2025-11-20 04:09:48');

-- --------------------------------------------------------

--
-- Structure for view `users`
--
DROP TABLE IF EXISTS `users`;

CREATE ALGORITHM=MERGE DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `users`  AS SELECT `app_users`.`id` AS `id`, `app_users`.`name` AS `name`, `app_users`.`email` AS `email`, `app_users`.`alt_email` AS `alt_email`, `app_users`.`email_verified_at` AS `email_verified_at`, `app_users`.`password` AS `password`, `app_users`.`remember_token` AS `remember_token`, `app_users`.`role` AS `role`, `app_users`.`is_active` AS `is_active`, `app_users`.`website` AS `website`, `app_users`.`photo` AS `photo`, `app_users`.`bio` AS `bio`, `app_users`.`job_title` AS `job_title`, `app_users`.`created_at` AS `created_at`, `app_users`.`updated_at` AS `updated_at`, `app_users`.`deleted_at` AS `deleted_at`, `app_users`.`last_login_at` AS `last_login_at` FROM `app_users` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `app_users`
--
ALTER TABLE `app_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `app_users_email_unique` (`email`),
  ADD KEY `app_users_alt_email_index` (`alt_email`),
  ADD KEY `app_users_role_index` (`role`),
  ADD KEY `app_users_is_active_index` (`is_active`),
  ADD KEY `app_users_deleted_at_index` (`deleted_at`);

--
-- Indexes for table `batches`
--
ALTER TABLE `batches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `batches_product_id_index` (`product_id`),
  ADD KEY `batches_production_id_index` (`production_id`),
  ADD KEY `batches_batch_code_index` (`batch_code`);

--
-- Indexes for table `batch_allocations`
--
ALTER TABLE `batch_allocations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `batch_allocations_sale_id_index` (`sale_id`),
  ADD KEY `batch_allocations_order_item_id_index` (`order_item_id`),
  ADD KEY `batch_allocations_production_id_index` (`production_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employees_email_unique` (`email`),
  ADD UNIQUE KEY `employees_username_unique` (`username`),
  ADD KEY `employees_user_id_index` (`user_id`),
  ADD KEY `employees_status_index` (`status`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inventory_product_name_index` (`product_name`),
  ADD KEY `inventory_batch_number_index` (`batch_number`),
  ADD KEY `inventory_stock_status_index` (`stock_status`);

--
-- Indexes for table `inventory_ledgers`
--
ALTER TABLE `inventory_ledgers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inventory_ledgers_inventory_id_idx` (`inventory_id`),
  ADD KEY `inventory_ledgers_product_id_idx` (`product_id`),
  ADD KEY `inventory_ledgers_material_id_idx` (`material_id`),
  ADD KEY `inventory_ledgers_production_id_idx` (`production_id`),
  ADD KEY `inventory_ledgers_sale_id_idx` (`sale_id`);

--
-- Indexes for table `invoice_sequences`
--
ALTER TABLE `invoice_sequences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_sequences_date_key_unique` (`date_key`);

--
-- Indexes for table `login_activities`
--
ALTER TABLE `login_activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `login_activities_user_id_index` (`user_id`),
  ADD KEY `login_activities_login_at_index` (`login_at`);

--
-- Indexes for table `materials`
--
ALTER TABLE `materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `materials_name_index` (`material_name`),
  ADD KEY `materials_sku_index` (`sku`),
  ADD KEY `materials_stock_status_index` (`stock_status`);

--
-- Indexes for table `order_sequences`
--
ALTER TABLE `order_sequences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_sequences_date_key_unique` (`date_key`);

--
-- Indexes for table `productions`
--
ALTER TABLE `productions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `productions_product_id_index` (`product_id`),
  ADD KEY `productions_batch_number_index` (`batch_number`),
  ADD KEY `productions_production_date_index` (`production_date`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `products_parent_id_index` (`parent_id`),
  ADD KEY `products_batch_number_index` (`batch_number`),
  ADD KEY `products_stock_status_index` (`stock_status`);

--
-- Indexes for table `product_recipes`
--
ALTER TABLE `product_recipes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_recipes_product_id_index` (`product_id`),
  ADD KEY `product_recipes_material_id_index` (`material_id`),
  ADD KEY `product_recipes_ingredient_id_index` (`ingredient_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sales_invoice_number_index` (`invoice_number`),
  ADD KEY `sales_order_number_index` (`order_number`),
  ADD KEY `sales_unit_type_index` (`unit_type`),
  ADD KEY `sales_status_index` (`status`),
  ADD KEY `sales_product_id_index` (`product_id`),
  ADD KEY `sales_production_id_index` (`production_id`);

--
-- Indexes for table `sale_audits`
--
ALTER TABLE `sale_audits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_audits_sale_id_index` (`sale_id`),
  ADD KEY `sale_audits_order_item_id_index` (`order_item_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_settings_user_id_unique` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `app_users`
--
ALTER TABLE `app_users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `batches`
--
ALTER TABLE `batches`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `batch_allocations`
--
ALTER TABLE `batch_allocations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_ledgers`
--
ALTER TABLE `inventory_ledgers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoice_sequences`
--
ALTER TABLE `invoice_sequences`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `login_activities`
--
ALTER TABLE `login_activities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `materials`
--
ALTER TABLE `materials`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `order_sequences`
--
ALTER TABLE `order_sequences`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `productions`
--
ALTER TABLE `productions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `product_recipes`
--
ALTER TABLE `product_recipes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `sale_audits`
--
ALTER TABLE `sale_audits`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_settings`
--
ALTER TABLE `user_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `batches`
--
ALTER TABLE `batches`
  ADD CONSTRAINT `batches_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `batches_production_id_foreign` FOREIGN KEY (`production_id`) REFERENCES `productions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `batch_allocations`
--
ALTER TABLE `batch_allocations`
  ADD CONSTRAINT `batch_allocations_production_fk` FOREIGN KEY (`production_id`) REFERENCES `productions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `batch_allocations_sale_fk` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `app_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `inventory_ledgers`
--
ALTER TABLE `inventory_ledgers`
  ADD CONSTRAINT `inventory_ledgers_inventory_id_fk` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `inventory_ledgers_material_id_fk` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `inventory_ledgers_product_id_fk` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `inventory_ledgers_production_id_fk` FOREIGN KEY (`production_id`) REFERENCES `productions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `inventory_ledgers_sale_id_fk` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `login_activities`
--
ALTER TABLE `login_activities`
  ADD CONSTRAINT `login_activities_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `app_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `product_recipes`
--
ALTER TABLE `product_recipes`
  ADD CONSTRAINT `product_recipes_ingredient_id_foreign` FOREIGN KEY (`ingredient_id`) REFERENCES `materials` (`id`),
  ADD CONSTRAINT `product_recipes_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `fk_sales_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sales_production` FOREIGN KEY (`production_id`) REFERENCES `productions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `sales_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `sales_production_id_foreign` FOREIGN KEY (`production_id`) REFERENCES `productions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `sale_audits`
--
ALTER TABLE `sale_audits`
  ADD CONSTRAINT `sale_audits_sale_fk` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD CONSTRAINT `user_settings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `app_users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
