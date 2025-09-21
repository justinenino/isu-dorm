-- Additional Database Optimization for Student Logs
-- This script adds optimized indexes for student logs filtering

-- 1. Add composite index for common student filtering queries
-- This covers: application_status + is_deleted + is_active
CREATE INDEX idx_students_status_active ON students (application_status, is_deleted, is_active);

-- 2. Add composite index for room-based student queries
-- This covers: room_id + application_status + is_deleted + is_active
CREATE INDEX idx_students_room_status ON students (room_id, application_status, is_deleted, is_active);

-- 3. Add composite index for student location logs queries
-- This covers: student_id + timestamp for efficient log retrieval
CREATE INDEX idx_location_logs_student_time ON student_location_logs (student_id, timestamp DESC);

-- 4. Add index for student location logs with status filtering
-- This helps with queries that filter by location_status
CREATE INDEX idx_location_logs_status ON student_location_logs (location_status, timestamp DESC);

-- 5. Add composite index for student name searches with status
-- This covers: first_name + last_name + application_status + is_deleted + is_active
CREATE INDEX idx_students_name_status ON students (first_name, last_name, application_status, is_deleted, is_active);

-- 6. Add index for school_id searches with status
-- This covers: school_id + application_status + is_deleted + is_active
CREATE INDEX idx_students_school_status ON students (school_id, application_status, is_deleted, is_active);

-- 7. Add index for timestamp-based queries
-- This helps with statistics queries that filter by recent timestamps
CREATE INDEX idx_location_logs_timestamp ON student_location_logs (timestamp DESC);

-- 8. Add composite index for student logs with room information
-- This helps with queries that join students with rooms and buildings
CREATE INDEX idx_students_room_building ON students (room_id, application_status, is_deleted, is_active);

-- Optimization complete! These indexes will significantly improve query performance for student logs.
