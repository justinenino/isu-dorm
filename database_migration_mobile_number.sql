-- =====================================================
-- DATABASE MIGRATION: Add Missing Columns to Students Table
-- =====================================================
-- This script adds the missing columns that are referenced in the code
-- but not present in the database schema

-- Add mobile_number column if it doesn't exist
ALTER TABLE `students` 
ADD COLUMN IF NOT EXISTS `mobile_number` varchar(15) NOT NULL DEFAULT '' AFTER `contact_number`;

-- Add province column if it doesn't exist
ALTER TABLE `students` 
ADD COLUMN IF NOT EXISTS `province` varchar(50) NOT NULL DEFAULT '' AFTER `address`;

-- Add municipality column if it doesn't exist
ALTER TABLE `students` 
ADD COLUMN IF NOT EXISTS `municipality` varchar(50) NOT NULL DEFAULT '' AFTER `province`;

-- Add barangay column if it doesn't exist
ALTER TABLE `students` 
ADD COLUMN IF NOT EXISTS `barangay` varchar(50) NOT NULL DEFAULT '' AFTER `municipality`;

-- Add street_purok column if it doesn't exist
ALTER TABLE `students` 
ADD COLUMN IF NOT EXISTS `street_purok` varchar(100) NOT NULL DEFAULT '' AFTER `barangay`;

-- Add facebook_link column if it doesn't exist
ALTER TABLE `students` 
ADD COLUMN IF NOT EXISTS `facebook_link` varchar(255) DEFAULT NULL AFTER `street_purok`;

-- Add attachment_file column if it doesn't exist
ALTER TABLE `students` 
ADD COLUMN IF NOT EXISTS `attachment_file` varchar(255) DEFAULT NULL AFTER `facebook_link`;

-- Add guardian_name column if it doesn't exist
ALTER TABLE `students` 
ADD COLUMN IF NOT EXISTS `guardian_name` varchar(100) NOT NULL DEFAULT '' AFTER `attachment_file`;

-- Add guardian_mobile column if it doesn't exist
ALTER TABLE `students` 
ADD COLUMN IF NOT EXISTS `guardian_mobile` varchar(15) NOT NULL DEFAULT '' AFTER `guardian_name`;

-- Add guardian_relationship column if it doesn't exist
ALTER TABLE `students` 
ADD COLUMN IF NOT EXISTS `guardian_relationship` varchar(50) NOT NULL DEFAULT '' AFTER `guardian_mobile`;

-- Update existing records to copy contact_number to mobile_number if mobile_number is empty
UPDATE `students` 
SET `mobile_number` = `contact_number` 
WHERE `mobile_number` = '' OR `mobile_number` IS NULL;

-- Update existing records with default values for required fields
UPDATE `students` 
SET 
    `province` = 'Isabela',
    `municipality` = 'Echague',
    `barangay` = 'Unknown',
    `street_purok` = 'Unknown',
    `guardian_name` = 'Emergency Contact',
    `guardian_mobile` = `contact_number`,
    `guardian_relationship` = 'Guardian'
WHERE 
    `province` = '' OR `province` IS NULL OR
    `municipality` = '' OR `municipality` IS NULL OR
    `barangay` = '' OR `barangay` IS NULL OR
    `street_purok` = '' OR `street_purok` IS NULL OR
    `guardian_name` = '' OR `guardian_name` IS NULL OR
    `guardian_mobile` = '' OR `guardian_mobile` IS NULL OR
    `guardian_relationship` = '' OR `guardian_relationship` IS NULL;

-- =====================================================
-- MIGRATION COMPLETE
-- =====================================================
-- The students table now has all the required columns
-- that are referenced in the application code
