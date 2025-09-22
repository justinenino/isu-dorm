# Hostinger Setup Guide - Step by Step

## 🚀 **Complete Setup Process for ISU Dormitory Management System**

### **Phase 1: Hostinger Account Setup**

#### **Step 1: Access Hostinger Control Panel**
1. Go to [hostinger.com](https://hostinger.com)
2. Log into your Hostinger account
3. Go to **"Websites"** section
4. Find your domain and click **"Manage"**

#### **Step 2: Create Database**
1. In Hostinger control panel, go to **"Databases"**
2. Click **"Create New Database"**
3. Note down these details (you'll need them):
   - **Database Name:** `u[your_id]_dormitory`
   - **Username:** `u[your_id]_admin`
   - **Password:** (create a strong password)
   - **Host:** `localhost` (usually)

#### **Step 3: Upload Database**
1. Go to **"phpMyAdmin"** in Hostinger control panel
2. Select your database
3. Click **"Import"** tab
4. Upload `hostinger_simple_clean.sql` file
5. Click **"Go"** to import

### **Phase 2: File Upload**

#### **Step 4: Upload Files via File Manager**
1. In Hostinger control panel, go to **"File Manager"**
2. Navigate to **"public_html"** folder
3. Upload ALL files from your project:
   - All PHP files
   - All folders (admin/, student/, config/, etc.)
   - All SQL files
   - All documentation files

#### **Step 5: Set File Permissions**
1. Right-click on the project folder
2. Set permissions to **755** for folders
3. Set permissions to **644** for files

### **Phase 3: Configuration**

#### **Step 6: Update Database Configuration**
1. Open `config/database_hostinger_updated.php`
2. Replace these lines with your actual Hostinger credentials:

```php
define('DB_HOST', 'localhost'); // Usually localhost
define('DB_USERNAME', 'u[YOUR_ID]_admin'); // Your actual username
define('DB_PASSWORD', 'your_actual_password'); // Your actual password
define('DB_NAME', 'u[YOUR_ID]_dormitory'); // Your actual database name
```

#### **Step 7: Test Database Connection**
1. Create a test file `test_db.php` in your root directory:

```php
<?php
require_once 'config/database_hostinger_updated.php';

$result = testDatabaseConnection();
echo "<h2>Database Connection Test</h2>";
echo "<p><strong>Status:</strong> " . $result['status'] . "</p>";
echo "<p><strong>Message:</strong> " . $result['message'] . "</p>";
if ($result['version']) {
    echo "<p><strong>MySQL Version:</strong> " . $result['version'] . "</p>";
}
?>
```

2. Visit `yourdomain.com/test_db.php` in browser
3. Should show "success" status

### **Phase 4: Email Configuration**

#### **Step 8: Configure Gmail App Password**
1. Go to your Gmail account settings
2. Enable **2-Factor Authentication**
3. Generate **App Password** for "Mail"
4. Note down the 16-character password

#### **Step 9: Update Email Configuration**
1. Open `config/hostinger_email.php`
2. Update line 14 with your Gmail App Password:

```php
define('EMAIL_SMTP_PASSWORD', 'your_16_character_app_password'); // Replace this
```

### **Phase 5: Testing**

#### **Step 10: Test Email System**
1. Create a test file `test_email.php`:

```php
<?php
require_once 'config/hostinger_email.php';

echo "<h2>Email System Test</h2>";
$result = testEmail('your_email@gmail.com');
if ($result) {
    echo "<p style='color: green;'>✅ Email sent successfully!</p>";
    echo "<p>Check your Gmail inbox (and spam folder).</p>";
} else {
    echo "<p style='color: red;'>❌ Email failed to send.</p>";
}
?>
```

2. Visit `yourdomain.com/test_email.php`
3. Check your email for the test message

#### **Step 11: Test Admin Panel**
1. Go to `yourdomain.com/admin/dashboard.php`
2. Log in with admin credentials
3. Test student approval process
4. Verify email notifications work

### **Phase 6: Final Configuration**

#### **Step 12: Set Up Cron Jobs (Optional)**
1. In Hostinger control panel, go to **"Cron Jobs"**
2. Add cron job for backup:
   - **Command:** `php /home/u[your_id]/public_html/backup_cron.php`
   - **Schedule:** `0 2 * * *` (daily at 2 AM)

#### **Step 13: Security Setup**
1. Delete test files: `test_db.php`, `test_email.php`
2. Set proper file permissions
3. Enable SSL certificate if available

### **Phase 7: Go Live**

#### **Step 14: Final Testing**
1. Test student registration
2. Test admin approval/rejection
3. Verify email notifications
4. Test all major features

#### **Step 15: Documentation**
1. Keep `HOSTINGER_EMAIL_SETUP.md` for reference
2. Document admin credentials securely
3. Note down database credentials

---

## 🎯 **Quick Checklist:**

- [ ] Database created and imported
- [ ] Files uploaded to public_html
- [ ] Database credentials updated
- [ ] Gmail App Password configured
- [ ] Email system tested
- [ ] Admin panel working
- [ ] Student registration working
- [ ] Email notifications working
- [ ] Test files deleted
- [ ] System ready for production

---

## 🆘 **Troubleshooting:**

**Database Connection Failed:**
- Check credentials in `database_hostinger_updated.php`
- Verify database exists in Hostinger
- Check if database user has proper permissions

**Email Not Sending:**
- Verify Gmail App Password is correct
- Check if 2FA is enabled on Gmail
- Check Hostinger error logs

**Files Not Loading:**
- Check file permissions (755 for folders, 644 for files)
- Verify all files uploaded correctly
- Check .htaccess file if exists

---

**Your ISU Dormitory Management System is now ready for Hostinger!** 🚀
