# Database Files Overview

This directory contains different versions of the dormitory management database for various purposes.

## 📁 Database Files

### 1. `fresh_dormitory_database_structure_only.sql` (33.7 KB)
- **Purpose**: Clean database structure without any data
- **Contents**: Only table structures, indexes, constraints, and relationships
- **Use Case**: 
  - Setting up a new installation
  - Development environment setup
  - Testing database schema changes
  - Creating a fresh database for new deployments

### 2. `current_dormitory_database_with_data.sql` (68.4 KB)
- **Purpose**: Complete database backup with all current data
- **Contents**: Full database structure + all existing data
- **Use Case**:
  - Full system backup
  - Data migration
  - Disaster recovery
  - Moving to production with existing data

### 3. `fresh_dormitory_database_structure_only_deployment.sql` (32.3 KB) ⭐ **DEPLOYMENT READY**
- **Purpose**: Clean database structure for hosting deployment
- **Contents**: Table structures without DROP statements
- **Use Case**: 
  - InfinityFree hosting deployment
  - Shared hosting deployment
  - Production deployment without existing data
  - Safe for hosting providers that restrict DROP statements

### 4. `current_dormitory_database_with_data_deployment.sql` (67.0 KB) ⭐ **DEPLOYMENT READY**
- **Purpose**: Complete database with data for hosting deployment
- **Contents**: Full database structure + data without DROP statements
- **Use Case**:
  - InfinityFree hosting deployment with existing data
  - Shared hosting deployment with data
  - Production deployment with existing data
  - Safe for hosting providers that restrict DROP statements

### 5. `fresh_dormitory_database.sql` (Original)
- **Purpose**: Original database structure file
- **Contents**: Initial database setup
- **Use Case**: Reference for original schema

## 🗃️ Database Tables Included

The database contains **25 tables** covering all aspects of dormitory management:

### Core Tables
- `admins` - Administrator accounts
- `students` - Student information and profiles
- `buildings` - Dormitory building information
- `rooms` - Room details and assignments
- `bed_spaces` - Individual bed space management

### Announcement System
- `announcements` - Main announcements table
- `announcement_comments` - Comments on announcements
- `announcement_likes` - Like system for announcements
- `announcement_views` - View tracking for announcements
- `announcement_interactions` - User interactions (acknowledge, etc.)
- `announcement_comment_likes` - Likes on comments
- `announcement_comment_replies` - Replies to comments

### Maintenance & Requests
- `maintenance_requests` - Maintenance request system
- `maintenance_status_history` - Status change tracking
- `maintenance_notifications` - Maintenance notifications
- `room_change_requests` - Room change requests

### Security & Logging
- `biometric_files` - Biometric data storage
- `student_location_logs` - Student location tracking
- `visitor_logs` - Visitor registration and tracking
- `offense_logs` - Student offense records

### Communication & Policies
- `complaints` - Student complaint system
- `policies` - Dormitory policies and rules
- `notifications` - System notifications
- `email_rate_limit` - Email sending rate limiting
- `form_submissions` - Form submission tracking

## 🚀 How to Use

### For Fresh Installation (No Data)
```sql
-- Create database
CREATE DATABASE dormitory_management;

-- Import structure only
mysql -u root -p dormitory_management < fresh_dormitory_database_structure_only.sql
```

### For Full Backup Restoration
```sql
-- Create database
CREATE DATABASE dormitory_management;

-- Import with all data
mysql -u root -p dormitory_management < current_dormitory_database_with_data.sql
```

### For InfinityFree/Shared Hosting Deployment
```sql
-- Use deployment-ready files (no DROP statements)
-- For new installation:
mysql -u username -p database_name < fresh_dormitory_database_structure_only_deployment.sql

-- For existing data:
mysql -u username -p database_name < current_dormitory_database_with_data_deployment.sql
```

### For Development Setup
1. Use `fresh_dormitory_database_structure_only.sql` for clean development
2. Add sample data as needed for testing
3. Use `current_dormitory_database_with_data.sql` for production-like testing

### For Production Deployment
1. **Use deployment-ready files** (`*_deployment.sql`)
2. **No DROP statements** - safe for shared hosting
3. **Follow InfinityFree Deployment Guide** for detailed instructions

## 📊 File Sizes
- **Structure Only**: 33.7 KB (tables, indexes, constraints)
- **With Data**: 68.4 KB (structure + all current data)
- **Data Size**: ~34.7 KB (actual data content)

## 🔄 Backup Strategy
- **Daily**: Use `current_dormitory_database_with_data.sql` for daily backups
- **Weekly**: Keep both files updated
- **Before Major Changes**: Always backup current data before schema modifications

## ⚠️ Important Notes
- Always backup your current database before importing any file
- Test imports on a development environment first
- The structure-only file is perfect for new installations
- The data file contains all current information and should be handled securely

---
*Generated on: $(Get-Date)*
*Database: dormitory_management*
*Total Tables: 25*
