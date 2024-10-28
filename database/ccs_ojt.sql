-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 22, 2024 at 08:59 AM
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
(1, '', 'Jhoanna', 'B', 'Robles', '+639441083491','samcena.902604@gmail.com', '$2y$10$j6qBb5wKj8Of0iRWerAUWe6SS8aEsJZM/YMGlF/xHMflG/P6qcvoa', 'IT Department', ''),
(2, '', 'Colet', '', 'Vergara', '+639441083493','colet@gmail.com', '$2y$10$j6qBb5wKj8Of0iRWerAUWe6SS8aEsJZM/YMGlF/xHMflG/P6qcvoa', 'IT Department', ''),
(3, '', 'Jhoanna', 'B', 'Robles', '+639441083491','samcena.902604@gmail.com', '$2y$10$j6qBb5wKj8Of0iRWerAUWe6SS8aEsJZM/YMGlF/xHMflG/P6qcvoa', 'IT Department', ''),
(4, '', 'Colet', '', 'Vergara', '+639441083493','colet@gmail.com', '$2y$10$j6qBb5wKj8Of0iRWerAUWe6SS8aEsJZM/YMGlF/xHMflG/P6qcvoa', 'IT Department', ''),
(5, '', 'Jhoanna', 'B', 'Robles', '+639441083491','samcena.902604@gmail.com', '$2y$10$j6qBb5wKj8Of0iRWerAUWe6SS8aEsJZM/YMGlF/xHMflG/P6qcvoa', 'CS Department', ''),
(6, '', 'Colet', '', 'Vergara', '+639441083493','colet@gmail.com', '$2y$10$j6qBb5wKj8Of0iRWerAUWe6SS8aEsJZM/YMGlF/xHMflG/P6qcvoa', 'CS Department', ''),
(7, '', 'Maraiah', 'Queen', 'Arceta','+639441083456', 'salasainahmad@gmail.com', '$2y$10$j6qBb5wKj8Of0iRWerAUWe6SS8aEsJZM/YMGlF/xHMflG/P6qcvoa', 'CS Department', '');

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
(1, 1, 'Announcement Name', '0000-00-00', 'Description');

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

INSERT INTO `company` (`company_id`, `company_image`, `company_name`, `company_rep_firstname`, `company_rep_middle`, `company_rep_lastname`,  `company_email`, `company_password`, `company_address`, `company_number`, `verification_code`) VALUES
(1, 'ccs.png', 'College of Computing Studies', 'Monkey' , 'D' , 'Garp', 'ccs.ojtmanagementsystem@gmail.com', '$2y$10$j6qBb5wKj8Of0iRWerAUWe6SS8aEsJZM/YMGlF/xHMflG/P6qcvoa', 'Kasanyangan, Hanapi Drive', '+63920783229', ''),
(2, 'ccs.png', 'College of Asian Studies', 'Direk' , 'D' , 'Loren', 'ccs@gmail.com', '$2y$10$j6qBb5wKj8Of0iRWerAUWe6SS8aEsJZM/YMGlF/xHMflG/P6qcvoa', 'Kasanyangan, Hanapi Drive', '+63920783229', ''),
(3, 'ccs.png', 'College of Computing Studies', 'Monkey' , 'D' , 'Garp', 'ccs.ojtmanagementsystem@gmail.com', '$2y$10$j6qBb5wKj8Of0iRWerAUWe6SS8aEsJZM/YMGlF/xHMflG/P6qcvoa', 'Kasanyangan, Hanapi Drive', '+63920783229', ''),
(4, 'ccs.png', 'College of Asian Studies', 'Direk' , 'D' , 'Loren', 'ccs@gmail.com', '$2y$10$j6qBb5wKj8Of0iRWerAUWe6SS8aEsJZM/YMGlF/xHMflG/P6qcvoa', 'Kasanyangan, Hanapi Drive', '+63920783229', ''),
(5, 'ccs.png', 'College of Computing Studies', 'Monkey' , 'D' , 'Garp', 'ccs.ojtmanagementsystem@gmail.com', '$2y$10$j6qBb5wKj8Of0iRWerAUWe6SS8aEsJZM/YMGlF/xHMflG/P6qcvoa', 'Kasanyangan, Hanapi Drive', '+63920783229', ''),
(6, 'ccs.png', 'College of Asian Studies', 'Direk' , 'D' , 'Loren', 'ccs@gmail.com', '$2y$10$j6qBb5wKj8Of0iRWerAUWe6SS8aEsJZM/YMGlF/xHMflG/P6qcvoa', 'Kasanyangan, Hanapi Drive', '+63920783229', ''),
(7, 'csm.png', 'College of Science and Mathematics', 'Hanamichi' , 'D' , 'Sakuragi', 'kaizoku902604@gmail.com', '$2y$10$j6qBb5wKj8Of0iRWerAUWe6SS8aEsJZM/YMGlF/xHMflG/P6qcvoa', 'Kasanyangan, Hanapi Drive', '+63920783229', '');

-- --------------------------------------------------------

--
-- Table structure for table `course_sections`
--

CREATE TABLE `course_sections` (
  `id` int(11) NOT NULL,
  `course_section_name` varchar(255) NOT NULL,
  `adviser_id` int(11) NOT NULL, 
  KEY `adviser_id` (`adviser_id`) 
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `course_sections`
--

INSERT INTO `course_sections` (`id`, `course_section_name`, `adviser_id`) VALUES
(1, 'BSIT-4A', 1),  
(2, 'BSIT-4B', 1), 
(3, 'BSIT-3A', 2),  
(4, 'BSIT-3B', 3); 

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
-- Table structure for table `address`
--

CREATE TABLE `address` (
  `address_id` int(11) NOT NULL,
  `address_barangay` varchar(255) NOT NULL,
  `address_street` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `address`
--

INSERT INTO `address` (`address_id`, `address_barangay`, `address_street`) VALUES
(1, 'Kasanyangan' , 'Hanapi Drive'),
(2, 'Baliwasan' , 'Normal Road');

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
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `feedback_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `question_1` int(11) NOT NULL COMMENT 'Rating for Question 1 as percentage',
  `question_2` int(11) NOT NULL COMMENT 'Rating for Question 2 as percentage',
  `question_3` int(11) NOT NULL COMMENT 'Rating for Question 3 as percentage',
  `question_4` int(11) NOT NULL COMMENT 'Rating for Question 4 as percentage',
  `question_5` int(11) NOT NULL COMMENT 'Rating for Question 5 as percentage',
  `total_score` int(11) AS ((`question_1` + `question_2` + `question_3` + `question_4` + `question_5`) / 5) STORED COMMENT 'Overall score as a percentage',
  `feedback_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`feedback_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

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
  `verification_code` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`student_id`, `wmsu_id`, `student_image`, `student_firstname`, `student_middle`, `student_lastname`, `student_email`, `student_password`, `contact_number`, `course_section`, `batch_year`, `department`, `company`, `adviser`, `student_address`, `generated_qr_code`, `verification_code`) VALUES
(1, '2024-206910', '', 'Maloi', 'K', 'Ricalde', 'maloi@gmail.com', '$2y$10$j6qBb5wKj8Of0iRWerAUWe6SS8aEsJZM/YMGlF/xHMflG/P6qcvoa', '+639771036244', 'BSIT 4A', '2024-2025', '', '3', '', '2', '', ''),
(2, '2024-206911', '', 'Traffy', 'D', 'Law', 'traffy@gmail.com', '$2y$10$j6qBb5wKj8Of0iRWerAUWe6SS8aEsJZM/YMGlF/xHMflG/P6qcvoa', '+639771036245', 'BSIT 4B', '2024-2025', '', '3', '', '1', '../../uploads/qrcodes/qr-code-Law-2024-206911.png', ''),
(3, '2024-206912', '', 'Shiro', 'D', 'Hige', 'shiro@gmail.com', '$2y$10$j6qBb5wKj8Of0iRWerAUWe6SS8aEsJZM/YMGlF/xHMflG/P6qcvoa', '+639771036246', 'BSIT 4C', '2024-2025', '', '3', '', '3', '../../uploads/qrcodes/qr-code-Hige-2024-206912.png', ''),
(4, '', '', 'Monkey', 'D', 'Luffy', 'binimaloi352@gmail.com', '$2y$10$drwEozKAgmONe05KqUwe2eX9UvaF8Y3qS.XH/a9RKXKVzWyxok7KO', '', '', '', '', '', '', '', '', '');

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
  `file_size` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `student_journal`
--

INSERT INTO `student_journal` (`journal_id`, `student_id`, `journal_name`, `journal_date`, `journal_description`, `file_size`) VALUES
(1, 1, 'Journal Name', '2024-10-01', 'Description', '');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `time_in` timestamp NOT NULL DEFAULT current_timestamp(),
  `time_out` timestamp NULL DEFAULT NULL,
  `ojt_hours` DECIMAL(10,2) GENERATED ALWAYS AS (TIMESTAMPDIFF(SECOND, `time_in`, `time_out`) / 3600) STORED,
  PRIMARY KEY (`attendance_id`) 
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `schedule`
--

CREATE TABLE `schedule` (
  `schedule_id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `time_in` time DEFAULT NULL, 
  `time_out` time DEFAULT NULL, 
  `generated_qr_code` varchar(255) NOT NULL,
  `day_type` enum('Regular', 'Halfday', 'Suspended') NOT NULL DEFAULT 'Regular',
  PRIMARY KEY (`schedule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `holiday`
--

CREATE TABLE `holiday` (
  `holiday_id` int(11) NOT NULL AUTO_INCREMENT,
  `holiday_date` date NOT NULL,
  `holiday_name` varchar(255) NOT NULL,
  PRIMARY KEY (`holiday_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Indexes for dumped tables
--

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
-- Indexes for table `company`
--
ALTER TABLE `company`
  ADD PRIMARY KEY (`company_id`);

--
-- Indexes for table `course_sections`
--
ALTER TABLE `course_sections`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`department_id`);

--
-- Indexes for table `address`
--
ALTER TABLE `address`
  ADD PRIMARY KEY (`address_id`);

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

-- --------------------------------------------------------

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `adviser`
--
ALTER TABLE `adviser`
  MODIFY `adviser_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `address`
--
ALTER TABLE `address`
  MODIFY `address_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `adviser_announcement`
--
ALTER TABLE `adviser_announcement`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `company`
--
ALTER TABLE `company`
  MODIFY `company_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `student_journal`
--
ALTER TABLE `student_journal`
  MODIFY `journal_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

-- --------------------------------------------------------

--
-- Constraints for dumped tables
--

--
-- Constraints for table `adviser_announcement`
--
ALTER TABLE `adviser_announcement`
  ADD CONSTRAINT `adviser_announcement_ibfk_1` FOREIGN KEY (`adviser_id`) REFERENCES `adviser` (`adviser_id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- Constraints for table `course_sections`
ALTER TABLE `course_sections`
  ADD CONSTRAINT `course_sections_ibfk_1` FOREIGN KEY (`adviser_id`) REFERENCES `adviser` (`adviser_id`) ON DELETE CASCADE ON UPDATE CASCADE; 
  
-- Constraints for table `feedback`
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE; 
--
-- Constraints for table `student_journal`
--
ALTER TABLE `student_journal`
  ADD CONSTRAINT `student_journal_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE;
--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`schedule_id`) REFERENCES `schedule` (`schedule_id`) ON DELETE CASCADE ON UPDATE CASCADE;
--
-- Constraints for table `schedule`
--
ALTER TABLE `schedule`
  ADD CONSTRAINT `schedule_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `company` (`company_id`) ON DELETE CASCADE ON UPDATE CASCADE;

COMMIT;


/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
