-- =============================================
-- Safe Database Setup for Hostinger
-- This will only create tables that don't exist
-- =============================================

-- Use the database
USE `u260113372_dormitory_db`;

-- =============================================
-- Create Tables Only If They Don't Exist
-- =============================================

-- Table structure for table `admins`
CREATE TABLE IF NOT EXISTS `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `role` enum('super_admin','admin','moderator') NOT NULL DEFAULT 'admin',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add unique constraints if they don't exist
ALTER TABLE `admins` ADD UNIQUE KEY `username` (`username`);
ALTER TABLE `admins` ADD UNIQUE KEY `email` (`email`);
ALTER TABLE `admins` ADD KEY `role` (`role`);

-- Table structure for table `buildings`
CREATE TABLE IF NOT EXISTS `buildings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `rooms`
CREATE TABLE IF NOT EXISTS `rooms` (
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
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add foreign key constraint if it doesn't exist
ALTER TABLE `rooms` ADD CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE CASCADE;

-- Table structure for table `bed_spaces`
CREATE TABLE IF NOT EXISTS `bed_spaces` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` int(11) NOT NULL,
  `bed_space_number` varchar(10) NOT NULL,
  `is_occupied` tinyint(1) NOT NULL DEFAULT 0,
  `student_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `room_id` (`room_id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add foreign key constraint if it doesn't exist
ALTER TABLE `bed_spaces` ADD CONSTRAINT `bed_spaces_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;

-- Table structure for table `students`
CREATE TABLE IF NOT EXISTS `students` (
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add unique constraints and foreign keys if they don't exist
ALTER TABLE `students` ADD UNIQUE KEY `school_id` (`school_id`);
ALTER TABLE `students` ADD UNIQUE KEY `learner_reference_number` (`learner_reference_number`);
ALTER TABLE `students` ADD UNIQUE KEY `email` (`email`);
ALTER TABLE `students` ADD KEY `room_id` (`room_id`);
ALTER TABLE `students` ADD KEY `bed_space_id` (`bed_space_id`);
ALTER TABLE `students` ADD KEY `application_status` (`application_status`);
ALTER TABLE `students` ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL;
ALTER TABLE `students` ADD CONSTRAINT `students_ibfk_2` FOREIGN KEY (`bed_space_id`) REFERENCES `bed_spaces` (`id`) ON DELETE SET NULL;

-- Table structure for table `announcements`
CREATE TABLE IF NOT EXISTS `announcements` (
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add indexes and foreign key if they don't exist
ALTER TABLE `announcements` ADD KEY `created_by` (`created_by`);
ALTER TABLE `announcements` ADD KEY `status` (`status`);
ALTER TABLE `announcements` ADD KEY `expires_at` (`expires_at`);
ALTER TABLE `announcements` ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

-- Table structure for table `policies`
CREATE TABLE IF NOT EXISTS `policies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `category` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add indexes and foreign key if they don't exist
ALTER TABLE `policies` ADD KEY `category` (`category`);
ALTER TABLE `policies` ADD KEY `is_active` (`is_active`);
ALTER TABLE `policies` ADD KEY `created_by` (`created_by`);
ALTER TABLE `policies` ADD CONSTRAINT `policies_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

-- Table structure for table `complaints`
CREATE TABLE IF NOT EXISTS `complaints` (
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add indexes and foreign keys if they don't exist
ALTER TABLE `complaints` ADD KEY `student_id` (`student_id`);
ALTER TABLE `complaints` ADD KEY `status` (`status`);
ALTER TABLE `complaints` ADD KEY `priority` (`priority`);
ALTER TABLE `complaints` ADD KEY `resolved_by` (`resolved_by`);
ALTER TABLE `complaints` ADD CONSTRAINT `complaints_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;
ALTER TABLE `complaints` ADD CONSTRAINT `complaints_ibfk_2` FOREIGN KEY (`resolved_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

-- Table structure for table `maintenance_requests`
CREATE TABLE IF NOT EXISTS `maintenance_requests` (
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add indexes and foreign keys if they don't exist
ALTER TABLE `maintenance_requests` ADD KEY `student_id` (`student_id`);
ALTER TABLE `maintenance_requests` ADD KEY `room_id` (`room_id`);
ALTER TABLE `maintenance_requests` ADD KEY `status` (`status`);
ALTER TABLE `maintenance_requests` ADD KEY `priority` (`priority`);
ALTER TABLE `maintenance_requests` ADD KEY `assigned_to` (`assigned_to`);
ALTER TABLE `maintenance_requests` ADD CONSTRAINT `maintenance_requests_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;
ALTER TABLE `maintenance_requests` ADD CONSTRAINT `maintenance_requests_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;
ALTER TABLE `maintenance_requests` ADD CONSTRAINT `maintenance_requests_ibfk_3` FOREIGN KEY (`assigned_to`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

-- Table structure for table `offenses`
CREATE TABLE IF NOT EXISTS `offenses` (
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add indexes and foreign keys if they don't exist
ALTER TABLE `offenses` ADD KEY `student_id` (`student_id`);
ALTER TABLE `offenses` ADD KEY `severity` (`severity`);
ALTER TABLE `offenses` ADD KEY `status` (`status`);
ALTER TABLE `offenses` ADD KEY `reported_by` (`reported_by`);
ALTER TABLE `offenses` ADD CONSTRAINT `offenses_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;
ALTER TABLE `offenses` ADD CONSTRAINT `offenses_ibfk_2` FOREIGN KEY (`reported_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

-- Table structure for table `reservations`
CREATE TABLE IF NOT EXISTS `reservations` (
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add indexes and foreign keys if they don't exist
ALTER TABLE `reservations` ADD KEY `student_id` (`student_id`);
ALTER TABLE `reservations` ADD KEY `room_id` (`room_id`);
ALTER TABLE `reservations` ADD KEY `status` (`status`);
ALTER TABLE `reservations` ADD KEY `start_date` (`start_date`);
ALTER TABLE `reservations` ADD KEY `end_date` (`end_date`);
ALTER TABLE `reservations` ADD KEY `approved_by` (`approved_by`);
ALTER TABLE `reservations` ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;
ALTER TABLE `reservations` ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;
ALTER TABLE `reservations` ADD CONSTRAINT `reservations_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

-- Table structure for table `room_change_requests`
CREATE TABLE IF NOT EXISTS `room_change_requests` (
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add indexes and foreign keys if they don't exist
ALTER TABLE `room_change_requests` ADD KEY `student_id` (`student_id`);
ALTER TABLE `room_change_requests` ADD KEY `current_room_id` (`current_room_id`);
ALTER TABLE `room_change_requests` ADD KEY `requested_room_id` (`requested_room_id`);
ALTER TABLE `room_change_requests` ADD KEY `status` (`status`);
ALTER TABLE `room_change_requests` ADD KEY `processed_by` (`processed_by`);
ALTER TABLE `room_change_requests` ADD CONSTRAINT `room_change_requests_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;
ALTER TABLE `room_change_requests` ADD CONSTRAINT `room_change_requests_ibfk_2` FOREIGN KEY (`current_room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL;
ALTER TABLE `room_change_requests` ADD CONSTRAINT `room_change_requests_ibfk_3` FOREIGN KEY (`requested_room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;
ALTER TABLE `room_change_requests` ADD CONSTRAINT `room_change_requests_ibfk_4` FOREIGN KEY (`processed_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

-- Table structure for table `visitor_logs`
CREATE TABLE IF NOT EXISTS `visitor_logs` (
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add indexes and foreign key if they don't exist
ALTER TABLE `visitor_logs` ADD KEY `student_id` (`student_id`);
ALTER TABLE `visitor_logs` ADD KEY `status` (`status`);
ALTER TABLE `visitor_logs` ADD KEY `time_in` (`time_in`);
ALTER TABLE `visitor_logs` ADD CONSTRAINT `visitor_logs_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

-- Table structure for table `announcement_likes`
CREATE TABLE IF NOT EXISTS `announcement_likes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `announcement_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add unique constraint and foreign keys if they don't exist
ALTER TABLE `announcement_likes` ADD UNIQUE KEY `announcement_student` (`announcement_id`, `student_id`);
ALTER TABLE `announcement_likes` ADD KEY `student_id` (`student_id`);
ALTER TABLE `announcement_likes` ADD CONSTRAINT `announcement_likes_ibfk_1` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE;
ALTER TABLE `announcement_likes` ADD CONSTRAINT `announcement_likes_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

-- Table structure for table `announcement_interactions`
CREATE TABLE IF NOT EXISTS `announcement_interactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `announcement_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `interaction_type` enum('view','acknowledge','like') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add indexes and foreign keys if they don't exist
ALTER TABLE `announcement_interactions` ADD KEY `announcement_id` (`announcement_id`);
ALTER TABLE `announcement_interactions` ADD KEY `student_id` (`student_id`);
ALTER TABLE `announcement_interactions` ADD KEY `interaction_type` (`interaction_type`);
ALTER TABLE `announcement_interactions` ADD CONSTRAINT `announcement_interactions_ibfk_1` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE;
ALTER TABLE `announcement_interactions` ADD CONSTRAINT `announcement_interactions_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

-- Table structure for table `announcement_comments`
CREATE TABLE IF NOT EXISTS `announcement_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `announcement_id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add indexes and foreign keys if they don't exist
ALTER TABLE `announcement_comments` ADD KEY `announcement_id` (`announcement_id`);
ALTER TABLE `announcement_comments` ADD KEY `student_id` (`student_id`);
ALTER TABLE `announcement_comments` ADD KEY `admin_id` (`admin_id`);
ALTER TABLE `announcement_comments` ADD CONSTRAINT `announcement_comments_ibfk_1` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE;
ALTER TABLE `announcement_comments` ADD CONSTRAINT `announcement_comments_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;
ALTER TABLE `announcement_comments` ADD CONSTRAINT `announcement_comments_ibfk_3` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE;

-- =============================================
-- Create Views
-- =============================================

-- Drop view if it exists and recreate
DROP VIEW IF EXISTS `active_announcements`;

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
-- Insert Sample Data (Only if tables are empty)
-- =============================================

-- Insert admin user if not exists
INSERT IGNORE INTO `admins` (`username`, `email`, `password`, `first_name`, `last_name`, `role`, `is_active`) VALUES
('admin', 'admin@dormitory.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', 'super_admin', 1);

-- Insert sample buildings if not exist
INSERT IGNORE INTO `buildings` (`id`, `name`, `description`, `address`) VALUES
(1, 'Building A', 'Main dormitory building with single and double rooms', '123 University Ave'),
(2, 'Building B', 'Secondary building with shared facilities', '124 University Ave'),
(3, 'Building C', 'New building with modern amenities', '125 University Ave');

-- Insert sample rooms if not exist
INSERT IGNORE INTO `rooms` (`id`, `building_id`, `room_number`, `room_type`, `capacity`, `occupied`, `status`, `is_available`) VALUES
(1, 1, 'A101', 'Single', 1, 0, 'available', 1),
(2, 1, 'A102', 'Single', 1, 0, 'available', 1),
(3, 1, 'A103', 'Double', 2, 0, 'available', 1),
(4, 1, 'A104', 'Double', 2, 0, 'available', 1),
(5, 2, 'B201', 'Single', 1, 0, 'available', 1),
(6, 2, 'B202', 'Single', 1, 0, 'available', 1),
(7, 2, 'B203', 'Double', 2, 0, 'available', 1),
(8, 3, 'C301', 'Single', 1, 0, 'available', 1),
(9, 3, 'C302', 'Single', 1, 0, 'available', 1),
(10, 3, 'C303', 'Double', 2, 0, 'available', 1);

-- Insert sample bed spaces if not exist
INSERT IGNORE INTO `bed_spaces` (`id`, `room_id`, `bed_space_number`, `is_occupied`, `student_id`) VALUES
(1, 1, 'A', 0, NULL),
(2, 2, 'A', 0, NULL),
(3, 3, 'A', 0, NULL),
(4, 3, 'B', 0, NULL),
(5, 4, 'A', 0, NULL),
(6, 4, 'B', 0, NULL),
(7, 5, 'A', 0, NULL),
(8, 6, 'A', 0, NULL),
(9, 7, 'A', 0, NULL),
(10, 7, 'B', 0, NULL),
(11, 8, 'A', 0, NULL),
(12, 9, 'A', 0, NULL),
(13, 10, 'A', 0, NULL),
(14, 10, 'B', 0, NULL);

-- Insert sample announcements if not exist
INSERT IGNORE INTO `announcements` (`id`, `title`, `content`, `category`, `priority`, `status`, `published_at`, `expires_at`, `is_pinned`, `created_by`) VALUES
(1, 'Welcome to Dormitory Management System', 'Welcome to our new dormitory management system. Please familiarize yourself with the policies and procedures.', 'General', 'high', 'published', NOW(), '2024-12-31 23:59:59', 1, 1),
(2, 'Maintenance Schedule', 'Scheduled maintenance will be conducted on Sunday from 2 PM to 4 PM. Please plan accordingly.', 'Maintenance', 'medium', 'published', NOW(), '2024-12-31 23:59:59', 0, 1),
(3, 'Visitor Policy Update', 'New visitor policies are now in effect. Please review the updated guidelines in the policies section.', 'Policies', 'medium', 'published', NOW(), '2024-12-31 23:59:59', 0, 1);

-- Insert sample policies if not exist
INSERT IGNORE INTO `policies` (`id`, `title`, `content`, `category`, `is_active`, `created_by`) VALUES
(1, 'General Rules', 'All students must follow the general dormitory rules and regulations. Violations may result in disciplinary action.', 'General', 1, 1),
(2, 'Quiet Hours', 'Quiet hours are from 10 PM to 7 AM. Please keep noise levels to a minimum during these hours.', 'General', 1, 1),
(3, 'Visitor Policy', 'Visitors must be registered at the front desk and are only allowed during designated hours.', 'Visitors', 1, 1),
(4, 'Room Maintenance', 'Students are responsible for keeping their rooms clean and reporting any maintenance issues promptly.', 'Maintenance', 1, 1);

-- =============================================
-- Safe Database Setup Complete
-- =============================================
