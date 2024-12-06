-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 06, 2024 at 02:17 AM
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
-- Database: `ccs_ojt`
--

-- --------------------------------------------------------

--
-- Table structure for table `address`
--

CREATE TABLE `address` (
  `address_id` int(11) NOT NULL,
  `address_barangay` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `address`
--

INSERT INTO `address` (`address_id`, `address_barangay`) VALUES
(1, 'Kasanyangan'),
(2, 'Talon Talon'),
(3, 'Baliwasan'),
(4, 'Mampang');

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `admin_image` varchar(255) NOT NULL,
  `admin_firstname` varchar(255) NOT NULL,
  `admin_middle` varchar(255) NOT NULL,
  `admin_lastname` varchar(255) NOT NULL,
  `admin_email` varchar(255) NOT NULL,
  `admin_password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `admin_image`, `admin_firstname`, `admin_middle`, `admin_lastname`, `admin_email`, `admin_password`) VALUES
(1, '', 'Admin', 'A', 'User', 'admin@example.com', '$2y$10$j6qBb5wKj8Of0iRWerAUWe6SS8aEsJZM/YMGlF/xHMflG/P6qcvoa');

-- --------------------------------------------------------

--
-- Table structure for table `adviser`
--

CREATE TABLE `adviser` (
  `adviser_id` int(11) NOT NULL,
  `adviser_image` varchar(255) NOT NULL,
  `adviser_firstname` varchar(255) NOT NULL,
  `adviser_middle` varchar(255) NOT NULL,
  `adviser_lastname` varchar(255) NOT NULL,
  `adviser_number` varchar(255) NOT NULL,
  `adviser_email` varchar(255) NOT NULL,
  `adviser_password` varchar(255) NOT NULL,
  `department` varchar(255) NOT NULL,
  `verification_code` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `adviser`
--

INSERT INTO `adviser` (`adviser_id`, `adviser_image`, `adviser_firstname`, `adviser_middle`, `adviser_lastname`, `adviser_number`, `adviser_email`, `adviser_password`, `department`, `verification_code`) VALUES
(1, '', 'Jhoanna', 'B', 'Robles', '+639441083491', 'adviser1@gmail.com', '$2y$10$j6qBb5wKj8Of0iRWerAUWe6SS8aEsJZM/YMGlF/xHMflG/P6qcvoa', 'IT Department', ''),
(2, 'adviserArcetaMaraiahQ.png', 'Maraiah', 'Queen', 'Arceta', '+639441083456', 'adviser2@gmail.com', '$2y$10$j6qBb5wKj8Of0iRWerAUWe6SS8aEsJZM/YMGlF/xHMflG/P6qcvoa', 'CS Department', '');

-- --------------------------------------------------------

--
-- Table structure for table `adviser_announcement`
--

CREATE TABLE `adviser_announcement` (
  `announcement_id` int(11) NOT NULL,
  `adviser_id` int(11) NOT NULL,
  `announcement_name` varchar(255) NOT NULL,
  `announcement_date` date NOT NULL,
  `announcement_description` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `adviser_announcement`
--

INSERT INTO `adviser_announcement` (`announcement_id`, `adviser_id`, `announcement_name`, `announcement_date`, `announcement_description`) VALUES
(1, 2, '2 Years Timeskip', '2024-10-29', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.'),
(2, 1, 'Sabaody Archipelago', '2024-10-31', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `time_in` timestamp NOT NULL DEFAULT current_timestamp(),
  `time_out` timestamp NULL DEFAULT NULL,
  `ojt_hours` decimal(10,5) GENERATED ALWAYS AS (timestampdiff(SECOND,`time_in`,`time_out`) / 3600) STORED,
  `time_out_reason` enum('Time-Out','Company Errand','Lunch Break') DEFAULT NULL COMMENT 'Reason for Time-Out'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_remarks`
--

CREATE TABLE `attendance_remarks` (
  `remark_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `remark_type` enum('Late','Absent','Forgot Time-out') NOT NULL,
  `remark` varchar(255) DEFAULT NULL,
  `proof_image` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Approved') NOT NULL DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `company`
--

CREATE TABLE `company` (
  `company_id` int(11) NOT NULL,
  `company_image` varchar(255) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `company_rep_firstname` varchar(255) NOT NULL,
  `company_rep_middle` varchar(255) NOT NULL,
  `company_rep_lastname` varchar(255) NOT NULL,
  `company_email` varchar(255) NOT NULL,
  `company_password` varchar(255) NOT NULL,
  `company_address` varchar(255) NOT NULL,
  `company_number` varchar(255) NOT NULL,
  `verification_code` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `company`
--

INSERT INTO `company` (`company_id`, `company_image`, `company_name`, `company_rep_firstname`, `company_rep_middle`, `company_rep_lastname`, `company_email`, `company_password`, `company_address`, `company_number`, `verification_code`) VALUES
(1, 'companyCompany 2.png', 'Company 2', 'Celso', 'D', 'Lobregat', 'company2@gmail.com', '$2y$10$j6qBb5wKj8Of0iRWerAUWe6SS8aEsJZM/YMGlF/xHMflG/P6qcvoa', 'Baliwasan', '+63920783229', ''),
(2, 'companyGov Lim Avenue.jpg', 'Company 2', 'Beng', 'D', 'Climaco', 'company2@gmail.com', '$2y$10$2djMrrkkrMhC68z6BstsG.rL3NlBQ0j9J0DTd5kswwbzqELTdj7S.', 'Talon Talon', '+63920783229', '');

-- --------------------------------------------------------

--
-- Table structure for table `course_sections`
--

CREATE TABLE `course_sections` (
  `id` int(11) NOT NULL,
  `course_section_name` varchar(255) NOT NULL,
  `adviser_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `course_sections`
--

INSERT INTO `course_sections` (`id`, `course_section_name`, `adviser_id`) VALUES
(1, 'BSIT-4A', 1),
(2, 'BSIT-4B', 1),
(3, 'BSIT-3B', 2),
(4, 'BSIT-3C', 2);

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `department_id` int(11) NOT NULL,
  `department_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`department_id`, `department_name`) VALUES
(1, 'IT Department'),
(2, 'CS Department');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `feedback_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `question_1` int(11) NOT NULL COMMENT 'Rating for Question 1 as percentage',
  `question_2` int(11) NOT NULL COMMENT 'Rating for Question 2 as percentage',
  `question_3` int(11) NOT NULL COMMENT 'Rating for Question 3 as percentage',
  `question_4` int(11) NOT NULL COMMENT 'Rating for Question 4 as percentage',
  `question_5` int(11) NOT NULL COMMENT 'Rating for Question 5 as percentage',
  `total_score` int(11) GENERATED ALWAYS AS ((`question_1` + `question_2` + `question_3` + `question_4` + `question_5`) / 5) STORED COMMENT 'Overall score as a percentage',
  `feedback_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `holiday`
--

CREATE TABLE `holiday` (
  `holiday_id` int(11) NOT NULL,
  `holiday_date` date NOT NULL,
  `holiday_name` varchar(255) NOT NULL,
  `memo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `holiday`
--

INSERT INTO `holiday` (`holiday_id`, `holiday_date`, `holiday_name`, `memo`) VALUES
(1, '2024-11-18', 'National Cat Day', NULL),
(2, '2024-11-08', 'Wmsu Palaro', NULL),
(3, '2024-11-28', 'Andres Bonifacio', NULL),
(4, '2024-12-05', 'Sample Holiday', '6750456b62599.pdf');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `sender_type` enum('adviser','company') NOT NULL,
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `required_hours`
--

CREATE TABLE `required_hours` (
  `required_hours_id` int(11) NOT NULL,
  `required_hours` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `required_hours`
--

INSERT INTO `required_hours` (`required_hours_id`, `required_hours`) VALUES
(1, 250);

-- --------------------------------------------------------

--
-- Table structure for table `schedule`
--

CREATE TABLE `schedule` (
  `schedule_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `generated_qr_code` varchar(255) NOT NULL,
  `day_type` enum('Regular','Halfday','Suspended') NOT NULL DEFAULT 'Regular'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `street`
--

CREATE TABLE `street` (
  `street_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `street`
--

INSERT INTO `street` (`street_id`, `name`) VALUES
(1, 'Duston Drive'),
(2, 'Loop');

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `student_id` int(11) NOT NULL,
  `wmsu_id` varchar(20) NOT NULL,
  `student_image` varchar(255) NOT NULL,
  `student_firstname` varchar(255) NOT NULL,
  `student_middle` varchar(255) NOT NULL,
  `student_lastname` varchar(255) NOT NULL,
  `student_email` varchar(255) NOT NULL,
  `student_password` varchar(255) NOT NULL,
  `contact_number` varchar(255) NOT NULL,
  `course_section` varchar(255) NOT NULL,
  `batch_year` varchar(255) NOT NULL,
  `department` varchar(255) NOT NULL,
  `company` varchar(255) NOT NULL,
  `adviser` varchar(255) NOT NULL,
  `student_address` varchar(255) NOT NULL,
  `generated_qr_code` varchar(255) NOT NULL,
  `verification_code` varchar(255) NOT NULL,
  `ojt_type` enum('Project-Based','Field-Based') NOT NULL,
  `street` varchar(255) DEFAULT NULL,
  `otp` varchar(15) DEFAULT NULL,
  `date_start` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`student_id`, `wmsu_id`, `student_image`, `student_firstname`, `student_middle`, `student_lastname`, `student_email`, `student_password`, `contact_number`, `course_section`, `batch_year`, `department`, `company`, `adviser`, `student_address`, `generated_qr_code`, `verification_code`, `ojt_type`, `street`, `otp`) VALUES
(1, '2024-61923', 'luffy_nima-loi352.png', 'Student', 'A', 'Boy', 'studentboy@gmail.com', '$2y$10$drwEozKAgmONe05KqUwe2eX9UvaF8Y3qS.XH/a9RKXKVzWyxok7KO', '+63', '4', '2025-2026', '1', '1', '1', '1', '', '', 'Field-Based', '1', NULL),
(2, '', 'luffy_nima-loi352.png', 'Student', 'A', 'Girl', 'studentgirl@gmail.com', '$2y$10$drwEozKAgmONe05KqUwe2eX9UvaF8Y3qS.XH/a9RKXKVzWyxok7KO', '+63', '2', '2021-2022', '2', '2', '2', '1', '', '', 'Field-Based', '2', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `student_journal`
--

CREATE TABLE `student_journal` (
  `journal_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `journal_name` varchar(255) NOT NULL,
  `journal_date` date NOT NULL,
  `journal_description` text NOT NULL,
  `file_size` varchar(20) NOT NULL,
  `journal_image1` varchar(255) DEFAULT NULL,
  `journal_image2` varchar(255) DEFAULT NULL,
  `journal_image3` varchar(255) DEFAULT NULL,
  `adviser_viewed` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `address`
--
ALTER TABLE `address`
  ADD PRIMARY KEY (`address_id`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`);

--
-- Indexes for table `adviser`
--
ALTER TABLE `adviser`
  ADD PRIMARY KEY (`adviser_id`);

--
-- Indexes for table `adviser_announcement`
--
ALTER TABLE `adviser_announcement`
  ADD PRIMARY KEY (`announcement_id`),
  ADD KEY `adviser_id` (`adviser_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD KEY `attendance_ibfk_1` (`student_id`),
  ADD KEY `attendance_ibfk_2` (`schedule_id`);

--
-- Indexes for table `attendance_remarks`
--
ALTER TABLE `attendance_remarks`
  ADD PRIMARY KEY (`remark_id`),
  ADD KEY `attendance_remarks_ibfk_1` (`schedule_id`),
  ADD KEY `attendance_remarks_ibfk_2` (`student_id`);

--
-- Indexes for table `company`
--
ALTER TABLE `company`
  ADD PRIMARY KEY (`company_id`);

--
-- Indexes for table `course_sections`
--
ALTER TABLE `course_sections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `adviser_id` (`adviser_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`department_id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `feedback_ibfk_1` (`student_id`);

--
-- Indexes for table `holiday`
--
ALTER TABLE `holiday`
  ADD PRIMARY KEY (`holiday_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`token`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `required_hours`
--
ALTER TABLE `required_hours`
  ADD PRIMARY KEY (`required_hours_id`);

--
-- Indexes for table `schedule`
--
ALTER TABLE `schedule`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `schedule_ibfk_1` (`company_id`);

--
-- Indexes for table `street`
--
ALTER TABLE `street`
  ADD PRIMARY KEY (`street_id`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`student_id`);

--
-- Indexes for table `student_journal`
--
ALTER TABLE `student_journal`
  ADD PRIMARY KEY (`journal_id`),
  ADD KEY `student_id` (`student_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `address`
--
ALTER TABLE `address`
  MODIFY `address_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `adviser`
--
ALTER TABLE `adviser`
  MODIFY `adviser_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `adviser_announcement`
--
ALTER TABLE `adviser_announcement`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance_remarks`
--
ALTER TABLE `attendance_remarks`
  MODIFY `remark_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `company`
--
ALTER TABLE `company`
  MODIFY `company_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `course_sections`
--
ALTER TABLE `course_sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `holiday`
--
ALTER TABLE `holiday`
  MODIFY `holiday_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `required_hours`
--
ALTER TABLE `required_hours`
  MODIFY `required_hours_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `schedule`
--
ALTER TABLE `schedule`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `street`
--
ALTER TABLE `street`
  MODIFY `street_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `student_journal`
--
ALTER TABLE `student_journal`
  MODIFY `journal_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `adviser_announcement`
--
ALTER TABLE `adviser_announcement`
  ADD CONSTRAINT `adviser_announcement_ibfk_1` FOREIGN KEY (`adviser_id`) REFERENCES `adviser` (`adviser_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`schedule_id`) REFERENCES `schedule` (`schedule_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `attendance_remarks`
--
ALTER TABLE `attendance_remarks`
  ADD CONSTRAINT `attendance_remarks_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `schedule` (`schedule_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `attendance_remarks_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `course_sections`
--
ALTER TABLE `course_sections`
  ADD CONSTRAINT `course_sections_ibfk_1` FOREIGN KEY (`adviser_id`) REFERENCES `adviser` (`adviser_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `schedule`
--
ALTER TABLE `schedule`
  ADD CONSTRAINT `schedule_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `company` (`company_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `student_journal`
--
ALTER TABLE `student_journal`
  ADD CONSTRAINT `student_journal_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
