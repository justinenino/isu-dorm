# 🚀 Hostinger Deployment Checklist

## **Pre-Deployment Setup**

### **1. Hostinger Account Preparation**
- [ ] Log into Hostinger control panel
- [ ] Note your database credentials
- [ ] Create database if not exists
- [ ] Get Gmail App Password ready

### **2. File Preparation**
- [ ] All files cleaned and ready
- [ ] Database SQL files ready
- [ ] Test files created (`test_db.php`, `test_email.php`)

## **Deployment Steps**

### **3. Database Setup**
- [ ] Import `hostinger_simple_clean.sql` to Hostinger database
- [ ] Optionally run `hostinger_student_logs_optimization.sql`
- [ ] Verify database tables created successfully

### **4. File Upload**
- [ ] Upload all files to `public_html` folder
- [ ] Set folder permissions to 755
- [ ] Set file permissions to 644

### **5. Configuration**
- [ ] Update `config/database_hostinger_updated.php` with your credentials
- [ ] Update `config/hostinger_email.php` with Gmail App Password
- [ ] Test database connection: `yourdomain.com/test_db.php`
- [ ] Test email system: `yourdomain.com/test_email.php`

### **6. Testing**
- [ ] Admin login works
- [ ] Student registration works
- [ ] Email notifications work
- [ ] All major features functional

### **7. Security Cleanup**
- [ ] Delete `test_db.php`
- [ ] Delete `test_email.php`
- [ ] Verify no sensitive files exposed

## **Post-Deployment**

### **8. Final Verification**
- [ ] System fully functional
- [ ] Email notifications working
- [ ] Admin can approve/reject students
- [ ] Students receive email notifications
- [ ] All features tested

### **9. Documentation**
- [ ] Keep setup guides for reference
- [ ] Document admin credentials securely
- [ ] Note database credentials safely

---

## **Quick Commands for Testing**

### **Database Test:**
```
Visit: yourdomain.com/test_db.php
Expected: Green success message
```

### **Email Test:**
```
Visit: yourdomain.com/test_email.php
Expected: Email sent successfully message
Check: Your Gmail inbox for test email
```

### **Admin Test:**
```
Visit: yourdomain.com/admin/dashboard.php
Login: With admin credentials
Test: Approve a student application
Verify: Student receives email notification
```

---

## **Troubleshooting Quick Fixes**

### **Database Connection Failed:**
- Check credentials in `database_hostinger_updated.php`
- Verify database exists in Hostinger
- Check database user permissions

### **Email Not Working:**
- Verify Gmail App Password is correct
- Check 2FA is enabled on Gmail
- Check Hostinger error logs

### **Files Not Loading:**
- Check file permissions (755/644)
- Verify all files uploaded correctly
- Check .htaccess configuration

---

**✅ Ready for Production!** 🎉
