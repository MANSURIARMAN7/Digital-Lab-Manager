-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 24, 2026 at 03:48 AM
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
-- Database: `kdp_collage`
--

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `subject_code` varchar(50) DEFAULT NULL,
  `subject_name` varchar(255) NOT NULL,
  `semester` varchar(50) NOT NULL,
  `department` varchar(100) NOT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `faculty_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `semester`, `department`, `status`, `created_at`, `faculty_name`) VALUES
(182, NULL, 'Mathematics-I', '1', 'Computer Engineering', 'Active', '2026-08-23 14:50:36', 'M. P. Patel (MPP) / H. A. Darji (HAD)'),
(183, NULL, 'Physics', '1', 'Computer Engineering', 'Active', '2026-08-23 14:50:36', 'A. B. Patel (ABP)'),
(184, NULL, 'Computer Programming Fundamentals (CPF)', '1', 'Computer Engineering', 'Active', '2026-08-23 14:50:36', 'P. R. Sharma (PRS) / A. M. Mewada (AMM) / N. J. Patel (NJP)'),
(185, NULL, 'Basics of Electronics (BOE)', '1', 'Computer Engineering', 'Active', '2026-08-23 14:50:36', 'U. V. Patel (UVP) / R. K. Prajapati (RKP) / Y. R. Patel (YRP)'),
(186, NULL, 'Computer Systems & Environment (CSE)', '1', 'Computer Engineering', 'Active', '2026-08-23 14:50:36', 'P. Vijay (PVY)'),
(187, NULL, 'Computer Building & Software Practice / Development (CB&SWPD)', '1', 'Computer Engineering', 'Active', '2026-08-23 14:50:36', 'N. A. Patel (NAP) / S. D. Prajapati (SDP) / M. C. Thakore (MCT)'),
(188, NULL, 'Sports and Yoga (SAY)', '1', 'Computer Engineering', 'Active', '2026-08-23 14:50:36', 'P. M. Prajapati / K. R. Prajapati'),
(189, NULL, 'Basic Python', '2', 'Computer Engineering', 'Active', '2026-08-23 15:24:44', 'D. R. Dodiya (DRD)');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=190;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
