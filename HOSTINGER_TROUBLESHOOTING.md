# Hostinger Database Deployment - Troubleshooting Guide

## 🚨 Common Hostinger Issues & Solutions

### Issue 1: ENUM Data Type Not Supported
**Error:** `#1064 - You have an error in your SQL syntax... near 'enum'`

**Solution:** Use the `hostinger_database_schema.sql` file instead of `complete_database_schema.sql`

**What I Fixed:**
- Replaced all `ENUM` types with `VARCHAR` 
- Removed `COLLATE utf8mb4_unicode_ci` (some Hostinger plans don't support it)
- Simplified data types for better compatibility

### Issue 2: Foreign Key Constraints Fail
**Error:** `#1215 - Cannot add foreign key constraint`

**Solution:** The `hostinger_database_schema.sql` creates tables first, then adds foreign keys at the end

**What I Fixed:**
- Moved all foreign key constraints to the end of the file
- Added proper error handling
- Used simpler constraint names

### Issue 3: Character Set Issues
**Error:** `#1115 - Unknown character set: 'utf8mb4'`

**Solution:** Use `utf8mb4` without collation specification

**What I Fixed:**
- Removed `COLLATE utf8mb4_unicode_ci`
- Kept `CHARSET=utf8mb4` (widely supported)

### Issue 4: Auto Increment Issues
**Error:** `#1062 - Duplicate entry for key 'PRIMARY'`

**Solution:** Use `IF NOT EXISTS` and proper sample data insertion

**What I Fixed:**
- All tables use `CREATE TABLE IF NOT EXISTS`
- Sample data uses proper INSERT statements
- Added UNIQUE constraints where needed

## 📋 Step-by-Step Hostinger Deployment

### Step 1: Access phpMyAdmin
1. Login to Hostinger cPanel
2. Find "phpMyAdmin" in the database section
3. Click to open phpMyAdmin

### Step 2: Create Database
1. Click "New" in the left sidebar
2. Database name: `dormitory_management`
3. Collation: `utf8mb4_general_ci` (or leave default)
4. Click "Create"

### Step 3: Import Schema
1. Select the `dormitory_management` database
2. Click "Import" tab
3. Click "Choose File" and select `hostinger_database_schema.sql`
4. Click "Go" to execute

### Step 4: Verify Tables
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

## 🔧 If Import Fails

### Option 1: Import Table by Table
If the full import fails, try importing in smaller chunks:

1. **First, create core tables:**
   - `admins`
   - `buildings`
   - `rooms`
   - `bed_spaces`
   - `students`

2. **Then create management tables:**
   - `announcements`
   - `complaints`
   - `offenses`
   - `maintenance_requests`
   - `room_change_requests`

3. **Finally create supporting tables:**
   - `announcement_likes`
   - `announcement_comments`
   - `visitor_logs`
   - `student_location_logs`
   - `biometric_files`
   - `policies`
   - `form_submissions`
   - `system_settings`

### Option 2: Manual Table Creation
If you get specific errors, create tables manually:

1. Copy the CREATE TABLE statement for the failing table
2. Paste it in the SQL tab
3. Execute it individually
4. Repeat for each table

### Option 3: Skip Foreign Keys Initially
If foreign key constraints fail:

1. Comment out all `ALTER TABLE` statements with foreign keys
2. Import the schema without foreign keys
3. Add foreign keys later using the ALTER TABLE statements

## 🐛 Common Error Solutions

### Error: "Table doesn't exist"
**Solution:** Make sure you're in the correct database and all tables were created

### Error: "Access denied"
**Solution:** Check your database user permissions in Hostinger cPanel

### Error: "Syntax error near 'enum'"
**Solution:** Use the `hostinger_database_schema.sql` file (no ENUM types)

### Error: "Duplicate entry"
**Solution:** Clear the database and re-import, or use `TRUNCATE` statements

## 📊 Testing After Import

### 1. Check Table Count
```sql
SELECT COUNT(*) as table_count 
FROM information_schema.tables 
WHERE table_schema = 'dormitory_management';
```
Should return: 20

### 2. Check Sample Data
```sql
SELECT COUNT(*) as admin_count FROM admins;
SELECT COUNT(*) as building_count FROM buildings;
SELECT COUNT(*) as room_count FROM rooms;
```
Should return: 1, 2, 8 respectively

### 3. Test Foreign Keys
```sql
SELECT * FROM students s 
JOIN rooms r ON s.room_id = r.id 
LIMIT 1;
```
Should work without errors

## 🆘 If All Else Fails

### Contact Hostinger Support
1. Check your hosting plan limits
2. Verify MySQL version compatibility
3. Ask about foreign key support
4. Request database user permissions

### Alternative: Use XAMPP First
1. Test the schema on XAMPP first
2. Export the working database
3. Import the exported file to Hostinger

## ✅ Success Indicators

After successful import, you should see:
- ✅ 20 tables created
- ✅ Sample data inserted
- ✅ Foreign key constraints working
- ✅ No error messages
- ✅ Admin login working (username: admin, password: password)

---

**Use `hostinger_database_schema.sql` for best Hostinger compatibility!**
