-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 07, 2025 at 01:42 AM
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
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `position` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `batch_number` varchar(100) NOT NULL,
  `production_date` date DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `stock_status` varchar(50) NOT NULL DEFAULT 'In Stock',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `product_name`, `batch_number`, `production_date`, `quantity`, `stock_status`, `created_at`, `updated_at`) VALUES
(1, 'Hotdog ni Arjay ulit ulit', '1', '2025-07-05', 21, 'Out of Stock', '2025-07-05 04:20:05', '2025-07-05 04:20:05'),
(2, 'assa', 'asd', '2025-07-05', 112, 'Low Stock', '2025-07-05 04:20:14', '2025-07-06 03:55:13'),
(3, 'Hotdog ni Arjay ulit ulit', '1', '2025-07-05', 444, 'Out of Stock', '2025-07-05 05:27:47', '2025-07-05 05:27:47'),
(4, 'Hotdog ni Arjay ulit ulit', '1', '2025-07-05', 444, 'In Stock', '2025-07-05 10:23:37', '2025-07-05 10:23:37');

-- --------------------------------------------------------

--
-- Table structure for table `materials`
--

CREATE TABLE `materials` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `quantity_kg` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `materials`
--

INSERT INTO `materials` (`id`, `name`, `quantity_kg`, `created_at`, `updated_at`) VALUES
(1, 'Pork Fat', 200.00, '2025-07-06 14:59:05', '2025-07-06 15:00:47'),
(2, 'Beef Trimmings', 340.75, '2025-07-06 14:59:05', '2025-07-06 14:59:05'),
(3, 'Chicken Skin', 88.00, '2025-07-06 14:59:05', '2025-07-06 14:59:05'),
(4, 'Salt', 45.25, '2025-07-06 14:59:05', '2025-07-06 14:59:05'),
(5, 'Spices Mix', 15.00, '2025-07-06 14:59:05', '2025-07-06 14:59:05'),
(6, 'Ice', 100.00, '2025-07-06 14:59:05', '2025-07-06 14:59:05'),
(7, 'Casing', 300.00, '2025-07-06 14:59:05', '2025-07-06 14:59:05');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2025_07_01_090000_create_products_table', 1),
(2, '2025_07_01_100000_create_sales_table', 1),
(3, '2025_07_02_153158_add_status_to_sales_table', 1),
(4, '2025_07_02_185820_create_employees_table', 1),
(5, '2025_07_04_090603_create_productions_table', 1),
(6, '2025_07_04_124347_create_materials_table', 1),
(7, '2025_07_04_141211_create_settings_table', 1),
(8, '2025_07_04_143022_create_users_table', 1),
(9, '2025_07_04_155330_add_role_to_users_table', 1),
(10, '2025_07_05_110359_add_production_date_to_inventory_table', 2),
(11, '2025_07_02_162412_add_timestamps_to_products_table', 3),
(12, '2025_07_04_080000_add_status_to_sales_table', 3);

-- --------------------------------------------------------

--
-- Table structure for table `productions`
--

CREATE TABLE `productions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `forecasted_demand` int(11) NOT NULL,
  `current_inventory` int(11) NOT NULL,
  `unit_cost` decimal(10,2) NOT NULL,
  `production_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `productions`
--

INSERT INTO `productions` (`id`, `product_name`, `forecasted_demand`, `current_inventory`, `unit_cost`, `production_date`, `created_at`, `updated_at`) VALUES
(3, 'hakdogg', 2323, 3223, 234.00, '2025-07-26', '2025-07-05 05:51:29', '2025-07-05 05:51:29'),
(4, 'Hotdog ni Arjay ulit ulit', 22, 123, 223.00, NULL, '2025-07-06 00:32:15', '2025-07-06 00:32:15'),
(5, 'aa', 22, 222, 22.00, NULL, '2025-07-06 00:47:06', '2025-07-06 00:47:06'),
(6, 'aa', 22, 222, 44.00, NULL, '2025-07-06 09:09:14', '2025-07-06 09:09:14');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `date` date NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'Pending',
  `invoice_number` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `product_name`, `quantity`, `price`, `date`, `status`, `invoice_number`, `created_at`, `updated_at`) VALUES
(1, 'Hotdog ni Arjay ulit ulit', 6666, 200.00, '2025-07-05', 'Paid', 'INV-20250705-001', '2025-07-05 04:17:26', '2025-07-05 04:17:26'),
(2, 'Arjay', 7, 333.00, '2025-07-05', 'Paid', 'INV-20250705-002', '2025-07-05 04:17:57', '2025-07-05 04:17:57'),
(3, 'sa', 222, 20.00, '2025-07-06', 'Paid', 'INV-20250706-001', '2025-07-06 00:06:48', '2025-07-06 00:06:48');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `company_name`, `email`, `phone`, `address`, `created_at`, `updated_at`) VALUES
(1, 'das', 'mandalonesjeth748@gmail.com', '09457302942', '0474', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'staff',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`, `updated_at`) VALUES
(1, 'AArjay PPanganiban', 'arjaypp123@gmail.com', 'pppp1234', 'staff', '2025-07-05 02:56:05', '2025-07-05 02:56:05'),
(2, 'jethro mandaloens', 'mandalonesjeth748@gmail.com', '$2y$12$YlW6FcvLuC2oh34pdNjN/etY0aO9UQ9P4ekhFE/mNff/rvcTRvJ12', 'staff', '2025-07-05 09:56:56', '2025-07-05 09:56:56');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employees_employee_id_unique` (`employee_id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `materials`
--
ALTER TABLE `materials`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `productions`
--
ALTER TABLE `productions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `materials`
--
ALTER TABLE `materials`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `productions`
--
ALTER TABLE `productions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
