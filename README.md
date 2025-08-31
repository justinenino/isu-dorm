# Dormitory Online Reservation & Management System

A comprehensive web application for managing dormitory reservations, student records, and administrative tasks.

## Features

### Admin Features
- Dashboard with analytics and occupancy tracking
- Building and room management
- Student approval and management
- Reservation management
- Visitor logs
- Biometrics uploads
- Maintenance requests
- Offense logging
- Announcements
- Complaints management
- Policies management
- Room transfer requests
- Reports and analytics
- System backup (manual + automated Google Drive)

### Student Features
- Registration and profile management
- Room/bedspace reservation
- View announcements
- Submit maintenance requests
- Submit complaints
- Visitor log submission
- Biometrics download
- View offense records and policies

## Technology Stack
- **Backend**: PHP 8.0+
- **Database**: MySQL 8.0+
- **Frontend**: HTML5, CSS3, Bootstrap 5, JavaScript
- **Server**: XAMPP (Apache)
- **Authentication**: PHP Sessions with password hashing
- **Backup**: Google Drive API integration

## Installation

1. Clone this repository to your XAMPP htdocs folder
2. Import the database schema from `database/isu_dorm.sql`
3. Configure database connection in `config/database.php`
4. Set up Google Drive API credentials for backup functionality
5. Access the application at `http://localhost/isu-dorm`

## Default Admin Account
- Username: `Dorm_admin`
- Password: `Dorm_admin`

## Database Structure
The system uses a hierarchical structure:
- Buildings → Rooms → Bedspaces → Students
- Comprehensive logging and audit trails
- Secure password hashing and session management

## Security Features
- SQL injection prevention
- XSS protection
- CSRF tokens
- Secure password hashing
- Session-based authentication
- Role-based access control

## Backup System
- Manual backup generation
- Automated daily backups via cron
- Google Drive integration
- Retention policy (7 days)
- Email notifications for failures
