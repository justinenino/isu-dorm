# Bed Space Migration Instructions

## Problem
The room change request form is working, but bed space information is not being saved to the database because the required columns don't exist yet.

## Solution
Run the database migration to add the bed space columns to the `room_change_requests` table.

## Migration Steps

### Option 1: Using the PHP Migration Script
1. Make sure you're connected to the correct database (Hostinger)
2. Run: `php run_migration.php`

### Option 2: Manual SQL Execution
Run this SQL in your database management tool (phpMyAdmin, MySQL Workbench, etc.):

```sql
ALTER TABLE room_change_requests
ADD COLUMN requested_bed_space_id INT(11) DEFAULT NULL AFTER requested_room_id,
ADD COLUMN current_bed_space_id INT(11) DEFAULT NULL AFTER current_room_id;

ALTER TABLE room_change_requests
ADD CONSTRAINT fk_requested_bed_space
FOREIGN KEY (requested_bed_space_id) REFERENCES bed_spaces(id) ON DELETE SET NULL;

ALTER TABLE room_change_requests
ADD CONSTRAINT fk_current_bed_space
FOREIGN KEY (current_bed_space_id) REFERENCES bed_spaces(id) ON DELETE SET NULL;
```

## What This Fixes
- Room change requests will save bed space information
- Admin room management will show correct bed numbers
- Bed space assignments will be properly tracked
- Room occupancy will be calculated correctly

## After Migration
1. Test the room change request form
2. Check admin room management to see bed numbers
3. Verify that bed space assignments are working correctly

## Current Status
- ✅ Frontend form is working
- ✅ Bed space selection is functional
- ✅ Form validation is working
- ⏳ Database migration needed
- ⏳ Admin processing needs bed space columns
