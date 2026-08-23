-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 19, 2026 at 04:55 PM
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
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `subject_code` varchar(50) DEFAULT NULL,
  `subject_name` varchar(255) NOT NULL,
  `semester` varchar(50) NOT NULL,
  `department` varchar(100) NOT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `semester`, `department`, `status`, `created_at`) VALUES
(7, NULL, 'Contributor Personality Development', 'Semester 2', 'Computer Engineering', 'Active', '2026-08-15 15:18:52'),
(8, NULL, 'Advanced Mathematics (Group-1)', 'Semester 2', 'Computer Engineering', 'Active', '2026-08-15 15:18:52'),
(9, NULL, 'Basic Physics (Group-2)', 'Semester 2', 'Computer Engineering', 'Active', '2026-08-15 15:18:52'),
(10, NULL, 'Basic Electronics', 'Semester 2', 'Computer Engineering', 'Active', '2026-08-15 15:18:52'),
(11, NULL, 'Advanced Computer Programming', 'Semester 2', 'Computer Engineering', 'Active', '2026-08-15 15:18:52'),
(12, NULL, 'Static Web Page Designing', 'Semester 2', 'Computer Engineering', 'Active', '2026-08-15 15:18:52'),
(13, NULL, 'Operating System', 'Semester 3', 'Computer Engineering', 'Active', '2026-08-15 15:18:52'),
(14, NULL, 'Programming in C++', 'Semester 3', 'Computer Engineering', 'Active', '2026-08-15 15:18:52'),
(15, NULL, 'Database Management System', 'Semester 3', 'Computer Engineering', 'Active', '2026-08-15 15:18:52'),
(16, NULL, 'Data Structure', 'Semester 3', 'Computer Engineering', 'Active', '2026-08-15 15:18:52'),
(17, NULL, 'Microprocessor & Assembly Language Programming', 'Semester 3', 'Computer Engineering', 'Active', '2026-08-15 15:18:52'),
(18, NULL, 'Advanced Database Management System', 'Semester 4', 'Computer Engineering', 'Active', '2026-08-15 15:18:52'),
(19, NULL, 'Computer Networks', 'Semester 4', 'Computer Engineering', 'Active', '2026-08-15 15:18:52'),
(20, NULL, 'Fundamentals of Software Development', 'Semester 4', 'Computer Engineering', 'Active', '2026-08-15 15:18:52'),
(21, NULL, '.NET Programming', 'Semester 4', 'Computer Engineering', 'Active', '2026-08-15 15:18:52'),
(22, NULL, 'Computer Organization and Architecture', 'Semester 4', 'Computer Engineering', 'Active', '2026-08-15 15:18:52'),
(23, NULL, 'Web Development Tools', 'Semester 4', 'Computer Engineering', 'Active', '2026-08-15 15:18:52'),
(24, NULL, 'Computer Maintenance and Troubleshooting', 'Semester 5', 'Computer Engineering', 'Active', '2026-08-15 15:18:52'),
(25, NULL, 'Dynamic Web Page Development', 'Semester 5', 'Computer Engineering', 'Active', '2026-08-15 15:18:52'),
(26, NULL, 'Java Programming', 'Semester 5', 'Computer Engineering', 'Active', '2026-08-15 15:18:52'),
(27, NULL, 'Project-I', 'Semester 5', 'Computer Engineering', 'Active', '2026-08-15 15:18:52'),
(28, NULL, 'Computer and Network Security', 'Semester 5', 'Computer Engineering', 'Active', '2026-08-15 15:18:52'),
(29, NULL, 'Multimedia and Animation Techniques', 'Semester 5', 'Computer Engineering', 'Active', '2026-08-15 15:18:52'),
(30, NULL, 'Advance Java Programming', 'Semester 6', 'Computer Engineering', 'Active', '2026-08-15 15:18:52'),
(31, NULL, 'Professional Practices Using Database', 'Semester 6', 'Computer Engineering', 'Active', '2026-08-15 15:18:52'),
(32, NULL, 'Project-II', 'Semester 6', 'Computer Engineering', 'Active', '2026-08-15 15:18:52'),
(33, NULL, 'Networking Management & Administration', 'Semester 6', 'Computer Engineering', 'Active', '2026-08-15 15:18:52'),
(34, NULL, 'Mobile Computing and Application Development', 'Semester 6', 'Computer Engineering', 'Active', '2026-08-15 15:18:52'),
(35, NULL, 'Dynamic Webpage with Scripting Language', 'Semester 6', 'Computer Engineering', 'Active', '2026-08-15 15:18:52'),
(36, NULL, 'Advanced Web Technology', 'Semester 6', 'Computer Engineering', 'Active', '2026-08-15 15:18:52'),
(37, NULL, 'Basic Mathematics', 'Semester 1', 'Civil Engineering', 'Active', '2026-08-15 15:18:52'),
(38, NULL, 'English', 'Semester 1', 'Civil Engineering', 'Active', '2026-08-15 15:18:52'),
(39, NULL, 'Environment Conservation & Hazard Management', 'Semester 1', 'Civil Engineering', 'Active', '2026-08-15 15:18:52'),
(40, NULL, 'Engineering Physics', 'Semester 1', 'Civil Engineering', 'Active', '2026-08-15 15:18:52'),
(41, NULL, 'Basic Engineering Drawing', 'Semester 1', 'Civil Engineering', 'Active', '2026-08-15 15:18:52'),
(42, NULL, 'Computer Application & Graphics', 'Semester 1', 'Civil Engineering', 'Active', '2026-08-15 15:18:52'),
(43, NULL, 'Contributor Personality Development', 'Semester 2', 'Civil Engineering', 'Active', '2026-08-15 15:18:52'),
(44, NULL, 'Advanced Mathematics (Group-2)', 'Semester 2', 'Civil Engineering', 'Active', '2026-08-15 15:18:52'),
(45, NULL, 'Applied Mechanics', 'Semester 2', 'Civil Engineering', 'Active', '2026-08-15 15:18:52'),
(46, NULL, 'Applied Chemistry (Group-1)', 'Semester 2', 'Civil Engineering', 'Active', '2026-08-15 15:18:52'),
(47, NULL, 'Building Drawing', 'Semester 2', 'Civil Engineering', 'Active', '2026-08-15 15:18:52'),
(48, NULL, 'Basic Mechanical Engineering', 'Semester 2', 'Civil Engineering', 'Active', '2026-08-15 15:18:52'),
(49, NULL, 'Civil Engineering Workshop Practice', 'Semester 2', 'Civil Engineering', 'Active', '2026-08-15 15:18:52'),
(50, NULL, 'Building Materials', 'Semester 3', 'Civil Engineering', 'Active', '2026-08-15 15:18:52'),
(51, NULL, 'Construction Technology', 'Semester 3', 'Civil Engineering', 'Active', '2026-08-15 15:18:52'),
(52, NULL, 'Hydraulics', 'Semester 3', 'Civil Engineering', 'Active', '2026-08-15 15:18:52'),
(53, NULL, 'Structural Mechanics', 'Semester 3', 'Civil Engineering', 'Active', '2026-08-15 15:18:52'),
(54, NULL, 'Surveying', 'Semester 3', 'Civil Engineering', 'Active', '2026-08-15 15:18:52'),
(55, NULL, 'Structural Mechanics-II', 'Semester 4', 'Civil Engineering', 'Active', '2026-08-15 15:18:52'),
(56, NULL, 'Advanced Surveying', 'Semester 4', 'Civil Engineering', 'Active', '2026-08-15 15:18:52'),
(57, NULL, 'Basic Transportation Engineering', 'Semester 4', 'Civil Engineering', 'Active', '2026-08-15 15:18:52'),
(58, NULL, 'Water Resources Management', 'Semester 4', 'Civil Engineering', 'Active', '2026-08-15 15:18:52'),
(59, NULL, 'Soil Mechanics', 'Semester 4', 'Civil Engineering', 'Active', '2026-08-15 15:18:52'),
(60, NULL, 'Computer Aided Drawing', 'Semester 4', 'Civil Engineering', 'Active', '2026-08-15 15:18:52'),
(61, NULL, 'Design of Reinforced Concrete Structures', 'Semester 5', 'Civil Engineering', 'Active', '2026-08-15 15:18:52'),
(62, NULL, 'Quantity Surveying & Valuation', 'Semester 5', 'Civil Engineering', 'Active', '2026-08-15 15:18:52'),
(63, NULL, 'Advanced Construction Technology', 'Semester 5', 'Civil Engineering', 'Active', '2026-08-15 15:18:52'),
(64, NULL, 'Environmental Engineering', 'Semester 5', 'Civil Engineering', 'Active', '2026-08-15 15:18:52'),
(65, NULL, 'Transportation Engineering', 'Semester 5', 'Civil Engineering', 'Active', '2026-08-15 15:18:52'),
(66, NULL, 'Project-I', 'Semester 5', 'Civil Engineering', 'Active', '2026-08-15 15:18:52'),
(67, NULL, 'Design of Steel Structure', 'Semester 6', 'Civil Engineering', 'Active', '2026-08-15 15:18:52'),
(68, NULL, 'Practice in Design of Steel Structure', 'Semester 6', 'Civil Engineering', 'Active', '2026-08-15 15:18:52'),
(69, NULL, 'Advanced Construction Technology', 'Semester 6', 'Civil Engineering', 'Active', '2026-08-15 15:18:52'),
(70, NULL, 'Advanced Construction Technology Practice', 'Semester 6', 'Civil Engineering', 'Active', '2026-08-15 15:18:52'),
(71, NULL, 'Concrete Technology', 'Semester 6', 'Civil Engineering', 'Active', '2026-08-15 15:18:52'),
(72, NULL, 'Project-II', 'Semester 6', 'Civil Engineering', 'Active', '2026-08-15 15:18:52'),
(73, NULL, 'Environmental Engineering', 'Semester 6', 'Civil Engineering', 'Active', '2026-08-15 15:18:52'),
(74, NULL, 'Advanced Transportation Engineering', 'Semester 6', 'Civil Engineering', 'Active', '2026-08-15 15:18:52'),
(75, NULL, 'Hydrology and Watershed Management', 'Semester 6', 'Civil Engineering', 'Active', '2026-08-15 15:18:52'),
(76, NULL, 'Advanced R.C.C. Structure', 'Semester 6', 'Civil Engineering', 'Active', '2026-08-15 15:18:52'),
(77, NULL, 'Computer Aided Structural Design and Drafting', 'Semester 6', 'Civil Engineering', 'Active', '2026-08-15 15:18:52'),
(78, NULL, 'Computer Aided Drafting and Programming Technologies', 'Semester 6', 'Civil Engineering', 'Active', '2026-08-15 15:18:52'),
(79, NULL, 'Basic Mathematics', 'Semester 1', 'Electrical Engineering', 'Active', '2026-08-15 15:18:52'),
(80, NULL, 'English', 'Semester 1', 'Electrical Engineering', 'Active', '2026-08-15 15:18:52'),
(81, NULL, 'Environment Conservation & Hazard Management', 'Semester 1', 'Electrical Engineering', 'Active', '2026-08-15 15:18:52'),
(82, NULL, 'Engineering Chemistry', 'Semester 1', 'Electrical Engineering', 'Active', '2026-08-15 15:18:52'),
(83, NULL, 'Basic of Computer & Information Technology', 'Semester 1', 'Electrical Engineering', 'Active', '2026-08-15 15:18:52'),
(84, NULL, 'Fundamental of Mechanical Engineering', 'Semester 1', 'Electrical Engineering', 'Active', '2026-08-15 15:18:52'),
(85, NULL, 'Contributor Personality Development', 'Semester 2', 'Electrical Engineering', 'Active', '2026-08-15 15:18:52'),
(86, NULL, 'Advanced Mathematics (Group-1)', 'Semester 2', 'Electrical Engineering', 'Active', '2026-08-15 15:18:52'),
(87, NULL, 'Basic of Civil Engineering', 'Semester 2', 'Electrical Engineering', 'Active', '2026-08-15 15:18:52'),
(88, NULL, 'Basic Physics', 'Semester 2', 'Electrical Engineering', 'Active', '2026-08-15 15:18:52'),
(89, NULL, 'Basic Engineering Drawing', 'Semester 2', 'Electrical Engineering', 'Active', '2026-08-15 15:18:52'),
(90, NULL, 'D.C. Circuits', 'Semester 2', 'Electrical Engineering', 'Active', '2026-08-15 15:18:52'),
(91, NULL, 'Electrical Engineering Workshop Practice', 'Semester 2', 'Electrical Engineering', 'Active', '2026-08-15 15:18:52'),
(92, NULL, 'Electrical Machines-I', 'Semester 3', 'Electrical Engineering', 'Active', '2026-08-15 15:18:52'),
(93, NULL, 'Electrical & Electronic Measurements', 'Semester 3', 'Electrical Engineering', 'Active', '2026-08-15 15:18:52'),
(94, NULL, 'Electrical Circuit Analysis', 'Semester 3', 'Electrical Engineering', 'Active', '2026-08-15 15:18:52'),
(95, NULL, 'Analog Electronics', 'Semester 3', 'Electrical Engineering', 'Active', '2026-08-15 15:18:52'),
(96, NULL, 'Electrical Installation, Maintenance & Repair', 'Semester 3', 'Electrical Engineering', 'Active', '2026-08-15 15:18:52'),
(97, NULL, 'Polyphase Transformers and Rotating AC Machines', 'Semester 4', 'Electrical Engineering', 'Active', '2026-08-15 15:18:52'),
(98, NULL, 'Transmission and Distribution of Electrical Power', 'Semester 4', 'Electrical Engineering', 'Active', '2026-08-15 15:18:52'),
(99, NULL, 'Utilization of Electrical Energy', 'Semester 4', 'Electrical Engineering', 'Active', '2026-08-15 15:18:52'),
(100, NULL, 'Digital Electronics and Digital Instruments', 'Semester 4', 'Electrical Engineering', 'Active', '2026-08-15 15:18:52'),
(101, NULL, 'Computer Aided Electrical Drawing and Simulation', 'Semester 4', 'Electrical Engineering', 'Active', '2026-08-15 15:18:52'),
(102, NULL, 'Wiring Estimating, Costing & Contracting', 'Semester 5', 'Electrical Engineering', 'Active', '2026-08-15 15:18:52'),
(103, NULL, 'Energy Conservation & Audit', 'Semester 5', 'Electrical Engineering', 'Active', '2026-08-15 15:18:52'),
(104, NULL, 'Power Electronics', 'Semester 5', 'Electrical Engineering', 'Active', '2026-08-15 15:18:52'),
(105, NULL, 'Microprocessor and Controller Applications', 'Semester 5', 'Electrical Engineering', 'Active', '2026-08-15 15:18:52'),
(106, NULL, 'Project-I', 'Semester 5', 'Electrical Engineering', 'Active', '2026-08-15 15:18:52'),
(107, NULL, 'Wind and Solar Energy Systems', 'Semester 5', 'Electrical Engineering', 'Active', '2026-08-15 15:18:52'),
(108, NULL, 'Special Electrical Machines', 'Semester 5', 'Electrical Engineering', 'Active', '2026-08-15 15:18:52'),
(109, NULL, 'Electric Traction and Control', 'Semester 5', 'Electrical Engineering', 'Active', '2026-08-15 15:18:52'),
(110, NULL, 'Switchgear & Protection', 'Semester 6', 'Electrical Engineering', 'Active', '2026-08-15 15:18:52'),
(111, NULL, 'Installation, Commissioning and Maintenance', 'Semester 6', 'Electrical Engineering', 'Active', '2026-08-15 15:18:52'),
(112, NULL, 'Project-II', 'Semester 6', 'Electrical Engineering', 'Active', '2026-08-15 15:18:52'),
(113, NULL, 'Elective-II', 'Semester 6', 'Electrical Engineering', 'Active', '2026-08-15 15:18:52'),
(114, NULL, 'Elective-III', 'Semester 6', 'Electrical Engineering', 'Active', '2026-08-15 15:18:52'),
(115, NULL, 'Basic Mathematics', 'Semester 1', 'Mechanical Engineering', 'Active', '2026-08-15 15:18:52'),
(116, NULL, 'English', 'Semester 1', 'Mechanical Engineering', 'Active', '2026-08-15 15:18:52'),
(117, NULL, 'Environment Conservation & Hazard Management', 'Semester 1', 'Mechanical Engineering', 'Active', '2026-08-15 15:18:52'),
(118, NULL, 'Engineering Physics', 'Semester 1', 'Mechanical Engineering', 'Active', '2026-08-15 15:18:52'),
(119, NULL, 'Basic Engineering Drawing', 'Semester 1', 'Mechanical Engineering', 'Active', '2026-08-15 15:18:52'),
(120, NULL, 'Engineering Workshop Practice', 'Semester 1', 'Mechanical Engineering', 'Active', '2026-08-15 15:18:52'),
(121, NULL, 'Contributor Personality Development', 'Semester 2', 'Mechanical Engineering', 'Active', '2026-08-15 15:18:52'),
(122, NULL, 'Advanced Mathematics (Group-2)', 'Semester 2', 'Mechanical Engineering', 'Active', '2026-08-15 15:18:52'),
(123, NULL, 'Applied Mechanics', 'Semester 2', 'Mechanical Engineering', 'Active', '2026-08-15 15:18:52'),
(124, NULL, 'Material Science & Metallurgy', 'Semester 2', 'Mechanical Engineering', 'Active', '2026-08-15 15:18:52'),
(125, NULL, 'Mechanical Drafting', 'Semester 2', 'Mechanical Engineering', 'Active', '2026-08-15 15:18:52'),
(126, NULL, 'Basic of Civil Engineering', 'Semester 2', 'Mechanical Engineering', 'Active', '2026-08-15 15:18:52'),
(127, NULL, 'Manufacturing Engineering-I', 'Semester 3', 'Mechanical Engineering', 'Active', '2026-08-15 15:18:52'),
(128, NULL, 'Thermodynamics', 'Semester 3', 'Mechanical Engineering', 'Active', '2026-08-15 15:18:52'),
(129, NULL, 'Fluid Mechanics and Hydraulic Machines', 'Semester 3', 'Mechanical Engineering', 'Active', '2026-08-15 15:18:52'),
(130, NULL, 'Strength of Materials', 'Semester 3', 'Mechanical Engineering', 'Active', '2026-08-15 15:18:52'),
(131, NULL, 'Applied Electrical and Electronic Engineering', 'Semester 3', 'Mechanical Engineering', 'Active', '2026-08-15 15:18:52'),
(132, NULL, 'Computer Aided Machine Drawing', 'Semester 3', 'Mechanical Engineering', 'Active', '2026-08-15 15:18:52'),
(133, NULL, 'Human Resource Management', 'Semester 3', 'Mechanical Engineering', 'Active', '2026-08-15 15:18:52'),
(134, NULL, 'Manufacturing Engineering-II', 'Semester 4', 'Mechanical Engineering', 'Active', '2026-08-15 15:18:52'),
(135, NULL, 'Thermal Engineering-I', 'Semester 4', 'Mechanical Engineering', 'Active', '2026-08-15 15:18:52'),
(136, NULL, 'Theory of Machines', 'Semester 4', 'Mechanical Engineering', 'Active', '2026-08-15 15:18:52'),
(137, NULL, 'Computer Aided Design', 'Semester 4', 'Mechanical Engineering', 'Active', '2026-08-15 15:18:52'),
(138, NULL, 'Metrology & Instrumentation', 'Semester 4', 'Mechanical Engineering', 'Active', '2026-08-15 15:18:52'),
(139, NULL, 'Plant Maintenance and Safety', 'Semester 4', 'Mechanical Engineering', 'Active', '2026-08-15 15:18:52'),
(140, NULL, 'Thermal Engineering-II', 'Semester 5', 'Mechanical Engineering', 'Active', '2026-08-15 15:18:52'),
(141, NULL, 'Design of Machine Elements', 'Semester 5', 'Mechanical Engineering', 'Active', '2026-08-15 15:18:52'),
(142, NULL, 'Manufacturing Engineering-III', 'Semester 5', 'Mechanical Engineering', 'Active', '2026-08-15 15:18:52'),
(143, NULL, 'Industrial Engineering', 'Semester 5', 'Mechanical Engineering', 'Active', '2026-08-15 15:18:52'),
(144, NULL, 'Estimating, Costing and Engineering Contracting', 'Semester 5', 'Mechanical Engineering', 'Active', '2026-08-15 15:18:52'),
(145, NULL, 'Project-I', 'Semester 5', 'Mechanical Engineering', 'Active', '2026-08-15 15:18:52'),
(146, NULL, 'Self Employment and Entrepreneurship Development', 'Semester 5', 'Mechanical Engineering', 'Active', '2026-08-15 15:18:52'),
(147, NULL, 'Operations Management and Information Systems', 'Semester 5', 'Mechanical Engineering', 'Active', '2026-08-15 15:18:52'),
(148, NULL, 'Computer Aided Manufacturing (CAM)', 'Semester 6', 'Mechanical Engineering', 'Active', '2026-08-15 15:18:52'),
(149, NULL, 'Tool Engineering', 'Semester 6', 'Mechanical Engineering', 'Active', '2026-08-15 15:18:52'),
(150, NULL, 'Industrial Management', 'Semester 6', 'Mechanical Engineering', 'Active', '2026-08-15 15:18:52'),
(151, NULL, 'Project-II', 'Semester 6', 'Mechanical Engineering', 'Active', '2026-08-15 15:18:52'),
(152, NULL, 'Elective-II', 'Semester 6', 'Mechanical Engineering', 'Active', '2026-08-15 15:18:52'),
(153, NULL, 'Elective-III', 'Semester 6', 'Mechanical Engineering', 'Active', '2026-08-15 15:18:52'),
(154, NULL, 'Basic Mathematics', 'Semester 1', 'Computer Engineering', 'Active', '2026-08-19 13:12:26'),
(155, NULL, 'Engineering Physics', 'Semester 1', 'Computer Engineering', 'Active', '2026-08-19 13:12:26'),
(156, NULL, 'Computer Programming', 'Semester 1', 'Computer Engineering', 'Active', '2026-08-19 13:12:26'),
(157, NULL, 'Fundamental of Digital Electronics', 'Semester 1', 'Computer Engineering', 'Active', '2026-08-19 13:12:26'),
(158, NULL, 'English', 'Semester 1', 'Computer Engineering', 'Active', '2026-08-19 13:12:26'),
(159, NULL, 'Fundamental of Computer Application', 'Semester 1', 'Computer Engineering', 'Active', '2026-08-19 13:12:26'),
(160, NULL, 'Environment Conservation & Hazard Management', 'Semester 1', 'Computer Engineering', 'Active', '2026-08-19 13:12:26');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=161;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
