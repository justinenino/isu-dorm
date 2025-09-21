-- =============================================
-- Complete Database Schema for Dormitory Management System
-- All tables required by the system
-- =============================================

-- Use the database
USE `u260113372_dormitory_db`;

-- =============================================
-- Drop existing tables if they exist (in correct order)
-- =============================================

DROP TABLE IF EXISTS `announcement_comments`;
DROP TABLE IF EXISTS `announcement_interactions`;
DROP TABLE IF EXISTS `announcement_likes`;
DROP TABLE IF EXISTS `visitor_logs`;
DROP TABLE IF EXISTS `room_change_requests`;
DROP TABLE IF EXISTS `room_requests`;
DROP TABLE IF EXISTS `reservations`;
DROP TABLE IF EXISTS `maintenance_requests`;
DROP TABLE IF EXISTS `complaints`;
DROP TABLE IF EXISTS `offenses`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `bed_spaces`;
DROP TABLE IF EXISTS `rooms`;
DROP TABLE IF EXISTS `buildings`;
DROP TABLE IF EXISTS `announcements`;
DROP TABLE IF EXISTS `policies`;
DROP TABLE IF EXISTS `admins`;

-- =============================================
-- Create Tables in Correct Order
-- =============================================

-- Table structure for table `admins`
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `email` varchar(255) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `role` enum('super_admin','admin','moderator') NOT NULL DEFAULT 'admin',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `username` (`username`),
  KEY `email` (`email`),
  KEY `role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `buildings`
CREATE TABLE `buildings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `rooms`
CREATE TABLE `rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `building_id` int(11) NOT NULL,
  `room_number` varchar(20) NOT NULL,
  `room_type` varchar(50) NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 1,
  `occupied` int(11) NOT NULL DEFAULT 0,
  `status` enum('available','full','maintenance','reserved') NOT NULL DEFAULT 'available',
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `building_id` (`building_id`),
  KEY `room_type` (`room_type`),
  KEY `status` (`status`),
  CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `bed_spaces`
CREATE TABLE `bed_spaces` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` int(11) NOT NULL,
  `bed_space_number` varchar(10) NOT NULL,
  `is_occupied` tinyint(1) NOT NULL DEFAULT 0,
  `student_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `room_id` (`room_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `bed_spaces_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `students`
CREATE TABLE `students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_id` varchar(20) NOT NULL,
  `learner_reference_number` varchar(20) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `date_of_birth` date NOT NULL,
  `gender` enum('male','female','other') NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `room_id` int(11) DEFAULT NULL,
  `bed_space_id` int(11) DEFAULT NULL,
  `application_status` enum('pending','approved','rejected','active','inactive') NOT NULL DEFAULT 'pending',
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `approved_at` datetime DEFAULT NULL,
  `document_path` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `school_id` (`school_id`),
  UNIQUE KEY `learner_reference_number` (`learner_reference_number`),
  UNIQUE KEY `email` (`email`),
  KEY `room_id` (`room_id`),
  KEY `bed_space_id` (`bed_space_id`),
  KEY `application_status` (`application_status`),
  CONSTRAINT `students_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL,
  CONSTRAINT `students_ibfk_2` FOREIGN KEY (`bed_space_id`) REFERENCES `bed_spaces` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `announcements`
CREATE TABLE `announcements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `category` varchar(100) DEFAULT 'General',
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `published_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `view_count` int(11) NOT NULL DEFAULT 0,
  `like_count` int(11) NOT NULL DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `status` (`status`),
  KEY `expires_at` (`expires_at`),
  CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `policies`
CREATE TABLE `policies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `category` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `category` (`category`),
  KEY `is_active` (`is_active`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `policies_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `complaints`
CREATE TABLE `complaints` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `status` enum('pending','in_progress','resolved','closed') NOT NULL DEFAULT 'pending',
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `admin_response` text DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `status` (`status`),
  KEY `priority` (`priority`),
  KEY `resolved_by` (`resolved_by`),
  CONSTRAINT `complaints_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `complaints_ibfk_2` FOREIGN KEY (`resolved_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `maintenance_requests`
CREATE TABLE `maintenance_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `issue_type` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `status` enum('pending','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `admin_notes` text DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `room_id` (`room_id`),
  KEY `status` (`status`),
  KEY `priority` (`priority`),
  KEY `assigned_to` (`assigned_to`),
  CONSTRAINT `maintenance_requests_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `maintenance_requests_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `maintenance_requests_ibfk_3` FOREIGN KEY (`assigned_to`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `offenses`
CREATE TABLE `offenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `offense_type` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `severity` enum('minor','major','severe') NOT NULL DEFAULT 'minor',
  `status` enum('pending','acknowledged','resolved') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `admin_notes` text DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `reported_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `severity` (`severity`),
  KEY `status` (`status`),
  KEY `reported_by` (`reported_by`),
  CONSTRAINT `offenses_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `offenses_ibfk_2` FOREIGN KEY (`reported_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `reservations`
CREATE TABLE `reservations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `admin_notes` text DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `room_id` (`room_id`),
  KEY `status` (`status`),
  KEY `start_date` (`start_date`),
  KEY `end_date` (`end_date`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reservations_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `room_change_requests`
CREATE TABLE `room_change_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `current_room_id` int(11) DEFAULT NULL,
  `requested_room_id` int(11) NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `admin_notes` text DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `current_room_id` (`current_room_id`),
  KEY `requested_room_id` (`requested_room_id`),
  KEY `status` (`status`),
  KEY `processed_by` (`processed_by`),
  CONSTRAINT `room_change_requests_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `room_change_requests_ibfk_2` FOREIGN KEY (`current_room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL,
  CONSTRAINT `room_change_requests_ibfk_3` FOREIGN KEY (`requested_room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `room_change_requests_ibfk_4` FOREIGN KEY (`processed_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `visitor_logs`
CREATE TABLE `visitor_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `visitor_name` varchar(100) NOT NULL,
  `visitor_age` int(11) DEFAULT NULL,
  `visitor_address` text DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `reason_of_visit` varchar(255) NOT NULL,
  `time_in` datetime NOT NULL,
  `time_out` datetime DEFAULT NULL,
  `status` enum('checked_in','checked_out') NOT NULL DEFAULT 'checked_in',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `status` (`status`),
  KEY `time_in` (`time_in`),
  CONSTRAINT `visitor_logs_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `announcement_likes`
CREATE TABLE `announcement_likes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `announcement_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `announcement_student` (`announcement_id`, `student_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `announcement_likes_ibfk_1` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `announcement_likes_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `announcement_interactions`
CREATE TABLE `announcement_interactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `announcement_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `interaction_type` enum('view','acknowledge','like') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `announcement_id` (`announcement_id`),
  KEY `student_id` (`student_id`),
  KEY `interaction_type` (`interaction_type`),
  CONSTRAINT `announcement_interactions_ibfk_1` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `announcement_interactions_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `announcement_comments`
CREATE TABLE `announcement_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `announcement_id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `announcement_id` (`announcement_id`),
  KEY `student_id` (`student_id`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `announcement_comments_ibfk_1` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `announcement_comments_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `announcement_comments_ibfk_3` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================
-- Create Views
-- =============================================

-- Create active_announcements view
CREATE VIEW `active_announcements` AS 
SELECT 
    `announcements`.`id` AS `id`,
    `announcements`.`title` AS `title`,
    `announcements`.`content` AS `content`,
    `announcements`.`category` AS `category`,
    `announcements`.`priority` AS `priority`,
    `announcements`.`status` AS `status`,
    `announcements`.`published_at` AS `published_at`,
    `announcements`.`expires_at` AS `expires_at`,
    `announcements`.`is_pinned` AS `is_pinned`,
    `announcements`.`is_archived` AS `is_archived`,
    `announcements`.`view_count` AS `view_count`,
    `announcements`.`like_count` AS `like_count`,
    `announcements`.`created_by` AS `created_by`,
    `announcements`.`created_at` AS `created_at`,
    `announcements`.`updated_at` AS `updated_at`
FROM `announcements` 
WHERE `announcements`.`is_archived` = 0 
    AND (`announcements`.`expires_at` IS NULL OR `announcements`.`expires_at` > CURRENT_TIMESTAMP()) 
    AND `announcements`.`status` = 'published';

-- =============================================
-- Insert Sample Data
-- =============================================

-- Insert admin user
INSERT INTO `admins` (`username`, `email`, `password`, `first_name`, `last_name`, `role`, `is_active`) VALUES
('admin', 'admin@dormitory.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', 'super_admin', 1);

-- Insert sample buildings
INSERT INTO `buildings` (`name`, `description`, `address`) VALUES
('Building A', 'Main dormitory building with single and double rooms', '123 University Ave'),
('Building B', 'Secondary building with shared facilities', '124 University Ave'),
('Building C', 'New building with modern amenities', '125 University Ave');

-- Insert sample rooms
INSERT INTO `rooms` (`building_id`, `room_number`, `room_type`, `capacity`, `occupied`, `status`, `is_available`) VALUES
(1, 'A101', 'Single', 1, 0, 'available', 1),
(1, 'A102', 'Single', 1, 0, 'available', 1),
(1, 'A103', 'Double', 2, 0, 'available', 1),
(1, 'A104', 'Double', 2, 0, 'available', 1),
(2, 'B201', 'Single', 1, 0, 'available', 1),
(2, 'B202', 'Single', 1, 0, 'available', 1),
(2, 'B203', 'Double', 2, 0, 'available', 1),
(3, 'C301', 'Single', 1, 0, 'available', 1),
(3, 'C302', 'Single', 1, 0, 'available', 1),
(3, 'C303', 'Double', 2, 0, 'available', 1);

-- Insert sample bed spaces
INSERT INTO `bed_spaces` (`room_id`, `bed_space_number`, `is_occupied`, `student_id`) VALUES
(1, 'A', 0, NULL),
(2, 'A', 0, NULL),
(3, 'A', 0, NULL),
(3, 'B', 0, NULL),
(4, 'A', 0, NULL),
(4, 'B', 0, NULL),
(5, 'A', 0, NULL),
(6, 'A', 0, NULL),
(7, 'A', 0, NULL),
(7, 'B', 0, NULL),
(8, 'A', 0, NULL),
(9, 'A', 0, NULL),
(10, 'A', 0, NULL),
(10, 'B', 0, NULL);

-- Insert sample announcements
INSERT INTO `announcements` (`title`, `content`, `category`, `priority`, `status`, `published_at`, `expires_at`, `is_pinned`, `created_by`) VALUES
('Welcome to Dormitory Management System', 'Welcome to our new dormitory management system. Please familiarize yourself with the policies and procedures.', 'General', 'high', 'published', NOW(), '2024-12-31 23:59:59', 1, 1),
('Maintenance Schedule', 'Scheduled maintenance will be conducted on Sunday from 2 PM to 4 PM. Please plan accordingly.', 'Maintenance', 'medium', 'published', NOW(), '2024-12-31 23:59:59', 0, 1),
('Visitor Policy Update', 'New visitor policies are now in effect. Please review the updated guidelines in the policies section.', 'Policies', 'medium', 'published', NOW(), '2024-12-31 23:59:59', 0, 1);

-- Insert sample policies
INSERT INTO `policies` (`title`, `content`, `category`, `is_active`, `created_by`) VALUES
('General Rules', 'All students must follow the general dormitory rules and regulations. Violations may result in disciplinary action.', 'General', 1, 1),
('Quiet Hours', 'Quiet hours are from 10 PM to 7 AM. Please keep noise levels to a minimum during these hours.', 'General', 1, 1),
('Visitor Policy', 'Visitors must be registered at the front desk and are only allowed during designated hours.', 'Visitors', 1, 1),
('Room Maintenance', 'Students are responsible for keeping their rooms clean and reporting any maintenance issues promptly.', 'Maintenance', 1, 1);

-- =============================================
-- Database Setup Complete
-- =============================================
