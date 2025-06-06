-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 18, 2025 at 01:17 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `salary`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `check_in` datetime NOT NULL,
  `check_out` datetime DEFAULT NULL,
  `status` enum('present','late','absent','half-day') DEFAULT 'present',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`attendance_id`, `user_id`, `check_in`, `check_out`, `status`, `notes`) VALUES
(3, 12, '2025-05-17 21:59:30', '2025-05-17 22:00:18', 'present', NULL),
(4, 12, '2025-05-18 18:29:46', '2025-05-18 23:29:53', 'present', NULL),
(5, 12, '2025-05-18 18:30:04', '2025-05-18 18:30:48', 'present', NULL),
(6, 13, '2025-05-18 18:38:10', '2025-05-19 18:38:44', 'present', NULL),
(7, 1, '2025-05-18 18:43:00', '2025-05-18 18:56:56', 'present', NULL),
(8, 1, '2025-05-18 18:57:03', '2025-05-18 18:57:08', 'present', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`, `description`) VALUES
(1, 'admin', 'System administrator with full access'),
(2, 'staff', 'Regular staff member');

-- --------------------------------------------------------

--
-- Table structure for table `salary`
--

CREATE TABLE `salary` (
  `salary_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `month_year` date NOT NULL,
  `basic_salary` decimal(12,2) NOT NULL,
  `overtime_hours` decimal(10,2) DEFAULT 0.00,
  `overtime_pay` decimal(12,2) DEFAULT 0.00,
  `allowances` decimal(12,2) DEFAULT 0.00,
  `deductions` decimal(12,2) DEFAULT 0.00,
  `tax` decimal(12,2) DEFAULT 0.00,
  `net_salary` decimal(12,2) NOT NULL,
  `payment_status` enum('pending','paid','cancelled') DEFAULT 'pending',
  `payment_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `salary`
--

INSERT INTO `salary` (`salary_id`, `user_id`, `month_year`, `basic_salary`, `overtime_hours`, `overtime_pay`, `allowances`, `deductions`, `tax`, `net_salary`, `payment_status`, `payment_date`, `notes`, `created_at`, `updated_at`) VALUES
(8, 12, '2025-05-01', 750.00, 0.00, 0.00, 0.00, 250.00, 75.00, 425.00, 'paid', '2025-05-18', NULL, '2025-05-18 10:34:29', '2025-05-18 10:41:56'),
(10, 13, '2025-05-01', 2000.00, 16.00, 5000.00, 0.00, 500.00, 200.00, 6300.00, 'pending', NULL, NULL, '2025-05-18 10:39:23', '2025-05-18 10:39:23');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_id`, `setting_key`, `setting_value`, `description`) VALUES
(1, 'working_hours_per_day', '8', 'Standard working hours per day'),
(2, 'late_threshold_minutes', '15', 'Minutes after which check-in is considered late'),
(3, 'salary_calculation_day', '25', 'Day of month when salary is calculated');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role_id` int(11) NOT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `hourly_rate` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `full_name`, `role_id`, `qr_code`, `hourly_rate`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$Yf94/bj.crXKrAfF8NRur.jj3VGDjpDpKLrCO1XnFOUMIzpnpF3x.', 'admin@sample.com', 'System Administrator', 1, 'staff_1_1747564959.png', 0.00, 1, '2025-05-16 11:01:08', '2025-05-18 10:42:39'),
(12, 'john', '$2y$10$GEGalttxDn5SEvNbl4xTteyCDOixjBbp7X4EN6n4h5iJ6RBBqUSCC', 'john@gmail.com', 'John Doe', 2, 'staff_12_1747490321.png', 150.00, 1, '2025-05-17 13:57:22', '2025-05-18 11:10:03'),
(13, 'jane', '$2y$10$bnX7Cdc0R9xILEtnMnULteCpTuERaj3bR7HXUPbYdPNEN2Ewny3tW', 'jane@gmail.com', 'Jane Doe', 2, 'staff_13_1747564661.png', 250.00, 1, '2025-05-18 10:37:28', '2025-05-18 10:37:41');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD KEY `idx_attendance_user` (`user_id`),
  ADD KEY `idx_attendance_date` (`check_in`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `salary`
--
ALTER TABLE `salary`
  ADD PRIMARY KEY (`salary_id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`month_year`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `qr_code` (`qr_code`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `salary`
--
ALTER TABLE `salary`
  MODIFY `salary_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `salary`
--
ALTER TABLE `salary`
  ADD CONSTRAINT `salary_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
