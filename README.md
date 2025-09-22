# Dormitory Management System

A comprehensive web-based dormitory management system built with PHP, MySQL, HTML, CSS, Bootstrap, and JavaScript. This system provides complete management functionality for dormitory operations including student registration, room management, offense tracking, biometric integration, location monitoring, and automated backups.

## 🎨 New Theme & Design
- **Green to Yellow Gradient Theme** - Modern, professional color scheme
- **Responsive Design** - Works perfectly on all devices
- **Enhanced UI/UX** - Improved navigation and user experience
- **Professional Styling** - Consistent design across all pages

## ✨ Enhanced Features

### 🔐 Authentication & User Management
- **Complete login system** with dual authentication (Admin/Student)
- **Updated Default Credentials** - Admin: `Dorm_admin` / `Dorm_admin`
- **Student registration system** with comprehensive form validation
- **Registration status tracking** for pending applications
- **Session management** with proper security
- **Logout functionality** with session cleanup

### 📊 Enhanced Admin Dashboard
- **Real-time Analytics** with interactive charts
- **Room Occupancy Pie Charts** - Visual representation of room status
- **Bed Capacity Pie Charts** - Bed space utilization tracking
- **Weekly Applicant Graphs** - Trend analysis over time
- **Quick Action Buttons** - Direct access to common tasks
- **System Status Overview** - Comprehensive system health monitoring
- **Recent Activities Feed** - Live updates of system activities

### 🏠 Advanced Room Management
- **Building & Floor Tracking** - Monitor capacity by building/floor
- **Bed Space Management** - Individual bed tracking and assignment
- **Room Status Monitoring** - Available, occupied, maintenance status
- **Student Assignment Details** - View who's in each room
- **Capacity Optimization** - Real-time availability tracking

### 📍 Student Location Monitoring
- **Real-time Location Tracking** - Monitor student whereabouts
- **Location Status Updates** - Inside dormitory, in class, outside campus
- **Weekly Log Flushing** - Automatic cleanup of old logs
- **Search & Filter** - Find students by name, location, or date
- **Individual Student Logs** - Detailed location history per student
- **Safety Monitoring** - Track student safety and attendance

### 🔧 Enhanced Maintenance Management
- **Fixed Data Duplication Issues** - Resolved maintenance request doubling
- **Improved Request Processing** - Better workflow management
- **Status Tracking** - Real-time updates on maintenance progress
- **Priority Management** - Urgent, high, medium, low priority levels
- **Assignment System** - Assign tasks to maintenance staff

### 💾 Automated Backup System
- **Complete System Backup** - Database and project files
- **Google Drive Integration** - Cloud backup storage
- **Automatic Daily Backups** - Cron job automation
- **Manual Backup Options** - Download and cloud upload
- **7-Day Retention Policy** - Automatic cleanup of old backups
- **Email Notifications** - Success/failure alerts
- **Comprehensive Logging** - Detailed backup activity tracking

### 📱 Student Self-Service Features
- **Location Updates** - Students can update their location status
- **Biometric File Downloads** - Access attendance records
- **Location History** - View personal location logs
- **Real-time Status** - Current location display
- **Easy Navigation** - Intuitive student portal

## 🚀 System Requirements

- **Web Server**: Apache/Nginx
- **PHP**: Version 7.4 or higher
- **Database**: MySQL 5.7 or higher
- **XAMPP**: Recommended for local development
- **Extensions**: ZipArchive, PDO, MySQL

## 📦 Installation Instructions

### 1. Setup XAMPP
1. Download and install XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)
2. Start Apache and MySQL services from XAMPP control panel

### 2. Database Setup
1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Create a new database named `dormitory_management`
3. Import the database schema:
   - Navigate to the database folder in the project
   - Execute the SQL file `dormitory_db.sql`

### 3. Project Setup
1. Clone or download this project
2. Copy the project folder to your XAMPP htdocs directory
3. Ensure the following directory structure exists:
   ```
   htdocs/dormitory-management/
   ├── admin/
   ├── student/
   ├── config/
   ├── database/
   ├── uploads/
   ├── backups/
   └── ...
   ```

### 4. Configuration
1. Update database configuration in `config/database.php` if needed:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USERNAME', 'root');
   define('DB_PASSWORD', '');
   define('DB_NAME', 'dormitory_management');
   ```

### 5. File Permissions
Ensure the following directories are writable:
- `uploads/`
- `uploads/student_documents/`
- `uploads/biometric_files/`
- `backups/`

### 6. Backup System Setup (Optional)
1. Follow the detailed instructions in `BACKUP_SETUP.md`
2. Set up Google Drive API for cloud backups
3. Configure cron jobs for automatic backups

## 🔑 Default Login Credentials

### Admin Access
- **Username**: `Dorm_admin`
- **Password**: `Dorm_admin`
- **URL**: `http://localhost/dormitory-management/admin/`

### Student Access
Students must first register through the registration form. After admin approval:
- **Username**: Student's 6-digit School ID
- **Password**: Student's 12-digit Learner Reference Number
- **URL**: `http://localhost/dormitory-management/student/`

## 📁 File Structure

```
dormitory-management/
├── admin/                      # Admin panel files
│   ├── includes/              # Header and footer includes
│   ├── dashboard.php          # Enhanced admin dashboard
│   ├── system_backup.php      # Backup management
│   ├── student_location_logs.php  # Location monitoring
│   ├── get_student_location_logs.php  # AJAX handler
│   └── ...                    # Other admin pages
├── student/                   # Student panel files
│   ├── includes/             # Header and footer includes
│   ├── dashboard.php         # Student dashboard
│   ├── biometric_logs.php    # Enhanced biometric & location
│   └── ...                   # Other student pages
├── config/
│   ├── database.php          # Database configuration
│   └── google_drive.php      # Backup system configuration
├── database/
│   └── dormitory_db.sql      # Database schema
├── uploads/                  # File upload directories
├── backups/                  # Backup storage directory
├── backup_cron.php           # Automated backup script
├── BACKUP_SETUP.md           # Backup setup documentation
├── login.php                 # Authentication
├── register.php              # Student registration
├── logout.php                # Session cleanup
└── index.php                 # Main entry point
```

## 🔧 Key Features Explained

### Student Registration Process
1. Student fills out comprehensive registration form
2. System validates all required fields
3. Application status shows "Waiting for Approval"
4. Admin reviews and approves/rejects applications
5. Approved students can login with School ID and LRN

### Room Management System
- Buildings contain multiple rooms
- Each room has 4 bed spaces by default
- Automatic occupancy tracking
- Room status updates (available/full/maintenance)
- Individual bed space management

### Location Monitoring System
- Students can update their location status
- Admin can monitor all student locations
- Real-time tracking for safety
- Weekly log cleanup for performance
- Search and filter capabilities

### Biometric Integration
- Admin uploads biometric attendance files
- Students can download their attendance logs
- File management with date tracking
- Location status integration

### Automated Backup System
- Daily automatic backups at 11:59 PM
- Google Drive cloud storage integration
- Email notifications for success/failure
- 7-day retention policy
- Manual backup options

### Visitor Management
- Students register visitors with complete details
- Time-in automatic, time-out button for logging
- Admin can view all visitor logs per student

## 🛡️ Security Features

- Password hashing for admin accounts
- Session management for user authentication
- SQL injection prevention using prepared statements
- File upload validation and security
- Role-based access control (Admin/Student)
- Secure backup storage and transmission

## 📊 System Capabilities

- **Multi-user support** (Admin + Students)
- **File management** (uploads, downloads)
- **Real-time updates** and notifications
- **Comprehensive reporting** and analytics
- **Data export** capabilities
- **Backup and restore** functionality
- **Location monitoring** and safety tracking
- **Automated maintenance** workflows

## 🎯 Use Cases

- **Educational institutions** managing student housing
- **Dormitory administrators** tracking student activities
- **Students** managing their dormitory experience
- **Security personnel** monitoring visitor access
- **Maintenance staff** handling repair requests
- **IT administrators** managing system backups

## 📈 Performance Optimizations

- **Database indexing** for fast queries
- **Optimized SQL queries** with proper joins
- **Efficient file handling** with proper validation
- **Responsive design** for all devices
- **Caching strategies** for better performance
- **Automated cleanup** for system maintenance

## 🔄 Maintenance Features

- **System health monitoring**
- **Error logging** and reporting
- **Database backup** recommendations
- **Update mechanisms** for future enhancements
- **Troubleshooting guides**
- **Automated backup** and cleanup

## 📚 Documentation

- **README.md** - Main system documentation
- **BACKUP_SETUP.md** - Comprehensive backup setup guide
- **INSTALLATION.md** - Detailed installation instructions
- **FINAL_SUMMARY.md** - Complete feature overview

## 🆘 Support

For technical support or questions about the system:
1. Check the troubleshooting section in documentation
2. Review the code comments for implementation details
3. Ensure all system requirements are met
4. Check backup logs for system issues
5. Verify Google Drive API setup for backups

## 📄 License

This project is developed for educational and institutional use. Please ensure compliance with your organization's policies when deploying.

---

**Status**: ✅ **COMPLETE AND ENHANCED**
**Version**: 2.0 Enhanced
**Last Updated**: Current Date

## 🧹 Project Cleanup

The project has been optimized and cleaned up for deployment:

### **Removed Unnecessary Files:**
- ❌ Redundant database files (kept only deployment-ready versions)
- ❌ Outdated documentation files
- ❌ Duplicate backup files
- ❌ Temporary and log files

### **Current Clean Structure:**
- ✅ **2 Database Files** - Deployment-ready (no DROP statements)
- ✅ **3 Documentation Files** - Comprehensive guides
- ✅ **Core PHP Files** - Essential application files
- ✅ **Organized Directories** - Clean folder structure

### **Space Saved:**
- **Before**: 244.6 KB (10 files)
- **After**: 121.8 KB (5 files)
- **Saved**: 122.8 KB (50% reduction)

**Note**: Remember to change the default admin password immediately after first login for security purposes.