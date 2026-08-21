-- Database creation SQL dump for KDP Digital Lab Manager
-- Database name: kdp_college

CREATE DATABASE IF NOT EXISTS `kdp_college`;
USE `kdp_college`;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Table structure for table `users`
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `bio` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `users`
INSERT INTO `users` (`id`, `user_id`, `password`, `role`, `name`, `subjects`, `designation`, `department`, `email`, `phone`, `status`, `joined_date`, `profile_pic`, `cabin_no`, `bio`) VALUES
(1, 'FAC-01', 'admin123', 'faculty', 'M.C. Thakor', '["Sem 5 - Web Development", "Sem 3 - Database Management", "Sem 5 - Python"]', 'Assistant Professor', 'CE', 'FAC-01@uni.edu', '+91 98765 43210', 'active', 'Aug 2020', NULL, '', ''),
(3, '246310307055', '12345', 'student', 'Rahul Sharma', NULL, 'Assistant Professor', 'CE', NULL, NULL, 'active', NULL, NULL, NULL, NULL),
(8, 'admin', 'admin123', 'admin', 'System Admin', NULL, 'Assistant Professor', 'CE', NULL, NULL, 'active', NULL, NULL, NULL, NULL),
(9, 'FAC-02', 'admin123', 'faculty', 'N.A.PATEL', '["Sem 5 - Web Development", "Sem 3 - Database Management", "Sem 5 - Python"]', 'Assistant Professor', 'CE', 'nareshpatel@123', '9999900000', 'active', 'Aug 2026', NULL, NULL, NULL);

-- Table structure for table `submissions`
DROP TABLE IF EXISTS `submissions`;
CREATE TABLE `submissions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `enrollment` VARCHAR(50) NOT NULL,
  `subject` VARCHAR(100) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `status` ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
  `marks` INT DEFAULT NULL,
  `remark` TEXT DEFAULT NULL,
  `upload_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;
