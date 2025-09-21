# XAMPP Email Setup Guide

This guide will help you configure email notifications for the Dormitory Management System using XAMPP.

## ✅ Current Status

The email system is now working using PHP's built-in `mail()` function - no external dependencies required!

## 🚀 Quick Setup

### 1. Test Email Functionality

Visit `http://localhost/isudorm/dormitory-2/test_email.php` in your browser to test the email system.

### 2. Configure XAMPP for Email (Optional)

For better email delivery, you can configure XAMPP to use an SMTP server:

#### Option A: Use Gmail SMTP (Recommended)

1. Open `C:\xampp\php\php.ini`
2. Find the `[mail function]` section
3. Update these settings:

```ini
[mail function]
SMTP = smtp.gmail.com
smtp_port = 587
sendmail_from = your-email@gmail.com
sendmail_path = "\"C:\xampp\sendmail\sendmail.exe\" -t"
```

4. Open `C:\xampp\sendmail\sendmail.ini`
5. Update the configuration:

```ini
[sendmail]
smtp_server=smtp.gmail.com
smtp_port=587
auth_username=your-email@gmail.com
auth_password=your-gmail-app-password
force_sender=your-email@gmail.com
```

6. Restart XAMPP

#### Option B: Use Local SMTP (For Testing)

1. Install a local SMTP server like **MailHog** or **FakeSMTP**
2. Configure XAMPP to use `localhost:1025` as SMTP server

### 3. Test the System

1. Go to the admin panel
2. Approve a student application
3. Check if the student receives an email notification

## 📧 Email Features

### What Students Receive:

**Approval Email:**
- Congratulations message
- Room assignment details
- Login link to dashboard
- Contact information
- Professional HTML formatting

**Rejection Email:**
- Polite rejection notification
- Reason for rejection (if provided)
- Information about reapplying
- Contact details

## 🔧 Configuration Files

- `config/simple_email.php` - Email configuration and templates
- `test_email.php` - Email testing script
- `admin/reservation_management.php` - Integration with approval system

## 🛠️ Troubleshooting

### Common Issues:

1. **Emails not being sent:**
   - Check XAMPP error logs
   - Verify SMTP configuration
   - Test with `test_email.php`

2. **Emails going to spam:**
   - Configure proper SPF records
   - Use a reputable SMTP service
   - Check email content

3. **PHP mail() function not working:**
   - Check if `mail()` function is enabled in php.ini
   - Verify sendmail configuration

### XAMPP-Specific Notes:

- XAMPP doesn't include a mail server by default
- You need to configure an external SMTP server
- Gmail SMTP is the easiest option for testing
- For production, use a proper email service

## 🎯 Next Steps

1. **Test the system** using `test_email.php`
2. **Configure SMTP** for better delivery
3. **Customize email templates** if needed
4. **Set up proper email service** for production

## 📞 Support

If you encounter issues:

1. Check XAMPP error logs
2. Test with the provided test script
3. Verify SMTP configuration
4. Check PHP mail() function status

The system is now ready to send email notifications when students are approved or rejected!
