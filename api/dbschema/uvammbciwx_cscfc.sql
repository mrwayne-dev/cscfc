-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 14, 2026 at 10:56 AM
-- Server version: 11.4.10-MariaDB-cll-lve
-- PHP Version: 8.3.31

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `uvammbciwx_cscfc`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password_hash`, `created_at`) VALUES
(1, 'admin', '$2y$10$Prk1R8wq7M5UlMSuQ.5mOOzEa37P5W3FyWMrtsGBFVuIGjIHFr7x2', '2026-03-16 15:32:37');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(10) UNSIGNED NOT NULL,
  `player_id` int(10) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `amount` int(10) UNSIGNED NOT NULL,
  `reference` varchar(100) NOT NULL,
  `payment_type` enum('full','installment') NOT NULL DEFAULT 'installment',
  `payment_status` enum('pending','success','failed') NOT NULL DEFAULT 'pending',
  `paid_at` timestamp NULL DEFAULT NULL,
  `channel` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `player_id`, `email`, `amount`, `reference`, `payment_type`, `payment_status`, `paid_at`, `channel`, `created_at`) VALUES
(1, 12, 'etienea@gmail.com', 7000, 'CSCFC-12-22581929', 'full', 'pending', NULL, NULL, '2026-03-16 15:46:20'),
(2, 12, 'lordsonzealy@gmail.com', 7000, 'CSCFC-12-37212013', 'installment', 'pending', NULL, NULL, '2026-03-16 17:20:18'),
(3, 14, 'franklincaleb045@gmail.com', 7000, 'CSCFC-14-63880693', 'full', 'success', '2026-03-16 17:32:48', 'bank', '2026-03-16 17:32:18'),
(4, 22, 'royalamaeze@gmail.com', 7000, 'CSCFC-22-43034551', 'full', 'pending', NULL, NULL, '2026-03-16 22:38:26'),
(5, 22, 'royalamaeze@gmail.com', 7000, 'CSCFC-22-61659243', 'full', 'pending', NULL, NULL, '2026-03-16 22:39:24'),
(6, 6, 'williamschristopherndam@gmail.com', 7000, 'CSCFC-6-62488915', 'full', 'success', '2026-03-17 06:23:22', 'bank_transfer', '2026-03-17 06:22:10'),
(7, 5, 'johngoodluck294@gmail.com', 3500, 'CSCFC-5-38801345', 'installment', 'success', '2026-03-20 13:25:20', 'bank', '2026-03-20 13:22:04'),
(8, 10, 'rechidee@gmail.com', 7000, 'CSCFC-10-73770803', 'full', 'success', '2026-04-03 20:16:50', 'bank', '2026-04-03 20:15:45'),
(9, 21, 'kurounited@icloud.com', 7000, 'CSCFC-21-73572717', 'full', 'success', '2026-04-03 21:21:57', 'bank_transfer', '2026-04-03 21:20:33'),
(10, 1, 'mickiawesome77@gmail.com', 7000, 'CSCFC-1-81088853', 'full', 'success', '2026-04-04 04:21:15', 'bank_transfer', '2026-04-04 04:19:14'),
(11, 18, 'miracleokw@gmail.com', 3500, 'CSCFC-18-92605537', 'installment', 'success', '2026-04-05 20:12:08', 'bank_transfer', '2026-04-05 20:09:46'),
(12, 7, 'nsirimkennedy@gmail.com', 7000, 'CSCFC-7-17941610', 'full', 'pending', NULL, NULL, '2026-04-11 15:15:51'),
(13, 7, 'nsirimkennedy@gmail.com', 7000, 'CSCFC-7-29110325', 'full', 'success', '2026-04-11 15:17:42', 'bank_transfer', '2026-04-11 15:16:27'),
(14, 12, 'etienearchibong09@gmail.com', 7000, 'CSCFC-12-80906703', 'full', 'pending', NULL, NULL, '2026-04-23 03:00:50'),
(15, 12, 'etienearchibong09@gmail.com', 7000, 'CSCFC-12-64121262', 'full', 'success', '2026-04-23 03:03:53', 'bank_transfer', '2026-04-23 03:02:31'),
(16, 11, 'destinybishop49@gmail.com', 4000, 'CSCFC-11-26332231', 'installment', 'success', '2026-05-04 07:56:50', 'bank_transfer', '2026-05-04 07:55:19'),
(17, 5, 'johngoodluck294@gmail.com', 3500, 'CSCFC-5-79794885', 'installment', 'success', '2026-05-09 17:20:57', 'bank_transfer', '2026-05-09 17:19:40'),
(18, 25, 'winterff55@gmail.com', 7000, 'CSCFC-25-16584249', 'full', 'success', '2026-05-09 19:43:56', 'bank_transfer', '2026-05-09 19:34:43'),
(19, 2, 'danjumbogab@gmail.com', 3500, 'CSCFC-2-05769179', 'installment', 'success', '2026-05-10 21:47:39', 'bank', '2026-05-10 21:46:37'),
(20, 8, 'owenndigbara06@gmail.com', 3500, 'CSCFC-8-64002131', 'installment', 'pending', NULL, NULL, '2026-05-11 22:19:46'),
(21, 8, 'owenndigbara06@gmail.com', 3500, 'CSCFC-8-82331997', 'installment', 'success', '2026-05-11 22:21:17', 'bank_transfer', '2026-05-11 22:20:34'),
(22, 18, 'miracleokw@gmail.com', 3500, 'CSCFC-18-92538974', 'installment', 'success', '2026-05-13 07:00:19', 'bank_transfer', '2026-05-13 06:58:46'),
(23, 9, 'joshuaorinate02@gmail.com', 3500, 'CSCFC-9-61960920', 'installment', 'success', '2026-05-13 19:52:54', 'bank_transfer', '2026-05-13 19:51:48');

-- --------------------------------------------------------

--
-- Table structure for table `players`
--

CREATE TABLE `players` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `full_name` varchar(150) DEFAULT NULL,
  `matric_number` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `target_amount` int(10) UNSIGNED NOT NULL DEFAULT 7000,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `players`
--

INSERT INTO `players` (`id`, `name`, `full_name`, `matric_number`, `email`, `target_amount`, `created_at`) VALUES
(1, 'Michael', NULL, NULL, 'mickiawesome77@gmail.com', 7000, '2026-03-16 14:23:41'),
(2, 'Gabi', NULL, NULL, 'danjumbogab@gmail.com', 7000, '2026-03-16 14:23:41'),
(3, 'Rex', NULL, NULL, NULL, 7000, '2026-03-16 14:23:41'),
(4, 'Diepiriye', NULL, NULL, NULL, 7000, '2026-03-16 14:23:41'),
(5, 'Goodluck', NULL, NULL, 'johngoodluck294@gmail.com', 7000, '2026-03-16 14:23:41'),
(6, 'Bobby', NULL, NULL, 'williamschristopherndam@gmail.com', 7000, '2026-03-16 14:23:41'),
(7, 'Kennedy', NULL, NULL, 'nsirimkennedy@gmail.com', 7000, '2026-03-16 14:23:41'),
(8, 'Owen', NULL, NULL, 'owenndigbara06@gmail.com', 7000, '2026-03-16 14:23:41'),
(9, 'Bellingham', NULL, NULL, 'joshuaorinate02@gmail.com', 7000, '2026-03-16 14:23:41'),
(10, 'Champion', NULL, NULL, 'rechidee@gmail.com', 7000, '2026-03-16 14:23:41'),
(11, 'Destiny', NULL, NULL, 'destinybishop49@gmail.com', 7000, '2026-03-16 14:23:41'),
(12, 'Etienne', NULL, NULL, 'etienearchibong09@gmail.com', 7000, '2026-03-16 14:23:41'),
(13, 'Fortune', NULL, NULL, NULL, 7000, '2026-03-16 14:23:41'),
(14, 'Franklin', NULL, NULL, 'franklincaleb045@gmail.com', 7000, '2026-03-16 14:23:41'),
(15, 'Genesis', NULL, NULL, NULL, 7000, '2026-03-16 14:23:41'),
(16, 'Ibiso', NULL, NULL, NULL, 7000, '2026-03-16 14:23:41'),
(17, 'Jobi', NULL, NULL, NULL, 7000, '2026-03-16 14:23:41'),
(18, 'Mill', NULL, NULL, 'miracleokw@gmail.com', 7000, '2026-03-16 14:23:41'),
(19, 'Miller', NULL, NULL, NULL, 7000, '2026-03-16 14:23:41'),
(20, 'Raymond', NULL, NULL, NULL, 7000, '2026-03-16 14:23:41'),
(21, 'Reuben', NULL, NULL, 'kurounited@icloud.com', 7000, '2026-03-16 14:23:41'),
(22, 'Royal', NULL, NULL, 'royalamaeze@gmail.com', 7000, '2026-03-16 14:23:41'),
(23, 'Samuel', NULL, NULL, NULL, 7000, '2026-03-16 14:23:41'),
(24, 'Sultan', NULL, NULL, NULL, 7000, '2026-03-16 14:23:41'),
(25, 'Winter', NULL, NULL, 'winterff55@gmail.com', 7000, '2026-03-16 14:23:41'),
(26, 'Omezi', NULL, NULL, 'Ogowis13@gmail.com', 7000, '2026-05-13 12:54:56');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_admin_username` (`username`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_reference` (`reference`),
  ADD KEY `idx_player_id` (`player_id`),
  ADD KEY `idx_status` (`payment_status`);

--
-- Indexes for table `players`
--
ALTER TABLE `players`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_matric_number` (`matric_number`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `players`
--
ALTER TABLE `players`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payment_player` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
