# Gmail SMTP Setup Guide for Dormitory Management System

This guide will help you configure the dormitory management system to use Gmail SMTP for reliable email notifications.

## 🚀 Quick Setup

### Step 1: Run the Setup Script

1. Visit: `http://localhost/isudorm/dormitory-2/setup_gmail.php`
2. Follow the on-screen instructions
3. Enter your Gmail credentials

### Step 2: Configure Gmail App Password

1. Go to [Google Account Security](https://myaccount.google.com/security)
2. Enable **2-Step Verification** if not already enabled
3. Go to **App passwords**
4. Select **Mail** as the app
5. Generate a 16-character app password
6. Copy the password (you'll need it for setup)

### Step 3: Configure XAMPP

The setup script will show you exactly what to configure, but here's the summary:

#### Update `C:\xampp\php\php.ini`:
```ini
[mail function]
SMTP = smtp.gmail.com
smtp_port = 587
sendmail_from = your-email@gmail.com
sendmail_path = "\"C:\xampp\sendmail\sendmail.exe\" -t"
```

#### Update `C:\xampp\sendmail\sendmail.ini`:
```ini
[sendmail]
smtp_server=smtp.gmail.com
smtp_port=587
auth_username=your-email@gmail.com
auth_password=your-16-character-app-password
force_sender=your-email@gmail.com
```

### Step 4: Test the System

1. Visit: `http://localhost/isudorm/dormitory-2/test_gmail.php`
2. Click "Send Gmail Test Email"
3. Check your email inbox (and spam folder)

## 📧 Email Features

### What Students Receive:

**Approval Email:**
- Professional HTML formatting
- Room assignment details
- Login link to dashboard
- Contact information
- Mobile-responsive design

**Rejection Email:**
- Polite rejection notification
- Reason for rejection (if provided)
- Information about reapplying
- Contact details

## 🔧 Configuration Files

- `config/gmail_email.php` - Gmail SMTP configuration
- `setup_gmail.php` - Interactive setup script
- `test_gmail.php` - Email testing script
- `admin/reservation_management.php` - Integration with approval system

## 🛠️ Troubleshooting

### Common Issues:

1. **"Authentication failed" error:**
   - Verify Gmail app password is correct
   - Ensure 2-Step Verification is enabled
   - Check sendmail.ini configuration

2. **"Connection timeout" error:**
   - Check firewall settings
   - Verify network connectivity
   - Try different SMTP port (465 with SSL)

3. **Emails not being sent:**
   - Check XAMPP error logs
   - Verify sendmail configuration
   - Test with test_gmail.php

4. **Emails going to spam:**
   - This is normal for test emails
   - Check spam folder
   - Configure SPF records for production

### XAMPP-Specific Notes:

- XAMPP doesn't include a mail server
- You must configure external SMTP (Gmail)
- sendmail.exe handles the SMTP connection
- Restart XAMPP after configuration changes

## 🎯 Benefits of Gmail SMTP

- **Reliable Delivery**: Gmail's servers are highly reliable
- **Professional Appearance**: Emails come from your Gmail address
- **Spam Protection**: Better deliverability than local mail
- **Easy Setup**: Well-documented configuration
- **Free**: No additional costs for basic usage

## 📋 System Requirements

- XAMPP with PHP 7.4+
- Gmail account with 2-Step Verification
- Network connectivity to Gmail servers
- Proper XAMPP sendmail configuration

## 🔒 Security Notes

- Never share your Gmail app password
- Use app passwords instead of your main password
- Consider using a dedicated Gmail account for the system
- Regularly rotate app passwords

## 🚀 Production Recommendations

For production environments, consider:

1. **Dedicated Email Service**: Use services like SendGrid, Mailgun, or Amazon SES
2. **Domain Authentication**: Set up SPF, DKIM, and DMARC records
3. **Email Templates**: Customize templates for your institution
4. **Monitoring**: Set up email delivery monitoring
5. **Backup SMTP**: Configure fallback email providers

## 📞 Support

If you encounter issues:

1. Check the troubleshooting section above
2. Verify XAMPP configuration
3. Test with the provided test scripts
4. Check Gmail account security settings
5. Review server error logs

## ✅ Success Checklist

- [ ] Gmail app password generated
- [ ] XAMPP sendmail configured
- [ ] Test email sent successfully
- [ ] Student approval emails working
- [ ] Email templates displaying correctly

The system is now ready to send professional email notifications via Gmail when students are approved or rejected!
