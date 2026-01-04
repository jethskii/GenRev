-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 04, 2026 at 10:59 AM
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
(1, 'Jethro k. Mandalones', 'jethrom@gmail.com', NULL, NULL, '$2y$12$ey9Mury8kX90c.z8sBx./.vwISFJTH.MiUrDnQBFrCjQPvQ5zhgte', '962bVHTUz2Pc8d2wEl98rct83Rq5Sbxjrwzpbe2wqZ4B67obhdZsjUVw5woR', 'masters admin', 1, NULL, NULL, NULL, NULL, '2025-11-14 04:27:06', '2025-12-16 23:13:02', NULL, '2025-12-16 23:13:02'),
(2, 'Jeankyla Cortuna', 'jeank@gmail.com', NULL, NULL, '$2y$12$5MnDqMemZ39pVnPZASkS9ebIVueHU9yK4ZVpfuCbNuMLQ3n7O.OKy', NULL, 'production manager', 1, NULL, NULL, NULL, NULL, '2025-11-14 05:52:47', '2025-11-14 12:12:58', NULL, '2025-11-14 12:12:58'),
(3, 'Jeankyla Cortuna', 'jeankk@gmail.com', NULL, NULL, '$2y$12$uSgWg8DNIvFBywlj7E/mVOak9FzWpillIoGqOB4NR3eRA7Y8BDJum', NULL, 'production manager', 0, NULL, NULL, NULL, NULL, '2025-11-14 11:31:32', '2025-11-16 06:11:28', NULL, NULL),
(4, 'Jethro k. Mandalones', 'danicab@gmail.com', NULL, NULL, '$2y$12$uAShhKEVPVFaJWz01GwGc.E.m6UvUXUEd2u0C4wkTZMPDwMUDtV7.', NULL, 'sales', 1, NULL, NULL, NULL, NULL, '2025-11-15 05:27:01', '2025-11-15 05:27:01', NULL, NULL),
(5, 'Elizabeth Guran', 'elizabethg@gmail.com', NULL, NULL, '$2y$12$Ye0eyKqCjxP1lhCbx75Ru.0v2/nZbwYZo8WW.C0k/To2IAOE7UFVa', NULL, 'sales', 1, NULL, NULL, NULL, NULL, '2025-11-15 07:37:05', '2025-12-05 05:30:56', NULL, '2025-12-05 05:30:56'),
(6, 'Jimboy Arvesu', 'jimboya@gmail.com', NULL, NULL, '$2y$12$lZqco.sMCcYLA7BNw8pXs.xyX7kEO00jvb0N9Nh3uKze11zMxazZi', NULL, 'inventory', 1, NULL, NULL, NULL, NULL, '2025-11-15 07:42:21', '2025-11-15 07:42:32', NULL, '2025-11-15 07:42:32'),
(7, 'Jimboy Arvesu', 'jimboyaa@gmail.com', NULL, NULL, '$2y$12$rPM2EefKnV/ge.E26gCkdeV7jTeSV3.6iYRrN22uPdP9j5QN9aCeG', NULL, 'production manager', 1, NULL, NULL, NULL, NULL, '2025-11-16 05:46:16', '2025-11-28 17:24:44', NULL, '2025-11-28 17:24:44'),
(8, 'Danica Ballesteros', 'danicabbb@gmail.com', NULL, NULL, '$2y$12$BqgR2UWP4D1HhpGK577MTOvNL126TdPkPj5L1Qn/s/s7tHzduTwI6', NULL, 'production manager', 1, NULL, NULL, NULL, NULL, '2025-11-16 06:14:58', '2025-11-20 04:08:08', NULL, '2025-11-16 06:15:07'),
(9, 'Ecco Nonee', 'ecconn@gmail.com', NULL, NULL, '$2y$12$OjJ958xemznYIb3c9yj7duBtP4TOhr7Gv4X1eQe62bMAkPW2SCSA6', NULL, 'masters admin', 1, NULL, NULL, NULL, NULL, '2025-11-20 03:44:31', '2025-11-20 04:09:29', NULL, '2025-11-20 03:44:47'),
(10, 'jevier Jefferson Del Mundo', 'jevierj@gmail.com', NULL, NULL, '$2y$12$Vsc2kLhSR4dFWybbMaYVm.Ih/RpQBDGMGMV0xXXt/4K6CfVX80S7G', NULL, 'production manager', 1, NULL, NULL, NULL, NULL, '2025-11-28 15:57:41', '2025-11-28 15:57:41', NULL, NULL),
(11, 'Allysa Averion', 'allysaa@gmail.com', NULL, NULL, '$2y$12$HCMhuc3wDoQk.O3//eMtweZtWuglZQJwOXPn37IBxqgvVE6TlNpvu', NULL, 'production manager', 1, NULL, NULL, NULL, NULL, '2025-12-05 04:32:01', '2025-12-05 05:30:08', NULL, '2025-12-05 05:30:08');

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
(39, 57, NULL, 91, 'pack', 5.000, '2025-11-28 01:32:33', '2025-11-28 01:32:33', NULL),
(40, 58, NULL, 84, 'bag', 5.000, '2025-11-28 01:32:53', '2025-11-28 01:32:53', NULL),
(41, 59, NULL, 87, 'bag', 5.000, '2025-11-28 01:46:41', '2025-11-28 03:17:42', '2025-11-28 03:17:42'),
(42, 59, NULL, 87, 'bag', 3.000, '2025-11-28 03:17:43', '2025-11-28 03:18:23', '2025-11-28 03:18:23'),
(43, 59, NULL, 87, 'bag', 4.000, '2025-11-28 03:18:23', '2025-11-28 18:30:10', '2025-11-28 18:30:10'),
(44, 60, NULL, 91, 'pack', 3.000, '2025-12-05 03:33:20', '2025-12-05 03:33:20', NULL),
(45, 61, NULL, 84, 'bag', 3.000, '2025-12-05 03:34:01', '2025-12-05 04:18:44', '2025-12-05 04:18:44'),
(46, 61, NULL, 84, 'bag', 3.000, '2025-12-05 04:18:44', '2025-12-05 04:18:44', NULL),
(47, 62, NULL, 84, 'pack', 5.000, '2025-12-05 04:21:12', '2025-12-05 04:21:12', NULL),
(48, 63, NULL, 98, 'pack', 5.000, '2025-12-05 05:13:34', '2025-12-05 05:14:25', '2025-12-05 05:14:25'),
(49, 63, NULL, 98, 'pack', 5.000, '2025-12-05 05:14:25', '2025-12-05 05:17:29', '2025-12-05 05:17:29'),
(50, 64, NULL, 87, 'bag', 2.000, '2025-12-05 05:33:38', '2025-12-05 05:54:32', '2025-12-05 05:54:32'),
(51, 65, NULL, 99, 'pack', 3.000, '2025-12-05 05:59:27', '2025-12-05 05:59:48', '2025-12-05 05:59:48'),
(52, 66, NULL, 98, 'pack', 2.000, '2025-12-16 23:05:59', '2025-12-16 23:05:59', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `demand_events`
--

CREATE TABLE `demand_events` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `event_type` varchar(30) NOT NULL DEFAULT 'reservation',
  `title` varchar(190) NOT NULL,
  `description` text DEFAULT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reserved_qty` decimal(12,3) NOT NULL DEFAULT 0.000,
  `expected_multiplier` decimal(6,3) NOT NULL DEFAULT 1.000,
  `color` varchar(20) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'planned',
  `customer_name` varchar(190) DEFAULT NULL,
  `customer_contact` varchar(190) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(5, 5, 'Elizabeth', 'Guran', NULL, 'elizabethg@gmail.com', 'elizabethg', NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2025-11-15 07:37:05', '2025-11-15 07:37:05'),
(6, 6, 'Jimboy', 'Arvesu', NULL, 'jimboya@gmail.com', 'jimboya', NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2025-11-15 07:42:21', '2025-11-15 07:42:21'),
(7, 7, 'Jimboy', 'Arvesu', NULL, 'jimboyaa@gmail.com', 'jimboyaa', NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2025-11-16 05:46:16', '2025-11-16 05:46:16'),
(8, 8, 'Danica', 'Ballesteros', NULL, 'danicabbb@gmail.com', 'danicabb', NULL, NULL, NULL, 'active', 'avatars/AbJxZAgpMubIkEe94mYTBoEDZProe6gMtpSKAsHi.jpg', NULL, NULL, '2025-11-16 06:14:59', '2025-11-21 03:38:41'),
(10, 10, 'jevier', 'Jefferson Del Mundo', NULL, 'jevierj@gmail.com', 'jevierj', NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2025-11-28 15:57:41', '2025-11-28 15:57:41'),
(11, 11, 'Allysa', 'Averion', NULL, 'allysaa@gmail.com', 'allysaa', NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2025-12-05 04:32:01', '2025-12-05 04:32:01');

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
(6, '2025-11-22', 2, '2025-11-21 23:26:08', '2025-11-21 23:46:23'),
(7, '2025-11-23', 6, '2025-11-23 04:28:10', '2025-11-23 10:58:12'),
(8, '2025-11-24', 1, '2025-11-23 21:09:44', '2025-11-23 21:09:44'),
(9, '2025-11-25', 1, '2025-11-25 07:01:00', '2025-11-25 07:01:00'),
(10, '2025-11-26', 2, '2025-11-26 09:28:34', '2025-11-26 09:58:49'),
(11, '2025-11-27', 2, '2025-11-27 12:25:42', '2025-11-27 13:02:48'),
(12, '2025-11-28', 3, '2025-11-28 01:32:30', '2025-11-28 01:46:41'),
(13, '2025-12-05', 8, '2025-12-05 03:33:16', '2025-12-05 05:59:41'),
(14, '2025-12-17', 1, '2025-12-16 23:05:56', '2025-12-16 23:05:56');

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
(34, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-22 09:56:21', NULL, 1, NULL, '2025-11-22 09:56:21', '2025-11-22 09:56:21'),
(35, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-22 20:07:58', '2025-11-22 20:42:01', 1, NULL, '2025-11-22 20:07:58', '2025-11-22 20:42:01'),
(36, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-22 20:56:47', NULL, 1, NULL, '2025-11-22 20:56:47', '2025-11-22 20:56:47'),
(37, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-23 02:19:13', NULL, 1, NULL, '2025-11-23 02:19:13', '2025-11-23 02:19:13'),
(38, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-23 10:06:28', NULL, 1, NULL, '2025-11-23 10:06:28', '2025-11-23 10:06:28'),
(39, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-23 21:08:00', NULL, 1, NULL, '2025-11-23 21:08:00', '2025-11-23 21:08:00'),
(40, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-25 02:34:18', '2025-11-25 04:50:44', 1, NULL, '2025-11-25 02:34:18', '2025-11-25 04:50:44'),
(41, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-25 04:50:55', NULL, 1, NULL, '2025-11-25 04:50:55', '2025-11-25 04:50:55'),
(42, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-26 06:40:49', NULL, 1, NULL, '2025-11-26 06:40:49', '2025-11-26 06:40:49'),
(43, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-26 17:20:35', NULL, 1, NULL, '2025-11-26 17:20:35', '2025-11-26 17:20:35'),
(44, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-26 21:30:08', NULL, 1, NULL, '2025-11-26 21:30:08', '2025-11-26 21:30:08'),
(45, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-27 08:09:25', '2025-11-27 09:05:56', 1, NULL, '2025-11-27 08:09:25', '2025-11-27 09:05:56'),
(46, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-27 09:07:51', NULL, 1, NULL, '2025-11-27 09:07:51', '2025-11-27 09:07:51'),
(47, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-27 20:47:04', NULL, 1, NULL, '2025-11-27 20:47:04', '2025-11-27 20:47:04'),
(48, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-28 15:57:51', '2025-11-28 17:24:17', 1, NULL, '2025-11-28 15:57:51', '2025-11-28 17:24:17'),
(49, 4, 'danicab@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-28 17:24:26', NULL, 0, 'invalid_credentials', '2025-11-28 17:24:26', '2025-11-28 17:24:26'),
(50, 4, 'danicab@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-28 17:24:31', NULL, 0, 'invalid_credentials', '2025-11-28 17:24:31', '2025-11-28 17:24:31'),
(51, 7, 'jimboyaa@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-28 17:24:44', '2025-11-28 17:25:09', 1, NULL, '2025-11-28 17:24:44', '2025-11-28 17:25:09'),
(52, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-28 17:25:16', '2025-11-28 18:07:43', 1, NULL, '2025-11-28 17:25:16', '2025-11-28 18:07:43'),
(53, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-28 18:10:23', NULL, 1, NULL, '2025-11-28 18:10:23', '2025-11-28 18:10:23'),
(54, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-05 03:14:42', '2025-12-05 04:29:24', 1, NULL, '2025-12-05 03:14:42', '2025-12-05 04:29:24'),
(55, 11, 'allysaa@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-05 04:32:11', '2025-12-05 04:32:40', 1, NULL, '2025-12-05 04:32:11', '2025-12-05 04:32:40'),
(56, 4, 'danicab@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-05 04:32:49', NULL, 0, 'invalid_credentials', '2025-12-05 04:32:49', '2025-12-05 04:32:49'),
(57, 4, 'danicab@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-05 04:32:53', NULL, 0, 'invalid_credentials', '2025-12-05 04:32:53', '2025-12-05 04:32:53'),
(58, 4, 'danicab@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-05 04:33:15', NULL, 0, 'invalid_credentials', '2025-12-05 04:33:15', '2025-12-05 04:33:15'),
(59, 5, 'elizabethg@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-05 04:33:29', '2025-12-05 04:33:49', 1, NULL, '2025-12-05 04:33:29', '2025-12-05 04:33:49'),
(60, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-05 04:35:59', NULL, 0, 'invalid_credentials', '2025-12-05 04:35:59', '2025-12-05 04:35:59'),
(61, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-05 04:40:46', '2025-12-05 05:29:15', 1, NULL, '2025-12-05 04:40:46', '2025-12-05 05:29:15'),
(62, 11, 'allysaa@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-05 05:30:08', '2025-12-05 05:30:42', 1, NULL, '2025-12-05 05:30:08', '2025-12-05 05:30:42'),
(63, 5, 'elizabethg@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-05 05:30:56', '2025-12-05 05:31:27', 1, NULL, '2025-12-05 05:30:56', '2025-12-05 05:31:27'),
(64, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-05 05:31:42', '2025-12-05 05:49:08', 1, NULL, '2025-12-05 05:31:42', '2025-12-05 05:49:08'),
(65, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-05 05:51:10', '2025-12-05 05:56:03', 1, NULL, '2025-12-05 05:51:10', '2025-12-05 05:56:03'),
(66, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-05 05:57:26', '2025-12-05 06:02:19', 1, NULL, '2025-12-05 05:57:26', '2025-12-05 06:02:19'),
(67, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-11 01:49:41', '2025-12-11 01:49:50', 1, NULL, '2025-12-11 01:49:41', '2025-12-11 01:49:50'),
(68, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-11 01:59:54', '2025-12-11 02:12:08', 1, NULL, '2025-12-11 01:59:54', '2025-12-11 02:12:08'),
(69, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-11 02:20:48', NULL, 1, NULL, '2025-12-11 02:20:48', '2025-12-11 02:20:48'),
(70, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-15 02:08:29', NULL, 0, 'invalid_credentials', '2025-12-15 02:08:29', '2025-12-15 02:08:29'),
(71, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-15 02:08:30', NULL, 0, 'invalid_credentials', '2025-12-15 02:08:30', '2025-12-15 02:08:30'),
(72, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-15 02:08:31', NULL, 0, 'invalid_credentials', '2025-12-15 02:08:31', '2025-12-15 02:08:31'),
(73, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-15 02:08:32', NULL, 0, 'invalid_credentials', '2025-12-15 02:08:32', '2025-12-15 02:08:32'),
(74, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-15 02:08:33', NULL, 0, 'invalid_credentials', '2025-12-15 02:08:33', '2025-12-15 02:08:33'),
(75, NULL, 'JethroM@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-15 02:08:33', NULL, 0, 'too_many_attempts', '2025-12-15 02:08:33', '2025-12-15 02:08:33'),
(76, NULL, 'jethroM@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-15 02:09:14', NULL, 0, 'too_many_attempts', '2025-12-15 02:09:14', '2025-12-15 02:09:14'),
(77, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-16 20:13:21', '2025-12-16 23:10:43', 1, NULL, '2025-12-16 20:13:21', '2025-12-16 23:10:43'),
(78, 1, 'jethrom@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-16 23:13:02', NULL, 1, NULL, '2025-12-16 23:13:02', '2025-12-16 23:13:02');

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
  `supplier_name` varchar(255) DEFAULT NULL,
  `batch_code` varchar(64) DEFAULT NULL,
  `storage_type` varchar(50) DEFAULT NULL,
  `manufactured_at` date DEFAULT NULL,
  `received_at` date DEFAULT NULL,
  `expires_at` date DEFAULT NULL,
  `notes` varchar(2000) DEFAULT NULL,
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

INSERT INTO `materials` (`id`, `material_name`, `category`, `unit`, `sku`, `supplier_name`, `batch_code`, `storage_type`, `manufactured_at`, `received_at`, `expires_at`, `notes`, `unit_price`, `quantity_kg`, `min_stock_kg`, `stock_status`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Sugar', 'Spices & Seasonings', 'kg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 30.00, 300.000, 300.000, 'low', '2025-11-14 05:57:14', '2025-11-14 05:57:14', NULL),
(2, 'Salt', 'Salt', 'kg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 40.00, 500.000, 100.000, 'in_stock', '2025-11-14 05:57:43', '2025-11-14 05:57:43', NULL),
(3, 'MDM', 'Meat Cuts & Trimmings', 'kg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 400.00, 200.000, 200.000, 'low', '2025-11-16 06:09:11', '2025-11-16 06:09:11', NULL),
(4, 'Flour', 'Packaging Films & Bags', 'kg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 50.00, 500.000, 500.000, 'low', '2025-11-20 04:05:11', '2025-11-20 04:05:11', NULL),
(5, 'Vinegar', 'Spices & Seasonings', 'lt', NULL, NULL, 'MAT-20251125-467', NULL, NULL, NULL, NULL, NULL, 30.00, 10.000, 10.000, 'low', '2025-11-25 07:04:22', '2025-11-25 07:04:22', NULL),
(6, 'Soy Sauce', 'Spices & Seasonings', 'lt', NULL, 'Jethro Mandalones', 'MAT-20251125-157', 'chiller', '2025-11-25', '2025-11-25', '2025-12-25', 'hi', 30.00, 20.000, 20.000, 'low', '2025-11-25 07:33:03', '2025-11-25 07:33:03', NULL),
(7, 'Tomato Sauce', 'Spices & Seasonings', 'tray', NULL, 'Genrev Meat Products', 'MAT-20251205-432', 'chiller', NULL, '2025-12-05', '2025-12-26', NULL, 30.00, 30.000, 30.000, 'low', '2025-12-05 03:53:47', '2025-12-05 03:53:47', NULL),
(8, 'Tomato Sauce', NULL, 'kg', NULL, 'Genrev Meat Products', 'MAT-20251217-502', NULL, NULL, '2025-12-05', '2025-12-26', NULL, 0.00, 0.000, 30.000, 'low', '2025-12-16 20:25:32', '2025-12-16 20:25:32', NULL),
(9, 'Tomato Sauce', 'Meat Cuts & Trimmings', 'kg', '44', 'Genrev Meat Products', 'MAT-20251217-773', 'freezer', '2025-12-11', '2025-12-05', '2025-12-26', NULL, 22.00, 33.000, 30.000, 'in_stock', '2025-12-16 21:07:24', '2025-12-16 21:07:24', NULL);

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
  `archived_reason` varchar(255) DEFAULT NULL,
  `output_qty_kg` decimal(14,3) NOT NULL DEFAULT 0.000
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `productions`
--

INSERT INTO `productions` (`id`, `product_id`, `parent_product_id`, `product_name_snapshot`, `batch_number`, `quantity`, `forecasted_demand`, `current_inventory`, `unit_cost`, `unit_price_pack`, `unit_price_bag`, `available_pack`, `available_bag`, `remarks`, `image_disk`, `image_path`, `image_medium_path`, `image_thumb_path`, `production_date`, `expiration_date`, `created_at`, `updated_at`, `deleted_at`, `archived_at`, `archived_reason`, `output_qty_kg`) VALUES
(45, 3, 3, 'Pork', '1', 15, 0.00, 15, 0.00, 27.00, 540.00, 10, 5, '', 'public', NULL, NULL, NULL, '2025-11-23', '2025-12-24', '2025-11-23 10:50:31', '2025-11-23 10:50:31', NULL, NULL, NULL, 0.000),
(47, 6, 6, 'Pork Tapa', '1', 20, 0.00, 20, 0.00, 50.00, 1000.00, 10, 10, '', 'public', NULL, NULL, NULL, '2025-11-23', '2025-12-24', '2025-11-23 11:18:28', '2025-11-26 09:37:12', NULL, NULL, NULL, 0.000),
(48, 6, 6, 'Pork Tapa', '2', 0, 0.00, 0, 0.00, 0.00, 0.00, 0, 0, '', 'public', NULL, NULL, NULL, '2025-11-23', '2025-12-24', '2025-11-23 11:19:57', '2025-11-26 18:35:42', NULL, NULL, NULL, 0.000),
(63, 3, 3, 'Pork', '2', 0, 0.00, 0, 0.00, 0.00, 0.00, 0, 0, '', 'public', NULL, NULL, NULL, '2025-11-23', '2025-12-24', '2025-11-26 07:18:05', '2025-11-26 07:18:05', NULL, NULL, NULL, 0.000),
(66, 5, 5, 'Sweet Ham', '2', 0, 0.00, 0, 0.00, 0.00, 0.00, 0, 0, '', 'public', NULL, NULL, NULL, '2025-11-25', '2025-11-28', '2025-11-26 07:28:35', '2025-11-26 07:28:35', NULL, NULL, NULL, 0.000),
(70, 5, 5, 'Sweet Ham', '3', 0, 36.00, 0, 0.00, 0.00, 0.00, 0, 0, '', 'public', NULL, NULL, NULL, '2025-11-25', '2025-11-28', '2025-11-26 07:39:46', '2025-11-26 07:39:46', NULL, NULL, NULL, 0.000),
(72, 4, 4, 'Pork', '1', 0, 0.00, 0, 0.00, 0.00, 0.00, 0, 0, '', 'public', NULL, NULL, NULL, '2025-11-26', '2025-12-03', '2025-11-26 08:04:36', '2025-11-26 08:04:36', NULL, NULL, NULL, 0.000),
(73, 3, 3, 'Pork', '3', 0, 0.00, 0, 0.00, 0.00, 0.00, 0, 0, '', 'public', NULL, NULL, NULL, '2025-11-23', '2025-12-24', '2025-11-26 10:08:08', '2025-11-26 10:08:08', NULL, NULL, NULL, 0.000),
(74, 3, 3, 'Pork', '4', 0, 0.00, 0, 0.00, 0.00, 0.00, 0, 0, '', 'public', NULL, NULL, NULL, '2025-11-23', '2025-12-24', '2025-11-26 10:08:25', '2025-11-26 10:08:25', NULL, NULL, NULL, 0.000),
(77, 1, 1, 'Special', '1', 50, 0.00, 50, 0.00, 22.00, 440.00, 30, 20, '', 'public', NULL, NULL, NULL, '2025-11-28', '2025-12-28', '2025-11-28 01:16:39', '2025-11-28 01:16:39', NULL, NULL, NULL, 0.000),
(78, 1, 1, 'Regular', '2', 20, 0.00, 20, 0.00, 22.00, 440.00, 10, 10, '', 'public', NULL, NULL, NULL, '2025-11-28', '2025-12-28', '2025-11-28 01:17:28', '2025-11-28 01:17:28', NULL, NULL, NULL, 0.000),
(79, 1, 1, 'Chicken', '3', 50, 0.00, 50, 0.00, 22.00, 440.00, 20, 30, '', 'public', NULL, NULL, NULL, '2025-11-28', '2025-12-28', '2025-11-28 01:17:55', '2025-11-28 01:17:55', NULL, NULL, NULL, 0.000),
(80, 1, 1, 'Hamonado', '4', 20, 0.00, 20, 0.00, 22.00, 440.00, 10, 10, '', 'public', NULL, NULL, NULL, '2025-11-28', '2025-12-28', '2025-11-28 01:18:22', '2025-11-28 01:18:22', NULL, NULL, NULL, 0.000),
(81, 1, 1, 'Garlic', '5', 50, 0.00, 50, 0.00, 22.00, 440.00, 20, 30, '', 'public', NULL, NULL, NULL, '2025-11-28', '2025-12-28', '2025-11-28 01:18:53', '2025-11-28 01:18:53', NULL, NULL, NULL, 0.000),
(82, 1, 1, 'Beef', '6', 50, 0.00, 50, 0.00, 22.00, 440.00, 20, 30, '', 'public', NULL, NULL, NULL, '2025-11-28', '2025-12-28', '2025-11-28 01:19:20', '2025-11-28 01:19:20', NULL, NULL, NULL, 0.000),
(83, 2, 2, 'Pork', '1', 30, 19.00, 30, 0.00, 30.00, 600.00, 20, 10, '', 'public', NULL, NULL, NULL, '2025-11-28', '2025-12-28', '2025-11-28 01:20:33', '2025-11-28 01:20:33', NULL, NULL, NULL, 0.000),
(84, 2, 2, 'Chicken', '2', 30, 19.00, 30, 0.00, 31.00, 620.00, 15, 2, '', 'public', NULL, NULL, NULL, '2025-11-28', '2025-12-28', '2025-11-28 01:21:11', '2025-12-05 04:21:12', NULL, NULL, NULL, 0.000),
(86, 7, 7, 'Pork', '1', 50, 0.00, 50, 0.00, 27.00, 540.00, 20, 30, '', 'public', NULL, NULL, NULL, '2025-11-28', '2025-12-28', '2025-11-28 01:24:46', '2025-11-28 01:24:46', NULL, NULL, NULL, 0.000),
(87, 7, 7, 'Chicken', '2', 50, 0.00, 50, 0.00, 27.00, 540.00, 30, 20, '', 'public', NULL, NULL, NULL, '2025-11-28', '2025-12-28', '2025-11-28 01:25:40', '2025-12-05 05:58:54', NULL, NULL, NULL, 0.000),
(88, 8, 8, 'Pork', '1', 30, 0.00, 30, 0.00, 50.00, 1000.00, 20, 10, '', 'public', NULL, NULL, NULL, '2025-11-28', '2025-12-28', '2025-11-28 01:27:53', '2025-11-28 01:27:53', NULL, NULL, NULL, 0.000),
(89, 8, 8, 'Pork', '2', 35, 0.00, 35, 0.00, 50.00, 1000.00, 30, 5, '', 'public', NULL, NULL, NULL, '2025-11-28', '2025-12-28', '2025-11-28 01:28:21', '2025-11-28 01:28:21', NULL, NULL, NULL, 0.000),
(90, 8, 8, 'Pork', '3', 30, 0.00, 30, 0.00, 50.00, 1000.00, 10, 20, '', 'public', NULL, NULL, NULL, '2025-11-28', '2025-12-02', '2025-11-28 01:29:04', '2025-12-05 03:34:56', '2025-12-05 03:34:56', NULL, NULL, 0.000),
(91, 2, 2, 'Pork', '3', 30, 19.00, 30, 0.00, 30.00, 600.00, 2, 20, '', 'public', NULL, NULL, NULL, '2025-11-28', '2025-12-01', '2025-11-28 01:31:33', '2025-12-05 03:33:20', NULL, NULL, NULL, 0.000),
(92, 9, 9, 'Pork', '1', 23, 0.00, 23, 0.00, 33.00, 660.00, 20, 3, '', 'public', NULL, NULL, NULL, '2025-11-28', '2025-12-28', '2025-11-28 01:37:40', '2025-11-28 01:37:40', NULL, NULL, NULL, 0.000),
(93, 9, 9, 'Pork', '2', 30, 0.00, 30, 0.00, 33.00, 660.00, 20, 10, '', 'public', NULL, NULL, NULL, '2025-11-28', '2025-11-30', '2025-11-28 01:42:16', '2025-11-28 01:42:16', NULL, NULL, NULL, 0.000),
(94, 9, 9, 'Pork', '3', 30, 0.00, 30, 0.00, 33.00, 660.00, 20, 10, '', 'public', NULL, NULL, NULL, '2025-11-28', '2025-11-30', '2025-11-28 01:44:58', '2025-11-28 01:44:58', NULL, NULL, NULL, 0.000),
(95, 10, 10, 'Pork', '1', 3, 0.00, 3, 0.00, 45.00, 900.00, 2, 1, '', 'public', NULL, NULL, NULL, '2025-11-28', '2025-12-02', '2025-11-28 01:46:04', '2025-12-05 03:34:35', '2025-12-05 03:34:35', NULL, NULL, 0.000),
(96, 10, 10, 'Pork', '1', 9, 0.00, 9, 0.00, 27.00, 500.00, 4, 5, '', 'public', NULL, NULL, NULL, '2025-12-05', '2025-12-08', '2025-12-05 03:36:13', '2025-12-16 21:46:02', '2025-12-16 21:46:02', NULL, NULL, 0.000),
(97, 7, 7, 'Pork', '3', 60, 0.00, 60, 0.00, 27.00, 450.00, 30, 30, '', 'public', NULL, NULL, NULL, '2025-11-28', '2026-01-06', '2025-12-05 04:15:17', '2025-12-05 04:16:09', '2025-12-05 04:16:09', NULL, NULL, 0.000),
(98, 2, 2, 'Pork', '4', 35, 19.00, 35, 0.00, 27.00, 450.00, 28, 5, '', 'public', NULL, NULL, NULL, '2025-11-28', '2026-01-05', '2025-12-05 05:11:01', '2025-12-16 23:05:59', NULL, NULL, NULL, 0.000),
(99, 2, 2, 'Pork', '5', 256, 20.00, 256, 0.00, 23.00, 1233.00, 233, 23, '', 'public', NULL, NULL, NULL, '2025-11-28', '2026-01-05', '2025-12-05 05:53:04', '2025-12-05 05:59:48', '2025-12-05 05:59:48', NULL, 'from_sale', 0.000),
(100, 2, 2, 'Pork', '5', 20, 20.00, 20, 0.00, 27.00, 450.00, 10, 10, '', 'public', NULL, NULL, NULL, '2025-11-28', '2025-12-08', '2025-12-16 23:07:23', '2025-12-16 23:07:23', NULL, NULL, NULL, 0.000),
(101, 2, 2, 'Pork', '6', 15, 21.00, 15, 0.00, 27.00, 459.00, 5, 10, '', 'public', NULL, NULL, NULL, '2025-11-28', '2025-12-20', '2025-12-16 23:08:07', '2025-12-16 23:08:07', NULL, NULL, NULL, 0.000);

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
(1, NULL, NULL, 'Skinless longganisa', NULL, NULL, NULL, 'Beef', 'public', 'products/1/skinless-longganisa.webp', NULL, NULL, '2025-11-28', 240, NULL, 0.00, NULL, NULL, 0, 'http://localhost:8000/storage/products/1/skinless-longganisa.webp', 'http://localhost:8000/storage/products/1/skinless-longganisa.webp', NULL, 'in_stock', NULL, NULL, '2025-11-14 04:56:06', '2025-11-28 01:19:20', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, NULL, NULL, 'Bologna', NULL, NULL, NULL, 'Pork', 'public', 'products/2/bologna.png', '', '', '2025-11-28', 114, NULL, 1790.00, NULL, NULL, 21, 'http://localhost:8000/storage/products/2/bologna.png', 'http://localhost:8000/storage/products/2/bologna.png', NULL, 'in_stock', NULL, NULL, '2025-11-15 08:03:09', '2025-12-16 23:08:07', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, NULL, NULL, 'Embutido', NULL, NULL, NULL, 'Pork', 'public', 'products/3/embutido.png', NULL, NULL, '2025-11-23', 15, NULL, 0.00, NULL, NULL, 0, 'http://localhost:8000/storage/products/3/embutido.png', 'http://localhost:8000/storage/products/3/embutido.png', NULL, 'in_stock', NULL, NULL, '2025-11-15 09:04:27', '2025-11-26 18:06:11', '2025-11-26 18:06:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, NULL, NULL, 'Salami', NULL, NULL, NULL, 'Pork', 'public', 'products/4/salami.png', NULL, NULL, '2025-11-26', 0, NULL, 0.00, NULL, NULL, 0, 'http://localhost:8000/storage/products/4/salami.png', 'http://localhost:8000/storage/products/4/salami.png', NULL, 'out_of_stock', NULL, NULL, '2025-11-22 00:05:22', '2025-11-26 10:17:24', '2025-11-26 10:17:24', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, NULL, NULL, 'Sweet ham', NULL, NULL, NULL, 'Sweet Ham', 'public', 'products/5/sweet-ham.png', '', '', '2025-11-25', 0, NULL, 0.00, NULL, NULL, 36, 'http://localhost:8000/storage/products/5/sweet-ham.png', 'http://localhost:8000/storage/products/5/sweet-ham.png', NULL, 'out_of_stock', NULL, NULL, '2025-11-23 06:23:08', '2025-11-26 17:54:31', '2025-11-26 17:54:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, NULL, NULL, 'Pork tapa', NULL, NULL, NULL, 'Pork Tapa', 'public', 'products/6/pork-tapa.png', '', '', '2025-11-23', 20, NULL, 0.00, NULL, NULL, 0, 'http://localhost:8000/storage/products/6/pork-tapa.png', 'http://localhost:8000/storage/products/6/pork-tapa.png', NULL, 'in_stock', NULL, NULL, '2025-11-23 11:18:27', '2025-11-26 17:59:15', '2025-11-26 17:59:15', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, NULL, NULL, 'Embutido', NULL, NULL, NULL, 'Pork', 'public', 'products/7/embutido.png', NULL, NULL, '2025-11-28', 100, NULL, 0.00, NULL, NULL, 0, 'http://localhost:8000/storage/products/7/embutido.png', 'http://localhost:8000/storage/products/7/embutido.png', NULL, 'in_stock', NULL, NULL, '2025-11-28 01:23:30', '2025-12-05 05:54:32', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(8, NULL, NULL, 'Pork tapa', NULL, NULL, NULL, 'Pork', 'public', 'products/8/pork-tapa.png', NULL, NULL, '2025-11-28', 65, NULL, 0.00, NULL, NULL, 0, 'http://localhost:8000/storage/products/8/pork-tapa.png', 'http://localhost:8000/storage/products/8/pork-tapa.png', NULL, 'in_stock', NULL, NULL, '2025-11-28 01:27:53', '2025-11-28 01:27:53', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(9, NULL, NULL, 'Salami', NULL, NULL, NULL, 'Pork', 'public', 'products/9/salami.png', NULL, NULL, '2025-11-28', 83, NULL, 0.00, NULL, NULL, 0, 'http://localhost:8000/storage/products/9/salami.png', 'http://localhost:8000/storage/products/9/salami.png', NULL, 'in_stock', NULL, NULL, '2025-11-28 01:37:40', '2025-11-28 01:44:58', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(10, NULL, NULL, 'Pork tocino', NULL, NULL, NULL, 'Pork', 'public', 'products/10/pork-tocino.png', NULL, NULL, NULL, 0, NULL, 0.00, NULL, NULL, 0, 'http://localhost:8000/storage/products/10/pork-tocino.png', 'http://localhost:8000/storage/products/10/pork-tocino.png', NULL, 'out_of_stock', NULL, NULL, '2025-11-28 01:46:04', '2025-12-05 03:36:12', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

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
  `quantity_per_unit` decimal(14,3) DEFAULT NULL,
  `wastage_pct` decimal(5,2) NOT NULL DEFAULT 0.00,
  `unit` varchar(50) NOT NULL DEFAULT 'kg',
  `unit_price_snapshot` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_recipes`
--

INSERT INTO `product_recipes` (`id`, `product_id`, `material_id`, `ingredient_id`, `qty`, `quantity_per_unit`, `wastage_pct`, `unit`, `unit_price_snapshot`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 2, 2.000, NULL, 0.00, 'kg', 40.00, '2025-11-14 05:58:16', '2025-12-17 05:33:10'),
(2, 1, 1, 1, 3.000, NULL, 0.00, 'kg', 30.00, '2025-11-14 05:58:24', '2025-12-17 05:33:10'),
(14, 2, 3, 3, 1.000, NULL, 0.00, 'kg', 400.00, '2025-11-25 09:29:58', '2025-12-17 05:33:10'),
(15, 2, 2, 2, 1.000, NULL, 0.00, 'kg', 40.00, '2025-11-25 09:30:02', '2025-12-17 05:33:10'),
(16, 2, 1, 1, 1.000, NULL, 0.00, 'kg', 30.00, '2025-11-25 09:30:07', '2025-12-17 05:33:10'),
(17, 2, 3, 3, 1.000, NULL, 0.00, 'kg', 400.00, '2025-11-25 09:32:38', '2025-12-17 05:33:10'),
(18, 2, 2, 2, 1.000, NULL, 0.00, 'kg', 40.00, '2025-11-25 09:32:38', '2025-12-17 05:33:10'),
(19, 2, 1, 1, 1.000, NULL, 0.00, 'kg', 30.00, '2025-11-25 09:32:38', '2025-12-17 05:33:10'),
(20, 2, 4, 4, 1.000, NULL, 0.00, 'kg', 50.00, '2025-12-05 03:52:37', '2025-12-17 05:33:10'),
(21, 2, 3, 3, 1.000, NULL, 0.00, 'kg', 400.00, '2025-12-05 05:53:50', '2025-12-17 05:33:10'),
(22, 2, 3, 3, 1.000, NULL, 0.00, 'kg', 400.00, '2025-12-05 06:00:24', '2025-12-17 05:33:10');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `production_id` bigint(20) UNSIGNED DEFAULT NULL,
  `reserved_date` date NOT NULL,
  `units` int(10) UNSIGNED NOT NULL,
  `unit_type` varchar(20) NOT NULL DEFAULT 'pack',
  `customer_name` varchar(255) DEFAULT NULL,
  `reference_code` varchar(255) DEFAULT NULL,
  `notes` varchar(1000) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'reserved',
  `sale_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id`, `product_id`, `production_id`, `reserved_date`, `units`, `unit_type`, `customer_name`, `reference_code`, `notes`, `status`, `sale_id`, `user_id`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 2, NULL, '2025-11-23', 5, 'bag', NULL, NULL, 'Jethro Mandalones', 'reserved', NULL, 1, '2025-11-27 21:29:36', '2025-11-27 21:29:36', NULL),
(2, 2, NULL, '2025-11-29', 3, 'pack', NULL, NULL, NULL, 'reserved', NULL, 1, '2025-11-27 21:30:21', '2025-11-27 21:30:21', NULL),
(3, 2, NULL, '2025-11-27', 2, 'bag', NULL, NULL, 'Jethro Mandalones', 'reserved', NULL, 1, '2025-11-27 22:21:32', '2025-11-27 22:21:32', NULL),
(4, 2, NULL, '2025-11-28', 2, 'pack', NULL, NULL, '2', 'reserved', NULL, 1, '2025-11-27 22:22:04', '2025-11-27 22:22:04', NULL),
(5, 2, NULL, '2025-11-23', 3, 'pack', NULL, NULL, 'Jethro Mandalones', 'reserved', NULL, 1, '2025-11-27 22:52:33', '2025-11-27 22:52:33', NULL),
(6, 2, NULL, '2025-11-27', 3, 'pack', NULL, NULL, 'Jethro Mandalones', 'reserved', NULL, 1, '2025-11-27 22:53:18', '2025-11-27 22:53:18', NULL),
(7, 1, NULL, '2025-11-28', 2, 'pack', NULL, NULL, 'Jethro Mandalones', 'reserved', NULL, 1, '2025-11-27 23:20:58', '2025-11-27 23:20:58', NULL),
(8, 2, NULL, '2025-11-29', 3, 'pack', NULL, NULL, 'Jethro mandalones', 'reserved', NULL, 1, '2025-11-27 23:23:25', '2025-11-27 23:23:25', NULL),
(9, 2, NULL, '2025-11-28', 2, 'pack', NULL, NULL, 'Jethro Mandalones', 'reserved', NULL, 1, '2025-11-27 23:44:47', '2025-11-27 23:44:47', NULL),
(10, 8, NULL, '2025-11-27', 5, 'pack', NULL, NULL, 'Jethro Mandalones', 'reserved', NULL, 1, '2025-11-28 01:48:59', '2025-11-28 01:48:59', NULL),
(11, 2, NULL, '2025-12-08', 3, 'pack', NULL, NULL, 'Ms. Hycian Bonilla', 'reserved', NULL, 1, '2025-12-05 04:11:21', '2025-12-05 04:11:21', NULL),
(12, 2, NULL, '2025-12-08', 3, 'pack', NULL, NULL, 'Ms. Hycian Bonilla', 'reserved', NULL, 1, '2025-12-05 04:11:42', '2025-12-05 04:11:42', NULL),
(13, 2, NULL, '2025-12-08', 3, 'pack', NULL, NULL, NULL, 'reserved', NULL, 1, '2025-12-05 04:12:09', '2025-12-05 04:12:09', NULL),
(14, 8, NULL, '2025-12-10', 2, 'pack', NULL, NULL, 'Ms. Lykah joy', 'reserved', NULL, 1, '2025-12-05 05:06:34', '2025-12-05 05:06:34', NULL);

-- --------------------------------------------------------

--
-- Stand-in structure for view `reservation_events`
-- (See below for the actual view)
--
CREATE TABLE `reservation_events` (
`id` bigint(20) unsigned
,`product_id` bigint(20) unsigned
,`product_name` varchar(255)
,`title` varchar(190)
,`start_date` date
,`end_date` date
,`reserved_qty` decimal(12,3)
,`status` varchar(20)
);

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
(57, 2, 30.00, '2025-11-28', NULL, NULL, 91, 'INV-20251128-001', 'INV-20251128-001', 'Bologna', 'Pork', NULL, '2025-11-28 00:00:00', 5, 5.000, 'pack', 30.00, 150.00, 150.00, 'Pending', NULL, NULL, '2025-11-28 01:32:33', '2025-11-28 01:32:33', NULL),
(58, 2, 620.00, '2025-11-28', NULL, NULL, 84, 'INV-20251128-002', 'INV-20251128-002', 'Bologna', 'Chicken', NULL, '2025-11-28 00:00:00', 5, 5.000, 'bag', 620.00, 3100.00, 3100.00, 'Pending', NULL, NULL, '2025-11-28 01:32:53', '2025-11-28 01:32:53', NULL),
(59, 7, 540.00, '2025-11-28', NULL, NULL, 87, 'INV-20251128-003', 'INV-20251128-003', 'Embutido', 'Chicken', NULL, '2025-11-28 00:00:00', 4, 4.000, 'bag', 540.00, 2160.00, 2160.00, 'Paid', 'Jethro Mandalones', NULL, '2025-11-28 01:46:41', '2025-11-28 18:30:10', '2025-11-28 18:30:10'),
(60, 2, 30.00, '2025-12-05', NULL, NULL, 91, 'INV-20251205-001', 'INV-20251205-001', 'Bologna', 'Pork', NULL, '2025-12-05 00:00:00', 3, 3.000, 'pack', 30.00, 90.00, 90.00, 'Pending', NULL, NULL, '2025-12-05 03:33:20', '2025-12-05 03:33:20', NULL),
(61, 2, 620.00, '2025-12-05', NULL, NULL, 84, 'INV-20251205-003', 'INV-20251205-003', 'Bologna', 'Chicken', NULL, '2025-12-05 00:00:00', 3, 3.000, 'bag', 620.00, 1860.00, 1860.00, 'Paid', NULL, NULL, '2025-12-05 03:34:01', '2025-12-05 04:18:44', NULL),
(62, 2, 31.00, '2025-12-05', NULL, NULL, 84, 'INV-20251205-004', 'INV-20251205-004', 'Bologna', 'Chicken', NULL, '2025-12-05 00:00:00', 5, 5.000, 'pack', 31.00, 155.00, 155.00, 'Pending', 'Cleo Fae Jantar', NULL, '2025-12-05 04:21:12', '2025-12-05 05:42:14', NULL),
(63, 2, 27.00, '2025-12-05', NULL, NULL, 98, 'INV-20251205-005', 'INV-20251205-005', 'Bologna', 'Pork', NULL, '2025-12-05 00:00:00', 5, 5.000, 'pack', 27.00, 135.00, 135.00, 'Paid', 'Cleo Fae Jantar', NULL, '2025-12-05 05:13:34', '2025-12-05 05:17:29', '2025-12-05 05:17:29'),
(64, 7, 540.00, '2025-12-05', NULL, NULL, 87, 'INV-20251205-006', 'INV-20251205-006', 'Embutido', 'Chicken', NULL, '2025-12-05 00:00:00', 2, 2.000, 'bag', 540.00, 1080.00, 1080.00, 'Pending', NULL, NULL, '2025-12-05 05:33:38', '2025-12-05 05:54:32', '2025-12-05 05:54:32'),
(65, 2, 1390.00, '2025-12-05', NULL, NULL, 99, 'INV-20251205-007', 'INV-20251205-007', 'Bologna', 'Pork', NULL, '2025-12-05 00:00:00', 3, 3.000, 'pack', 1390.00, 4170.00, 4170.00, 'Pending', NULL, NULL, '2025-12-05 05:59:27', '2025-12-05 05:59:48', '2025-12-05 05:59:48'),
(66, 2, 27.00, '2025-12-17', NULL, NULL, 98, 'INV-20251217-001', 'INV-20251217-001', 'Bologna', 'Pork', NULL, '2025-12-17 00:00:00', 2, 2.000, 'pack', 27.00, 54.00, 54.00, 'Pending', NULL, NULL, '2025-12-16 23:05:59', '2025-12-16 23:05:59', NULL);

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
(75, 57, NULL, 'Deducted 5 pack(s) from batch 3 (Production #91).', '2025-11-28 09:32:33', '2025-11-28 01:32:33', '2025-11-28 01:32:33'),
(76, 58, NULL, 'Deducted 5 bag(s) from batch 2 (Production #84).', '2025-11-28 09:32:53', '2025-11-28 01:32:53', '2025-11-28 01:32:53'),
(77, 59, NULL, 'Deducted 5 bag(s) from batch 2 (Production #87).', '2025-11-28 09:46:41', '2025-11-28 01:46:41', '2025-11-28 01:46:41'),
(78, 59, NULL, 'Returned 5 bag(s) to batch 2 (Production #87).', '2025-11-28 11:17:42', '2025-11-28 03:17:42', '2025-11-28 03:17:42'),
(79, 59, NULL, 'Deducted 3 bag(s) from batch 2 (Production #87).', '2025-11-28 11:17:43', '2025-11-28 03:17:43', '2025-11-28 03:17:43'),
(80, 59, NULL, 'Returned 3 bag(s) to batch 2 (Production #87).', '2025-11-28 11:18:23', '2025-11-28 03:18:23', '2025-11-28 03:18:23'),
(81, 59, NULL, 'Deducted 4 bag(s) from batch 2 (Production #87).', '2025-11-28 11:18:23', '2025-11-28 03:18:23', '2025-11-28 03:18:23'),
(82, 59, NULL, 'Returned 4 bag(s) to batch 2 (Production #87).', '2025-11-29 02:30:10', '2025-11-28 18:30:10', '2025-11-28 18:30:10'),
(83, 60, NULL, 'Deducted 3 pack(s) from batch 3 (Production #91).', '2025-12-05 11:33:20', '2025-12-05 03:33:20', '2025-12-05 03:33:20'),
(84, 61, NULL, 'Deducted 3 bag(s) from batch 2 (Production #84).', '2025-12-05 11:34:01', '2025-12-05 03:34:01', '2025-12-05 03:34:01'),
(85, 61, NULL, 'Returned 3 bag(s) to batch 2 (Production #84).', '2025-12-05 12:18:44', '2025-12-05 04:18:44', '2025-12-05 04:18:44'),
(86, 61, NULL, 'Deducted 3 bag(s) from batch 2 (Production #84).', '2025-12-05 12:18:45', '2025-12-05 04:18:45', '2025-12-05 04:18:45'),
(87, 62, NULL, 'Deducted 5 pack(s) from batch 2 (Production #84).', '2025-12-05 12:21:12', '2025-12-05 04:21:12', '2025-12-05 04:21:12'),
(88, 63, NULL, 'Deducted 5 pack(s) from batch 4 (Production #98).', '2025-12-05 13:13:34', '2025-12-05 05:13:34', '2025-12-05 05:13:34'),
(89, 63, NULL, 'Returned 5 pack(s) to batch 4 (Production #98).', '2025-12-05 13:14:25', '2025-12-05 05:14:25', '2025-12-05 05:14:25'),
(90, 63, NULL, 'Deducted 5 pack(s) from batch 4 (Production #98).', '2025-12-05 13:14:25', '2025-12-05 05:14:25', '2025-12-05 05:14:25'),
(91, 63, NULL, 'Returned 5 pack(s) to batch 4 (Production #98).', '2025-12-05 13:17:29', '2025-12-05 05:17:29', '2025-12-05 05:17:29'),
(92, 64, NULL, 'Deducted 2 bag(s) from batch 2 (Production #87).', '2025-12-05 13:33:38', '2025-12-05 05:33:38', '2025-12-05 05:33:38'),
(93, 64, NULL, 'Returned 2 bag(s) to batch 2 (Production #87).', '2025-12-05 13:54:32', '2025-12-05 05:54:32', '2025-12-05 05:54:32'),
(94, 65, NULL, 'Deducted 3 pack(s) from batch 5 (Production #99).', '2025-12-05 13:59:27', '2025-12-05 05:59:27', '2025-12-05 05:59:27'),
(95, 65, NULL, 'Returned 3 pack(s) to batch 5 (Production #99).', '2025-12-05 13:59:48', '2025-12-05 05:59:48', '2025-12-05 05:59:48'),
(96, 66, NULL, 'Deducted 2 pack(s) from batch 4 (Production #98).', '2025-12-17 07:05:59', '2025-12-16 23:05:59', '2025-12-16 23:05:59');

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
-- Structure for view `reservation_events`
--
DROP TABLE IF EXISTS `reservation_events`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `reservation_events`  AS SELECT `de`.`id` AS `id`, `de`.`product_id` AS `product_id`, `p`.`product_name` AS `product_name`, `de`.`title` AS `title`, `de`.`start_date` AS `start_date`, `de`.`end_date` AS `end_date`, `de`.`reserved_qty` AS `reserved_qty`, `de`.`status` AS `status` FROM (`demand_events` `de` left join `products` `p` on(`p`.`id` = `de`.`product_id`)) WHERE `de`.`event_type` = 'reservation' AND `de`.`status` <> 'cancelled' AND `de`.`deleted_at` is null ;

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
-- Indexes for table `demand_events`
--
ALTER TABLE `demand_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_demand_events_product` (`product_id`);

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
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reservations_user_id` (`user_id`),
  ADD KEY `idx_reservations_product_id` (`product_id`);

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `batches`
--
ALTER TABLE `batches`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `batch_allocations`
--
ALTER TABLE `batch_allocations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `demand_events`
--
ALTER TABLE `demand_events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `login_activities`
--
ALTER TABLE `login_activities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `materials`
--
ALTER TABLE `materials`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `order_sequences`
--
ALTER TABLE `order_sequences`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `productions`
--
ALTER TABLE `productions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=102;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `product_recipes`
--
ALTER TABLE `product_recipes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `sale_audits`
--
ALTER TABLE `sale_audits`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;

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
-- Constraints for table `demand_events`
--
ALTER TABLE `demand_events`
  ADD CONSTRAINT `fk_demand_events_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

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
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `fk_reservations_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reservations_user` FOREIGN KEY (`user_id`) REFERENCES `app_users` (`id`) ON DELETE SET NULL;

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
