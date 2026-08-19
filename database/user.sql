-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 19, 2026 at 04:56 PM
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
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','faculty','student') NOT NULL,
  `department` varchar(100) DEFAULT 'Computer Engineering',
  `sem` varchar(20) DEFAULT NULL,
  `designation` varchar(100) DEFAULT 'Assistant Professor',
  `cabin_no` varchar(50) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `subjects` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`subjects`)),
  `profile_pic` varchar(255) DEFAULT NULL,
  `joined_date` date DEFAULT NULL,
  `status` enum('Active','Inactive','Suspended') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `user_id`, `name`, `email`, `phone`, `password`, `role`, `department`, `sem`, `designation`, `cabin_no`, `bio`, `subjects`, `profile_pic`, `joined_date`, `status`, `created_at`) VALUES
(1, 'admin', 'HOD - Computer Engineering', NULL, NULL, 'admin123', 'admin', 'Computer Engineering', NULL, 'HOD', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-06 15:58:36'),
(4, '246310307055', 'MANSURI ARMAN', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 5', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-07 13:33:56'),
(5, '246310307003', 'BEHLIM HAMZA', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 5', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-07 13:34:19'),
(15, 'MCT', 'M.C. Thakor', '', '', '123456', 'faculty', 'Computer Engineering', NULL, 'Faculty', '', '', NULL, NULL, NULL, 'Active', '2026-08-19 13:05:51'),
(16, 'MPP', 'M.P. Patel', NULL, NULL, '123456', 'faculty', 'Computer Engineering', NULL, 'Faculty', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:05:51'),
(17, 'ABP', 'A.B. Patel', NULL, NULL, '123456', 'faculty', 'Computer Engineering', NULL, 'Faculty', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:05:51'),
(18, 'PRS', 'P.R. S', NULL, NULL, '123456', 'faculty', 'Computer Engineering', NULL, 'Faculty', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:05:51'),
(19, 'UVP', 'U.V. P', NULL, NULL, '123456', 'faculty', 'Computer Engineering', NULL, 'Faculty', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:05:51'),
(20, 'PVY', 'P.V. Y', NULL, NULL, '123456', 'faculty', 'Computer Engineering', NULL, 'Faculty', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:05:51'),
(21, 'RKP', 'R.K. P', NULL, NULL, '123456', 'faculty', 'Computer Engineering', NULL, 'Faculty', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:05:51'),
(22, '261200121509', 'AAFIYA MAHAMMADSADIK SHAIΚΗ', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(23, '261200103444', 'ADITI CHANDRAKANT PANDYA', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(24, '261200103040', 'AFNAN AFAZALBHAI CHAROLIYA', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(25, '261200121696', 'AMITJI JOGIJI THAKOR', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(26, '261200119192', 'ANIKETSINH ASHOKBHAI ZALA', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(27, '261200132757', 'ARMAN JAVIDBHAI DHOBI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(28, '261200118673', 'ARPITBHAI CHAMANBHAI PATEL', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(29, '261200103192', 'ARUSHI KIRANBHAI PATEL', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(30, '261200123049', 'ARYA SANDIPKUMAR PATEL', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(31, '261200125908', 'ARYAN ASHOKBHAI THAKOR', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(32, '261200110754', 'ARYAN JAHULKUMAR PANCHAL', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(33, '261200144539', 'ASHAL JINALBEN MAGANBHAI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(34, '261200109421', 'ASHISH RANJITBHAI KUMBHAR', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(35, '261200144608', 'AYAN ALTAFHUSEN MEMON', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(36, '261200131130', 'BANSARI BHARATKUMAR LAVAR', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(37, '261200107076', 'BHARATKUMAR KANUJI DUDHECHA', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(38, '261200105306', 'BHAVIK VISHNUBHAI PATEL', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(39, '261200128065', 'BHAVY KRUNALKUMAR DARJI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(40, '261200147579', 'CHAMAR GANESH PRAVINBHAI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(41, '261200121414', 'CHAUDHARY MAMTABEN VINUBHAI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(42, '261200136655', 'CHAUHAN PRIYANIBEN MANILAL', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(43, '261200102957', 'CHAUHAN RUTURAJSINH VISHNUSINH', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(44, '261200143602', 'CHETANJI RAMESHJI THAKOR', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(45, '261200100920', 'DAKSH AMARATBHAI MAKWANA', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(46, '261200119399', 'DARJI RIDDHIBEN GOVINDBHAI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(47, '261200135650', 'DARJI SAUMYAKUMAR MAUNIKKUMAR', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(48, '261200125975', 'DARSHANKUMAR DILIPBHAI PARMAR', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(49, '261200139253', 'DEVESH SURESHKUMAR NAYI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(50, '261200106401', 'DHARABEN JAYESHBHAI RAMI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(51, '261200112628', 'DHIRAJBHAI DAHYABHAI CHAUDHARY', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(52, '261200122389', 'DHRUTIBEN JITENDRAKUMAR BHANUSHALI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(53, '261200108587', 'DHRUV DINESHBHAI PRAJAPATI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(54, '261200134031', 'DHRUVI NARESHBHAI PARMAR', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(55, '261200122394', 'DILIPSANG AMRUTJI THAKOR', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(56, '261200117149', 'DIPAKKUMAR PRAVINBHAI TURI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(57, '261200120113', 'DISHTI JAYESHKUMAR PATEL', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(58, '261200131573', 'DIVYANSH PRAHLADBHAI CHAUDHARY', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(59, '261200125972', 'DRASHTIBEN NATAVARBHAI RAVAL', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(60, '261200119574', 'DWARIKA JIGNESHKUMAR SONI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(61, '261200120641', 'GAURAV MANOJBHAI PRAJAPATI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(62, '261200122787', 'GAYTRI RAMESHBHAI PRAJAPATI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(63, '261200106645', 'Hafsa jalaloddin saryad', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(64, '261200141865', 'HAMZA AJIJAHMAD SHAΙΚΗ', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(65, '261200121084', 'HARSH SURESHBHAI JOSHI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(66, '261200102675', 'HET PANKAJKUMAR JOSHI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(67, '261200122716', 'ISHIKA KANUBHAI PATEL', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(68, '261200128548', 'JANVI RAVIKUMAR MAKWANA', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(69, '261200107667', 'JASH SAMIR VYAS', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(70, '261200119837', 'JAYDIP MAHESHBHAI PRAJAPATI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(71, '261200132864', 'JAYDIPJI VISHNUJI THAKOR', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(72, '261200109224', 'JAYMIN SURESHBHAI PANCHAL', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(73, '261200108622', 'JENIL DIPAKKUMAR JOSHI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(74, '261200116422', 'JIGNESHKUMAR BHALABHAI PRAJAPATI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(75, '261200121880', 'JINITKUMAR SURESHKUMAR SINDHI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(76, '261200109684', 'JITUJI LALAJI THAKOR', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(77, '261200118237', 'JIYA GAURANGKUMAR THAKAR', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(78, '261200125483', 'JOSHI RUDRA KAMLESHBHAI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(79, '261200126551', 'KAVYA AVASARKUMAR OZA', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(80, '261200141162', 'KHUSHLL YAGNIKBHAI UPADHYAY', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(81, '261200125163', 'KHYATIBEN KANAIYALAL SHRIMALI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(82, '261200123221', 'KINJALBEN VIRAMJI THAKOR', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(83, '261200118775', 'KIRANJI SHAMBHUJI THAKOR', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(84, '261200122494', 'LALSANG SOMAJI ZALA', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(85, '261200140767', 'LALUBHA VISHNUKUMAR ZALA', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(86, '261200110898', 'LUHAR MOHAMMADARAMAN UMARDIN', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(87, '261200107137', 'MAHAMMADHUSEN IMTIYAJBHAI MANSURI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(88, '261200135607', 'MAHERABANU MAJIDKHAN BALOCH', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(89, '261200141231', 'MALEK MAHEK MAHMADSADIK', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(90, '261200123230', 'MAN DINESHKUMAR PATEL', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(91, '261200113330', 'MANSURI MOHAMMADABRAR ISMAILBHAI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(92, '261200103612', 'MANSURI RAIYAN MAHEBUBHUSEN', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(93, '261200114272', 'MASALIYA SHIVABHAI SOMABHAI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(94, '261200136556', 'MAYURIBEN BAKAJI THAKOR', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(95, '261200121877', 'MEHULKUMAR MAHESHBHAI VANIYA', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(96, '261200120104', 'MESHVA NARESHBHAI PRAJAPATI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(97, '261200111912', 'MIKETKUMAR MAHESHBHAI PATEL', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(98, '261200105377', 'MIT MANUBHAI PRAJAPATI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(99, '261200110735', 'MITRRAJSINH NIRMALSINH SOLANKI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(100, '261200147066', 'MODI YASH RAKESHKUMAR', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(101, '261200145244', 'MOHAMADHAMZA', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(102, '261200143097', 'MOHAMMAD ALTAF MOMIN', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(103, '261200115973', 'NAITIK HITESHKUMAR MODI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(104, '261200134429', 'NAYI JAIMIN KANUBHAI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(105, '261200128362', 'NEEV KAMBIKESHWAR PADHYA', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(106, '261200126917', 'NETRA PRAVINBHAI PATEL', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(107, '261200143917', 'NIKHIL KALLANSINGH VERMAN', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(108, '261200118763', 'NITIN JIVANBHAI PATANI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(109, '261200107108', 'OM HARESHBHAI SOLANKI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(110, '261200127448', 'OM VASUDEVBHAI JOSHI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(111, '261200146479', 'OM VISHNUBHAI PATEL', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(112, '261200142587', 'PANCHAL DHARMIK SACHINKUMAR', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(113, '261200143762', 'PANCHAL SHIVANI SURESHBHAI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(114, '261200117745', 'PARMAR HIMESH SURESHBHAI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(115, '261200123050', 'PARMAR KRISH DHARMENDRABHAI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(116, '261200139181', 'PARMAR PINKYBEN CHHAGANLAL', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(117, '261200102421', 'PARMAR PRANJAL DINESHKUMAR', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(118, '261200141313', 'PARMAR RASHMITABEN BABUBHAI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(119, '261200120766', 'PARTHSINH PRAKASHSINH SOLANKI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(120, '261200141012', 'PARV JAYESHKUMAR SHRIMALI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(121, '261200107585', 'PATEL AENABEN SURESHBHAI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(122, '261200113306', 'PATEL ASKA KALPESHKUMAR', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(123, '261200109182', 'PATEL HIMANI UMESHBHAI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(124, '261200133384', 'PATEL KELIBEN SHAILESHBHAI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(125, '261200100757', 'PATEL PARL URVISHKUMAR', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(126, '261200107752', 'PATEL RUTU JIGARKUMAR', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(127, '261200120195', 'PIYUSH JITENDRAKUMAR PARMAR', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(128, '261200119603', 'PRADIPSINH BALUBHA VAGHELA', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(129, '261200135457', 'PRAJAPATI JAINIS SATISHKUMAR', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(130, '261200147355', 'PRAJAPATI JOLEE RAMESHBHAI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(131, '261200116623', 'PRAJAPATI SUMITKUMAR BHARATJI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(132, '261200104172', 'PREM SHAILESHKUMAR PATEL', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(133, '261200130886', 'PRINCEKUMAR RAMABHAI NAI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(134, '261200120130', 'PRUTHVI PARESHKUMAR OD', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(135, '261200109023', 'RABARI SANDIP KESARBHAI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(136, '261200132168', 'RAHULKUMAR DALPATBHAI THAKOR', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(137, '261200110283', 'RAJESHKUMAR MANUJI DUDHECHA', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(138, '261200103750', 'RAJPUT RAJENDRASINH DIPAKSINH', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(139, '261200117143', 'RAJPUT YASHSINH KETANSINH', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(140, '261200123553', 'RANJITSINH LALAJI THAKOR', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(141, '261200116033', 'RAUNAKKUMAR BHUSHAN SINGH', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(142, '261200125225', 'REHANBHAI HAIBHAI MIR', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(143, '261200128770', 'RISHIT VIPULKUMAR PRAJAPATI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(144, '261200116723', 'RUDRA ALKESHBHAI LIMBACHIYA', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(145, '261200113921', 'RUDRA DEVENDRABHAI JOSHI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(146, '261200127360', 'RUDRAKSHA HARSHADBHAI PATEL', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(147, '261200124775', 'RUDRAKUMAR PIYUSHKUMAR GOHIL', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(148, '261200104034', 'RUTVA ROHITKUMAR PRAJAPATI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(149, '261200129038', 'RUTVIK KISHORBHAI SUTHAR', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(150, '261200105542', 'SADHU AMITKUMAR SITARAM', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(151, '261200105011', 'SAHIL CHETANKUMAR PRAJAPATI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(152, '261200115083', 'SALAVI MANASI VIPULKUMAR', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(153, '261200133940', 'SALMAN ABBASBHAI MIR', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(154, '261200116131', 'SANA MAHEMUD PIRJADA', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(155, '261200132047', 'SANJAYJI SHRAVANJI THAKOR', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(156, '261200122593', 'SARTHAK ALPESHKUMAR CHAUHAN', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(157, '261200138881', 'SATHVARA PRANJAL SURESHBHAI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(158, '261200125018', 'SAVAN RAKESHKUMAR PRAJAPATI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(159, '261200103344', 'SENMA AJITKUMAR RAMESHBHAI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(160, '261200139112', 'SENMA LIMBATKUMAR HIRABHAI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(161, '261200132138', 'SHAIKH MOHAMADAAMIR MAHAMADSALIM', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(162, '261200114385', 'SHEKH MAHAMMADMUDASSIR SAJIDBHAI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(163, '261200103144', 'SHUBHAM BHARATKUMAR BHAVSAR', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(164, '261200135877', 'SIDHDHARAJSINH PRAVINSINH GOHIL', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(165, '261200145675', 'SNEH KULDIPBHAI MODI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(166, '261200125985', 'SOHAM CHETANBHAI CHAUDHARI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(167, '261200112951', 'SOLANKI DHANRAJSINH KIRPALSINH', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(168, '261200114581', 'SOLANKI JAYDEEPKUMAR BHARATBHAI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(169, '261200122751', 'SOLANKI MIHIRBHAI NARESHBHAI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(170, '261200110216', 'SONI SAHILKUMAR VIKRAMBHAI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(171, '261200133654', 'TANISH RAMESHBHAI PATEL', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(172, '261200124535', 'TANVIBEN RAKESHKUMAR THAKKAR', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(173, '261200119719', 'TARANNUMBANU ABDULKADIR RANGREJ', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(174, '261200114537', 'THAKOR ALPESHJI SHAMBHUJI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(175, '261200106296', 'THAKOR BHARATJI PRABНАТЛ', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(176, '261200138270', 'THAKOR CHETANJI SHIVAJI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(177, '261200140411', 'THAKOR DAXABEN CHENJII', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(178, '261200134108', 'THAKOR KIRANKUMAR PRATAPJI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(179, '261200139169', 'THAKOR MEHULJI RANCHHODJI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(180, '261200114406', 'THAKOR RAHULJI SHAMBHUJI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(181, '261200117162', 'THAKOR RUSHIKESH KALUJI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(182, '261200105655', 'THAKOR SEJAL DINESHBHAI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(183, '261200122195', 'THAKOR VAISHALI RANAJI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(184, '261200123082', 'THAKOR YUVRAJJI DASHARATHI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(185, '261200111953', 'TURI JIGARKUMAR VINODBHAI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(186, '261200125830', 'TUSHAR DAMODAR MAKWANA', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(187, '261200134788', 'UMANG SHAILESHKUMAR PRAJAPATI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(188, '261200119114', 'VANSH UMASHANKAR SHARMA', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(189, '261200135180', 'VANSHIKA GOPALBHAI THAKKAR', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(190, '261200115504', 'VEDANT SANJAYBHAI JOSHI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(191, '261200118987', 'VINAY ALKESHKUMAR PATANI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(192, '261200102835', 'VISHVBHAI ARVINDBHAI DESAI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(193, '261200138068', 'VIVEK PUNAMCHAND SOLANKI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(194, '261200135569', 'VRUSHABH PARESHBHAI SATHVARA', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(195, '261200123051', 'YOGENDRASING BALWANTJI RAJPUT', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(196, '261200119377', 'YUG AMRUTBHAI DESAI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(197, '261200127389', 'YUGKUMAR BHARATKUMAR CHAUDHARI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(198, '261200143741', 'YUVRAJ BHANUBHAI SHREEMALI', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32'),
(199, '261200145243', 'YUVRAJ RAMESHJI THAKOR', NULL, NULL, '123456', 'student', 'Computer Engineering', NULL, 'Semester 1', NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 13:08:32');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=200;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
