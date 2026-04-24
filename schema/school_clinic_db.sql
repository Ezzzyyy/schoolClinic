-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 23, 2026 at 06:28 PM
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
-- Database: `school_clinic_db`
--
CREATE DATABASE IF NOT EXISTS `school_clinic_db`;
USE `school_clinic_db`;


-- --------------------------------------------------------

--
-- Table structure for table `clinic_settings`
--

CREATE TABLE `clinic_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clinic_settings`
--

INSERT INTO `clinic_settings` (`setting_key`, `setting_value`, `updated_at`) VALUES
('clinic_address', 'Main Building, Ground Floor, St. Mary\'s Campus', '2026-04-23 15:50:42'),
('clinic_contact', '+63 32 123 4569', '2026-04-23 15:59:42'),
('clinic_name', 'ClinIQ School Clinic', '2026-04-23 15:53:15'),
('document_footer', 'This document is issued by the school clinic and is valid for internal compliance use only.', '2026-04-23 16:06:10'),
('email_enabled', '0', '2026-04-23 15:50:42'),
('email_username', '', '2026-04-23 15:50:42'),
('email_password', '', '2026-04-23 15:50:42'),
('email_from_address', 'clinic@school.edu.ph', '2026-04-23 15:50:42'),
('email_from_name', 'ClinIQ School Clinic', '2026-04-23 15:50:42'),
('head_nurse', 'Nurse Paula Santos', '2026-04-23 15:50:42'),
('max_login_attempts', '5', '2026-04-23 15:50:42'),
('nurse_license', 'RN-9876', '2026-04-23 15:50:42'),
('physician_license', '0123456', '2026-04-23 15:50:42'),
('primary_physician', 'Dr. Maria Reyes', '2026-04-23 15:50:42'),
('req_cbc', 'required', '2026-04-23 15:50:42'),
('req_cbc_enabled', '1', '2026-04-23 16:13:35'),
('req_drug_test', 'optional', '2026-04-23 15:50:42'),
('req_drug_test_enabled', '1', '2026-04-23 16:13:35'),
('req_med_cert', 'optional', '2026-04-23 15:50:42'),
('req_med_cert_enabled', '0', '2026-04-23 16:13:35'),
('req_urinalysis', 'required', '2026-04-23 15:50:42'),
('req_urinalysis_enabled', '1', '2026-04-23 16:13:35'),
('req_vaccination', 'optional', '2026-04-23 15:50:42'),
('req_vaccination_enabled', '0', '2026-04-23 16:13:35'),
('req_xray', 'required', '2026-04-23 15:50:42'),
('req_xray_enabled', '1', '2026-04-23 16:13:35'),
('school_year', '2026 ??? 2027', '2026-04-23 15:50:42'),
('session_timeout', '60', '2026-04-23 15:50:42'),
('two_factor_auth', 'disabled', '2026-04-23 15:50:42'),
('data_retention_days', '365', '2026-04-23 15:50:42'),
('last_backup_date', '2026-04-23 15:50:42', '2026-04-23 15:50:42'),
('backup_enabled', '1', '2026-04-23 15:50:42');

-- --------------------------------------------------------

--
-- Table structure for table `clinic_visits`
--

CREATE TABLE `clinic_visits` (
  `visit_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `visit_date` datetime NOT NULL DEFAULT current_timestamp(),
  `complaint` text DEFAULT NULL,
  `symptoms` text DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `treatment` text DEFAULT NULL,
  `visit_status` int(11) DEFAULT NULL,
  `handled_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clinic_visits`
--

INSERT INTO `clinic_visits` (`visit_id`, `student_id`, `visit_date`, `complaint`, `symptoms`, `diagnosis`, `treatment`, `visit_status`, `handled_by`, `notes`, `created_at`) VALUES
(1, 1, '2026-04-19 16:25:29', 'Headache', 'Dizziness, fatigue', 'Tension headache', 'Hydration + rest', 2, 2, NULL, '2026-04-19 08:25:29'),
(2, 2, '2026-04-19 16:25:29', 'Abdominal pain', 'Nausea, mild cramping', 'Gastritis', 'Antacid provided', 1, 1, NULL, '2026-04-19 08:25:29'),
(3, 3, '2026-04-19 16:25:29', 'Fever', '38.6°C, chills', 'Acute viral syndrome', 'Paracetamol + sent home', 3, 2, NULL, '2026-04-19 08:25:29'),
(4, 4, '2026-04-19 16:25:29', 'Cough', 'Sore throat, dry cough', 'Upper resp. irritation', 'Cough syrup advice', 2, 1, NULL, '2026-04-19 08:25:29'),
(5, 5, '2026-04-19 16:25:29', 'Sprained Ankle', 'Swelling, pain on movement', 'Grade 1 sprain', 'Ice pack + elastic bandage', 2, 2, NULL, '2026-04-19 08:25:29'),
(6, 7, '2026-04-21 00:39:00', 'Nahihilo', 'Dizziness, nasusuka', 'Buntis', 'lambing', 3, 2, 'pinadala sa hospital kasi need na manganak', '2026-04-20 16:38:17'),
(7, 7, '2026-04-21 01:14:00', 'severe blood loss', 'lacerations', 'sinaksak', 'poking may tahi', 3, 2, 'need na sha mahalin maayos', '2026-04-20 17:15:20'),
(8, 7, '2026-04-21 01:21:00', 'Follow-up for nahihilo', 'vaginal bleeding', 'manganganak na', 'nanganak na', 1, 2, 'sheena', '2026-04-20 17:21:35'),
(9, 7, '2026-04-21 01:49:00', 'Follow-up for nahihilo', 'preggy', '12', '12', 2, 2, '12', '2026-04-20 17:49:19'),
(10, 6, '2026-04-21 01:52:00', 'idk', '12', '212', '12', 2, 2, '12', '2026-04-20 17:53:05');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `course_id` int(11) NOT NULL,
  `course_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`course_id`, `course_name`) VALUES
(1, 'BS Information Technology'),
(2, 'BS Computer Science'),
(3, 'BS Nursing'),
(4, 'BS Business Administration'),
(5, 'BS Accountancy'),
(6, 'BS Civil Engineering'),
(7, 'BS Mechanical Engineering'),
(8, 'BS Electrical Engineering'),
(9, 'BS Architecture'),
(10, 'BS Psychology'),
(11, 'BS Criminology'),
(12, 'BS Pharmacy'),
(13, 'BS Medical Technology'),
(14, 'BS Biology'),
(15, 'BS Hospitality Management'),
(16, 'BS Tourism Management'),
(17, 'AB Communication'),
(18, 'AB Political Science'),
(19, 'AB English Language'),
(20, 'Bachelor of Elementary Education'),
(21, 'Bachelor of Secondary Education');

-- --------------------------------------------------------

--
-- Table structure for table `health_assessments`
--

CREATE TABLE `health_assessments` (
  `assessment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `x_ray` varchar(255) DEFAULT NULL,
  `urinalysis` varchar(255) DEFAULT NULL,
  `hematology` varchar(255) DEFAULT NULL,
  `drug_test` varchar(255) DEFAULT NULL,
  `med_certificate` varchar(255) DEFAULT NULL,
  `vaccination_card` varchar(255) DEFAULT NULL,
  `height` varchar(20) DEFAULT NULL,
  `weight` varchar(20) DEFAULT NULL,
  `blood_pressure` varchar(30) DEFAULT NULL,
  `pulse_rate` varchar(20) DEFAULT NULL,
  `lab_remarks` text DEFAULT NULL,
  `clearance_status` enum('cleared','conditional','pending') DEFAULT 'pending',
  `assessment_date` date NOT NULL,
  `handled_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `health_assessments`
--

INSERT INTO `health_assessments` (`assessment_id`, `student_id`, `x_ray`, `urinalysis`, `hematology`, `height`, `weight`, `blood_pressure`, `pulse_rate`, `lab_remarks`, `clearance_status`, `drug_test`, `assessment_date`, `handled_by`, `created_at`) VALUES
(1, 1, 'uploads/xray/2024-1001.jpg', 'uploads/uri/2024-1001.pdf', 'uploads/hem/2024-1001.pdf', NULL, NULL, NULL, NULL, NULL, 'pending', NULL, '2024-04-01', 1, '2026-04-19 08:25:29'),
(2, 2, 'uploads/xray/2024-1002.jpg', NULL, 'uploads/hem/2024-1002.pdf', '', '', '', '', '', 'cleared', NULL, '2026-04-20', 2, '2026-04-19 08:25:29'),
(3, 3, 'uploads/xray/2023-0911.jpg', 'uploads/uri/2023-0911.pdf', 'uploads/hem/2023-0911.pdf', NULL, NULL, NULL, NULL, NULL, 'pending', NULL, '2024-04-10', 1, '2026-04-19 08:25:29'),
(4, 4, NULL, NULL, NULL, '', '', '', '', '', 'cleared', NULL, '2026-04-20', 1, '2026-04-19 08:25:29'),
(5, 5, 'uploads/xray/2022-0850.jpg', 'uploads/uri/2022-0850.pdf', 'uploads/hem/2022-0850.pdf', NULL, NULL, NULL, NULL, NULL, 'pending', NULL, '2024-04-15', 1, '2026-04-19 08:25:29'),
(6, 6, 'uploads/xray/2024-01888_1776591746.png', 'uploads/urinalysis/2024-01888_1776591746.png', 'uploads/hematology/2024-01888_1776591746.png', NULL, NULL, NULL, NULL, NULL, 'pending', 'uploads/drugtest/2024-01888_1776591746.png', '2026-04-19', NULL, '2026-04-19 09:42:26'),
(7, 7, 'uploads/xray/2024-0111_1776702312.png', 'uploads/urinalysis/2024-0111_1776702312.png', 'uploads/hematology/2024-0111_1776702312.png', '111', '11', '11', '11', '11', 'cleared', NULL, '2026-04-20', 2, '2026-04-20 16:25:12'),
(8, 8, 'uploads/xray/2022-01234_1776702723.png', 'uploads/urinalysis/2022-01234_1776702723.png', 'uploads/hematology/2022-01234_1776702723.png', '190', '10kg', '1000/550', 'shaira', 'complete', 'cleared', NULL, '2026-04-20', 1, '2026-04-20 16:32:03'),
(9, 9, 'uploads/xray/2024-01500_1776746697.png', 'uploads/urinalysis/2024-01500_1776746697.png', 'uploads/hematology/2024-01500_1776746697.png', NULL, NULL, NULL, NULL, NULL, 'pending', NULL, '2026-04-21', NULL, '2026-04-21 04:44:57');

-- --------------------------------------------------------

--
-- Table structure for table `medical_certificates`
--

CREATE TABLE `medical_certificates` (
  `certificate_id` int(11) NOT NULL,
  `visit_id` int(11) DEFAULT NULL,
  `assessment_id` int(11) DEFAULT NULL,
  `issued_by` int(11) NOT NULL,
  `date_issued` date NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','released') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medical_certificates`
--

INSERT INTO `medical_certificates` (`certificate_id`, `visit_id`, `assessment_id`, `issued_by`, `date_issued`, `file_path`, `remarks`, `created_at`, `status`) VALUES
(1, 1, NULL, 2, '2026-04-15', NULL, NULL, '2026-04-19 08:25:30', 'pending'),
(2, 3, NULL, 2, '2026-04-14', NULL, NULL, '2026-04-19 08:25:30', 'pending'),
(3, 5, NULL, 2, '2026-04-19', NULL, NULL, '2026-04-19 08:25:30', 'pending'),
(4, 2, NULL, 1, '2026-04-15', NULL, NULL, '2026-04-19 08:25:30', 'pending'),
(5, 4, NULL, 1, '2026-04-14', NULL, NULL, '2026-04-19 08:25:30', 'pending'),
(6, NULL, 4, 2, '2026-04-20', 'uploads/certificates/CERT_6_1776956811.png', '', '2026-04-20 17:17:59', 'released'),
(7, NULL, 2, 2, '2026-04-20', NULL, NULL, '2026-04-20 17:18:17', 'pending'),
(8, NULL, 8, 2, '2026-04-20', NULL, NULL, '2026-04-20 17:18:27', 'pending'),
(9, NULL, 7, 2, '2026-04-20', NULL, NULL, '2026-04-20 17:18:37', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `medicines`
--

CREATE TABLE `medicines` (
  `medicine_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `unit` varchar(30) DEFAULT NULL,
  `reorder_level` int(11) DEFAULT 10,
  `location` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `expiration_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medicines`
--

INSERT INTO `medicines` (`medicine_id`, `name`, `category`, `description`, `quantity`, `unit`, `reorder_level`, `location`, `notes`, `expiration_date`, `created_at`) VALUES
(1, 'Paracetamol', '', '500mg Tablet for pain/fever', 500, 'pcs', 10, NULL, NULL, '2027-12-31', '2026-04-19 08:25:30'),
(2, 'Amoxicillin', '', '500mg Antibiotic', 210, 'pcs', 10, NULL, NULL, '2026-06-30', '2026-04-19 08:25:30'),
(3, 'Ibuprofen', '', '200mg for inflammation', 277, 'pcs', 10, NULL, NULL, '2027-05-15', '2026-04-19 08:25:30'),
(4, 'Cetirizine', '', '10mg Antihistamine', 150, 'pcs', 10, NULL, NULL, '2027-01-20', '2026-04-19 08:25:30'),
(5, 'Antacid', '', 'Chewable tablet for hyperacidity', 250, 'pcs', 10, NULL, NULL, '2026-11-10', '2026-04-19 08:25:30'),
(6, 'Paracetamol 500mg', 'Analgesic', 'Pain reliever and fever reducer', 120, 'tabs', 60, 'Cabinet A, Shelf 1', '', '2026-10-15', '2026-04-19 09:04:16'),
(7, 'Amoxicillin 250mg', 'Antibiotic', 'Antibacterial medication', 30, 'caps', 80, 'Cabinet B, Shelf 2', 'Prescription required', '2026-07-20', '2026-04-19 09:04:16'),
(8, 'Cetirizine 10mg', 'Antihistamine', 'Allergy medication', 18, 'tabs', 50, 'Cabinet A, Shelf 3', '', '2026-05-12', '2026-04-19 09:04:16'),
(9, 'Mefenamic Acid 500mg', 'Analgesic', 'Nonsteroidal anti-inflammatory drug', 230, 'tabs', 100, 'Cabinet A, Shelf 2', '', '2026-12-01', '2026-04-19 09:04:16'),
(10, 'Betadine Solution', 'First Aid', 'Antiseptic solution', 3, 'bottles', 10, 'First Aid Cabinet', '', '2026-08-30', '2026-04-19 09:04:16');

-- --------------------------------------------------------

--
-- Table structure for table `medicine_logs`
--

CREATE TABLE `medicine_logs` (
  `log_id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `action_type` enum('in','out') NOT NULL,
  `date` datetime NOT NULL DEFAULT current_timestamp(),
  `handled_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medicine_logs`
--

INSERT INTO `medicine_logs` (`log_id`, `medicine_id`, `quantity`, `action_type`, `date`, `handled_by`) VALUES
(1, 1, 1000, 'in', '2026-04-19 16:25:30', 1),
(2, 2, 500, 'in', '2026-04-19 16:25:30', 1),
(3, 3, 300, 'in', '2026-04-19 16:25:30', 1),
(4, 4, 150, 'in', '2026-04-19 16:25:30', 1),
(5, 5, 250, 'in', '2026-04-19 16:25:30', 1),
(6, 7, 10, 'out', '2026-04-21 01:49:19', 2),
(7, 3, 10, 'out', '2026-04-21 01:49:19', 2),
(8, 3, 13, 'out', '2026-04-21 01:53:05', 2),
(9, 2, 10, 'in', '2026-04-23 22:46:43', 2);

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(100) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `report_logs`
--

CREATE TABLE `report_logs` (
  `log_id` int(11) NOT NULL,
  `report_type` varchar(100) NOT NULL,
  `scope` varchar(255) DEFAULT NULL,
  `generated_by` int(11) DEFAULT NULL,
  `format` enum('pdf','excel','print') DEFAULT 'pdf',
  `record_count` int(11) DEFAULT 0,
  `status` enum('Queued','Ready','Printed','Error') DEFAULT 'Ready',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `report_logs`
--

INSERT INTO `report_logs` (`log_id`, `report_type`, `scope`, `generated_by`, `format`, `record_count`, `status`, `created_at`) VALUES
(1, 'Enrollment Clearance Summary', 'Full History', 2, 'pdf', 8, 'Ready', '2026-04-23 15:49:16');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `student_number` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `birth_date` date DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `year_id` int(11) DEFAULT NULL,
  `emergency_contact` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `health_notes` text DEFAULT NULL,
  `status` enum('Active','Pending review','Inactive') NOT NULL DEFAULT 'Pending review',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `student_number`, `first_name`, `middle_name`, `last_name`, `gender`, `birth_date`, `contact_number`, `address`, `course_id`, `year_id`, `emergency_contact`, `email`, `health_notes`, `status`, `created_at`) VALUES
(1, '2024-1001', 'Juan', NULL, 'Dela Cruz', 'Male', '2005-05-15', NULL, 'Manila, Philippines', 1, 2, NULL, NULL, NULL, 'Active', '2026-04-19 08:25:29'),
(2, '2024-1002', 'Ana', NULL, 'Lim', 'Female', '2006-08-20', NULL, 'Quezon City, Philippines', 3, 1, NULL, NULL, 'Health Assessment: cleared. ', 'Active', '2026-04-19 08:25:29'),
(3, '2023-0911', 'Ramon', NULL, 'Santos', 'Male', '2004-03-10', NULL, 'Makati, Philippines', 4, 3, NULL, NULL, NULL, 'Inactive', '2026-04-19 08:25:29'),
(4, '2024-1099', 'Karl', NULL, 'Bautista', 'Male', '2007-01-25', NULL, 'Pasig, Philippines', 2, 1, NULL, NULL, 'Health Assessment: cleared. ', 'Active', '2026-04-19 08:25:29'),
(5, '2022-0850', 'Maria', NULL, 'Clara', 'Female', '2003-11-30', NULL, 'Taguig, Philippines', 5, 4, NULL, NULL, '', 'Active', '2026-04-19 08:25:29'),
(6, '2024-01888', 'DIXON', 'COLUMBRES.', 'TRUMATA', '', '2006-01-13', '09453792822', 'Jan sa tabi', NULL, NULL, 'Eleny Trumata + 09362501669', 'ae202401888@wmsu.edu.ph', '', 'Active', '2026-04-19 09:42:26'),
(7, '2024-0111', 'Princess Shaira Mae', 'Bayabos', 'Sailela', 'Female', '2005-05-01', '09111111111', 'jan lang sa tabi', 1, 2, 'Madam Sailela 091111111112', 'shaira123@gmail.com', 'Health Assessment: cleared. 11', 'Active', '2026-04-20 16:25:12'),
(8, '2022-01234', 'jeoff nikko ', 'amabao', 'ricafort', 'Male', '2005-01-13', '12345678910', 'talon talon', 13, 2, '12345678910 jani', 'jd202201234@wmsu.edu.ph', 'Health Assessment: cleared. complete', 'Active', '2026-04-20 16:32:03'),
(9, '2024-01500', 'Princess', '', 'Bartolome', 'Female', '2006-02-23', '09099998998', 'Somewhere St, In Nowhere CIty', 1, 2, 'Mom 09096667777', 'ae202401500@wmsu.edu.ph', 'I have arthritis', 'Pending review', '2026-04-21 04:44:57');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('nurse','doctor') NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `first_name`, `middle_name`, `last_name`, `email`, `username`, `password`, `role`, `last_login`, `status`, `created_at`) VALUES
(1, 'Paula', NULL, 'Gomez', 'paula@example.com', 'nurse_paula', '$2y$10$yqLt2slmvzxQvTzEAttFfeLTKEd6pHuYFoOtYncVtsKEhkS3O6f9m', 'nurse', NULL, 'active', '2026-04-19 08:25:29'),
(2, 'Antonio', NULL, 'Reyes', 'antonio@example.com', 'dr_reyes', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', NULL, 'active', '2026-04-19 08:25:29');

-- --------------------------------------------------------

--
-- Table structure for table `visit_medicine`
--

CREATE TABLE `visit_medicine` (
  `id` int(11) NOT NULL,
  `visit_id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `quantity_given` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `visit_medicine`
--

INSERT INTO `visit_medicine` (`id`, `visit_id`, `medicine_id`, `quantity_given`) VALUES
(1, 1, 1, 2),
(2, 2, 5, 3),
(3, 3, 1, 4),
(4, 4, 4, 1),
(5, 5, 3, 2),
(6, 9, 7, 10),
(7, 9, 3, 10),
(8, 10, 3, 13);

-- --------------------------------------------------------

--
-- Table structure for table `visit_status`
--

CREATE TABLE `visit_status` (
  `status_id` int(11) NOT NULL,
  `status_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `visit_status`
--

INSERT INTO `visit_status` (`status_id`, `status_name`) VALUES
(1, 'Pending'),
(2, 'Completed'),
(3, 'Referred'),
(4, 'Cancelled');

-- --------------------------------------------------------

--
-- Table structure for table `year_levels`
--

CREATE TABLE `year_levels` (
  `year_id` int(11) NOT NULL,
  `year_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `year_levels`
--

INSERT INTO `year_levels` (`year_id`, `year_name`) VALUES
(1, '1st Year'),
(2, '2nd Year'),
(3, '3rd Year'),
(4, '4th Year'),
(5, '5th Year');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `clinic_settings`
--
ALTER TABLE `clinic_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `clinic_visits`
--
ALTER TABLE `clinic_visits`
  ADD PRIMARY KEY (`visit_id`),
  ADD KEY `fk_cv_student` (`student_id`),
  ADD KEY `fk_cv_visit_status` (`visit_status`),
  ADD KEY `fk_cv_handled_by` (`handled_by`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`course_id`);

--
-- Indexes for table `health_assessments`
--
ALTER TABLE `health_assessments`
  ADD PRIMARY KEY (`assessment_id`),
  ADD KEY `fk_ha_student` (`student_id`),
  ADD KEY `fk_ha_handled_by` (`handled_by`);

--
-- Indexes for table `medical_certificates`
--
ALTER TABLE `medical_certificates`
  ADD PRIMARY KEY (`certificate_id`),
  ADD KEY `fk_mc_visit` (`visit_id`),
  ADD KEY `fk_mc_issued_by` (`issued_by`),
  ADD KEY `fk_mc_assessment` (`assessment_id`);

--
-- Indexes for table `medicines`
--
ALTER TABLE `medicines`
  ADD PRIMARY KEY (`medicine_id`);

--
-- Indexes for table `medicine_logs`
--
ALTER TABLE `medicine_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `fk_ml_medicine` (`medicine_id`),
  ADD KEY `fk_ml_handled_by` (`handled_by`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD KEY `email` (`email`),
  ADD KEY `token` (`token`);

--
-- Indexes for table `report_logs`
--
ALTER TABLE `report_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `generated_by` (`generated_by`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `student_number` (`student_number`),
  ADD KEY `fk_students_course` (`course_id`),
  ADD KEY `fk_students_year` (`year_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email_address` (`email`);

--
-- Indexes for table `visit_medicine`
--
ALTER TABLE `visit_medicine`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_vm_visit` (`visit_id`),
  ADD KEY `fk_vm_medicine` (`medicine_id`);

--
-- Indexes for table `visit_status`
--
ALTER TABLE `visit_status`
  ADD PRIMARY KEY (`status_id`);

--
-- Indexes for table `year_levels`
--
ALTER TABLE `year_levels`
  ADD PRIMARY KEY (`year_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `clinic_visits`
--
ALTER TABLE `clinic_visits`
  MODIFY `visit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `health_assessments`
--
ALTER TABLE `health_assessments`
  MODIFY `assessment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `medical_certificates`
--
ALTER TABLE `medical_certificates`
  MODIFY `certificate_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `medicines`
--
ALTER TABLE `medicines`
  MODIFY `medicine_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `medicine_logs`
--
ALTER TABLE `medicine_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `report_logs`
--
ALTER TABLE `report_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `visit_medicine`
--
ALTER TABLE `visit_medicine`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `visit_status`
--
ALTER TABLE `visit_status`
  MODIFY `status_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `year_levels`
--
ALTER TABLE `year_levels`
  MODIFY `year_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `fk_al_user` (`user_id`),
  ADD KEY `timestamp` (`timestamp`),
  ADD KEY `entity_type` (`entity_type`);

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Add constraint for audit_logs
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_al_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `clinic_visits`
--
ALTER TABLE `clinic_visits`
  ADD CONSTRAINT `fk_cv_handled_by` FOREIGN KEY (`handled_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_cv_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`),
  ADD CONSTRAINT `fk_cv_visit_status` FOREIGN KEY (`visit_status`) REFERENCES `visit_status` (`status_id`);

--
-- Constraints for table `health_assessments`
--
ALTER TABLE `health_assessments`
  ADD CONSTRAINT `fk_ha_handled_by` FOREIGN KEY (`handled_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_ha_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`);

--
-- Constraints for table `medical_certificates`
--
ALTER TABLE `medical_certificates`
  ADD CONSTRAINT `fk_mc_assessment` FOREIGN KEY (`assessment_id`) REFERENCES `health_assessments` (`assessment_id`),
  ADD CONSTRAINT `fk_mc_issued_by` FOREIGN KEY (`issued_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_mc_visit` FOREIGN KEY (`visit_id`) REFERENCES `clinic_visits` (`visit_id`);

--
-- Constraints for table `medicine_logs`
--
ALTER TABLE `medicine_logs`
  ADD CONSTRAINT `fk_ml_handled_by` FOREIGN KEY (`handled_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_ml_medicine` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`medicine_id`);

--
-- Constraints for table `report_logs`
--
ALTER TABLE `report_logs`
  ADD CONSTRAINT `report_logs_ibfk_1` FOREIGN KEY (`generated_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `fk_students_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`),
  ADD CONSTRAINT `fk_students_year` FOREIGN KEY (`year_id`) REFERENCES `year_levels` (`year_id`);

--
-- Constraints for table `visit_medicine`
--
ALTER TABLE `visit_medicine`
  ADD CONSTRAINT `fk_vm_medicine` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`medicine_id`),
  ADD CONSTRAINT `fk_vm_visit` FOREIGN KEY (`visit_id`) REFERENCES `clinic_visits` (`visit_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
