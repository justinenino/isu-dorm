<?php
/**
 * Hostinger Email Configuration for Dormitory Management System
 * 
 * This file handles email notifications specifically for Hostinger hosting
 * Uses multiple fallback methods for better reliability
 */

// Email Configuration for Hostinger
define('EMAIL_ENABLED', true);
define('EMAIL_SMTP_HOST', 'smtp.gmail.com');
define('EMAIL_SMTP_PORT', 587);
define('EMAIL_SMTP_USERNAME', 'dormitoryisue2025@gmail.com'); // Replace with your Gmail
define('EMAIL_SMTP_PASSWORD', 'wwtw ovek dzbt yawj'); // Replace with your app password
define('EMAIL_SMTP_ENCRYPTION', 'tls');

// Email Settings
define('EMAIL_FROM_NAME', 'ISU Dormitory Management');
define('EMAIL_FROM_EMAIL', 'dormitoryisue2025@gmail.com');
define('EMAIL_REPLY_TO', 'dormitoryisue2025@gmail.com');

// Hostinger-specific settings
define('EMAIL_USE_PHPMailer', true); // Use PHPMailer if available
define('EMAIL_FALLBACK_TO_MAIL', true); // Fallback to mail() function
define('EMAIL_DEBUG_MODE', false); // Set to true for debugging

/**
 * Send email with multiple fallback methods for Hostinger
 * 
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $body Email body (HTML)
 * @param string $alt_body Alternative text body
 * @return bool Success status
 */
function sendEmailHostinger($to, $subject, $body, $alt_body = '') {
    // Validate inputs
    if (empty($to) || empty($subject) || empty($body)) {
        error_log("Email validation failed: Missing required parameters");
        return false;
    }
    
    // Check if email is enabled
    if (!defined('EMAIL_ENABLED') || !EMAIL_ENABLED) {
        error_log("Email sending is disabled");
        return false;
    }
    
    // Log email attempt
    error_log("Attempting to send email to: $to with subject: $subject");
    
    // Try PHPMailer first (if available)
    if (defined('EMAIL_USE_PHPMailer') && EMAIL_USE_PHPMailer) {
        $result = sendEmailViaPHPMailer($to, $subject, $body, $alt_body);
        if ($result) {
            error_log("Email sent successfully via PHPMailer to: $to");
            return true;
        }
        error_log("PHPMailer failed, trying fallback method");
    }
    
    // Try basic mail() function as fallback
    if (defined('EMAIL_FALLBACK_TO_MAIL') && EMAIL_FALLBACK_TO_MAIL) {
        $result = sendEmailViaMail($to, $subject, $body, $alt_body);
        if ($result) {
            error_log("Email sent successfully via mail() to: $to");
            return true;
        }
        error_log("mail() function also failed");
    }
    
    // All methods failed
    error_log("All email sending methods failed for: $to");
    return false;
}

/**
 * Send email using PHPMailer (if available)
 */
function sendEmailViaPHPMailer($to, $subject, $body, $alt_body = '') {
    // Check if PHPMailer is available
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        error_log("PHPMailer not available, skipping PHPMailer method");
        return false;
    }
    
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = EMAIL_SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = EMAIL_SMTP_USERNAME;
        $mail->Password = EMAIL_SMTP_PASSWORD;
        $mail->SMTPSecure = EMAIL_SMTP_ENCRYPTION;
        $mail->Port = EMAIL_SMTP_PORT;
        
        // Recipients
        $mail->setFrom(EMAIL_FROM_EMAIL, EMAIL_FROM_NAME);
        $mail->addAddress($to);
        $mail->addReplyTo(EMAIL_REPLY_TO, EMAIL_FROM_NAME);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = $alt_body;
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("PHPMailer error: " . $e->getMessage());
        return false;
    }
}

/**
 * Send email using basic mail() function
 */
function sendEmailViaMail($to, $subject, $body, $alt_body = '') {
    try {
        // Create email headers
        $boundary = md5(uniqid(time()));
        $headers = array(
            'From: ' . EMAIL_FROM_NAME . ' <' . EMAIL_FROM_EMAIL . '>',
            'Reply-To: ' . EMAIL_REPLY_TO,
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
            'X-Mailer: PHP/' . phpversion()
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
        
        // Send email
        $result = mail($to, $subject, $email_body, implode("\r\n", $headers));
        
        if ($result) {
            error_log("Email sent successfully via mail() to: $to");
            return true;
        } else {
            error_log("mail() function returned false for: $to");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("mail() function error: " . $e->getMessage());
        return false;
    }
}

/**
 * Send student approval notification email (Hostinger version)
 */
function sendStudentApprovalEmailHostinger($student, $room) {
    $subject = "Dormitory Application Approved - Welcome to " . $room['building_name'] . "!";
    
    $body = getApprovalEmailTemplate($student, $room);
    $alt_body = getApprovalEmailTextTemplate($student, $room);
    
    return sendEmailHostinger($student['email'], $subject, $body, $alt_body);
}

/**
 * Send student rejection notification email (Hostinger version)
 */
function sendStudentRejectionEmailHostinger($student, $reason = '') {
    $subject = "Dormitory Application Status Update";
    
    $body = getRejectionEmailTemplate($student, $reason);
    $alt_body = getRejectionEmailTextTemplate($student, $reason);
    
    return sendEmailHostinger($student['email'], $subject, $body, $alt_body);
}

/**
 * Get HTML email template for approval notification
 */
function getApprovalEmailTemplate($student, $room) {
    $login_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . 
                 '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/login.php';
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Application Approved</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .success-box { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 20px; border-radius: 5px; margin: 20px 0; }
            .info-box { background: white; border: 1px solid #dee2e6; padding: 20px; border-radius: 5px; margin: 20px 0; }
            .button { display: inline-block; background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 10px 0; }
            .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎉 Congratulations!</h1>
                <h2>Your Dormitory Application Has Been Approved</h2>
            </div>
            
            <div class='content'>
                <div class='success-box'>
                    <h3>✅ Application Status: APPROVED</h3>
                    <p>Dear {$student['first_name']} {$student['last_name']},</p>
                    <p>We are pleased to inform you that your dormitory application has been <strong>APPROVED</strong>! 
                    You have been assigned to the following accommodation:</p>
                </div>
                
                <div class='info-box'>
                    <h3>🏠 Your Room Assignment</h3>
                    <p><strong>Building:</strong> {$room['building_name']}</p>
                    <p><strong>Room Number:</strong> {$room['room_number']}</p>
                    <p><strong>Bed Space:</strong> {$room['bed_space_number']}</p>
                    <p><strong>Room Type:</strong> {$room['room_type']}</p>
                </div>
                
                <div class='info-box'>
                    <h3>📋 Next Steps</h3>
                    <ol>
                        <li>Log in to your student dashboard</li>
                        <li>Review dormitory policies and rules</li>
                        <li>Check your room assignment details</li>
                        <li>Contact dormitory management if you have any questions</li>
                    </ol>
                    <a href='{$login_url}' class='button'>Login to Your Dashboard</a>
                </div>
                
                <div class='info-box'>
                    <h3>📞 Contact Information</h3>
                    <p>If you have any questions or need assistance, please contact the dormitory management office.</p>
                    <p><strong>Email:</strong> admin@dormitory.com</p>
                    <p><strong>Phone:</strong> (123) 456-7890</p>
                </div>
            </div>
            
            <div class='footer'>
                <p>This is an automated message from the Dormitory Management System.</p>
                <p>Please do not reply to this email.</p>
            </div>
        </div>
    </body>
    </html>";
}

/**
 * Get text email template for approval notification
 */
function getApprovalEmailTextTemplate($student, $room) {
    $login_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . 
                 '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/login.php';
    
    return "
CONGRATULATIONS! Your dormitory application has been approved!

Dear {$student['first_name']} {$student['last_name']},

We are pleased to inform you that your dormitory application has been APPROVED! 
You have been assigned to the following accommodation:

YOUR ROOM ASSIGNMENT:
- Building: {$room['building_name']}
- Room Number: {$room['room_number']}
- Bed Space: {$room['bed_space_number']}
- Room Type: {$room['room_type']}

NEXT STEPS:
1. You can now log in to your student dashboard
2. Review dormitory policies and rules
3. Check your room assignment details
4. Contact dormitory management if you have any questions

LOGIN TO YOUR DASHBOARD:
{$login_url}

CONTACT INFORMATION:
If you have any questions or need assistance, please contact the dormitory management office.
Email: admin@dormitory.com
Phone: (123) 456-7890

This is an automated message from the Dormitory Management System.";
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
 * Test email functionality
 */
function testEmailHostinger($test_email = '') {
    if (empty($test_email)) {
        $test_email = EMAIL_FROM_EMAIL;
    }
    
    $subject = "Test Email from Dormitory Management System";
    $body = "<h1>Test Email</h1><p>This is a test email to verify email functionality on Hostinger.</p>";
    $alt_body = "Test Email - This is a test email to verify email functionality on Hostinger.";
    
    return sendEmailHostinger($test_email, $subject, $body, $alt_body);
}
?>
