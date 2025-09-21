<?php
/**
 * Simple Email Configuration for Dormitory Management System
 * 
 * This file handles email notifications using PHP's built-in mail() function
 * No external dependencies required - works with basic PHP installation
 */

// Email Configuration
define('EMAIL_ENABLED', true);
define('EMAIL_FROM_NAME', 'Dormitory Management System');
define('EMAIL_FROM_EMAIL', 'noreply@dormitory.com');
define('EMAIL_REPLY_TO', 'admin@dormitory.com');

/**
 * Send email using PHP's built-in mail() function
 * 
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $body Email body (HTML)
 * @param string $alt_body Alternative text body
 * @return bool Success status
 */
function sendEmail($to, $subject, $body, $alt_body = '') {
    if (!EMAIL_ENABLED) {
        error_log("Email sending is disabled");
        return false;
    }
    
    try {
        // Set headers for HTML email
        $headers = array(
            'From: ' . EMAIL_FROM_NAME . ' <' . EMAIL_FROM_EMAIL . '>',
            'Reply-To: ' . EMAIL_REPLY_TO,
            'Content-Type: text/html; charset=UTF-8',
            'MIME-Version: 1.0'
        );
        
        // Send email
        $result = mail($to, $subject, $body, implode("\r\n", $headers));
        
        if ($result) {
            error_log("Email sent successfully to: $to");
            return true;
        } else {
            error_log("Failed to send email to: $to");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
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
 * Get HTML email template for approval notification
 * 
 * @param array $student Student data
 * @param array $room Room data
 * @return string HTML email body
 */
function getApprovalEmailTemplate($student, $room) {
    $login_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . 
                 '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/login.php';
    
    return "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Application Approved</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
            .success-badge { background: #28a745; color: white; padding: 10px 20px; border-radius: 25px; display: inline-block; margin: 20px 0; }
            .info-box { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #667eea; }
            .btn { display: inline-block; background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 25px; margin: 20px 0; }
            .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎉 Congratulations!</h1>
                <p>Your dormitory application has been approved!</p>
            </div>
            
            <div class='content'>
                <div style='text-align: center;'>
                    <div class='success-badge'>
                        <strong>✓ APPLICATION APPROVED</strong>
                    </div>
                </div>
                
                <p>Dear <strong>{$student['first_name']} {$student['last_name']}</strong>,</p>
                
                <p>We are pleased to inform you that your dormitory application has been <strong>approved</strong>! You have been assigned to the following accommodation:</p>
                
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
                        <li>You can now log in to your student dashboard</li>
                        <li>Review dormitory policies and rules</li>
                        <li>Check your room assignment details</li>
                        <li>Contact dormitory management if you have any questions</li>
                    </ol>
                </div>
                
                <div style='text-align: center;'>
                    <a href='{$login_url}' class='btn'>Login to Your Dashboard</a>
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
 * 
 * @param array $student Student data
 * @param array $room Room data
 * @return string Text email body
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

This is an automated message from the Dormitory Management System.
Please do not reply to this email.
";
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
 * Get HTML email template for rejection notification
 * 
 * @param array $student Student data
 * @param string $reason Rejection reason
 * @return string HTML email body
 */
function getRejectionEmailTemplate($student, $reason) {
    return "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Application Status Update</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #6c757d; color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
            .info-box { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #6c757d; }
            .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Application Status Update</h1>
            </div>
            
            <div class='content'>
                <p>Dear <strong>{$student['first_name']} {$student['last_name']}</strong>,</p>
                
                <p>Thank you for your interest in our dormitory accommodation. After careful review of your application, we regret to inform you that we are unable to approve your request at this time.</p>
                
                " . (!empty($reason) ? "<div class='info-box'><h3>Reason for Rejection</h3><p>{$reason}</p></div>" : "") . "
                
                <div class='info-box'>
                    <h3>Next Steps</h3>
                    <p>You may reapply for dormitory accommodation in the future. Please ensure all required documents are complete and accurate when submitting a new application.</p>
                </div>
                
                <div class='info-box'>
                    <h3>Contact Information</h3>
                    <p>If you have any questions about this decision, please contact the dormitory management office.</p>
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
 * Get text email template for rejection notification
 * 
 * @param array $student Student data
 * @param string $reason Rejection reason
 * @return string Text email body
 */
function getRejectionEmailTextTemplate($student, $reason) {
    return "
APPLICATION STATUS UPDATE

Dear {$student['first_name']} {$student['last_name']},

Thank you for your interest in our dormitory accommodation. After careful review of your application, we regret to inform you that we are unable to approve your request at this time.

" . (!empty($reason) ? "REASON FOR REJECTION:\n{$reason}\n\n" : "") . "

NEXT STEPS:
You may reapply for dormitory accommodation in the future. Please ensure all required documents are complete and accurate when submitting a new application.

CONTACT INFORMATION:
If you have any questions about this decision, please contact the dormitory management office.
Email: admin@dormitory.com
Phone: (123) 456-7890

This is an automated message from the Dormitory Management System.
Please do not reply to this email.
";
}
?>
