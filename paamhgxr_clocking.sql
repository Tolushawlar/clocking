-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jan 19, 2026 at 05:49 AM
-- Server version: 11.4.9-MariaDB-cll-lve
-- PHP Version: 8.3.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `paamhgxr_clocking`
--

-- --------------------------------------------------------

--
-- Table structure for table `business`
--

CREATE TABLE `business` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `clocking_enabled` tinyint(1) DEFAULT 1,
  `reporting_enabled` tinyint(1) DEFAULT 1,
  `clock_in_start` time DEFAULT '08:00:00',
  `clock_in_end` time DEFAULT '10:00:00',
  `plan_deadline` time DEFAULT '11:00:00',
  `report_deadline` time DEFAULT '17:00:00',
  `clock_out_start` time DEFAULT '17:00:00',
  `clock_out_end` time DEFAULT '19:00:00',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `business`
--

INSERT INTO `business` (`id`, `name`, `email`, `password`, `clocking_enabled`, `reporting_enabled`, `clock_in_start`, `clock_in_end`, `plan_deadline`, `report_deadline`, `clock_out_start`, `clock_out_end`, `created_at`) VALUES
(1, 'Sample Business', 'admin@business.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1, '08:00:00', '10:00:00', '11:00:00', '17:00:00', '17:00:00', '19:00:00', '2026-01-05 15:08:37'),
(2, 'Livepetal', 'livepetal@gmail.com', '$2y$10$EhbVyayrCMEAuZAfHAy/K.Ufgg7MVGA5tsw0eRJ.dwOJoEokv78pm', 1, 1, '08:00:00', '10:00:00', '11:00:00', '17:00:00', '17:00:00', '19:00:00', '2026-01-06 07:31:03');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `report_date` date NOT NULL,
  `clock_in_time` timestamp NULL DEFAULT NULL,
  `plan` text DEFAULT NULL,
  `plan_submitted_at` timestamp NULL DEFAULT NULL,
  `daily_report` text DEFAULT NULL,
  `report_submitted_at` timestamp NULL DEFAULT NULL,
  `clock_out_time` timestamp NULL DEFAULT NULL,
  `status` enum('clocked_in','plan_submitted','report_submitted','clocked_out') DEFAULT 'clocked_in',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`id`, `user_id`, `report_date`, `clock_in_time`, `plan`, `plan_submitted_at`, `daily_report`, `report_submitted_at`, `clock_out_time`, `status`, `created_at`) VALUES
(1, 4, '2026-01-06', '2026-01-06 11:24:50', 'create clocking and reporting app', '2026-01-06 14:42:35', 'created clocking and reporting app successfully', '2026-01-06 14:44:13', '2026-01-06 16:03:33', 'clocked_out', '2026-01-06 11:24:50'),
(2, 3, '2026-01-06', '2026-01-06 14:12:10', 'This is my report for today', '2026-01-06 14:41:27', 'My Report submitted', '2026-01-06 14:42:21', '2026-01-06 16:01:41', 'clocked_out', '2026-01-06 14:12:10'),
(3, 5, '2026-01-06', '2026-01-06 15:06:23', 'create app', '2026-01-06 15:09:05', 'the computer api', '2026-01-06 15:24:00', '2026-01-06 16:04:44', 'clocked_out', '2026-01-06 15:06:23'),
(4, 3, '2026-01-07', '2026-01-07 08:16:27', 'Testing Report submission from this end', '2026-01-07 11:45:13', 'some accomplishment are here', '2026-01-07 11:46:12', '2026-01-07 13:44:38', 'clocked_out', '2026-01-07 08:16:27'),
(5, 5, '2026-01-07', '2026-01-07 08:16:35', 'update the app', '2026-01-07 15:28:49', NULL, NULL, NULL, 'plan_submitted', '2026-01-07 08:16:35'),
(6, 6, '2026-01-07', '2026-01-07 15:00:01', 'Reaching out to 30 schools concerning our 7-day Visibility challenge for school owners for the month of January,2026.', '2026-01-07 15:04:06', 'The 30 schools has been reached out to through Text messages. One of the schools replied our text and said even scammers use The same approach for people to join Whatsapp groups but we gave a call back to them through our official line to confirm our originality', '2026-01-07 15:07:01', '2026-01-07 15:44:47', 'clocked_out', '2026-01-07 15:00:01'),
(7, 7, '2026-01-07', '2026-01-07 15:00:18', 'Engaging my daily method of operation. I have plans to reach out to at least 10 school owners and invite them for 7 days visibility challenge for school growth and visibility.', '2026-01-07 15:08:12', 'I reached out 11 school owners on SMS through google map, we got 1 feedback of being scared of scammers, 1 joined the group.', '2026-01-07 15:22:29', '2026-01-07 15:45:06', 'clocked_out', '2026-01-07 15:00:18'),
(8, 4, '2026-01-07', '2026-01-07 15:12:22', 'Finish redesigning the clocking system frontend', '2026-01-07 15:15:42', 'Finished the redesign and deployed to the live server', '2026-01-07 15:42:59', '2026-01-07 15:45:23', 'clocked_out', '2026-01-07 15:12:22'),
(9, 5, '2026-01-08', '2026-01-08 06:56:33', 'Make the adjustments on the clocking app. Respond to job applicants and schedule interviews. Meet with oga on the responsibilities of HR and Financial Manager', '2026-01-08 07:52:52', 'Successfully updated the clocking and reporting app. Reviewed job applications and sent Scheduled interview mails to the applicants.', '2026-01-08 16:32:22', '2026-01-08 16:33:19', 'clocked_out', '2026-01-08 06:56:33'),
(10, 6, '2026-01-08', '2026-01-08 07:02:37', 'To put a call through to the 30 schools we sent text messages to, to further remind them and also assure them we sent the messages from a legit company and certified source', '2026-01-08 15:39:19', 'We were able to reach out to 20 out of 30 schools, the remaining 10 schools could not be reached out to due to their lines not reachable', '2026-01-08 15:40:23', '2026-01-08 16:00:12', 'clocked_out', '2026-01-08 07:02:37'),
(11, 4, '2026-01-08', '2026-01-08 07:23:36', 'Begin work on the implementation of the time track pro new features addition.', '2026-01-08 07:37:32', 'Implemented the team and projects features in the time track pro', '2026-01-08 15:33:02', '2026-01-08 15:59:52', 'clocked_out', '2026-01-08 07:23:36'),
(12, 7, '2026-01-08', '2026-01-08 07:42:03', 'Put through warm calls to everyone SMS was delivered to in the previous days', '2026-01-08 08:11:03', 'i was able to make calls to 13 schools and invited them verbally for the school owner visibility challenge', '2026-01-08 15:44:43', '2026-01-08 16:01:06', 'clocked_out', '2026-01-08 07:42:03'),
(13, 5, '2026-01-09', '2026-01-09 07:00:03', 'send interview schedules to office secretary applicants. To complete the correction on the clocking and reporting app. meet with oga on financial plans and recordings', '2026-01-09 11:16:53', 'successfully sent response to the office secretary applicants. updated the clocking app', '2026-01-09 14:14:26', '2026-01-09 14:15:01', 'clocked_out', '2026-01-09 07:00:03'),
(14, 4, '2026-01-09', '2026-01-09 07:03:35', 'Re-edit the Pam documets', '2026-01-09 14:17:42', 'Edited the documents', '2026-01-09 14:17:58', '2026-01-09 14:19:30', 'clocked_out', '2026-01-09 07:03:35'),
(15, 7, '2026-01-09', '2026-01-09 07:33:49', '1. Make calls to 20 schools and invite the for virtual visibility challenge\r\n2. Add all 30 schools up on a whatsapp broadcast and send another follow up message to them.\r\n3. Engage 2 prospects in conversation(features, negotiation) for website design proposition', '2026-01-09 07:53:20', 'i was able to Make calls to 11 schools and invite the for virtual visibility challenge\r\n2. Add 18 schools up on a whatsapp broadcast and send another follow up message to them.\r\n3. Engaged 2 prospects in conversation(features, negotiation) for website design proposition', '2026-01-09 13:06:07', '2026-01-09 14:02:47', 'clocked_out', '2026-01-09 07:33:49'),
(16, 5, '2026-01-12', '2026-01-12 07:01:23', 'To ensure staff and students\' ID cards are ready.\r\nOpen Opay account for livepetals.\r\ncheck mail for new job applicants and respond to the eligible for interview.\r\nfix the plan and report submission form in the reporting app', '2026-01-12 09:04:48', 'successfully updated the clocking and reporting app to sync status with database even if user use different devices.\n\nsuccessfully opened opay account for Livepetals.\n\nEntered financial outflow records so far.\n\nworked on Paam IOS account creation', '2026-01-12 16:06:10', '2026-01-12 16:06:39', 'clocked_out', '2026-01-12 07:01:23'),
(17, 6, '2026-01-12', '2026-01-12 07:01:41', 'Carrying out follow-ups and Next line of action on existing engagements with prospects from 2025.', '2026-01-12 08:18:54', 'I engaged 5 prospects today and I worked on the execution of the Leads generation project, which is all subjects competition & spelling bee for all schools that could participate.\r\nI also reached out to pending prospects from 2025 and we are still in contact.', '2026-01-12 16:04:36', '2026-01-12 16:06:25', 'clocked_out', '2026-01-12 07:01:41'),
(18, 4, '2026-01-12', '2026-01-12 07:02:08', '* Work on the feature to give access to other programs in PAAM.\r\n* Implement the project management operations phase feature in the time track pro app', '2026-01-12 08:35:39', 'Finished the paam course access implementation and continued the project management features implementation', '2026-01-12 16:09:40', '2026-01-12 16:10:31', 'clocked_out', '2026-01-12 07:02:08'),
(19, 7, '2026-01-12', '2026-01-12 08:28:14', 'To engage 10 propects at least for different products and services', '2026-01-12 15:56:00', 'i engaged 3 prospect in conversations about our training programs, they had showed interest initially and right now, they promised to get back to us as they sort the fund for it. would keep following up.', '2026-01-12 15:59:05', '2026-01-12 16:01:27', 'clocked_out', '2026-01-12 08:28:14'),
(20, 5, '2026-01-13', '2026-01-13 06:57:33', 'Update users list to Fetch based on business ID. \ncontinue research on account creation for paam ios app.\nbuild apk for reporting app and share with staffs. \nWork on staffs and students ID cards', '2026-01-13 07:58:41', 'Updated user list to filter by business Id.\nbuilt apk for the clocking and reporting app and shared to the staff. \nuploaded the project management app and paam app to GitHub. \nworked on conversion of stitch designs to flutter for project management UI screens.\nsent follow up email to applicants to confirm their availability for upcoming interviews.', '2026-01-13 16:22:35', '2026-01-13 16:22:51', 'clocked_out', '2026-01-13 06:57:33'),
(21, 6, '2026-01-13', '2026-01-13 07:01:27', 'Engaging 10 prospects and try to seal a deal.', '2026-01-13 07:02:43', 'The lead generation project is already in the plans.', '2026-01-13 15:55:46', '2026-01-13 15:56:06', 'clocked_out', '2026-01-13 07:01:27'),
(22, 7, '2026-01-13', '2026-01-13 07:07:49', 'planned to engage 10 prospects for our variety of products and services.', '2026-01-13 08:10:24', 'I was able to accomplish my goal for today, I engaged 10 prospects on different products and services, none is yet to be converted but 3 are on the fence.', '2026-01-13 15:50:19', '2026-01-13 15:53:04', 'clocked_out', '2026-01-13 07:07:49'),
(23, 4, '2026-01-13', '2026-01-13 07:10:08', '* Continue the implementation of the project management in the clocking\r\n* Research for the iOS app building', '2026-01-13 07:48:26', 'built the iOS version of the apps and finished phases feature', '2026-01-13 16:11:05', '2026-01-13 16:11:16', 'clocked_out', '2026-01-13 07:10:08'),
(24, 5, '2026-01-14', '2026-01-14 07:00:56', 'Attend Interview \nchange app icon for clocking appand change the app name.\nupload latest version of paam to GitHub', '2026-01-14 08:20:33', 'conducted interviews for applicants.\npushed the updated paam app to github and copied it on flash drive.\nchanged the hard disk of my Pc to SSD', '2026-01-14 15:39:13', '2026-01-14 15:39:27', 'clocked_out', '2026-01-14 07:00:56'),
(25, 4, '2026-01-14', '2026-01-14 07:02:59', 'Continue project management app', '2026-01-14 08:18:26', 'Finished up on implementation of the admin features for the project management.', '2026-01-14 15:37:15', '2026-01-14 15:37:30', 'clocked_out', '2026-01-14 07:02:59'),
(26, 7, '2026-01-14', '2026-01-14 07:56:47', 'Planned to operate my DMO for today. Engaging 10 people at least on our products and services', '2026-01-14 09:40:01', 'I engaged 12 people from my status view, across different products and services we offer.\nEach person engagement and product marketing was determined by what I see as need of each person.\nI was able to gain their interest and more importantly their awareness about what we do and offer', '2026-01-14 15:35:39', '2026-01-14 15:37:59', 'clocked_out', '2026-01-14 07:56:47'),
(27, 6, '2026-01-14', '2026-01-14 14:01:36', 'Drafting the whole plan and details concerning the leads generation project which is Spelling bee and all subject competition between schools.', '2026-01-14 14:16:26', 'The plan and the whole details has been drafted', '2026-01-14 15:42:32', '2026-01-14 15:42:58', 'clocked_out', '2026-01-14 14:01:36'),
(28, 5, '2026-01-15', '2026-01-15 06:59:26', 'conduct interview for software developers.\nDraft offer letters to the chosen applicants. send email to them to come for their offer letter \ndraft salary increment letters to the staffs \ninstall flutter on my pc', '2026-01-15 13:21:08', 'Conducted interview for software developers\nsent mail to chosen applicants \ndrafted offer letters to new employees \ndrafted salary increment letters for staffs', '2026-01-15 16:53:03', '2026-01-15 16:53:18', 'clocked_out', '2026-01-15 06:59:26'),
(29, 6, '2026-01-15', '2026-01-15 07:01:05', 'Engaging with 10 prospects and seal a deal.', '2026-01-15 15:37:27', 'No response from the prospects yet. 10 Business pages on Instagram and tiktok in total', '2026-01-15 15:57:02', '2026-01-15 16:06:19', 'clocked_out', '2026-01-15 07:01:05'),
(30, 4, '2026-01-15', '2026-01-15 07:07:43', 'Finish up on some design changes in the admin dashboard.\nBegin work on the user dashboard project management implementation.', '2026-01-15 07:38:11', NULL, NULL, NULL, 'plan_submitted', '2026-01-15 07:07:43'),
(31, 7, '2026-01-15', '2026-01-15 07:33:47', 'To engage at least 10 people across all our products and services', '2026-01-15 15:44:30', 'I held a training on my watsapp status on e commerce website building, and I engaged 10 people from my status view.\r\nI have 1 serious prospect already.\r\nwould follow up till she convert', '2026-01-15 15:46:46', '2026-01-15 15:56:01', 'clocked_out', '2026-01-15 07:33:47'),
(32, 5, '2026-01-16', '2026-01-16 06:55:51', 'update app icon and change app name.\ncontinue the conversion of stitch designs to flutter for the project management app.', '2026-01-16 08:41:29', 'Changed the app name and  added app icon, built app, created the marketing training schedule.', '2026-01-16 09:30:46', '2026-01-16 09:30:46', 'clocked_out', '2026-01-16 06:55:51'),
(33, 4, '2026-01-16', '2026-01-16 06:56:12', 'Work on the business e-commerce platform', '2026-01-16 08:52:32', 'Worked on designs for the e-commerce.', '2026-01-16 07:30:46', '2026-01-16 07:30:46', 'clocked_out', '2026-01-16 06:56:12'),
(34, 6, '2026-01-16', '2026-01-16 07:02:24', 'Engage with 10 prospects and try to seal a deal.', '2026-01-16 12:50:32', 'Out of the 10 prospects, 2 of them showed interest in WordPro and would love to get it for their kids. I\'m still engaging with them and trying to seal the deals.', '2026-01-16 12:51:58', '2026-01-16 09:30:50', 'clocked_out', '2026-01-16 07:02:24'),
(35, 5, '2026-01-19', '2026-01-19 06:42:01', 'facilitate training program for new employee(organisation\'s ethics and conduct).\r\ninstall flutter sdk on the changed pc drive.\r\nquickly fix update on clockit app.', '2026-01-19 10:37:29', NULL, NULL, NULL, 'plan_submitted', '2026-01-19 06:42:01'),
(36, 4, '2026-01-19', '2026-01-19 06:59:40', 'Onboard new developer with projects and discuss plans.', '2026-01-19 10:34:28', NULL, NULL, NULL, 'plan_submitted', '2026-01-19 06:59:40'),
(37, 7, '2026-01-19', '2026-01-19 10:29:00', NULL, NULL, NULL, NULL, NULL, 'clocked_in', '2026-01-19 10:29:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `business_id` int(11) NOT NULL,
  `barcode` varchar(100) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `category` enum('staff','student') NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `can_clock` tinyint(1) DEFAULT 1,
  `can_clock_others` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `business_id`, `barcode`, `firstname`, `lastname`, `email`, `password`, `category`, `is_active`, `can_clock`, `can_clock_others`, `created_at`) VALUES
(1, 1, 'BC001', 'John', 'Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 1, 1, 0, '2026-01-05 15:08:37'),
(2, 1, 'BC002', 'Jane', 'Smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1, 1, 0, '2026-01-05 15:08:37'),
(3, 2, '6762968911210', 'Godwin', 'Ogbaji', 'ogbajigodwin@gmail.com', '$2y$10$jVx/5pk/.GqicHFYCHaagebcqP9NbRTs14RPSENOmphNETWyP1ODO', 'staff', 1, 0, 1, '2026-01-06 09:49:06'),
(4, 2, '6156000261201', 'sola', 'itunu', 'sola@gmail.com', '$2y$10$exW85XF/ONDSQzej7nyXLuDnKf8zolz2iYamWiNqS9csy6jjLyNZK', 'staff', 1, 1, 0, '2026-01-06 11:12:13'),
(5, 2, '6165968911219', 'Adeyemi', 'Aminat', 'aminat@gmail.com', '$2y$10$pvtAWgHTAv8xv8gZ1agjruftmRNpJAN1dDKTRiKQfIet4A54eDxyu', 'staff', 1, 1, 1, '2026-01-06 14:53:05'),
(6, 2, '9780743287937', 'John', 'Adedeji', 'johnadedeji69@gmail.com', '$2y$10$yKwSyf/yPnLR5W4DHbBvvucB/ArrPmuGl6VoMsGjxngBpPFYZYdjm', 'staff', 1, 1, 0, '2026-01-07 14:42:04'),
(7, 2, '6156062261201', 'Grace', 'Paul-Adeniran', 'gracepauladeniran@gmail.com', '$2y$10$aEA8spbS832lQrZJvhPyz..4P94cOVYAYhumMFRTCFpNmlknZ01G2', 'staff', 1, 1, 0, '2026-01-07 14:45:02');

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
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_date` (`user_id`,`report_date`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `business` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
