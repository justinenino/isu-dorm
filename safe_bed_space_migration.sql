-- Safe migration script that checks for existing columns first

-- Check if requested_bed_space_id exists, if not add it
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'room_change_requests' 
     AND COLUMN_NAME = 'requested_bed_space_id') = 0,
    'ALTER TABLE room_change_requests ADD COLUMN requested_bed_space_id INT(11) DEFAULT NULL AFTER requested_room_id',
    'SELECT "requested_bed_space_id column already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if current_bed_space_id exists, if not add it
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'room_change_requests' 
     AND COLUMN_NAME = 'current_bed_space_id') = 0,
    'ALTER TABLE room_change_requests ADD COLUMN current_bed_space_id INT(11) DEFAULT NULL AFTER current_room_id',
    'SELECT "current_bed_space_id column already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if foreign key constraints exist and add them if they don't
-- For requested_bed_space_id foreign key
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'room_change_requests' 
     AND CONSTRAINT_NAME = 'fk_requested_bed_space') = 0,
    'ALTER TABLE room_change_requests ADD CONSTRAINT fk_requested_bed_space FOREIGN KEY (requested_bed_space_id) REFERENCES bed_spaces(id) ON DELETE SET NULL',
    'SELECT "fk_requested_bed_space constraint already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- For current_bed_space_id foreign key
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'room_change_requests' 
     AND CONSTRAINT_NAME = 'fk_current_bed_space') = 0,
    'ALTER TABLE room_change_requests ADD CONSTRAINT fk_current_bed_space FOREIGN KEY (current_bed_space_id) REFERENCES bed_spaces(id) ON DELETE SET NULL',
    'SELECT "fk_current_bed_space constraint already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
