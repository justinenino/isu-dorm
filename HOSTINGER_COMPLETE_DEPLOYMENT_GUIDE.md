# Complete Hostinger Deployment Guide

## 🚀 **Step-by-Step Hostinger Deployment**

### **Phase 1: Pre-Deployment Setup**

#### 1.1 Get Hostinger Database Credentials
1. **Login to Hostinger cPanel**
2. **Go to "Databases" section**
3. **Note down:**
   - Database name (usually `u123456789_dormitory`)
   - Database username (usually `u123456789_admin`)
   - Database password
   - Database host (usually `localhost`)

#### 1.2 Update Database Configuration
1. **Edit `config/database.php`:**
```php
define('DB_HOST', 'localhost'); // or your specific host
define('DB_USERNAME', 'u123456789_admin'); // your actual username
define('DB_PASSWORD', 'your_actual_password'); // your actual password
define('DB_NAME', 'u123456789_dormitory'); // your actual database name
```

2. **Or use the Hostinger-specific config:**
   - Rename `config/database_hostinger.php` to `config/database.php`
   - Update the credentials in the file

### **Phase 2: Database Setup**

#### 2.1 Create Database
1. **In Hostinger cPanel:**
   - Go to "MySQL Databases"
   - Create new database: `dormitory_management`
   - Create database user with full privileges
   - Assign user to database

#### 2.2 Import Database Schema
1. **Open phpMyAdmin**
2. **Select your database**
3. **Go to "Import" tab**
4. **Upload `hostinger_database_schema.sql`**
5. **Click "Go" to execute**

#### 2.3 Verify Database
1. **Run `hostinger_deployment_test.php` in browser**
2. **Check all tests pass**
3. **Verify all 20 tables exist**

### **Phase 3: File Upload**

#### 3.1 Upload Files
1. **Upload all files to `/public_html/` directory**
2. **Maintain folder structure:**
```
public_html/
├── admin/
├── student/
├── config/
├── cron/
├── index.php
├── login.php
└── [other files]
```

#### 3.2 Set File Permissions
1. **Files:** 644
2. **Directories:** 755
3. **Config files:** 600 (for security)

### **Phase 4: Configuration**

#### 4.1 Update Database Config
```php
// config/database.php
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'your_hostinger_db_user');
define('DB_PASSWORD', 'your_hostinger_db_password');
define('DB_NAME', 'your_hostinger_db_name');
```

#### 4.2 Enable Error Logging
```php
// Add to config/database.php
ini_set('display_errors', 0); // Hide errors from users
ini_set('log_errors', 1); // Log errors to file
ini_set('error_log', '/path/to/error.log');
```

### **Phase 5: Testing**

#### 5.1 Run Deployment Test
1. **Visit:** `yourdomain.com/hostinger_deployment_test.php`
2. **Check all tests pass**
3. **Fix any red ❌ errors**

#### 5.2 Test Admin Access
1. **Visit:** `yourdomain.com/admin/dashboard.php`
2. **Login with:** username: `admin`, password: `password`
3. **Verify dashboard shows content**

#### 5.3 Test Student Access
1. **Visit:** `yourdomain.com/register.php`
2. **Register a test student**
3. **Approve in admin panel**
4. **Test student login and dashboard**

## 🔧 **Common Hostinger Issues & Solutions**

### Issue 1: Database Connection Failed
**Error:** "Connection failed: Access denied"
**Solution:**
1. Check database credentials in cPanel
2. Verify database user has proper permissions
3. Ensure database name includes prefix

### Issue 2: Tables Don't Exist
**Error:** "Table 'dormitory_management.offenses' doesn't exist"
**Solution:**
1. Import `hostinger_database_schema.sql`
2. Check all 20 tables were created
3. Verify foreign key constraints

### Issue 3: Empty Dashboard
**Error:** Dashboard loads but shows empty content
**Solution:**
1. Use `admin/dashboard_hostinger.php` instead of `admin/dashboard.php`
2. Check database queries in error logs
3. Verify sample data was imported

### Issue 4: File Not Found
**Error:** "Failed to open stream: No such file or directory"
**Solution:**
1. Check file paths use forward slashes
2. Verify files uploaded to correct directory
3. Check file permissions

### Issue 5: Memory Limit Exceeded
**Error:** "Fatal error: Allowed memory size exhausted"
**Solution:**
1. Optimize database queries
2. Increase memory limit in PHP settings
3. Use pagination for large datasets

## 📊 **Hostinger-Specific Optimizations**

### 1. **Database Optimization**
```sql
-- Add indexes for better performance
CREATE INDEX idx_students_status ON students(application_status);
CREATE INDEX idx_offenses_status ON offenses(status);
CREATE INDEX idx_announcements_status ON announcements(status);
```

### 2. **PHP Optimization**
```php
// Enable opcache if available
ini_set('opcache.enable', 1);
ini_set('opcache.memory_consumption', 128);
ini_set('opcache.max_accelerated_files', 4000);
```

### 3. **Error Handling**
```php
// Comprehensive error handling
try {
    // Database operations
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    // Show user-friendly message
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    // Handle gracefully
}
```

## 🧪 **Testing Checklist**

### Pre-Deployment:
- [ ] Database credentials correct
- [ ] All files uploaded
- [ ] File permissions set
- [ ] Database schema imported

### Post-Deployment:
- [ ] Deployment test passes
- [ ] Admin login works
- [ ] Admin dashboard shows content
- [ ] Student registration works
- [ ] Student dashboard shows content
- [ ] All modules accessible
- [ ] No JavaScript errors
- [ ] No PHP errors in logs

### Performance:
- [ ] Pages load quickly
- [ ] Database queries optimized
- [ ] Memory usage reasonable
- [ ] Error logs clean

## 🆘 **Troubleshooting Commands**

### Check Database Connection:
```php
// Add to any page
$pdo = new PDO("mysql:host=localhost;dbname=your_db", "user", "pass");
echo "Connected successfully!";
```

### Check File Permissions:
```bash
# Via cPanel File Manager
ls -la /public_html/
```

### Check Error Logs:
```php
// View recent errors
$errors = file_get_contents('/path/to/error.log');
echo $errors;
```

### Check PHP Version:
```php
echo "PHP Version: " . phpversion();
```

## 📈 **Performance Monitoring**

### 1. **Database Performance**
- Monitor query execution time
- Check for slow queries
- Optimize frequently used queries

### 2. **Memory Usage**
- Monitor memory consumption
- Optimize large operations
- Use pagination for large datasets

### 3. **Error Monitoring**
- Check error logs regularly
- Monitor for recurring errors
- Fix issues promptly

## ✅ **Success Indicators**

After successful deployment:
- ✅ All tests pass in deployment test
- ✅ Admin dashboard shows statistics
- ✅ Student dashboard shows personal info
- ✅ All modules load without errors
- ✅ Database queries execute successfully
- ✅ No JavaScript errors in console
- ✅ No PHP errors in logs
- ✅ Pages load quickly
- ✅ All functionality works as expected

---

**This guide covers all the differences between localhost and Hostinger hosting. Follow it step by step for successful deployment!**
