-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 24, 2026 at 03:49 AM
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
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','faculty','student') NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `class_name` varchar(50) DEFAULT NULL,
  `batch` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `subjects` text DEFAULT NULL,
  `sem` varchar(50) DEFAULT NULL,
  `designation` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `password`, `role`, `department`, `semester`, `class_name`, `batch`, `created_at`, `subjects`, `sem`, `designation`) VALUES
('1', 'System Administrator', 'admin', 'e10adc3949ba59abbe56e057f20f883e', 'admin', 'Computer Engineering', NULL, NULL, NULL, '2026-08-23 15:34:57', NULL, NULL, NULL),
('10', 'N. A. Patel', 'nap', 'e10adc3949ba59abbe56e057f20f883e', 'faculty', 'Computer Engineering', NULL, NULL, NULL, '2026-08-23 15:34:57', NULL, NULL, NULL),
('11', 'H. A. Darji', 'had', 'e10adc3949ba59abbe56e057f20f883e', 'faculty', 'Computer Engineering', NULL, NULL, NULL, '2026-08-23 15:34:57', NULL, NULL, NULL),
('12', 'M. P. Patel', 'mpp', 'e10adc3949ba59abbe56e057f20f883e', 'faculty', 'Computer Engineering', NULL, NULL, NULL, '2026-08-23 15:34:57', NULL, NULL, NULL),
('13', 'A. B. Patel', 'abp', 'e10adc3949ba59abbe56e057f20f883e', 'faculty', 'Computer Engineering', NULL, NULL, NULL, '2026-08-23 15:34:57', NULL, NULL, NULL),
('2', 'A. M. Mewada', 'amm', 'e10adc3949ba59abbe56e057f20f883e', 'faculty', 'Computer Engineering', NULL, NULL, NULL, '2026-08-23 15:34:57', NULL, NULL, NULL),
('261200100757', 'PATEL PARL URVISHKUMAR', '261200100757@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200100920', 'DAKSH AMARATBHAI MAKWANA', '261200100920@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200102421', 'PARMAR PRANJAL DINESHKUMAR', '261200102421@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200102675', 'HET PANKAJKUMAR JOSHI', '261200102675@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200102835', 'VISHVBHAI ARVINDBHAI DESAI', '261200102835@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200102957', 'CHAUHAN RUTURAJSINH VISHNUSINH', '261200102957@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200103040', 'AFNAN AFAZALBHAI CHAROLIYA', '261200103040@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200103144', 'SHUBHAM BHARATKUMAR BHAVSAR', '261200103144@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200103192', 'ARUSHI KIRANBHAI PATEL', '261200103192@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200103344', 'SENMA AJITKUMAR RAMESHBHAI', '261200103344@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200103444', 'ADITI CHANDRAKANT PANDYA', '261200103444@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200103612', 'MANSURI RAIYAN MAHEBUBHUSEN', '261200103612@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200103750', 'RAJPUT RAJENDRASINH DIPAKSINH', '261200103750@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200104034', 'RUTVA ROHITKUMAR PRAJAPATI', '261200104034@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200104172', 'PREM SHAILESHKUMAR PATEL', '261200104172@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200105011', 'SAHIL CHETANKUMAR PRAJAPATI', '261200105011@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200105306', 'BHAVIK VISHNUBHAI PATEL', '261200105306@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200105377', 'MIT MANUBHAI PRAJAPATI', '261200105377@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200105542', 'SADHU AMITKUMAR SITARAM', '261200105542@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200105655', 'THAKOR SEJAL DINESHBHAI', '261200105655@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200106296', 'THAKOR BHARATJI PRABHATJI', '261200106296@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200106401', 'DHARABEN JAYESHBHAI RAMI', '261200106401@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200106645', 'HAFSA JALALODDIN SAIYAD', '261200106645@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200107076', 'BHARATKUMAR KANUJI DUDHECHA', '261200107076@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200107108', 'OM HARESHBHAI SOLANKI', '261200107108@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200107137', 'MAHAMMADHUSEN IMTIYAJBHAI MANSURI', '261200107137@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200107585', 'PATEL AENABEN SURESHBHAI', '261200107585@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200107667', 'JASH SAMIR VYAS', '261200107667@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200107752', 'PATEL RUTU JIGARKUMAR', '261200107752@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200108587', 'DHRUV DINESHBHAI PRAJAPATI', '261200108587@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200108622', 'JENIL DIPAKKUMAR JOSHI', '261200108622@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200109023', 'RABARI SANDIP KESARBHAI', '261200109023@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200109182', 'PATEL HIMANI UMESHBHAI', '261200109182@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200109224', 'JAYMIN SURESHBHAI PANCHAL', '261200109224@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200109421', 'ASHISH RANJITBHAI KUMBHAR', '261200109421@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200109684', 'JITUJI LALAJI THAKOR', '261200109684@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200110216', 'SONI SAHILKUMAR VIKRAMBHAI', '261200110216@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200110283', 'RAJESHKUMAR MANUJI DUDHECHA', '261200110283@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200110735', 'MITRRAJSINH NIRMALSINH SOLANKI', '261200110735@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200110754', 'ARYAN JAHULKUMAR PANCHAL', '261200110754@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200110898', 'LUHAR MOHAMMADARAMAN UMARDIN', '261200110898@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200111912', 'MIKETKUMAR MAHESHBHAI PATEL', '261200111912@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200111953', 'TURI JIGARKUMAR VINODBHAI', '261200111953@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200112628', 'DHIRAJBHAI DAHYABHAI CHAUDHARY', '261200112628@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200112951', 'SOLANKI DHANRAJSINH KIRPALSINH', '261200112951@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200113306', 'PATEL ASKA KALPESHKUMAR', '261200113306@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200113330', 'MANSURI MOHAMMADABRAR ISMAILBHAI', '261200113330@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200113921', 'RUDRA DEVENDRABHAI JOSHI', '261200113921@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200114272', 'MASALIYA SHIVABHAI SOMABHAI', '261200114272@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200114385', 'SHEKH MAHAMMADMUDASSIR SAJIDBHAI', '261200114385@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200114406', 'THAKOR RAHULJI SHAMBHUJI', '261200114406@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200114537', 'THAKOR ALPESHJI SHAMBHUJI', '261200114537@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200114581', 'SOLANKI JAYDEEPKUMAR BHARATBHAI', '261200114581@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200115083', 'SALAVI MANASI VIPULKUMAR', '261200115083@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200115504', 'VEDANT SANJAYBHAI JOSHI', '261200115504@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200115973', 'NAITIK HITESHKUMAR MODI', '261200115973@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200116033', 'RAUNAKKUMAR BHUSHAN SINGH', '261200116033@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200116131', 'SANA MAHEMUD PIRJADA', '261200116131@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200116422', 'JIGNESHKUMAR BHALABHAI PRAJAPATI', '261200116422@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200116623', 'PRAJAPATI SUMITKUMAR BHARATJI', '261200116623@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200116723', 'RUDRA ALKESHBHAI LIMBACHIYA', '261200116723@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200117143', 'RAJPUT YASHSINH KETANSINH', '261200117143@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200117149', 'DIPAKKUMAR PRAVINBHAI TURI', '261200117149@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200117162', 'THAKOR RUSHIKESH KALUJI', '261200117162@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200117745', 'PARMAR HIMESH SURESHBHAI', '261200117745@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200118237', 'JIYA GAURANGKUMAR THAKAR', '261200118237@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200118673', 'ARPITBHAI CHAMANBHAI PATEL', '261200118673@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200118763', 'NITIN JIVANBHAI PATANI', '261200118763@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200118775', 'KIRANJI SHAMBHUJI THAKOR', '261200118775@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200118987', 'VINAY ALKESHKUMAR PATANI', '261200118987@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200119114', 'VANSH UMASHANKAR SHARMA', '261200119114@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200119192', 'ANIKETSINH ASHOKBHAI ZALA', '261200119192@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200119377', 'YUG AMRUTBHAI DESAI', '261200119377@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200119399', 'DARJI RIDDHIBEN GOVINDBHAI', '261200119399@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200119574', 'DWARIKA JIGNESHKUMAR SONI', '261200119574@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200119603', 'PRADIPSINH BALUBHA VAGHELA', '261200119603@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200119719', 'TARANNUMBANU ABDULKADIR RANGREJ', '261200119719@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200119837', 'JAYDIP MAHESHBHAI PRAJAPATI', '261200119837@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200120104', 'MESHVA NARESHBHAI PRAJAPATI', '261200120104@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200120113', 'DISHTI JAYESHKUMAR PATEL', '261200120113@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200120130', 'PRUTHVI PARESHKUMAR OD', '261200120130@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200120195', 'PIYUSH JITENDRAKUMAR PARMAR', '261200120195@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200120641', 'GAURAV MANOJBHAI PRAJAPATI', '261200120641@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200120766', 'PARTHSINH PRAKASHSINH SOLANKI', '261200120766@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200121084', 'HARSH SURESHBHAI JOSHI', '261200121084@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200121414', 'CHAUDHARY MAMTABEN VINUBHAI', '261200121414@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200121509', 'AAFIYA MAHAMMADSADIK SHAIKH', '261200121509@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200121696', 'AMITJI JOGIJI THAKOR', '261200121696@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200121877', 'MEHULKUMAR MAHESHBHAI VANIYA', '261200121877@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200121880', 'JINITKUMAR SURESHKUMAR SINDHI', '261200121880@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200122195', 'THAKOR VAISHALI RANAJI', '261200122195@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200122389', 'DHRUTIBEN JITENDRAKUMAR BHANUSHALI', '261200122389@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200122394', 'DILIPSANG AMRUTJI THAKOR', '261200122394@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200122494', 'LALSANG SOMAJI ZALA', '261200122494@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200122593', 'SARTHAK ALPESHKUMAR CHAUHAN', '261200122593@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200122716', 'ISHIKA KANUBHAI PATEL', '261200122716@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200122751', 'SOLANKI MIHIRBHAI NARESHBHAI', '261200122751@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200122787', 'GAYTRI RAMESHBHAI PRAJAPATI', '261200122787@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200123049', 'ARYA SANDIPKUMAR PATEL', '261200123049@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200123050', 'PARMAR KRISH DHARMENDRABHAI', '261200123050@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200123051', 'YOGENDRASING BALWANTJI RAJPUT', '261200123051@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200123082', 'THAKOR YUVRAJJI DASHARATHI', '261200123082@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200123221', 'KINJALBEN VIRAMJI THAKOR', '261200123221@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200123230', 'MAN DINESHKUMAR PATEL', '261200123230@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200123553', 'RANJITSINH LALAJI THAKOR', '261200123553@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200124535', 'TANVIBEN RAKESHKUMAR THAKKAR', '261200124535@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200124775', 'RUDRAKUMAR PIYUSHKUMAR GOHIL', '261200124775@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200125018', 'SAVAN RAKESHKUMAR PRAJAPATI', '261200125018@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200125163', 'KHYATIBEN KANAIYALAL SHRIMALI', '261200125163@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200125225', 'REHANBHAI HAIBHAI MIR', '261200125225@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200125483', 'JOSHI RUDRA KAMLESHBHAI', '261200125483@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200125830', 'TUSHAR DAMODAR MAKWANA', '261200125830@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200125908', 'ARYAN ASHOKBHAI THAKOR', '261200125908@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200125972', 'DRASHTIBEN NATAVARBHAI RAVAL', '261200125972@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200125975', 'DARSHANKUMAR DILIPBHAI PARMAR', '261200125975@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200125985', 'SOHAM CHETANBHAI CHAUDHARI', '261200125985@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200126551', 'KAVYA AVASARKUMAR OZA', '261200126551@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200126917', 'NETRA PRAVINBHAI PATEL', '261200126917@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200127360', 'RUDRAKSHA HARSHADBHAI PATEL', '261200127360@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200127389', 'YUGKUMAR BHARATKUMAR CHAUDHARI', '261200127389@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200127448', 'OM VASUDEVBHAI JOSHI', '261200127448@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200128065', 'BHAVY KRUNALKUMAR DARJI', '261200128065@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200128362', 'NEEV KAMBIKESHWAR PADHYA', '261200128362@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200128548', 'JANVI RAVIKUMAR MAKWANA', '261200128548@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200128770', 'RISHIT VIPULKUMAR PRAJAPATI', '261200128770@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200129038', 'RUTVIK KISHORBHAI SUTHAR', '261200129038@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200130886', 'PRINCEKUMAR RAMABHAI NAI', '261200130886@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200131130', 'BANSARI BHARATKUMAR LAVAR', '261200131130@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200131573', 'DIVYANSH PRAHLADBHAI CHAUDHARY', '261200131573@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200132047', 'SANJAYJI SHRAVANJI THAKOR', '261200132047@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200132138', 'SHAIKH MOHAMADAAMIR MAHAMADSALIM', '261200132138@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200132168', 'RAHULKUMAR DALPATBHAI THAKOR', '261200132168@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200132757', 'ARMAN JAVIDBHAI DHOBI', '261200132757@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200132864', 'JAYDIPJI VISHNUJI THAKOR', '261200132864@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200133384', 'PATEL KELIBEN SHAILESHBHAI', '261200133384@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200133654', 'TANISH RAMESHBHAI PATEL', '261200133654@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200133940', 'SALMAN ABBASBHAI MIR', '261200133940@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200134031', 'DHRUVI NARESHBHAI PARMAR', '261200134031@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200134108', 'THAKOR KIRANKUMAR PRATAPJI', '261200134108@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200134429', 'NAYI JAIMIN KANUBHAI', '261200134429@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200134788', 'UMANG SHAILESHKUMAR PRAJAPATI', '261200134788@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200135180', 'VANSHIKA GOPALBHAI THAKKAR', '261200135180@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200135457', 'PRAJAPATI JAINIS SATISHKUMAR', '261200135457@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200135569', 'VRUSHABH PARESHBHAI SATHVARA', '261200135569@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200135607', 'MAHERABANU MAJIDKHAN BALOCH', '261200135607@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200135650', 'DARJI SAUMYAKUMAR MAUNIKKUMAR', '261200135650@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200135877', 'SIDHDHARAJSINH PRAVINSINH GOHIL', '261200135877@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200136556', 'MAYURIBEN BAKAJI THAKOR', '261200136556@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200136655', 'CHAUHAN PRIYANIBEN MANILAL', '261200136655@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200138068', 'VIVEK PUNAMCHAND SOLANKI', '261200138068@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200138270', 'THAKOR CHETANJI SHIVAJI', '261200138270@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200138881', 'SATHVARA PRANJAL SURESHBHAI', '261200138881@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200139112', 'SENMA LIMBATKUMAR HIRABHAI', '261200139112@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200139169', 'THAKOR MEHULJI RANCHHODJI', '261200139169@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200139181', 'PARMAR PINKYBEN CHHAGANLAL', '261200139181@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200139253', 'DEVESH SURESHKUMAR NAYI', '261200139253@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200140411', 'THAKOR DAXABEN CHENJII', '261200140411@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200140767', 'LALUBHA VISHNUKUMAR ZALA', '261200140767@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200141012', 'PARV JAYESHKUMAR SHRIMALI', '261200141012@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200141162', 'KHUSHLL YAGNIKBHAI UPADHYAY', '261200141162@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200141231', 'MALEK MAHEK MAHMADSADIK', '261200141231@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200141313', 'PARMAR RASHMITABEN BABUBHAI', '261200141313@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200141865', 'HAMZA AJIJAHMAD SHAIKH', '261200141865@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200142587', 'PANCHAL DHARMIK SACHINKUMAR', '261200142587@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200143097', 'MOHAMMAD ALTAF MOMIN', '261200143097@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200143602', 'CHETANJI RAMESHJI THAKOR', '261200143602@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200143741', 'YUVRAJ BHANUBHAI SHREEMALI', '261200143741@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200143762', 'PANCHAL SHIVANI SURESHBHAI', '261200143762@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200143917', 'NIKHIL KALLANSINGH VERMAN', '261200143917@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200144539', 'ASHAL JINALBEN MAGANBHAI', '261200144539@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200144608', 'AYAN ALTAFHUSEN MEMON', '261200144608@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200145243', 'YUVRAJ RAMESHJI THAKOR', '261200145243@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200145244', 'MOHAMADHAMZA', '261200145244@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200145675', 'SNEH KULDIPBHAI MODI', '261200145675@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200146479', 'OM VISHNUBHAI PATEL', '261200146479@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200147066', 'MODI YASH RAKESHKUMAR', '261200147066@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200147355', 'PRAJAPATI JOLEE RAMESHBHAI', '261200147355@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('261200147579', 'CHAMAR GANESH PRAVINBHAI', '261200147579@kdp.edu', '123456', 'student', 'Computer Engineering', NULL, NULL, NULL, '2026-08-24 01:09:09', NULL, NULL, 'Semester 1'),
('3', 'P. R. Sharma', 'prs', 'e10adc3949ba59abbe56e057f20f883e', 'faculty', 'Computer Engineering', NULL, NULL, NULL, '2026-08-23 15:34:57', NULL, NULL, NULL),
('4', 'U. V. Patel', 'uvp', 'e10adc3949ba59abbe56e057f20f883e', 'faculty', 'Computer Engineering', NULL, NULL, NULL, '2026-08-23 15:34:57', NULL, NULL, NULL),
('5', 'R. K. Prajapati', 'rkp', 'e10adc3949ba59abbe56e057f20f883e', 'faculty', 'Computer Engineering', NULL, NULL, NULL, '2026-08-23 15:34:57', NULL, NULL, NULL),
('503', 'System Administrator', '', 'admin123', 'admin', NULL, NULL, NULL, NULL, '2026-08-23 15:35:49', NULL, NULL, NULL),
('6', 'Y. R. Patel', 'yrp', 'e10adc3949ba59abbe56e057f20f883e', 'faculty', 'Computer Engineering', NULL, NULL, NULL, '2026-08-23 15:34:57', NULL, NULL, NULL),
('7', 'M. C. Thakore', 'mct', 'e10adc3949ba59abbe56e057f20f883e', 'faculty', 'Computer Engineering', NULL, NULL, NULL, '2026-08-23 15:34:57', NULL, NULL, NULL),
('8', 'N. J. Patel', 'njp', 'e10adc3949ba59abbe56e057f20f883e', 'faculty', 'Computer Engineering', NULL, NULL, NULL, '2026-08-23 15:34:57', NULL, NULL, NULL),
('9', 'S. D. Prajapati', 'sdp', 'e10adc3949ba59abbe56e057f20f883e', 'faculty', 'Computer Engineering', NULL, NULL, NULL, '2026-08-23 15:34:57', NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
