-- =====================================================
-- ISU DORMITORY MANAGEMENT SYSTEM - HOSTINGER COMPATIBLE SCHEMA
-- =====================================================
-- This file is specifically designed for Hostinger's MySQL environment
-- Handles common Hostinger compatibility issues

-- =====================================================
-- 1. ADMINISTRATORS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` varchar(20) DEFAULT 'admin',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 2. STUDENTS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_id` varchar(20) NOT NULL,
  `learner_reference_number` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `date_of_birth` date NOT NULL,
  `gender` varchar(10) NOT NULL,
  `contact_number` varchar(15) NOT NULL,
  `email` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `emergency_contact_name` varchar(100) NOT NULL,
  `emergency_contact_number` varchar(15) NOT NULL,
  `course` varchar(100) NOT NULL,
  `year_level` varchar(20) NOT NULL,
  `room_id` int(11) DEFAULT NULL,
  `bed_space_id` int(11) DEFAULT NULL,
  `application_status` varchar(20) DEFAULT 'pending',
  `application_date` timestamp DEFAULT CURRENT_TIMESTAMP,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `school_id` (`school_id`),
  UNIQUE KEY `learner_reference_number` (`learner_reference_number`),
  KEY `idx_room_id` (`room_id`),
  KEY `idx_bed_space_id` (`bed_space_id`),
  KEY `idx_application_status` (`application_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 3. BUILDINGS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `buildings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `total_floors` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 4. ROOMS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `building_id` int(11) NOT NULL,
  `room_number` varchar(20) NOT NULL,
  `floor_number` int(11) NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 1,
  `occupied` int(11) NOT NULL DEFAULT 0,
  `room_type` varchar(20) DEFAULT 'double',
  `status` varchar(20) DEFAULT 'available',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_room` (`building_id`, `room_number`),
  KEY `idx_building_id` (`building_id`),
  KEY `idx_floor_number` (`floor_number`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 5. BED SPACES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `bed_spaces` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` int(11) NOT NULL,
  `bed_number` int(11) NOT NULL,
  `status` varchar(20) DEFAULT 'available',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_bed` (`room_id`, `bed_number`),
  KEY `idx_room_id` (`room_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 6. ANNOUNCEMENTS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `announcements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `status` varchar(20) DEFAULT 'draft',
  `priority` varchar(20) DEFAULT 'medium',
  `target_audience` varchar(20) DEFAULT 'all',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `expires_at` timestamp NULL DEFAULT NULL,
  `view_count` int(11) DEFAULT 0,
  `like_count` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 7. ANNOUNCEMENT LIKES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `announcement_likes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `announcement_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_like` (`announcement_id`, `student_id`),
  KEY `idx_announcement_id` (`announcement_id`),
  KEY `idx_student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 8. ANNOUNCEMENT COMMENTS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `announcement_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `announcement_id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_announcement_id` (`announcement_id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_admin_id` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 9. ANNOUNCEMENT INTERACTIONS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `announcement_interactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `announcement_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `interaction_type` varchar(20) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_interaction` (`announcement_id`, `student_id`, `interaction_type`),
  KEY `idx_announcement_id` (`announcement_id`),
  KEY `idx_student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 10. ANNOUNCEMENT VIEWS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `announcement_views` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `announcement_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_announcement_id` (`announcement_id`),
  KEY `idx_student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 11. COMPLAINTS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `complaints` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `priority` varchar(20) DEFAULT 'medium',
  `assigned_to` int(11) DEFAULT NULL,
  `admin_response` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `resolved_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_assigned_to` (`assigned_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 12. OFFENSES TABLE (FIXED - was offense_logs)
-- =====================================================
CREATE TABLE IF NOT EXISTS `offenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `offense_type` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `severity` varchar(20) NOT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `reported_by` varchar(100) NOT NULL,
  `complaint_id` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `resolved_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_offense_type` (`offense_type`),
  KEY `idx_severity` (`severity`),
  KEY `idx_status` (`status`),
  KEY `idx_complaint_id` (`complaint_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 13. MAINTENANCE REQUESTS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `maintenance_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `priority` varchar(20) DEFAULT 'medium',
  `status` varchar(20) DEFAULT 'pending',
  `assigned_to` int(11) DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_room_id` (`room_id`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_assigned_to` (`assigned_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 14. ROOM CHANGE REQUESTS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `room_change_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `current_room_id` int(11) NOT NULL,
  `requested_room_id` int(11) NOT NULL,
  `reason` text NOT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `admin_response` text DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `processed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_current_room_id` (`current_room_id`),
  KEY `idx_requested_room_id` (`requested_room_id`),
  KEY `idx_status` (`status`),
  KEY `idx_processed_by` (`processed_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 15. VISITOR LOGS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `visitor_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `visitor_name` varchar(100) NOT NULL,
  `visitor_age` int(11) DEFAULT NULL,
  `visitor_address` text DEFAULT NULL,
  `contact_number` varchar(15) DEFAULT NULL,
  `reason_of_visit` text NOT NULL,
  `time_in` timestamp DEFAULT CURRENT_TIMESTAMP,
  `time_out` timestamp NULL DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_time_in` (`time_in`),
  KEY `idx_time_out` (`time_out`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 16. STUDENT LOCATION LOGS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `student_location_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `location_status` varchar(20) NOT NULL,
  `timestamp` timestamp DEFAULT CURRENT_TIMESTAMP,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_timestamp` (`timestamp`),
  KEY `idx_location_status` (`location_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 17. BIOMETRIC FILES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `biometric_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `file_size` int(11) NOT NULL,
  `upload_date` timestamp DEFAULT CURRENT_TIMESTAMP,
  `status` varchar(20) DEFAULT 'pending',
  PRIMARY KEY (`id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_upload_date` (`upload_date`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 18. POLICIES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `policies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `offense_descriptions` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 19. FORM SUBMISSIONS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `form_submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `form_data` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 20. SYSTEM SETTINGS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `description` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- SAMPLE DATA INSERTION
-- =====================================================

-- Insert sample admin
INSERT INTO `admins` (`username`, `password`, `first_name`, `last_name`, `email`, `role`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', 'admin@isu.edu.ph', 'super_admin');

-- Insert sample building
INSERT INTO `buildings` (`name`, `description`, `total_floors`) VALUES
('Building A', 'Main dormitory building for male students', 3),
('Building B', 'Main dormitory building for female students', 3);

-- Insert sample rooms
INSERT INTO `rooms` (`building_id`, `room_number`, `floor_number`, `capacity`, `room_type`) VALUES
(1, '101', 1, 2, 'double'),
(1, '102', 1, 2, 'double'),
(1, '201', 2, 2, 'double'),
(1, '202', 2, 2, 'double'),
(2, '101', 1, 2, 'double'),
(2, '102', 1, 2, 'double'),
(2, '201', 2, 2, 'double'),
(2, '202', 2, 2, 'double');

-- Insert sample bed spaces
INSERT INTO `bed_spaces` (`room_id`, `bed_number`) VALUES
(1, 1), (1, 2),
(2, 1), (2, 2),
(3, 1), (3, 2),
(4, 1), (4, 2),
(5, 1), (5, 2),
(6, 1), (6, 2),
(7, 1), (7, 2),
(8, 1), (8, 2);

-- Insert sample announcement
INSERT INTO `announcements` (`title`, `content`, `status`, `priority`, `created_by`) VALUES
('Welcome to ISU Dormitory Management System', 'Welcome to the new dormitory management system. Please familiarize yourself with the features and policies.', 'published', 'high', 1);

-- Insert sample policy
INSERT INTO `policies` (`title`, `content`, `offense_descriptions`, `created_by`) VALUES
('General Dormitory Rules', 'All students must follow the general dormitory rules and regulations.', 'Curfew violations, noise violations, unauthorized visitors, smoking/vaping, alcohol/drugs, fighting, theft, disruptive behavior', 1);

-- Insert sample system settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`) VALUES
('curfew_time', '22:00', 'Dormitory curfew time'),
('visitor_hours_start', '08:00', 'Visitor hours start time'),
('visitor_hours_end', '20:00', 'Visitor hours end time'),
('max_visitors_per_student', '2', 'Maximum number of visitors per student'),
('announcement_expiry_days', '30', 'Number of days before announcements expire');

-- =====================================================
-- ADD FOREIGN KEY CONSTRAINTS (AFTER ALL TABLES EXIST)
-- =====================================================

-- Students table foreign keys
ALTER TABLE `students`
  ADD CONSTRAINT `fk_students_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_students_bed_space` FOREIGN KEY (`bed_space_id`) REFERENCES `bed_spaces` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_students_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Rooms table foreign keys
ALTER TABLE `rooms`
  ADD CONSTRAINT `fk_rooms_building` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- Bed spaces table foreign keys
ALTER TABLE `bed_spaces`
  ADD CONSTRAINT `fk_bed_spaces_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- Announcements table foreign keys
ALTER TABLE `announcements`
  ADD CONSTRAINT `fk_announcements_created_by` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- Announcement likes table foreign keys
ALTER TABLE `announcement_likes`
  ADD CONSTRAINT `fk_announcement_likes_announcement` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_announcement_likes_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- Announcement comments table foreign keys
ALTER TABLE `announcement_comments`
  ADD CONSTRAINT `fk_announcement_comments_announcement` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_announcement_comments_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_announcement_comments_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- Announcement interactions table foreign keys
ALTER TABLE `announcement_interactions`
  ADD CONSTRAINT `fk_announcement_interactions_announcement` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_announcement_interactions_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- Announcement views table foreign keys
ALTER TABLE `announcement_views`
  ADD CONSTRAINT `fk_announcement_views_announcement` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_announcement_views_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- Complaints table foreign keys
ALTER TABLE `complaints`
  ADD CONSTRAINT `fk_complaints_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_complaints_assigned_to` FOREIGN KEY (`assigned_to`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Offenses table foreign keys
ALTER TABLE `offenses`
  ADD CONSTRAINT `fk_offenses_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_offenses_complaint` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Maintenance requests table foreign keys
ALTER TABLE `maintenance_requests`
  ADD CONSTRAINT `fk_maintenance_requests_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_maintenance_requests_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_maintenance_requests_assigned_to` FOREIGN KEY (`assigned_to`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Room change requests table foreign keys
ALTER TABLE `room_change_requests`
  ADD CONSTRAINT `fk_room_change_requests_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_room_change_requests_current_room` FOREIGN KEY (`current_room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_room_change_requests_requested_room` FOREIGN KEY (`requested_room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_room_change_requests_processed_by` FOREIGN KEY (`processed_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Visitor logs table foreign keys
ALTER TABLE `visitor_logs`
  ADD CONSTRAINT `fk_visitor_logs_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- Student location logs table foreign keys
ALTER TABLE `student_location_logs`
  ADD CONSTRAINT `fk_student_location_logs_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- Biometric files table foreign keys
ALTER TABLE `biometric_files`
  ADD CONSTRAINT `fk_biometric_files_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- Policies table foreign keys
ALTER TABLE `policies`
  ADD CONSTRAINT `fk_policies_created_by` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- Form submissions table foreign keys
ALTER TABLE `form_submissions`
  ADD CONSTRAINT `fk_form_submissions_user` FOREIGN KEY (`user_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- System settings table foreign keys
ALTER TABLE `system_settings`
  ADD CONSTRAINT `fk_system_settings_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- =====================================================
-- COMPLETION MESSAGE
-- =====================================================
-- Hostinger-compatible database schema created successfully!
-- All 20 tables with proper relationships and constraints
-- Sample data inserted for testing
-- Ready for deployment to Hostinger
