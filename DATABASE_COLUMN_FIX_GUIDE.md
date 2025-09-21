# Database Column Fix Guide - mobile_number Error

## 🚨 **Error Identified**

**Error:** `Database error: SQLSTATE[42S22]: Column not found: 1054 Unknown column 's.mobile_number' in 'SELECT'`

**Root Cause:** The application code references a `mobile_number` column in the `students` table, but this column doesn't exist in the database schema.

## 🔍 **Analysis**

### What's Happening:
1. The registration form (`register.php`) collects `mobile_number` data
2. The code tries to SELECT `s.mobile_number` from the students table
3. The database schema only has `contact_number` column
4. This causes a "Column not found" error

### Files Affected:
- `admin/reservation_management.php` - Line 213
- `student/dashboard.php` - Line 95
- `student/dashboard_simple_fixed.php` - Line 131
- `admin/visitor_logs.php` - Line 53
- `admin/maintenance_requests.php` - Lines 67, 86
- `admin/get_student_details.php` - Line 82
- `register.php` - Multiple lines

## ✅ **Solutions Implemented**

### 1. **Updated Database Schema**
- ✅ Added `mobile_number` column to `students` table
- ✅ Added other missing columns: `province`, `municipality`, `barangay`, `street_purok`, `facebook_link`, `attachment_file`, `guardian_name`, `guardian_mobile`, `guardian_relationship`
- ✅ Updated `hostinger_database_schema.sql` with complete column definitions

### 2. **Created Migration Scripts**
- ✅ `database_migration_mobile_number.sql` - SQL migration script
- ✅ `fix_mobile_number_column.php` - PHP fix script with error handling

### 3. **Added Sample Data**
- ✅ Updated schema with sample student records
- ✅ Proper data for testing and development

## 🚀 **How to Fix the Error**

### Option 1: Run the PHP Fix Script (Recommended)
1. Upload `fix_mobile_number_column.php` to your Hostinger account
2. Visit: `https://yourdomain.com/fix_mobile_number_column.php`
3. The script will automatically add missing columns
4. Delete the script after running

### Option 2: Run SQL Migration
1. Open phpMyAdmin on Hostinger
2. Select your database
3. Go to SQL tab
4. Copy and paste the contents of `database_migration_mobile_number.sql`
5. Click "Go" to execute

### Option 3: Import Updated Schema
1. Use the updated `hostinger_database_schema.sql`
2. This will create the table with all required columns
3. **Warning:** This will overwrite existing data

## 📋 **Columns Added to Students Table**

| Column Name | Type | Description |
|-------------|------|-------------|
| `mobile_number` | varchar(15) | Student's mobile phone number |
| `province` | varchar(50) | Student's province |
| `municipality` | varchar(50) | Student's municipality |
| `barangay` | varchar(50) | Student's barangay |
| `street_purok` | varchar(100) | Student's street/purok |
| `facebook_link` | varchar(255) | Student's Facebook profile |
| `attachment_file` | varchar(255) | Uploaded attachment file |
| `guardian_name` | varchar(100) | Guardian's name |
| `guardian_mobile` | varchar(15) | Guardian's mobile number |
| `guardian_relationship` | varchar(50) | Relationship to student |

## 🔧 **What the Fix Script Does**

1. **Checks for missing columns** in the students table
2. **Adds missing columns** with appropriate data types
3. **Updates existing records** with default values
4. **Copies contact_number to mobile_number** for existing data
5. **Provides detailed feedback** on what was fixed
6. **Tests the fix** with a sample query

## ✅ **Expected Results**

After running the fix:
- ✅ `mobile_number` column exists in students table
- ✅ All SELECT queries with `s.mobile_number` will work
- ✅ Student registration will work properly
- ✅ Admin and student dashboards will display mobile numbers
- ✅ No more "Column not found" errors

## 🧪 **Testing the Fix**

### Test 1: Check Column Exists
```sql
SHOW COLUMNS FROM students LIKE 'mobile_number';
```

### Test 2: Test SELECT Query
```sql
SELECT id, first_name, last_name, mobile_number FROM students LIMIT 1;
```

### Test 3: Test Application
1. Go to admin dashboard
2. Check if student details show mobile numbers
3. Go to student dashboard
4. Verify mobile number is displayed

## 🚨 **Important Notes**

### For Existing Data:
- The fix script copies `contact_number` to `mobile_number` for existing records
- This ensures no data is lost
- You can manually update mobile numbers later if needed

### For New Installations:
- Use the updated `hostinger_database_schema.sql`
- This includes all required columns from the start
- No migration needed

### Backup Recommendation:
- Always backup your database before running migrations
- The fix script is safe, but backups are good practice

## 📁 **Files Created/Modified**

### New Files:
- `database_migration_mobile_number.sql` - SQL migration script
- `fix_mobile_number_column.php` - PHP fix script
- `DATABASE_COLUMN_FIX_GUIDE.md` - This guide

### Modified Files:
- `hostinger_database_schema.sql` - Added missing columns and sample data

## 🎯 **Next Steps**

1. **Run the fix script** to add missing columns
2. **Test the application** to ensure error is resolved
3. **Verify data integrity** for existing students
4. **Delete fix script** after confirming everything works
5. **Update any custom queries** that might reference missing columns

The "Column not found: mobile_number" error should now be completely resolved! 🎉
