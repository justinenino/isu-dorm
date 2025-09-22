<?php
/**
 * Hostinger Email Configuration for Dormitory Management System
 * 
 * This file is specifically configured for Hostinger hosting
 * Uses Hostinger's SMTP settings and Gmail integration
 */

// Email Configuration for Hostinger
define('EMAIL_ENABLED', true);
define('EMAIL_FROM_NAME', 'ISU Dormitory Management');
define('EMAIL_FROM_EMAIL', 'dormitoryisue2025@gmail.com');
define('EMAIL_REPLY_TO', 'dormitoryisue2025@gmail.com');

// Hostinger SMTP Configuration
define('EMAIL_SMTP_HOST', 'smtp.gmail.com');
define('EMAIL_SMTP_PORT', 587);
define('EMAIL_SMTP_USERNAME', 'dormitoryisue2025@gmail.com');
define('EMAIL_SMTP_PASSWORD', 'dxpm ekie zguc yliy'); // Gmail app password
define('EMAIL_SMTP_ENCRYPTION', 'tls');

/**
 * Send email using Hostinger's mail() function with Gmail SMTP
 * 
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $body Email body (HTML)
 * @param string $alt_body Alternative text body
 * @return bool Success status
 */
function sendEmail($to, $subject, $body, $alt_body = '') {
    // Check if email is enabled
    if (!defined('EMAIL_ENABLED') || !EMAIL_ENABLED) {
        error_log("Email sending is disabled");
        return false;
    }
    
    // Validate inputs
    if (empty($to) || empty($subject) || empty($body)) {
        error_log("Email validation failed: Missing required parameters");
        return false;
    }
    
    // Log email attempt
    error_log("Attempting to send email to: $to with subject: $subject");
    
    try {
        // Configure SMTP settings for Hostinger
        ini_set('SMTP', EMAIL_SMTP_HOST);
        ini_set('smtp_port', EMAIL_SMTP_PORT);
        ini_set('sendmail_from', EMAIL_FROM_EMAIL);
        ini_set('smtp_username', EMAIL_SMTP_USERNAME);
        ini_set('smtp_password', EMAIL_SMTP_PASSWORD);
        
        // Create email headers with anti-spam measures
        $boundary = md5(uniqid(time()));
        $headers = array(
            'From: ' . EMAIL_FROM_NAME . ' <' . EMAIL_FROM_EMAIL . '>',
            'Reply-To: ' . EMAIL_REPLY_TO,
            'Return-Path: ' . EMAIL_FROM_EMAIL,
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
            'X-Mailer: ISU Dormitory Management System',
            'X-Priority: 3',
            'X-MSMail-Priority: Normal',
            'Importance: Normal',
            'List-Unsubscribe: <mailto:' . EMAIL_REPLY_TO . '>',
            'X-Auto-Response-Suppress: All',
            'Precedence: bulk'
        );
        
        // Create email body with both HTML and text versions
        $email_body = "--$boundary\r\n";
        $email_body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $email_body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $email_body .= $alt_body . "\r\n\r\n";
        
        $email_body .= "--$boundary\r\n";
        $email_body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $email_body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $email_body .= $body . "\r\n\r\n";
        $email_body .= "--$boundary--\r\n";
        
        // Send email using Hostinger's mail() function
        $result = mail($to, $subject, $email_body, implode("\r\n", $headers));
        
        if ($result) {
            error_log("Email sent successfully to: $to via Hostinger SMTP");
            return true;
        } else {
            error_log("Failed to send email to: $to via Hostinger SMTP");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Hostinger email sending error: " . $e->getMessage());
        return false;
    }
}

/**
 * Send student approval notification email
 * 
 * @param array $student Student data
 * @param array $room Room data
 * @return bool Success status
 */
function sendStudentApprovalEmail($student, $room) {
    $subject = "Dormitory Application Approved - Welcome to " . $room['building_name'] . "!";
    
    $body = getApprovalEmailTemplate($student, $room);
    $alt_body = getApprovalEmailTextTemplate($student, $room);
    
    return sendEmail($student['email'], $subject, $body, $alt_body);
}

/**
 * Send student rejection notification email
 * 
 * @param array $student Student data
 * @param string $reason Rejection reason
 * @return bool Success status
 */
function sendStudentRejectionEmail($student, $reason = '') {
    $subject = "Dormitory Application Status Update";
    
    $body = getRejectionEmailTemplate($student, $reason);
    $alt_body = getRejectionEmailTextTemplate($student, $reason);
    
    return sendEmail($student['email'], $subject, $body, $alt_body);
}

/**
 * Get HTML email template for approval notification
 */
function getApprovalEmailTemplate($student, $room) {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    $script_path = isset($_SERVER['PHP_SELF']) ? dirname($_SERVER['PHP_SELF']) : '';
    $login_url = $protocol . '://' . $host . $script_path . '/login.php';
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Dormitory Application Approved - ISU</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f8f9fa; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #2c3e50; color: white; padding: 30px; text-align: center; }
            .content { background: white; padding: 30px; }
            .success-box { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 20px; margin: 20px 0; }
            .info-box { background: #f8f9fa; border: 1px solid #dee2e6; padding: 20px; margin: 20px 0; }
            .button { display: inline-block; background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 10px 0; }
            .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Dormitory Application Approved</h1>
                <p>Isabela State University - Dormitory Management</p>
            </div>
            
            <div class='content'>
                <p>Dear {$student['first_name']} {$student['last_name']},</p>
                
                <div class='success-box'>
                    <h3>Application Status: APPROVED</h3>
                    <p>Your dormitory application has been approved. You have been assigned to the following accommodation:</p>
                </div>
                
                <div class='info-box'>
                    <h3>Room Assignment Details</h3>
                    <p><strong>Building:</strong> {$room['building_name']}</p>
                    <p><strong>Room Number:</strong> {$room['room_number']}</p>
                    <p><strong>Bed Space:</strong> {$room['bed_space_number']}</p>
                    <p><strong>Room Type:</strong> " . (isset($room['room_type']) ? $room['room_type'] : 'Standard') . "</p>
                </div>
                
                <div class='info-box'>
                    <h3>Next Steps</h3>
                    <ol>
                        <li>Log in to your student dashboard using your credentials</li>
                        <li>Review dormitory policies and rules</li>
                        <li>Check your room assignment details</li>
                        <li>Contact dormitory management if you have questions</li>
                    </ol>
                    <p><a href='{$login_url}' class='button'>Access Student Dashboard</a></p>
                </div>
                
                <div class='info-box'>
                    <h3>Contact Information</h3>
                    <p>For questions or assistance, contact the dormitory management office.</p>
                    <p><strong>Email:</strong> dormitoryisue2025@gmail.com</p>
                    <p><strong>Phone:</strong> (02) 123-4567</p>
                </div>
            </div>
            
            <div class='footer'>
                <p>This is an official notification from ISU Dormitory Management System.</p>
                <p>Please add this email to your contacts to ensure future notifications reach your inbox.</p>
            </div>
        </div>
    </body>
    </html>";
}

/**
 * Get text email template for approval notification
 */
function getApprovalEmailTextTemplate($student, $room) {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    $script_path = isset($_SERVER['PHP_SELF']) ? dirname($_SERVER['PHP_SELF']) : '';
    $login_url = $protocol . '://' . $host . $script_path . '/login.php';
    
    return "
DORMITORY APPLICATION APPROVED - ISU

Dear {$student['first_name']} {$student['last_name']},

Your dormitory application has been approved. You have been assigned to the following accommodation:

ROOM ASSIGNMENT DETAILS:
- Building: {$room['building_name']}
- Room Number: {$room['room_number']}
- Bed Space: {$room['bed_space_number']}
- Room Type: " . (isset($room['room_type']) ? $room['room_type'] : 'Standard') . "

NEXT STEPS:
1. Log in to your student dashboard using your credentials
2. Review dormitory policies and rules
3. Check your room assignment details
4. Contact dormitory management if you have questions

ACCESS YOUR DASHBOARD:
{$login_url}

CONTACT INFORMATION:
For questions or assistance, contact the dormitory management office.
Email: dormitoryisue2025@gmail.com
Phone: (02) 123-4567

This is an official notification from ISU Dormitory Management System.
Please add this email to your contacts to ensure future notifications reach your inbox.";
}

/**
 * Get HTML email template for rejection notification
 */
function getRejectionEmailTemplate($student, $reason = '') {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Application Status Update</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #dc3545; color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .info-box { background: white; border: 1px solid #dee2e6; padding: 20px; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Application Status Update</h1>
            </div>
            
            <div class='content'>
                <div class='info-box'>
                    <h3>Application Status: Not Approved</h3>
                    <p>Dear {$student['first_name']} {$student['last_name']},</p>
                    <p>Thank you for your interest in our dormitory. Unfortunately, we are unable to approve your application at this time.</p>
                    " . (!empty($reason) ? "<p><strong>Reason:</strong> $reason</p>" : "") . "
                    <p>You may reapply in the future when space becomes available.</p>
                </div>
                
                <div class='info-box'>
                    <h3>📞 Contact Information</h3>
                    <p>If you have any questions, please contact the dormitory management office.</p>
                    <p><strong>Email:</strong> admin@dormitory.com</p>
                    <p><strong>Phone:</strong> (123) 456-7890</p>
                </div>
            </div>
            
            <div class='footer'>
                <p>This is an automated message from the Dormitory Management System.</p>
            </div>
        </div>
    </body>
    </html>";
}

/**
 * Get text email template for rejection notification
 */
function getRejectionEmailTextTemplate($student, $reason = '') {
    return "
Application Status Update

Dear {$student['first_name']} {$student['last_name']},

Thank you for your interest in our dormitory. Unfortunately, we are unable to approve your application at this time.

" . (!empty($reason) ? "Reason: $reason\n\n" : "") . "
You may reapply in the future when space becomes available.

CONTACT INFORMATION:
If you have any questions, please contact the dormitory management office.
Email: admin@dormitory.com
Phone: (123) 456-7890

This is an automated message from the Dormitory Management System.";
}

/**
 * Test email functionality for Hostinger
 */
function testEmail($test_email = '') {
    if (empty($test_email)) {
        $test_email = EMAIL_FROM_EMAIL;
    }
    
    $subject = "Test Email from ISU Dormitory Management System";
    $body = "<h1>Test Email</h1><p>This is a test email to verify email functionality on Hostinger.</p><p>If you receive this email, the system is working correctly!</p>";
    $alt_body = "Test Email - This is a test email to verify email functionality on Hostinger. If you receive this email, the system is working correctly!";
    
    return sendEmail($test_email, $subject, $body, $alt_body);
}
?>
