-- Database creation SQL dump for KDP Digital Lab Manager
-- Database name: kdp_college

CREATE DATABASE IF NOT EXISTS `kdp_college`;
USE `kdp_college`;

-- Table structure for table `users`
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'faculty', 'student') NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `subjects` TEXT DEFAULT NULL,
  `sem` VARCHAR(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dumping data for table `users`
INSERT INTO `users` (`id`, `user_id`, `password`, `role`, `name`, `subjects`, `sem`) VALUES
(1, 'FAC-01', 'admin', 'faculty', 'M.C. Thakor', '["Web Development", "Database Management"]', NULL),
(2, 'admin', 'admin123', 'admin', 'System Administrator', NULL, NULL);

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
