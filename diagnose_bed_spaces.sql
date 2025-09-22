-- Diagnostic queries to check bed space functionality

-- 1. Check the structure of room_change_requests table
DESCRIBE room_change_requests;

-- 2. Check recent room change requests and their bed space data
SELECT 
    rcr.id,
    rcr.student_id,
    s.first_name,
    s.last_name,
    rcr.current_room_id,
    rcr.requested_room_id,
    rcr.requested_bed_space_id,
    rcr.current_bed_space_id,
    rcr.status,
    rcr.requested_at
FROM room_change_requests rcr
LEFT JOIN students s ON rcr.student_id = s.id
ORDER BY rcr.requested_at DESC
LIMIT 10;

-- 3. Check if students have bed_space_id assigned
SELECT 
    s.id,
    s.first_name,
    s.last_name,
    s.room_id,
    s.bed_space_id,
    bs.bed_number,
    r.room_number
FROM students s
LEFT JOIN bed_spaces bs ON s.bed_space_id = bs.id
LEFT JOIN rooms r ON s.room_id = r.id
WHERE s.application_status = 'approved'
ORDER BY s.id DESC
LIMIT 10;

-- 4. Check bed spaces and their occupancy
SELECT 
    bs.id,
    bs.room_id,
    r.room_number,
    bs.bed_number,
    bs.is_occupied,
    bs.student_id,
    s.first_name,
    s.last_name
FROM bed_spaces bs
LEFT JOIN rooms r ON bs.room_id = r.id
LEFT JOIN students s ON bs.student_id = s.id
ORDER BY bs.room_id, bs.bed_number
LIMIT 20;
