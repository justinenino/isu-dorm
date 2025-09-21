<?php
/**
 * Validation helper functions for student features
 */

class StudentValidation {
    
    /**
     * Validate maintenance request data
     */
    public static function validateMaintenanceRequest($data) {
        $errors = [];
        
        // Validate title
        if (empty($data['title'])) {
            $errors['title'] = 'Title is required';
        } elseif (strlen($data['title']) < 5) {
            $errors['title'] = 'Title must be at least 5 characters long';
        } elseif (strlen($data['title']) > 200) {
            $errors['title'] = 'Title must not exceed 200 characters';
        }
        
        // Validate description
        if (empty($data['description'])) {
            $errors['description'] = 'Description is required';
        } elseif (strlen($data['description']) < 10) {
            $errors['description'] = 'Description must be at least 10 characters long';
        } elseif (strlen($data['description']) > 1000) {
            $errors['description'] = 'Description must not exceed 1000 characters';
        }
        
        // Validate priority
        $validPriorities = ['low', 'medium', 'high', 'urgent'];
        if (empty($data['priority'])) {
            $errors['priority'] = 'Priority is required';
        } elseif (!in_array($data['priority'], $validPriorities)) {
            $errors['priority'] = 'Invalid priority level';
        }
        
        // Validate room_id
        if (empty($data['room_id'])) {
            $errors['room_id'] = 'Room ID is required';
        } elseif (!is_numeric($data['room_id'])) {
            $errors['room_id'] = 'Invalid room ID';
        }
        
        return $errors;
    }
    
    /**
     * Validate room change request data
     */
    public static function validateRoomChangeRequest($data) {
        $errors = [];
        
        // Validate requested room
        if (empty($data['requested_room_id'])) {
            $errors['requested_room_id'] = 'Requested room is required';
        } elseif (!is_numeric($data['requested_room_id'])) {
            $errors['requested_room_id'] = 'Invalid requested room ID';
        }
        
        // Validate reason
        if (empty($data['reason'])) {
            $errors['reason'] = 'Reason is required';
        } elseif (strlen($data['reason']) < 10) {
            $errors['reason'] = 'Reason must be at least 10 characters long';
        } elseif (strlen($data['reason']) > 500) {
            $errors['reason'] = 'Reason must not exceed 500 characters';
        }
        
        return $errors;
    }
    
    /**
     * Validate complaint data
     */
    public static function validateComplaint($data) {
        $errors = [];
        
        // Validate subject
        if (empty($data['subject'])) {
            $errors['subject'] = 'Subject is required';
        } elseif (strlen($data['subject']) < 5) {
            $errors['subject'] = 'Subject must be at least 5 characters long';
        } elseif (strlen($data['subject']) > 200) {
            $errors['subject'] = 'Subject must not exceed 200 characters';
        }
        
        // Validate description
        if (empty($data['description'])) {
            $errors['description'] = 'Description is required';
        } elseif (strlen($data['description']) < 10) {
            $errors['description'] = 'Description must be at least 10 characters long';
        } elseif (strlen($data['description']) > 1000) {
            $errors['description'] = 'Description must not exceed 1000 characters';
        }
        
        return $errors;
    }
    
    /**
     * Validate visitor registration data
     */
    public static function validateVisitorRegistration($data) {
        $errors = [];
        
        // Validate visitor name
        if (empty($data['visitor_name'])) {
            $errors['visitor_name'] = 'Visitor name is required';
        } elseif (strlen($data['visitor_name']) < 2) {
            $errors['visitor_name'] = 'Visitor name must be at least 2 characters long';
        } elseif (strlen($data['visitor_name']) > 100) {
            $errors['visitor_name'] = 'Visitor name must not exceed 100 characters';
        }
        
        // Validate visitor age
        if (empty($data['visitor_age'])) {
            $errors['visitor_age'] = 'Visitor age is required';
        } elseif (!is_numeric($data['visitor_age']) || $data['visitor_age'] < 1 || $data['visitor_age'] > 120) {
            $errors['visitor_age'] = 'Visitor age must be between 1 and 120';
        }
        
        // Validate contact number
        if (empty($data['contact_number'])) {
            $errors['contact_number'] = 'Contact number is required';
        } elseif (!preg_match('/^[0-9+\-\s()]+$/', $data['contact_number'])) {
            $errors['contact_number'] = 'Invalid contact number format';
        }
        
        // Validate visitor address
        if (empty($data['visitor_address'])) {
            $errors['visitor_address'] = 'Visitor address is required';
        } elseif (strlen($data['visitor_address']) < 10) {
            $errors['visitor_address'] = 'Visitor address must be at least 10 characters long';
        } elseif (strlen($data['visitor_address']) > 500) {
            $errors['visitor_address'] = 'Visitor address must not exceed 500 characters';
        }
        
        // Validate reason of visit
        if (empty($data['reason_of_visit'])) {
            $errors['reason_of_visit'] = 'Reason of visit is required';
        } elseif (!in_array($data['reason_of_visit'], ['Project', 'Activities', 'Friends', 'Family', 'Study Group', 'Meeting', 'Personal', 'Other'])) {
            $errors['reason_of_visit'] = 'Invalid reason of visit selected';
        }
        
        return $errors;
    }
    
    /**
     * Sanitize input data
     */
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate email format
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate phone number format
     */
    public static function validatePhone($phone) {
        return preg_match('/^[0-9+\-\s()]+$/', $phone);
    }
    
    /**
     * Check if user has permission to perform action
     */
    public static function checkPermission($action, $user_id, $resource_id = null) {
        // Add permission checks here
        return true; // Placeholder
    }
    
    /**
     * Rate limiting for form submissions
     */
    public static function checkRateLimit($action, $user_id, $limit = 5, $timeframe = 300) {
        $pdo = getConnection();
        
        // Special handling for maintenance requests - check for duplicate submissions instead of strict rate limiting
        if ($action === 'maintenance_request') {
            // Check if there's a very recent duplicate submission (within 10 seconds)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM form_submissions 
                WHERE user_id = ? AND action = ? AND created_at > DATE_SUB(NOW(), INTERVAL 10 SECOND)");
            $stmt->execute([$user_id, $action]);
            $recent_count = $stmt->fetchColumn();
            
            if ($recent_count > 0) {
                return false; // Prevent duplicate submissions within 10 seconds
            }
            
            // Allow up to 50 maintenance requests per hour (more reasonable for maintenance issues)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM form_submissions 
                WHERE user_id = ? AND action = ? AND created_at > DATE_SUB(NOW(), INTERVAL 3600 SECOND)");
            $stmt->execute([$user_id, $action]);
            $hourly_count = $stmt->fetchColumn();
            
            if ($hourly_count >= 50) {
                return false; // Prevent spam - max 50 requests per hour
            }
        } else {
            // Default rate limiting for other actions
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM form_submissions 
                WHERE user_id = ? AND action = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)");
            $stmt->execute([$user_id, $action, $timeframe]);
            $count = $stmt->fetchColumn();
            
            if ($count >= $limit) {
                return false;
            }
        }
        
        // Record this submission
        $stmt = $pdo->prepare("INSERT INTO form_submissions (user_id, action, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$user_id, $action]);
        
        return true;
    }
}

/**
 * Error handling class
 */
class StudentErrorHandler {
    
    /**
     * Log error
     */
    public static function logError($message, $context = []) {
        error_log("Student Error: " . $message . " Context: " . json_encode($context));
    }
    
    /**
     * Handle database errors
     */
    public static function handleDatabaseError($e, $action = 'database operation') {
        self::logError("Database error during $action: " . $e->getMessage());
        return "An error occurred while processing your request. Please try again later.";
    }
    
    /**
     * Handle validation errors
     */
    public static function handleValidationErrors($errors) {
        $message = "Please correct the following errors:\n";
        foreach ($errors as $field => $error) {
            $message .= "• " . ucfirst($field) . ": " . $error . "\n";
        }
        return $message;
    }
    
    /**
     * Handle permission errors
     */
    public static function handlePermissionError($action) {
        return "You don't have permission to perform this action: $action";
    }
    
    /**
     * Handle rate limit errors
     */
    public static function handleRateLimitError($action) {
        if ($action === 'maintenance request submission') {
            return "Please wait a moment before submitting another maintenance request. You can submit multiple requests, but please avoid rapid duplicate submissions.";
        }
        return "You have exceeded the rate limit for $action. Please wait before trying again.";
    }
}

/**
 * Success message handler
 */
class StudentSuccessHandler {
    
    /**
     * Set success message
     */
    public static function setSuccess($message) {
        $_SESSION['success'] = $message;
    }
    
    /**
     * Set error message
     */
    public static function setError($message) {
        $_SESSION['error'] = $message;
    }
    
    /**
     * Get and clear success message
     */
    public static function getSuccess() {
        $message = $_SESSION['success'] ?? null;
        unset($_SESSION['success']);
        return $message;
    }
    
    /**
     * Get and clear error message
     */
    public static function getError() {
        $message = $_SESSION['error'] ?? null;
        unset($_SESSION['error']);
        return $message;
    }
}
?>
