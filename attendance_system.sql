-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 05, 2025 at 11:57 PM
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
-- Database: `attendance_system`
--

-- --------------------------------------------------------

--

--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `status` enum('present','absent','late') DEFAULT 'present',
  `marked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `marked_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--

--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `course_name` varchar(200) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `day` varchar(20) DEFAULT NULL,
  `time` varchar(50) DEFAULT NULL,
  `faculty_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--

--

INSERT INTO `courses` (`id`, `course_name`, `course_code`, `description`, `day`, `time`, `faculty_id`, `created_at`) VALUES
(1, 'WEBBBB', 'CS3333', 'LEARN', 'Monday', '8:00AM - 9:30AM', 2, '2025-11-24 23:09:20');

-- --------------------------------------------------------

--

--

CREATE TABLE `course_requests` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `enrollment_type` enum('regular','auditor','observer') DEFAULT 'regular',
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `request_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--

--

CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `enrollment_type` enum('regular','auditor','observer') DEFAULT 'regular',
  `status` enum('approved','pending','rejected') DEFAULT 'approved',
  `grade` varchar(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--

--

CREATE TABLE `sessions` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `session_date` date NOT NULL,
  `session_time` varchar(50) DEFAULT NULL,
  `attendance_code` varchar(10) NOT NULL,
  `code_expires_at` datetime NOT NULL,
  `created_by` int(11) NOT NULL,
  `status` enum('active','completed','cancelled') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--

--

INSERT INTO `sessions` (`id`, `course_id`, `session_date`, `session_time`, `attendance_code`, `code_expires_at`, `created_by`, `status`, `created_at`) VALUES
(1, 1, '2025-12-05', '8:00AM - 9:30AM', '704DB9', '2025-12-05 01:00:00', 2, 'active', '2025-12-05 21:24:07');

-- --------------------------------------------------------

--

--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `role` enum('student','Faculty','FacultyIntern') NOT NULL,
  `email` varchar(255) NOT NULL,
  `contact` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--

--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `role`, `email`, `contact`, `password`, `created_at`, `updated_at`) VALUES
(1, 'Edwin', 'Engmann', 'student', 'edwinengmann01@gmail.com', '0535744695', '$2y$10$DoRO0K/0291X8Y/BqmU5/uOZ6YFRLs9wN80OVVinUzkKyFFv4n1va', '2025-11-23 22:52:05', '2025-11-23 22:52:05'),
(2, 'David', 'Sampah', 'Faculty', 'davidsampah@gmail.com', '0544675675', '$2y$10$rz4FUl7NZ0bzO2JZlwMTG.3KelyzUN1FcxMsY4ML9vytDxHentOCS', '2025-11-24 22:56:35', '2025-11-24 22:56:35'),
(3, 'Hutton', 'Addy', 'FacultyIntern', 'huttonaddy@gmail.com', '0543456785', '$2y$10$YM03NUqm0ZXopANb25bUPOEdHrOEfVxj.QlJW1.LSYbEoXmnsym0S', '2025-12-05 22:52:44', '2025-12-05 22:52:44');

--

--

--

--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attendance` (`session_id`,`student_id`),
  ADD KEY `marked_by` (`marked_by`),
  ADD KEY `idx_attendance_student` (`student_id`),
  ADD KEY `idx_attendance_session` (`session_id`);

--

--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `course_code` (`course_code`),
  ADD KEY `idx_course_faculty` (`faculty_id`);

--

--
ALTER TABLE `course_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_request` (`student_id`,`course_id`),
  ADD KEY `idx_request_student` (`student_id`),
  ADD KEY `idx_request_course` (`course_id`),
  ADD KEY `idx_request_status` (`status`);

--

--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_enrollment` (`user_id`,`course_id`),
  ADD KEY `idx_enrollment_user` (`user_id`),
  ADD KEY `idx_enrollment_course` (`course_id`);

--

--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `attendance_code` (`attendance_code`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_session_code` (`attendance_code`),
  ADD KEY `idx_session_course` (`course_id`);

--

--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_user_email` (`email`),
  ADD KEY `idx_user_role` (`role`);

--

--

--

--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--

--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--

--
ALTER TABLE `course_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--

--
ALTER TABLE `enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--

--
ALTER TABLE `sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--

--

--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_3` FOREIGN KEY (`marked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--

--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`faculty_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--

--
ALTER TABLE `course_requests`
  ADD CONSTRAINT `course_requests_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_requests_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--

--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sessions_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
