-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 23, 2025 at 06:39 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sskpao_kp7`
--

-- --------------------------------------------------------

--
-- Table structure for table `form_submissions`
--

CREATE TABLE `form_submissions` (
  `id` int(11) NOT NULL,
  `prefix` varchar(50) NOT NULL COMMENT 'คำนำหน้า',
  `other_prefix` varchar(100) DEFAULT NULL COMMENT 'คำนำหน้า (อื่นๆ)',
  `first_name` varchar(255) NOT NULL COMMENT 'ชื่อ',
  `last_name` varchar(255) NOT NULL COMMENT 'นามสกุล',
  `position` varchar(255) DEFAULT NULL COMMENT 'ตำแหน่ง',
  `department` varchar(255) DEFAULT NULL COMMENT 'สังกัด',
  `phone` varchar(20) NOT NULL COMMENT 'เบอร์โทรศัพท์',
  `reason` text NOT NULL COMMENT 'เหตุผล',
  `delivery_method` varchar(50) NOT NULL COMMENT 'ช่องทางการรับ',
  `address` text DEFAULT NULL COMMENT 'ที่อยู่จัดส่ง',
  `email` varchar(255) DEFAULT NULL COMMENT 'อีเมล',
  `pdpa_consent` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'การยินยอม PDPA',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'วันที่ยื่นคำร้อง',
  `status` varchar(50) NOT NULL DEFAULT 'pending' COMMENT 'สถานะ (pending, processing, completed, rejected)',
  `uploaded_file` varchar(255) DEFAULT NULL COMMENT 'ชื่อไฟล์ PDF ที่เข้ารหัสแล้ว'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `form_submissions`
--

INSERT INTO `form_submissions` (`id`, `prefix`, `other_prefix`, `first_name`, `last_name`, `position`, `department`, `phone`, `reason`, `delivery_method`, `address`, `email`, `pdpa_consent`, `submitted_at`, `status`, `uploaded_file`) VALUES
(1, 'นาย', '', 'ปฐวีกานต์', 'ศรีคราม', 'นักวิชาการคอมพิวเตอร์', 'กองยุทธศาสตร์', '0981051534', 'งาน', 'pickup', '', '', 1, '2025-10-23 15:31:55', 'completed', NULL),
(2, 'นาย', '', 'ปฐวีกานต์', 'ศรีคราม', 'นักวิชาการคอมพิวเตอร์', 'กองยุทธศาสตร์', '0981051534', 'งาน', 'email', '', 'superaidigital2025@gmail.com', 1, '2025-10-23 15:40:58', 'pending', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `form_submissions`
--
ALTER TABLE `form_submissions`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `form_submissions`
--
ALTER TABLE `form_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
