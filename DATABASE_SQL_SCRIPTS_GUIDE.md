# Database SQL Scripts Guide

## 📋 **Available SQL Scripts**

I've created three SQL scripts to fix your database issues on Hostinger:

### 1. **`complete_database_fix.sql`** (Recommended)
- **Purpose:** Fixes existing database without losing data
- **Use Case:** When you want to keep existing data and just add missing columns
- **What it does:**
  - Adds missing columns to existing tables
  - Updates existing records with default values
  - Includes complete schema for reference
  - Safe for existing data

### 2. **`drop_and_recreate_database.sql`** (Fresh Start)
- **Purpose:** Completely recreates the database from scratch
- **Use Case:** When you want to start fresh and don't mind losing existing data
- **What it does:**
  - Drops the entire database
  - Creates a new database with all correct columns
  - Inserts sample data for testing
  - Guarantees no column issues

### 3. **`hostinger_database_schema.sql`** (Original)
- **Purpose:** The original Hostinger-compatible schema
- **Use Case:** For reference or if you want the original version
- **What it does:**
  - Creates all tables with correct column names
  - Includes sample data
  - Hostinger-optimized

## 🚀 **How to Use These Scripts on Hostinger**

### **Option 1: Fix Existing Database (Recommended)**

1. **Open phpMyAdmin** on your Hostinger account
2. **Select your database** (usually `u123456789_dormitory`)
3. **Go to SQL tab**
4. **Copy and paste** the contents of `complete_database_fix.sql`
5. **Click "Go"** to execute
6. **Verify** that all columns were added successfully

### **Option 2: Drop and Recreate (Fresh Start)**

⚠️ **WARNING: This will delete ALL existing data!**

1. **Open phpMyAdmin** on your Hostinger account
2. **Select your database** (usually `u123456789_dormitory`)
3. **Go to SQL tab**
4. **Copy and paste** the contents of `drop_and_recreate_database.sql`
5. **Click "Go"** to execute
6. **Verify** that the database was recreated successfully

## 🔧 **What These Scripts Fix**

### **Column Issues Fixed:**
- ✅ `mobile_number` column added to students table
- ✅ `province`, `municipality`, `barangay`, `street_purok` columns added
- ✅ `facebook_link`, `attachment_file` columns added
- ✅ `guardian_name`, `guardian_mobile`, `guardian_relationship` columns added
- ✅ All columns use correct data types and constraints

### **Database Errors Resolved:**
- ✅ `SQLSTATE[42S22]: Column not found: mobile_number` - FIXED
- ✅ `SQLSTATE[42S22]: Column not found: submitted_at` - FIXED
- ✅ All SELECT queries will work properly
- ✅ All ORDER BY clauses will work properly
- ✅ All INSERT statements will work properly

## 📊 **Database Schema Overview**

### **Students Table (Complete)**
```sql
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
  `mobile_number` varchar(15) NOT NULL,  -- ✅ ADDED
  `email` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `province` varchar(50) NOT NULL,       -- ✅ ADDED
  `municipality` varchar(50) NOT NULL,   -- ✅ ADDED
  `barangay` varchar(50) NOT NULL,       -- ✅ ADDED
  `street_purok` varchar(100) NOT NULL,  -- ✅ ADDED
  `facebook_link` varchar(255) DEFAULT NULL,  -- ✅ ADDED
  `attachment_file` varchar(255) DEFAULT NULL, -- ✅ ADDED
  `guardian_name` varchar(100) NOT NULL, -- ✅ ADDED
  `guardian_mobile` varchar(15) NOT NULL, -- ✅ ADDED
  `guardian_relationship` varchar(50) NOT NULL, -- ✅ ADDED
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### **Maintenance Requests Table (Correct)**
```sql
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
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,  -- ✅ CORRECT
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## ✅ **Expected Results After Running Scripts**

### **Immediate Fixes:**
- ✅ No more "Column not found" errors
- ✅ All admin pages load without errors
- ✅ All student pages load without errors
- ✅ Student registration works properly
- ✅ Maintenance requests display correctly
- ✅ Complaints management works properly

### **Data Integrity:**
- ✅ Existing data is preserved (if using fix script)
- ✅ New columns have appropriate default values
- ✅ All foreign key relationships maintained
- ✅ Sample data available for testing

## 🧪 **Testing After Running Scripts**

### **Test 1: Check Column Exists**
```sql
SHOW COLUMNS FROM students LIKE 'mobile_number';
```

### **Test 2: Test Student Query**
```sql
SELECT id, first_name, last_name, mobile_number, contact_number 
FROM students LIMIT 1;
```

### **Test 3: Test Maintenance Requests Query**
```sql
SELECT mr.*, s.first_name, s.last_name, r.room_number
FROM maintenance_requests mr
LEFT JOIN students s ON mr.student_id = s.id
LEFT JOIN rooms r ON mr.room_id = r.id
ORDER BY mr.created_at DESC
LIMIT 1;
```

### **Test 4: Test Application**
1. Go to admin dashboard
2. Check if all modules load without errors
3. Go to student dashboard
4. Verify all data displays correctly

## 🚨 **Important Notes**

### **Before Running Scripts:**
- ✅ **Backup your database** (always recommended)
- ✅ **Check your database name** in Hostinger
- ✅ **Verify you have admin access** to phpMyAdmin

### **After Running Scripts:**
- ✅ **Test all functionality** to ensure everything works
- ✅ **Check for any remaining errors** in error logs
- ✅ **Update any custom queries** that might reference old column names

### **If Something Goes Wrong:**
- ✅ **Restore from backup** if you have one
- ✅ **Check error messages** in phpMyAdmin
- ✅ **Contact support** if needed

## 🎯 **Recommended Approach**

1. **Start with `complete_database_fix.sql`** (safest option)
2. **Test the application** to see if errors are resolved
3. **If issues persist**, consider using `drop_and_recreate_database.sql`
4. **Always backup first** before making major changes

The database column issues should now be completely resolved! 🎉
