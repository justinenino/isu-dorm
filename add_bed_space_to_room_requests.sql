-- Add bed space ID column to room_change_requests table
ALTER TABLE `room_change_requests` 
ADD COLUMN `requested_bed_space_id` int(11) DEFAULT NULL AFTER `requested_room_id`,
ADD COLUMN `current_bed_space_id` int(11) DEFAULT NULL AFTER `current_room_id`;

-- Add foreign key constraints
ALTER TABLE `room_change_requests` 
ADD CONSTRAINT `room_change_requests_ibfk_4` FOREIGN KEY (`requested_bed_space_id`) REFERENCES `bed_spaces` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `room_change_requests_ibfk_5` FOREIGN KEY (`current_bed_space_id`) REFERENCES `bed_spaces` (`id`) ON DELETE SET NULL;
