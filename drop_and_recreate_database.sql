-- =====================================================
-- DROP AND RECREATE DATABASE SCRIPT
-- =====================================================
-- WARNING: This will delete ALL existing data!
-- Use this only if you want to start fresh

-- Drop the existing database
DROP DATABASE IF EXISTS dormitory_management;

-- Create a new database
CREATE DATABASE dormitory_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Use the new database
USE dormitory_management;

-- =====================================================
-- COMPLETE DATABASE SCHEMA
-- =====================================================

-- 1. ADMINISTRATORS TABLE
CREATE TABLE `admins` (
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
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. STUDENTS TABLE (COMPLETE VERSION)
CREATE TABLE `students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_id` varchar(20) NOT NULL,
  `learner_reference_number` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `date_of_birth` date NOT NULL,
  `gender` varchar(10) NOT NULL,
  `contact_number` varchar(15) NOT NULL,
  `mobile_number` varchar(15) NOT NULL,
  `email` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `province` varchar(50) NOT NULL,
  `municipality` varchar(50) NOT NULL,
  `barangay` varchar(50) NOT NULL,
  `street_purok` varchar(100) NOT NULL,
  `facebook_link` varchar(255) DEFAULT NULL,
  `attachment_file` varchar(255) DEFAULT NULL,
  `guardian_name` varchar(100) NOT NULL,
  `guardian_mobile` varchar(15) NOT NULL,
  `guardian_relationship` varchar(50) NOT NULL,
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
  UNIQUE KEY `email` (`email`),
  KEY `idx_application_status` (`application_status`),
  KEY `idx_room_id` (`room_id`),
  KEY `idx_bed_space_id` (`bed_space_id`),
  KEY `idx_approved_by` (`approved_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. BUILDINGS TABLE
CREATE TABLE `buildings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `total_floors` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. ROOMS TABLE
CREATE TABLE `rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `building_id` int(11) NOT NULL,
  `room_number` varchar(20) NOT NULL,
  `floor_number` int(11) NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 1,
  `occupied` int(11) NOT NULL DEFAULT 0,
  `room_type` varchar(50) NOT NULL DEFAULT 'standard',
  `status` varchar(20) NOT NULL DEFAULT 'available',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `building_room` (`building_id`, `room_number`),
  KEY `idx_building_id` (`building_id`),
  KEY `idx_floor_number` (`floor_number`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. BED SPACES TABLE
CREATE TABLE `bed_spaces` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` int(11) NOT NULL,
  `bed_number` int(11) NOT NULL,
  `is_occupied` tinyint(1) NOT NULL DEFAULT 0,
  `student_id` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `room_bed` (`room_id`, `bed_number`),
  KEY `idx_room_id` (`room_id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_is_occupied` (`is_occupied`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. ANNOUNCEMENTS TABLE
CREATE TABLE `announcements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `priority` varchar(20) NOT NULL DEFAULT 'normal',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `expires_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. ANNOUNCEMENT LIKES TABLE
CREATE TABLE `announcement_likes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `announcement_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `announcement_student` (`announcement_id`, `student_id`),
  KEY `idx_announcement_id` (`announcement_id`),
  KEY `idx_student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. ANNOUNCEMENT COMMENTS TABLE
CREATE TABLE `announcement_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `announcement_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_announcement_id` (`announcement_id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_admin_id` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. ANNOUNCEMENT INTERACTIONS TABLE
CREATE TABLE `announcement_interactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `announcement_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `interaction_type` varchar(20) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_announcement_id` (`announcement_id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_interaction_type` (`interaction_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. ANNOUNCEMENT VIEWS TABLE
CREATE TABLE `announcement_views` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `announcement_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `viewed_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `announcement_student` (`announcement_id`, `student_id`),
  KEY `idx_announcement_id` (`announcement_id`),
  KEY `idx_student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. COMPLAINTS TABLE
CREATE TABLE `complaints` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. OFFENSES TABLE
CREATE TABLE `offenses` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. MAINTENANCE REQUESTS TABLE
CREATE TABLE `maintenance_requests` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. ROOM CHANGE REQUESTS TABLE
CREATE TABLE `room_change_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `current_room_id` int(11) NOT NULL,
  `requested_room_id` int(11) NOT NULL,
  `reason` text NOT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `processed_by` int(11) DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `processed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_current_room_id` (`current_room_id`),
  KEY `idx_requested_room_id` (`requested_room_id`),
  KEY `idx_status` (`status`),
  KEY `idx_processed_by` (`processed_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. VISITOR LOGS TABLE
CREATE TABLE `visitor_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `visitor_name` varchar(100) NOT NULL,
  `visitor_contact` varchar(15) NOT NULL,
  `relationship` varchar(50) NOT NULL,
  `purpose` text NOT NULL,
  `time_in` timestamp DEFAULT CURRENT_TIMESTAMP,
  `time_out` timestamp NULL DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_time_in` (`time_in`),
  KEY `idx_time_out` (`time_out`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. STUDENT LOCATION LOGS TABLE
CREATE TABLE `student_location_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `location` varchar(100) NOT NULL,
  `check_in_time` timestamp DEFAULT CURRENT_TIMESTAMP,
  `check_out_time` timestamp NULL DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_check_in_time` (`check_in_time`),
  KEY `idx_check_out_time` (`check_out_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. BIOMETRIC FILES TABLE
CREATE TABLE `biometric_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `uploaded_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_uploaded_at` (`uploaded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. POLICIES TABLE
CREATE TABLE `policies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `offense_descriptions` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. FORM SUBMISSIONS TABLE
CREATE TABLE `form_submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `form_type` varchar(50) NOT NULL,
  `form_data` text NOT NULL,
  `submitted_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_form_type` (`form_type`),
  KEY `idx_submitted_at` (`submitted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. SYSTEM SETTINGS TABLE
CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `description` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `idx_updated_by` (`updated_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- FOREIGN KEY CONSTRAINTS
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
-- SAMPLE DATA INSERTION
-- =====================================================

-- Insert sample admin
INSERT INTO `admins` (`username`, `password`, `first_name`, `last_name`, `email`, `role`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', 'admin@isu.edu.ph', 'super_admin');

-- Sample students data
INSERT INTO `students` (`school_id`, `learner_reference_number`, `first_name`, `middle_name`, `last_name`, `date_of_birth`, `gender`, `contact_number`, `mobile_number`, `email`, `address`, `province`, `municipality`, `barangay`, `street_purok`, `facebook_link`, `attachment_file`, `guardian_name`, `guardian_mobile`, `guardian_relationship`, `emergency_contact_name`, `emergency_contact_number`, `course`, `year_level`, `application_status`) VALUES
('2024-001', 'LRN001', 'John', 'Doe', 'Smith', '2000-01-15', 'Male', '09123456789', '09123456789', 'john.smith@student.isu.edu.ph', '123 Main Street', 'Isabela', 'Echague', 'Barangay 1', 'Purok 1', 'https://facebook.com/johnsmith', 'attachment1.pdf', 'Jane Smith', '09123456788', 'Mother', 'Jane Smith', '09123456788', 'Computer Science', '3rd Year', 'approved'),
('2024-002', 'LRN002', 'Maria', 'Santos', 'Garcia', '1999-05-20', 'Female', '09123456790', '09123456790', 'maria.garcia@student.isu.edu.ph', '456 Oak Avenue', 'Isabela', 'Echague', 'Barangay 2', 'Purok 2', 'https://facebook.com/mariagarcia', 'attachment2.pdf', 'Pedro Garcia', '09123456791', 'Father', 'Pedro Garcia', '09123456791', 'Information Technology', '4th Year', 'pending');

-- Insert sample building
INSERT INTO `buildings` (`name`, `description`, `total_floors`) VALUES
('Building A', 'Main dormitory building for male students', 3),
('Building B', 'Main dormitory building for female students', 3);

-- Insert sample rooms
INSERT INTO `rooms` (`building_id`, `room_number`, `floor_number`, `capacity`, `room_type`) VALUES
(1, '101', 1, 2, 'double'),
(1, '102', 1, 2, 'double'),
(1, '201', 2, 2, 'double'),
(2, '101', 1, 2, 'double'),
(2, '102', 1, 2, 'double');

-- Insert sample bed spaces
INSERT INTO `bed_spaces` (`room_id`, `bed_number`) VALUES
(1, 1), (1, 2),
(2, 1), (2, 2),
(3, 1), (3, 2),
(4, 1), (4, 2),
(5, 1), (5, 2);

-- Insert sample announcements
INSERT INTO `announcements` (`title`, `content`, `status`, `priority`, `created_by`) VALUES
('Welcome to ISU Dormitory Management System', 'Welcome to the new dormitory management system. Please familiarize yourself with the features and policies.', 'published', 'high', 1);

-- Insert sample policies
INSERT INTO `policies` (`title`, `content`, `offense_descriptions`, `created_by`) VALUES
('General Dormitory Rules', 'All students must follow the general dormitory rules and regulations.', 'Curfew violations, noise violations, unauthorized visitors, smoking/vaping, alcohol/drugs, fighting, theft, disruptive behavior', 1);

-- Insert sample system settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`) VALUES
('curfew_time', '22:00', 'Dormitory curfew time'),
('visitor_hours_start', '08:00', 'Visitor hours start time'),
('visitor_hours_end', '20:00', 'Visitor hours end time'),
('max_visitors_per_student', '2', 'Maximum number of visitors per student'),
('maintenance_priority_high', '24', 'High priority maintenance response time in hours'),
('maintenance_priority_medium', '72', 'Medium priority maintenance response time in hours'),
('maintenance_priority_low', '168', 'Low priority maintenance response time in hours');

-- =====================================================
-- COMPLETION MESSAGE
-- =====================================================
-- Database recreated successfully! All tables created with proper relationships and constraints.
-- Sample data inserted for testing.
-- Ready for deployment to Hostinger.
