# ISU Dormitory Management System - Hostinger Deployment Guide

## 🚀 Complete Database Tables for phpMyAdmin

### Database Name: `dormitory_management`

## 📋 All Required Tables (20 Tables)

### Core Tables:
1. **`admins`** - Administrator accounts
2. **`students`** - Student information and applications
3. **`buildings`** - Dormitory buildings
4. **`rooms`** - Individual rooms in buildings
5. **`bed_spaces`** - Bed spaces within rooms

### Announcement System:
6. **`announcements`** - Main announcements
7. **`announcement_likes`** - Student likes on announcements
8. **`announcement_comments`** - Comments on announcements
9. **`announcement_interactions`** - Student interactions (acknowledge, read, view)
10. **`announcement_views`** - View tracking for announcements

### Management Tables:
11. **`complaints`** - Student complaints
12. **`offenses`** - Offense records (FIXED - was offense_logs)
13. **`maintenance_requests`** - Maintenance request system
14. **`room_change_requests`** - Room change requests
15. **`visitor_logs`** - Visitor tracking
16. **`student_location_logs`** - Student location tracking
17. **`biometric_files`** - Biometric file uploads
18. **`policies`** - Dormitory policies
19. **`form_submissions`** - Form submission tracking
20. **`system_settings`** - System configuration

## 🔧 Deployment Steps

### Step 1: Database Setup
1. **Login to Hostinger cPanel**
2. **Open phpMyAdmin**
3. **Create Database:**
   - Database name: `dormitory_management`
   - Collation: `utf8mb4_unicode_ci`

### Step 2: Import SQL Schema
1. **Copy the entire content from `complete_database_schema.sql`**
2. **Paste into phpMyAdmin SQL tab**
3. **Click "Go" to execute**

### Step 3: Verify Tables
After import, you should see these 20 tables:
```
admins
announcement_comments
announcement_interactions
announcement_likes
announcement_views
announcements
bed_spaces
biometric_files
buildings
complaints
form_submissions
maintenance_requests
offenses
policies
room_change_requests
rooms
student_location_logs
students
system_settings
visitor_logs
```

### Step 4: Update Database Configuration
Update `config/database.php` with your Hostinger database credentials:

```php
define('DB_HOST', 'localhost'); // or your Hostinger DB host
define('DB_USERNAME', 'your_hostinger_db_user');
define('DB_PASSWORD', 'your_hostinger_db_password');
define('DB_NAME', 'dormitory_management');
```

### Step 5: Upload Files
Upload all PHP files to your Hostinger public_html directory maintaining the folder structure:
```
public_html/
├── admin/
├── student/
├── config/
├── cron/
└── [other files]
```

## 🐛 Fixed Issues

### 1. Offense Table Error
- **Problem:** `Table 'dormitory_management.offenses' doesn't exist`
- **Solution:** 
  - Created `offenses` table (was incorrectly named `offense_logs`)
  - Added auto-creation in `admin/offense_logs.php`
  - Fixed all references throughout the system

### 2. Announcement System
- **Complete announcement system with:**
  - Like functionality
  - Comment system
  - View tracking
  - Acknowledge feature
  - Expiry management

### 3. Database Relationships
- **All foreign key constraints properly set**
- **Proper indexing for performance**
- **Sample data included for testing**

## 📊 Sample Data Included

The SQL file includes sample data:
- 1 admin account (username: `admin`, password: `password`)
- 2 buildings with 8 rooms total
- 16 bed spaces
- 1 sample announcement
- 1 sample policy
- System settings

## 🔍 Testing After Deployment

1. **Login as admin:**
   - Username: `admin`
   - Password: `password`

2. **Test all modules:**
   - Admin dashboard
   - Student management
   - Room management
   - Announcements
   - Offense logs
   - Complaints
   - Maintenance requests

3. **Test student registration:**
   - Register a new student
   - Approve the application
   - Test student login

## ⚠️ Important Notes

1. **Change default admin password** after first login
2. **Update system settings** in the admin panel
3. **Configure email settings** for notifications
4. **Set up cron jobs** for announcement expiry
5. **Regular database backups** recommended

## 🆘 Troubleshooting

### If you get "Table doesn't exist" errors:
1. Check if all 20 tables were created
2. Verify database name matches in config
3. Check user permissions

### If you get connection errors:
1. Verify database credentials in `config/database.php`
2. Check if database user has proper permissions
3. Ensure database exists

### If offense logs don't work:
1. The system will auto-create the `offenses` table
2. Check error logs for any issues
3. Verify foreign key constraints

## 📞 Support

If you encounter any issues:
1. Check the error logs
2. Verify all tables exist
3. Test with sample data first
4. Contact system administrator

---

**✅ All 20 tables are ready for deployment!**
**✅ All foreign key relationships properly configured!**
**✅ Sample data included for testing!**
**✅ Offense table error fixed!**
