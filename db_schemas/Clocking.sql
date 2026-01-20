-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Jan 19, 2026 at 11:29 AM
-- Server version: 8.0.40
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `Clocking`
--

--
-- Table structure for table `business`
--

CREATE TABLE `business` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `clocking_enabled` tinyint(1) DEFAULT '1',
  `reporting_enabled` tinyint(1) DEFAULT '1',
  `clock_in_start` time DEFAULT '08:00:00',
  `clock_in_end` time DEFAULT '10:00:00',
  `plan_deadline` time DEFAULT '11:00:00',
  `report_deadline` time DEFAULT '17:00:00',
  `clock_out_start` time DEFAULT '17:00:00',
  `clock_out_end` time DEFAULT '19:00:00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `business`
--

INSERT INTO `business` (`id`, `name`, `email`, `password`, `clocking_enabled`, `reporting_enabled`, `clock_in_start`, `clock_in_end`, `plan_deadline`, `report_deadline`, `clock_out_start`, `clock_out_end`, `created_at`) VALUES
(1, 'Sample Business', 'admin@business.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1, '08:00:00', '10:00:00', '11:00:00', '17:00:00', '17:00:00', '19:00:00', '2026-01-05 13:36:18'),
(2, 'Livepetal', 'livepetal@gmail.com', '$2y$10$J36J5oSnRoA/iiexdc2xUO.paAt3KWDa.u12LsVGxIhs31MBDM39y', 1, 1, '08:00:00', '23:45:00', '20:30:00', '17:00:00', '12:00:00', '19:30:00', '2026-01-05 13:37:36'),
(3, 'Inel', 'ined@gmail.com', '$2y$10$nk2LX4Qkcs2cDkUJg/5CZObJTTytO0jpZqSxxqO7lWC4v7aob0NGi', 1, 1, '08:00:00', '10:00:00', '11:00:00', '17:00:00', '17:00:00', '19:00:00', '2026-01-08 13:53:58');

-- --------------------------------------------------------

--
-- Table structure for table `daily_schedules`
--

CREATE TABLE `daily_schedules` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `task_id` int DEFAULT NULL,
  `schedule_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `activity_type` enum('task','meeting','break','personal','other') DEFAULT 'task',
  `title` varchar(255) NOT NULL,
  `description` text,
  `location` varchar(255) DEFAULT NULL,
  `status` enum('scheduled','in_progress','completed','cancelled') DEFAULT 'scheduled',
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deliverables`
--

CREATE TABLE `deliverables` (
  `id` int NOT NULL,
  `task_id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `status` enum('pending','in_progress','completed') DEFAULT 'pending',
  `due_date` date DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int NOT NULL,
  `business_id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `client_name` varchar(255) DEFAULT NULL,
  `status` enum('planning','active','on_hold','completed','cancelled') DEFAULT 'planning',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `budget_hours` decimal(8,2) DEFAULT NULL,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `team_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `business_id`, `name`, `description`, `client_name`, `status`, `start_date`, `end_date`, `budget_hours`, `created_by`, `created_at`, `updated_at`, `team_id`) VALUES
(1, 1, 'Website Redesign', 'Complete redesign of company website', NULL, 'active', NULL, NULL, NULL, 1, '2026-01-08 13:34:08', '2026-01-08 13:34:08', NULL),
(2, 1, 'Mobile App Development', 'New mobile application for customers', NULL, 'active', NULL, NULL, NULL, 1, '2026-01-08 13:34:08', '2026-01-08 13:34:08', NULL),
(3, 1, 'Website Redesign', 'Complete redesign of company website', NULL, 'active', NULL, NULL, NULL, 1, '2026-01-08 13:34:42', '2026-01-08 13:34:42', NULL),
(4, 1, 'Mobile App Development', 'New mobile application for customers', NULL, 'active', NULL, NULL, NULL, 1, '2026-01-08 13:34:42', '2026-01-08 13:34:42', NULL),
(6, 2, 'Paam', 'for the completing', NULL, 'active', '2026-01-01', '2026-01-14', NULL, 3, '2026-01-08 15:00:39', '2026-01-08 15:00:39', 2),
(7, 2, 'years', 'for planning', NULL, 'active', '2026-01-15', '2026-01-23', NULL, 1, '2026-01-08 15:04:51', '2026-01-08 15:04:51', 3),
(8, 2, 'pro', 'music', 'mor', 'active', '2026-01-21', '2026-01-21', 120.00, 1, '2026-01-14 14:14:14', '2026-01-14 14:14:14', 3);

-- --------------------------------------------------------

--
-- Table structure for table `project_members`
--

CREATE TABLE `project_members` (
  `id` int NOT NULL,
  `project_id` int NOT NULL,
  `user_id` int NOT NULL,
  `role` enum('owner','manager','contributor','viewer') DEFAULT 'contributor',
  `added_by` int NOT NULL,
  `added_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `project_phases`
--

CREATE TABLE `project_phases` (
  `id` int NOT NULL,
  `project_id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `status` enum('pending','active','completed','cancelled') DEFAULT 'pending',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `estimated_hours` decimal(8,2) DEFAULT NULL,
  `order_index` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_phases`
--

INSERT INTO `project_phases` (`id`, `project_id`, `name`, `description`, `status`, `start_date`, `end_date`, `estimated_hours`, `order_index`, `created_at`, `updated_at`) VALUES
(1, 7, 'Research Phase', 'Gather info', 'pending', NULL, NULL, NULL, 0, '2026-01-13 14:17:24', '2026-01-13 14:17:24'),
(2, 7, 'phase 2', 'great', 'pending', NULL, NULL, NULL, 0, '2026-01-13 15:56:44', '2026-01-13 15:56:44'),
(3, 7, 'phase 2', 'great', 'pending', NULL, NULL, NULL, 0, '2026-01-13 15:56:48', '2026-01-13 15:56:48'),
(4, 7, 'phase 2', 'great', 'pending', NULL, NULL, NULL, 0, '2026-01-13 15:57:59', '2026-01-13 15:57:59'),
(5, 7, 'stage 3', 'nk', 'pending', NULL, NULL, NULL, 0, '2026-01-13 16:05:20', '2026-01-13 16:05:20'),
(6, 8, 'phase 0', 'starting', 'completed', NULL, NULL, NULL, 0, '2026-01-14 14:14:37', '2026-01-19 10:17:26'),
(7, 8, 'Implement auth', 'n0 2', 'pending', NULL, NULL, NULL, 0, '2026-01-14 15:25:41', '2026-01-14 15:48:40');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `report_date` date NOT NULL,
  `clock_in_time` timestamp NULL DEFAULT NULL,
  `plan` text,
  `plan_submitted_at` timestamp NULL DEFAULT NULL,
  `daily_report` text,
  `report_submitted_at` timestamp NULL DEFAULT NULL,
  `clock_out_time` timestamp NULL DEFAULT NULL,
  `status` enum('clocked_in','plan_submitted','report_submitted','clocked_out') DEFAULT 'clocked_in',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`id`, `user_id`, `report_date`, `clock_in_time`, `plan`, `plan_submitted_at`, `daily_report`, `report_submitted_at`, `clock_out_time`, `status`, `created_at`) VALUES
(1, 3, '2026-01-05', '2026-01-05 13:59:16', '123456', '2026-01-05 14:36:55', '233545464675465465', '2026-01-05 14:37:08', '2026-01-05 14:37:16', 'clocked_out', '2026-01-05 13:59:16'),
(2, 3, '2026-01-06', '2026-01-06 10:25:09', 'okonik', '2026-01-06 10:29:48', 'nioj', '2026-01-06 10:30:11', '2026-01-06 11:07:38', 'clocked_out', '2026-01-06 10:25:09'),
(3, 3, '2026-01-07', '2026-01-07 14:04:22', 'Ensure to finish the page', '2026-01-07 14:35:09', NULL, NULL, NULL, 'plan_submitted', '2026-01-07 14:04:22'),
(4, 3, '2026-01-08', '2026-01-08 13:47:35', NULL, NULL, NULL, NULL, NULL, 'clocked_in', '2026-01-08 13:47:35'),
(5, 3, '2026-01-12', '2026-01-12 15:53:17', 'dede', '2026-01-12 15:54:07', 'dede', '2026-01-12 15:54:33', NULL, 'report_submitted', '2026-01-12 15:53:17'),
(6, 3, '2026-01-13', '2026-01-13 14:18:36', 'dede', '2026-01-13 14:18:50', NULL, NULL, NULL, 'plan_submitted', '2026-01-13 14:18:36'),
(7, 3, '2026-01-19', '2026-01-19 09:51:02', 'code', '2026-01-19 10:18:20', NULL, NULL, NULL, 'plan_submitted', '2026-01-19 09:51:02');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int NOT NULL,
  `project_id` int NOT NULL,
  `phase_id` int DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `start_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `estimated_hours` decimal(8,2) DEFAULT NULL,
  `actual_hours` decimal(8,2) DEFAULT '0.00',
  `assigned_to` int DEFAULT NULL,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deadline` date DEFAULT NULL,
  `completed_by` int DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `project_id`, `phase_id`, `name`, `description`, `status`, `priority`, `start_date`, `due_date`, `estimated_hours`, `actual_hours`, `assigned_to`, `created_by`, `created_at`, `updated_at`, `deadline`, `completed_by`, `completed_at`) VALUES
(2, 7, 1, 'new 1', 'de', 'pending', 'medium', NULL, NULL, NULL, 0.00, NULL, 1, '2026-01-13 14:25:48', '2026-01-13 14:25:48', NULL, NULL, NULL),
(3, 7, 5, 'tast 3 stage 3', 'h', 'pending', 'medium', NULL, NULL, NULL, 0.00, NULL, 1, '2026-01-13 16:05:38', '2026-01-13 16:05:38', NULL, NULL, NULL),
(4, 8, 6, 'project duco', 'der', 'completed', 'medium', NULL, NULL, NULL, 0.00, 4, 1, '2026-01-14 14:14:57', '2026-01-14 15:49:08', NULL, NULL, NULL),
(5, 8, 6, 'phase 1 task 2', 'new', 'completed', 'medium', NULL, NULL, NULL, 0.00, 3, 1, '2026-01-14 15:55:53', '2026-01-14 15:56:14', NULL, NULL, NULL),
(6, 8, 6, 'research the market', 'go for documentation', 'completed', 'medium', NULL, NULL, NULL, 0.00, 5, 1, '2026-01-19 10:16:09', '2026-01-19 10:17:20', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `task_reports`
--

CREATE TABLE `task_reports` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `task_id` int DEFAULT NULL,
  `schedule_id` int DEFAULT NULL,
  `report_date` date NOT NULL,
  `hours_worked` decimal(4,2) DEFAULT NULL,
  `progress_percentage` int DEFAULT '0',
  `status_flag` enum('on_track','at_risk','blocked') DEFAULT 'on_track',
  `activity_notes` text,
  `blockers` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teacher_activities`
--

CREATE TABLE `teacher_activities` (
  `id` int NOT NULL,
  `business_id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `activity_name` varchar(200) NOT NULL,
  `activity_date` date NOT NULL,
  `start_time` time NOT NULL,
  `duration` int NOT NULL,
  `location` varchar(100) NOT NULL,
  `grade_level` varchar(50) DEFAULT NULL,
  `status` enum('pending','completed') DEFAULT 'pending',
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_activities`
--

INSERT INTO `teacher_activities` (`id`, `business_id`, `teacher_id`, `activity_name`, `activity_date`, `start_time`, `duration`, `location`, `grade_level`, `status`, `completed_at`, `created_at`) VALUES
(1, 1, 1, 'Homeroom Registration', '2026-01-08', '08:00:00', 45, 'Room 101', 'Grade 10B', 'completed', NULL, '2026-01-08 13:34:42'),
(2, 1, 1, 'Mathematics - Calculus II', '2026-01-08', '10:30:00', 60, 'Room 304', 'Grade 12C', 'pending', NULL, '2026-01-08 13:34:42'),
(3, 1, 1, 'Staff Meeting', '2026-01-08', '13:00:00', 60, 'Conference Room B', '', 'pending', NULL, '2026-01-08 13:34:42'),
(4, 1, 1, 'Homeroom Registration', '2026-01-08', '08:00:00', 45, 'Room 101', 'Grade 10B', 'completed', NULL, '2026-01-08 13:34:53'),
(5, 1, 1, 'Mathematics - Calculus II', '2026-01-08', '10:30:00', 60, 'Room 304', 'Grade 12C', 'pending', NULL, '2026-01-08 13:34:53'),
(6, 1, 1, 'Staff Meeting', '2026-01-08', '13:00:00', 60, 'Conference Room B', '', 'pending', NULL, '2026-01-08 13:34:53');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_classes`
--

CREATE TABLE `teacher_classes` (
  `id` int NOT NULL,
  `business_id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `class_name` varchar(100) NOT NULL,
  `subject` varchar(50) NOT NULL,
  `room` varchar(50) NOT NULL,
  `day_of_week` tinyint NOT NULL,
  `start_time` time NOT NULL,
  `duration` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teams`
--

CREATE TABLE `teams` (
  `id` int NOT NULL,
  `business_id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `team_leader_id` int NOT NULL,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teams`
--

INSERT INTO `teams` (`id`, `business_id`, `name`, `description`, `team_leader_id`, `created_by`, `created_at`) VALUES
(2, 2, 'Techincal', 'Coding things', 3, 3, '2026-01-08 15:00:09'),
(3, 2, 'sales', 'selling', 4, 1, '2026-01-08 15:03:55');

-- --------------------------------------------------------

--
-- Table structure for table `team_members`
--

CREATE TABLE `team_members` (
  `id` int NOT NULL,
  `team_id` int NOT NULL,
  `user_id` int NOT NULL,
  `added_by` int NOT NULL,
  `added_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `team_members`
--

INSERT INTO `team_members` (`id`, `team_id`, `user_id`, `added_by`, `added_at`) VALUES
(3, 2, 3, 3, '2026-01-08 15:00:09'),
(4, 2, 4, 3, '2026-01-08 15:00:17'),
(5, 3, 4, 1, '2026-01-08 15:03:55'),
(6, 3, 3, 1, '2026-01-08 15:04:19'),
(7, 3, 5, 1, '2026-01-14 15:58:47');

-- --------------------------------------------------------

--
-- Table structure for table `timetables`
--

CREATE TABLE `timetables` (
  `id` int NOT NULL,
  `business_id` int NOT NULL,
  `user_id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `timetable_fulfillment`
--

CREATE TABLE `timetable_fulfillment` (
  `id` int NOT NULL,
  `slot_id` int NOT NULL,
  `fulfillment_date` date NOT NULL,
  `status` enum('scheduled','completed','cancelled','rescheduled') DEFAULT 'scheduled',
  `actual_start_time` time DEFAULT NULL,
  `actual_end_time` time DEFAULT NULL,
  `notes` text,
  `marked_by` int NOT NULL,
  `marked_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `timetable_slots`
--

CREATE TABLE `timetable_slots` (
  `id` int NOT NULL,
  `timetable_id` int NOT NULL,
  `day_of_week` enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `class_name` varchar(255) DEFAULT NULL,
  `room` varchar(100) DEFAULT NULL,
  `activity_type` enum('class','lab','meeting','break','planning','assembly','other') DEFAULT 'class',
  `notes` text,
  `is_recurring` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `business_id` int NOT NULL,
  `barcode` varchar(100) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `category` enum('staff','student') NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `can_clock` tinyint(1) DEFAULT '1',
  `can_clock_others` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `user_role` enum('admin','team_leader','team_member') DEFAULT 'team_member'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `business_id`, `barcode`, `firstname`, `lastname`, `email`, `password`, `category`, `is_active`, `can_clock`, `can_clock_others`, `created_at`, `user_role`) VALUES
(1, 1, 'BC001', 'John', 'Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 1, 1, 0, '2026-01-05 13:36:18', 'team_member'),
(2, 1, 'BC002', 'Jane', 'Smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1, 1, 0, '2026-01-05 13:36:18', 'team_member'),
(3, 2, '12345', 'Olusola', 'Itunu', 'sola@gmail.com', '$2y$10$A4fM8xP6CYfovoZmmrQ37OiyuPBa3rjjabDMT4.Z1Mk5202aMaYAG', 'staff', 1, 1, 0, '2026-01-05 13:39:49', 'team_member'),
(4, 2, '123456', 'Desola', 'Adeyemi', 'desol@gmail.com', '$2y$10$78J3/GSy/E55XcKWQRLeR.bdxliOrJUVWcVqzjs0CXRCC8RBBRabK', 'staff', 1, 1, 0, '2026-01-08 13:59:10', 'team_leader'),
(5, 2, '12345678', 'jioopp', 'popo', 'moj@gmail.com', '$2y$10$Bt/LfavAQeCpjuhv8qnU7.drB9gTsdUAGbTjqc.k4ByVkunqb9XTa', 'staff', 1, 1, 0, '2026-01-14 15:52:22', 'team_member');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `business`
--
ALTER TABLE `business`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `daily_schedules`
--
ALTER TABLE `daily_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `deliverables`
--
ALTER TABLE `deliverables`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `business_id` (`business_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `team_id` (`team_id`);

--
-- Indexes for table `project_members`
--
ALTER TABLE `project_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_project_member` (`project_id`,`user_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `added_by` (`added_by`);

--
-- Indexes for table `project_phases`
--
ALTER TABLE `project_phases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_date` (`user_id`,`report_date`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `phase_id` (`phase_id`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `completed_by` (`completed_by`);

--
-- Indexes for table `task_reports`
--
ALTER TABLE `task_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `schedule_id` (`schedule_id`);

--
-- Indexes for table `teacher_activities`
--
ALTER TABLE `teacher_activities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `teacher_classes`
--
ALTER TABLE `teacher_classes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `teams`
--
ALTER TABLE `teams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `business_id` (`business_id`),
  ADD KEY `team_leader_id` (`team_leader_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `team_members`
--
ALTER TABLE `team_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_team_member` (`team_id`,`user_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `added_by` (`added_by`);

--
-- Indexes for table `timetables`
--
ALTER TABLE `timetables`
  ADD PRIMARY KEY (`id`),
  ADD KEY `business_id` (`business_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `timetable_fulfillment`
--
ALTER TABLE `timetable_fulfillment`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_slot_date` (`slot_id`,`fulfillment_date`),
  ADD KEY `marked_by` (`marked_by`);

--
-- Indexes for table `timetable_slots`
--
ALTER TABLE `timetable_slots`
  ADD PRIMARY KEY (`id`),
  ADD KEY `timetable_id` (`timetable_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `barcode` (`barcode`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `business_id` (`business_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `business`
--
ALTER TABLE `business`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `daily_schedules`
--
ALTER TABLE `daily_schedules`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `deliverables`
--
ALTER TABLE `deliverables`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `project_members`
--
ALTER TABLE `project_members`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_phases`
--
ALTER TABLE `project_phases`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `task_reports`
--
ALTER TABLE `task_reports`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teacher_activities`
--
ALTER TABLE `teacher_activities`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `teacher_classes`
--
ALTER TABLE `teacher_classes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teams`
--
ALTER TABLE `teams`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `team_members`
--
ALTER TABLE `team_members`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `timetables`
--
ALTER TABLE `timetables`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `timetable_fulfillment`
--
ALTER TABLE `timetable_fulfillment`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `timetable_slots`
--
ALTER TABLE `timetable_slots`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `daily_schedules`
--
ALTER TABLE `daily_schedules`
  ADD CONSTRAINT `daily_schedules_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `daily_schedules_ibfk_2` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `daily_schedules_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `deliverables`
--
ALTER TABLE `deliverables`
  ADD CONSTRAINT `deliverables_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `business` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `projects_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `projects_ibfk_3` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `project_members`
--
ALTER TABLE `project_members`
  ADD CONSTRAINT `project_members_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `project_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `project_members_ibfk_3` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `project_phases`
--
ALTER TABLE `project_phases`
  ADD CONSTRAINT `project_phases_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`phase_id`) REFERENCES `project_phases` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tasks_ibfk_3` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tasks_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tasks_ibfk_5` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `task_reports`
--
ALTER TABLE `task_reports`
  ADD CONSTRAINT `task_reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_reports_ibfk_2` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `task_reports_ibfk_3` FOREIGN KEY (`schedule_id`) REFERENCES `daily_schedules` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `teams`
--
ALTER TABLE `teams`
  ADD CONSTRAINT `teams_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `business` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teams_ibfk_2` FOREIGN KEY (`team_leader_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teams_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `team_members`
--
ALTER TABLE `team_members`
  ADD CONSTRAINT `team_members_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `team_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `team_members_ibfk_3` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `timetables`
--
ALTER TABLE `timetables`
  ADD CONSTRAINT `timetables_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `business` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetables_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `timetable_fulfillment`
--
ALTER TABLE `timetable_fulfillment`
  ADD CONSTRAINT `timetable_fulfillment_ibfk_1` FOREIGN KEY (`slot_id`) REFERENCES `timetable_slots` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_fulfillment_ibfk_2` FOREIGN KEY (`marked_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `timetable_slots`
--
ALTER TABLE `timetable_slots`
  ADD CONSTRAINT `timetable_slots_ibfk_1` FOREIGN KEY (`timetable_id`) REFERENCES `timetables` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `business` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
