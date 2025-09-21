# Email Notification Setup Guide

This guide will help you configure email notifications for the Dormitory Management System.

## Prerequisites

1. A Gmail account
2. Composer installed (for PHPMailer dependency)

## Step 1: Install Dependencies

Run the following command to install PHPMailer:

```bash
composer install
```

## Step 2: Configure Gmail App Password

1. Go to your Google Account settings
2. Navigate to Security → 2-Step Verification
3. Enable 2-Step Verification if not already enabled
4. Go to Security → App passwords
5. Generate a new app password for "Mail"
6. Copy the generated 16-character password

## Step 3: Update Email Configuration

Edit the file `config/email.php` and update the following settings:

```php
// Change these values to your Gmail credentials
define('EMAIL_SMTP_USERNAME', 'your-email@gmail.com'); // Your Gmail address
define('EMAIL_SMTP_PASSWORD', 'your-app-password'); // Your Gmail app password
```

## Step 4: Test Email Configuration

You can test the email configuration by:

1. Logging into the admin panel
2. Approving a student application
3. Check if the student receives the approval email

## Email Templates

The system includes two email templates:

### Approval Email
- Sent when a student's application is approved
- Includes room assignment details
- Contains login link to student dashboard

### Rejection Email
- Sent when a student's application is rejected
- Includes contact information for inquiries

## Troubleshooting

### Common Issues

1. **"Email sending failed" error**
   - Check if Gmail app password is correct
   - Ensure 2-Step Verification is enabled
   - Verify SMTP settings in `config/email.php`

2. **Emails not being received**
   - Check spam/junk folder
   - Verify recipient email address is correct
   - Check server logs for error messages

3. **PHPMailer not found error**
   - Run `composer install` to install dependencies
   - Check if `vendor/autoload.php` exists

### Server Requirements

- PHP 7.4 or higher
- OpenSSL extension enabled
- cURL extension enabled
- Composer installed

## Security Notes

- Never commit your Gmail credentials to version control
- Use environment variables for production deployments
- Consider using a dedicated email service for production

## Customization

You can customize email templates by editing the functions in `config/email.php`:

- `getApprovalEmailTemplate()` - HTML approval email
- `getRejectionEmailTemplate()` - HTML rejection email
- `getApprovalEmailTextTemplate()` - Text approval email
- `getRejectionEmailTextTemplate()` - Text rejection email

## Support

If you encounter any issues with email configuration, please check:

1. Server error logs
2. PHP error logs
3. Gmail account security settings
4. Network connectivity

For additional support, contact the system administrator.
