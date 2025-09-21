# ISU Dormitory Management System - Hostinger Deployment Guide

## 🚀 Complete Step-by-Step Deployment Process

This guide will help you deploy your local ISU Dormitory Management System to Hostinger hosting.

---

## 📋 Prerequisites

- ✅ Local XAMPP system running
- ✅ Hostinger hosting account
- ✅ Database export completed
- ✅ All files ready for upload

---

## 🔧 Step 1: Prepare Your Local Database

### 1.1 Export Your Local Database
```bash
# Option A: Use the provided script (RECOMMENDED)
# Visit: http://localhost/hostinger/isu-dorm-3/deploy_to_hostinger.php
# This will download a complete database export

# Option B: Clean existing export (FIXES DEFINER ISSUES)
# Run: php simple_clean_export.php
# This removes DEFINER clauses from your existing export

# Option C: Manual export via phpMyAdmin
# 1. Open phpMyAdmin (http://localhost/phpmyadmin)
# 2. Select your 'dormitory_management' database
# 3. Click "Export" tab
# 4. Choose "Custom" export method
# 5. Select all tables
# 6. Check "Add DROP TABLE / VIEW / PROCEDURE / FUNCTION / EVENT / TRIGGER statement"
# 7. Check "Add IF NOT EXISTS"
# 8. Click "Go" to download the SQL file
```

### 1.2 Verify Database Export
- Check that `hostinger_simple_clean.sql` was created (RECOMMENDED)
- Or verify `local_database_export.sql` exists
- File should contain all your tables and data
- Size should be reasonable (not empty)
- **Important**: Use the simple clean export to avoid DEFINER permission errors

---

## 🌐 Step 2: Hostinger Database Setup

### 2.1 Create Database in Hostinger
1. **Login to Hostinger Control Panel**
   - Go to https://hpanel.hostinger.com
   - Login with your credentials

2. **Create New Database**
   - Navigate to "Databases" section
   - Click "Create New Database"
   - Database name: `u[your_id]_dormitory` (e.g., `u123456789_dormitory`)
   - Username: `u[your_id]_admin` (e.g., `u123456789_admin`)
   - Password: Create a strong password (save this!)
   - Click "Create"

3. **Note Down Credentials**
   - Database Host: Usually `localhost`
   - Database Name: `u[your_id]_dormitory`
   - Username: `u[your_id]_admin`
   - Password: (the one you created)

### 2.2 Import Database to Hostinger
1. **Open phpMyAdmin in Hostinger**
   - Go to "Databases" section
   - Click "Manage" next to your database
   - This opens phpMyAdmin

2. **Import Your Database**
   - Click "Import" tab
   - Choose file: Upload your `hostinger_simple_clean.sql` (RECOMMENDED)
   - Or use `local_database_export.sql` if clean export not available
   - Click "Go" to import
   - Wait for completion (should show success message)
   - **Note**: The simple clean export avoids DEFINER permission errors

---

## 📁 Step 3: Upload Files to Hostinger

### 3.1 Prepare Files for Upload
1. **Create Upload Package**
   - Copy all files from your local project
   - Exclude: `local_database_export.sql` (already uploaded)
   - Include: All PHP files, CSS, JS, images

2. **Update Configuration**
   - Replace `config/database.php` with `config/database_hostinger_updated.php`
   - Update database credentials in the new config file

### 3.2 Upload via File Manager
1. **Access File Manager**
   - In Hostinger control panel, go to "File Manager"
   - Navigate to `public_html` folder

2. **Upload Files**
   - Upload all your project files
   - Maintain the same folder structure
   - Ensure all files are in `public_html/isu-dorm-3/`

### 3.3 Set Permissions
```bash
# Set proper permissions for folders
chmod 755 admin/
chmod 755 student/
chmod 755 config/
chmod 644 *.php
chmod 644 config/*.php
```

---

## ⚙️ Step 4: Configure for Hostinger

### 4.1 Update Database Configuration
1. **Edit `config/database_hostinger_updated.php`**
   ```php
   // Replace these with your actual Hostinger credentials
   define('DB_HOST', 'localhost');
   define('DB_USERNAME', 'u123456789_admin'); // Your actual username
   define('DB_PASSWORD', 'your_actual_password'); // Your actual password
   define('DB_NAME', 'u123456789_dormitory'); // Your actual database name
   ```

2. **Rename Configuration File**
   ```bash
   # Rename the Hostinger config to be the main config
   mv config/database_hostinger_updated.php config/database.php
   ```

### 4.2 Update File Paths (if needed)
- Check all `require_once` statements
- Ensure paths are relative to the web root
- Update any absolute paths to relative paths

---

## 🧪 Step 5: Test Your Deployment

### 5.1 Basic Connection Test
1. **Visit Your Site**
   - Go to `https://yourdomain.com/isu-dorm-3/`
   - Check if the site loads without errors

2. **Test Database Connection**
   - Try logging in with admin credentials
   - Check if data is displaying correctly

### 5.2 Functionality Tests
- [ ] Admin login works
- [ ] Student registration works
- [ ] Database queries execute
- [ ] File uploads work (if any)
- [ ] All pages load correctly

---

## 🔧 Step 6: Hostinger-Specific Optimizations

### 6.1 Email Configuration
1. **Update Email Settings**
   - Use Hostinger's SMTP settings
   - Update `config/email_hostinger.php` with correct settings

2. **Test Email Functionality**
   - Send test emails
   - Verify delivery

### 6.2 Performance Optimizations
1. **Enable Caching** (if available)
2. **Optimize Images**
3. **Minify CSS/JS** (if needed)

---

## 🚨 Troubleshooting Common Issues

### Issue 1: Database Connection Failed
**Solution:**
- Double-check database credentials
- Ensure database exists in Hostinger
- Check if MySQL service is running

### Issue 2: 500 Internal Server Error
**Solution:**
- Check file permissions
- Look for PHP syntax errors
- Check Hostinger error logs

### Issue 3: File Upload Issues
**Solution:**
- Check upload limits in Hostinger
- Verify folder permissions
- Update PHP settings if needed

### Issue 4: Email Not Working
**Solution:**
- Use Hostinger's SMTP settings
- Check spam folder
- Verify email configuration

---

## 📞 Support and Maintenance

### Regular Maintenance
1. **Backup Database Weekly**
2. **Update System Regularly**
3. **Monitor Error Logs**
4. **Check Disk Usage**

### Getting Help
- Hostinger Support: https://support.hostinger.com
- Check Hostinger documentation
- Review error logs in control panel

---

## ✅ Deployment Checklist

- [ ] Local database exported successfully
- [ ] Hostinger database created
- [ ] Database imported to Hostinger
- [ ] Files uploaded to Hostinger
- [ ] Database configuration updated
- [ ] Site accessible via web browser
- [ ] Admin login working
- [ ] Student registration working
- [ ] All features tested
- [ ] Email functionality working
- [ ] Error logs clean

---

## 🎉 Congratulations!

Your ISU Dormitory Management System is now successfully deployed on Hostinger!

**Next Steps:**
1. Update DNS settings if using custom domain
2. Set up SSL certificate
3. Configure regular backups
4. Monitor system performance

---

## 📝 Important Notes

- **Keep your local database as backup**
- **Regularly backup your Hostinger database**
- **Test all functionality after deployment**
- **Update credentials if changed**
- **Monitor system performance**

---

*For additional support, refer to the Hostinger documentation or contact their support team.*
