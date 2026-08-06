-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 06, 2026 at 05:21 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `kdp_college`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','faculty','student') NOT NULL,
  `name` varchar(100) NOT NULL,
  `subjects` text DEFAULT NULL,
  `designation` varchar(100) DEFAULT 'Assistant Professor',
  `department` varchar(50) DEFAULT 'CE',
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `joined_date` varchar(50) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `cabin_no` varchar(50) DEFAULT NULL,
  `bio` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `user_id`, `password`, `role`, `name`, `subjects`, `designation`, `department`, `email`, `phone`, `status`, `joined_date`, `profile_pic`, `cabin_no`, `bio`) VALUES
(1, 'FAC-01', 'admin123', 'faculty', 'M.C. Thakor', '[\"Sem 5 - Web Development\", \"Sem 3 - Database Management\", \"Sem 5 - Python\"]', 'Assistant Professor', 'CE', 'FAC-01@uni.edu', '+91 98765 43210', 'active', 'Aug 2020', NULL, '', ''),
(3, '246310307055', '12345', 'student', 'Rahul Sharma', NULL, 'Assistant Professor', 'CE', NULL, NULL, 'active', NULL, NULL, NULL, NULL),
(8, 'admin', 'admin123', 'admin', 'System Admin', NULL, 'Assistant Professor', 'CE', NULL, NULL, 'active', NULL, NULL, NULL, NULL),
(9, 'FAC-02', 'admin123', 'faculty', 'N.A.PATEL', '[\"Sem 5 - Web Development\", \"Sem 3 - Database Management\", \"Sem 5 - Python\"]', 'Assistant Professor', 'CE', 'nareshpatel@123', '9999900000', 'active', 'Aug 2026', NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
