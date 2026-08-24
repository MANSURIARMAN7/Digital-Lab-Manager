-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 24, 2026 at 03:46 AM
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
-- Table structure for table `lab_manuals`
--

CREATE TABLE `lab_manuals` (
  `id` int(11) NOT NULL,
  `branch` varchar(100) NOT NULL,
  `semester` varchar(50) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `practical_no` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lab_manuals`
--

INSERT INTO `lab_manuals` (`id`, `branch`, `semester`, `subject_name`, `practical_no`, `title`, `file_path`, `start_date`, `end_date`, `uploaded_at`) VALUES
(6, 'Computer Engineering', '1', 'Mathematics-I', 'PR.1', 'Demo', '../uploads/manuals/1787532759_Demo.pdf', '0000-00-00', '2026-08-30', '2026-08-24 00:52:39'),
(7, 'Computer Engineering', '1', 'Physics', 'PR.1', 'second time demo', '../uploads/manuals/1787535445_second_time_demo.pdf', '0000-00-00', '2026-08-30', '2026-08-24 01:37:25');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `lab_manuals`
--
ALTER TABLE `lab_manuals`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `lab_manuals`
--
ALTER TABLE `lab_manuals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
