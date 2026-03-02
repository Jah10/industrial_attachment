-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 18, 2025 at 10:13 PM
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
-- Database: ``
--

-- --------------------------------------------------------

--
-- Table structure for table `final_reports`
--

CREATE TABLE `final_reports` (
  `report_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `submission_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `final_reports`
--

INSERT INTO `final_reports` (`report_id`, `student_id`, `title`, `content`, `submission_date`) VALUES
(1, 1, 'test', 'test', '2025-04-18 19:51:15');

-- --------------------------------------------------------

--
-- Table structure for table `industrial_assessments`
--

CREATE TABLE `industrial_assessments` (
  `assessment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `punctuality` int(11) NOT NULL,
  `teamwork` int(11) NOT NULL,
  `problem_solving` int(11) NOT NULL,
  `technical_skills` int(11) NOT NULL,
  `communication` int(11) NOT NULL,
  `overall_remarks` text NOT NULL,
  `total_score` int(11) NOT NULL,
  `submission_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `industrial_assessments`
--

INSERT INTO `industrial_assessments` (`assessment_id`, `student_id`, `organization_id`, `punctuality`, `teamwork`, `problem_solving`, `technical_skills`, `communication`, `overall_remarks`, `total_score`, `submission_date`) VALUES
(1, 1, 1, 3, 4, 2, 4, 5, 'good', 18, '2025-04-18 19:49:44');

-- --------------------------------------------------------

--
-- Table structure for table `logbooks`
--

CREATE TABLE `logbooks` (
  `logbook_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `week_number` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `activities` text NOT NULL,
  `challenges` text DEFAULT NULL,
  `solutions` text DEFAULT NULL,
  `submission_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `logbooks`
--

INSERT INTO `logbooks` (`logbook_id`, `student_id`, `week_number`, `start_date`, `end_date`, `activities`, `challenges`, `solutions`, `submission_date`) VALUES
(1, 1, 3, '2025-04-18', '2025-04-18', 'tets', 'test', 'test', '2025-04-18 19:50:57');

-- --------------------------------------------------------

--
-- Table structure for table `organizations`
--

CREATE TABLE `organizations` (
  `organization_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `location` varchar(100) NOT NULL,
  `contact_person` varchar(100) NOT NULL,
  `contact_email` varchar(100) NOT NULL,
  `contact_phone` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `is_approved` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `organizations`
--

INSERT INTO `organizations` (`organization_id`, `user_id`, `name`, `location`, `contact_person`, `contact_email`, `contact_phone`, `description`, `is_approved`) VALUES
(1, 2, 'University Of Botswana', 'Gaborone', 'Obakeng Omphemetse Mothusi', 'Organisation1@email.com', '71234567', 'Web development', '1'),
(2, 5, 'Organisation2', 'Gaborone', 'Obakeng Omphemetse Mothusi', 'organisation2@example.com', '75438734', '0', '1');

-- --------------------------------------------------------

--
-- Table structure for table `organization_requirements`
--

CREATE TABLE `organization_requirements` (
  `requirement_id` int(11) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `skills_required` text NOT NULL,
  `positions_available` int(11) NOT NULL DEFAULT 1,
  `project_description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `organization_requirements`
--

INSERT INTO `organization_requirements` (`requirement_id`, `organization_id`, `skills_required`, `positions_available`, `project_description`) VALUES
(1, 1, 'Web development', 3, 'Make websites'),
(2, 2, 'dev', 1, 'Web development');

-- --------------------------------------------------------

--
-- Table structure for table `reminders`
--

CREATE TABLE `reminders` (
  `reminder_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `due_date` date NOT NULL,
  `user_type` enum('student','coordinator','supervisor','organization','all') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reminders`
--

INSERT INTO `reminders` (`reminder_id`, `title`, `description`, `due_date`, `user_type`, `created_at`, `is_active`) VALUES
(1, 'Report', 'Submit report', '2025-04-30', 'student', '2025-04-18 19:44:42', 1),
(2, 'Report', 'report', '2025-04-18', 'all', '2025-04-18 19:45:00', 1);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `registration_number` varchar(20) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `skills` text DEFAULT NULL,
  `preferred_location` varchar(100) DEFAULT NULL,
  `preferred_project_type` text DEFAULT NULL,
  `attachment_status` enum('pending','matched','ongoing','completed') DEFAULT 'pending',
  `organization_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `user_id`, `full_name`, `registration_number`, `phone`, `skills`, `preferred_location`, `preferred_project_type`, `attachment_status`, `organization_id`) VALUES
(1, 1, '', '202000531', '', 'web development', 'gaborone', 'Remote', 'completed', 1),
(2, 6, '', '22222222', '', 'test', 'gaborone', 'test', 'pending', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `student_supervisor`
--

CREATE TABLE `student_supervisor` (
  `assignment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `supervisor_id` int(11) NOT NULL,
  `assigned_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_supervisor`
--

INSERT INTO `student_supervisor` (`assignment_id`, `student_id`, `supervisor_id`, `assigned_date`) VALUES
(1, 1, 1, '2025-04-18 19:22:44');

-- --------------------------------------------------------

--
-- Table structure for table `supervisors`
--

CREATE TABLE `supervisors` (
  `supervisor_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supervisors`
--

INSERT INTO `supervisors` (`supervisor_id`, `user_id`, `full_name`, `department`, `phone`) VALUES
(1, 3, 'supervisor supervisor', 'Computer Science', '76965289');

-- --------------------------------------------------------

--
-- Table structure for table `university_assessments`
--

CREATE TABLE `university_assessments` (
  `assessment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `supervisor_id` int(11) NOT NULL,
  `visit_number` int(11) NOT NULL,
  `visit_date` date NOT NULL,
  `progress` int(11) NOT NULL,
  `attendance` int(11) NOT NULL,
  `technical_skills` int(11) NOT NULL,
  `presentation` int(11) NOT NULL,
  `remarks` text NOT NULL,
  `total_score` int(11) NOT NULL,
  `submission_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `university_assessments`
--

INSERT INTO `university_assessments` (`assessment_id`, `student_id`, `supervisor_id`, `visit_number`, `visit_date`, `progress`, `attendance`, `technical_skills`, `presentation`, `remarks`, `total_score`, `submission_date`) VALUES
(1, 1, 1, 1, '2025-04-18', 15, 18, 20, 19, '0', 72, '2025-04-18 19:54:30'),
(2, 1, 1, 2, '2025-04-20', 20, 19, 18, 18, '0', 75, '2025-04-18 19:55:12');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `user_type` enum('student','coordinator','supervisor','organization') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `user_type`, `created_at`) VALUES
(1, 'student', '$2y$10$gKZTrvUPaB6f9PQAU.uoBeBAUjli4uT2JA4oqqSd9M4KReEZB3wmC', 'student1@example.com', 'student', '2025-04-18 18:13:56'),
(2, 'organisation@example.com', '$2y$10$y/UmdSqLkMMp5eSMFtcBY.r7SeXaiYA2BSnQhyWzmi72W.tSB5pgK', 'organisation1@example.com', 'organization', '2025-04-18 18:57:21'),
(3, 'supervisor1', '$2y$10$TP.8mZUyq75DILUlw8lZc.7pEXHXd7cfZ0zK3b57G7z8/EVn5soau', 'supervisor1@example.com', 'supervisor', '2025-04-18 18:57:51'),
(4, 'coordinator', '$2y$10$TP.8mZUyq75DILUlw8lZc.7pEXHXd7cfZ0zK3b57G7z8/EVn5soau', 'coordinator@example.com', 'coordinator', '2025-04-18 18:57:51'),
(5, 'organisation2', '$2y$10$7C99Mu1fdSG7H1kaqrcrdeSlGWUtyHmLSiIzMASYkbVBdiMFKSQCK', 'organisation2@example.com', 'organization', '2025-04-18 19:46:44'),
(6, 'student2', '$2y$10$GwVJcjyjgRnBKGIkXq6vee1F3My4d4jLtku07k1Qkth0lHWWoY9xS', 'student2@example.com', 'student', '2025-04-18 19:58:10');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `final_reports`
--
ALTER TABLE `final_reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `industrial_assessments`
--
ALTER TABLE `industrial_assessments`
  ADD PRIMARY KEY (`assessment_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `organization_id` (`organization_id`);

--
-- Indexes for table `logbooks`
--
ALTER TABLE `logbooks`
  ADD PRIMARY KEY (`logbook_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `organizations`
--
ALTER TABLE `organizations`
  ADD PRIMARY KEY (`organization_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `organization_requirements`
--
ALTER TABLE `organization_requirements`
  ADD PRIMARY KEY (`requirement_id`),
  ADD KEY `organization_id` (`organization_id`);

--
-- Indexes for table `reminders`
--
ALTER TABLE `reminders`
  ADD PRIMARY KEY (`reminder_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `registration_number` (`registration_number`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `student_supervisor`
--
ALTER TABLE `student_supervisor`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `supervisor_id` (`supervisor_id`);

--
-- Indexes for table `supervisors`
--
ALTER TABLE `supervisors`
  ADD PRIMARY KEY (`supervisor_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `university_assessments`
--
ALTER TABLE `university_assessments`
  ADD PRIMARY KEY (`assessment_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `supervisor_id` (`supervisor_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `final_reports`
--
ALTER TABLE `final_reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `industrial_assessments`
--
ALTER TABLE `industrial_assessments`
  MODIFY `assessment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `logbooks`
--
ALTER TABLE `logbooks`
  MODIFY `logbook_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `organizations`
--
ALTER TABLE `organizations`
  MODIFY `organization_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `organization_requirements`
--
ALTER TABLE `organization_requirements`
  MODIFY `requirement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `reminders`
--
ALTER TABLE `reminders`
  MODIFY `reminder_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `student_supervisor`
--
ALTER TABLE `student_supervisor`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `supervisors`
--
ALTER TABLE `supervisors`
  MODIFY `supervisor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `university_assessments`
--
ALTER TABLE `university_assessments`
  MODIFY `assessment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `final_reports`
--
ALTER TABLE `final_reports`
  ADD CONSTRAINT `final_reports_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `industrial_assessments`
--
ALTER TABLE `industrial_assessments`
  ADD CONSTRAINT `industrial_assessments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `industrial_assessments_ibfk_2` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`organization_id`) ON DELETE CASCADE;

--
-- Constraints for table `logbooks`
--
ALTER TABLE `logbooks`
  ADD CONSTRAINT `logbooks_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `organizations`
--
ALTER TABLE `organizations`
  ADD CONSTRAINT `organizations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `organization_requirements`
--
ALTER TABLE `organization_requirements`
  ADD CONSTRAINT `organization_requirements_ibfk_1` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`organization_id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `student_supervisor`
--
ALTER TABLE `student_supervisor`
  ADD CONSTRAINT `student_supervisor_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_supervisor_ibfk_2` FOREIGN KEY (`supervisor_id`) REFERENCES `supervisors` (`supervisor_id`) ON DELETE CASCADE;

--
-- Constraints for table `supervisors`
--
ALTER TABLE `supervisors`
  ADD CONSTRAINT `supervisors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `university_assessments`
--
ALTER TABLE `university_assessments`
  ADD CONSTRAINT `university_assessments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `university_assessments_ibfk_2` FOREIGN KEY (`supervisor_id`) REFERENCES `supervisors` (`supervisor_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
