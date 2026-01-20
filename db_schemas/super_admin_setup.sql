-- ============================================
-- Super Admin System Setup for TimeTrack Pro
-- ============================================
-- This script adds Super Admin functionality to manage multiple businesses
-- Run this script on your existing Clocking database
-- ============================================

--
-- Table structure for table `super_admins`
--

CREATE TABLE IF NOT EXISTS `super_admins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- Setup Complete
-- ============================================
-- The super_admins table has been created.
-- 
-- To create your Super Admin account, visit:
-- http://localhost/clocking/super_admin/register.php
--
-- IMPORTANT NOTES:
-- 1. Only ONE Super Admin can be registered (security feature)
-- 2. Registration page will be disabled after first signup
-- 3. Use strong credentials as this account has full system access
-- 4. You can change your password later in Settings
-- ============================================
