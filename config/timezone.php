<?php
/**
 * Timezone Configuration
 * Centralized timezone settings for the dormitory management system
 */

// Set default timezone to Philippines (Asia/Manila)
// This ensures all date/time functions use the correct timezone
date_default_timezone_set('Asia/Manila');

// Define timezone constants for easy reference
define('SYSTEM_TIMEZONE', 'Asia/Manila');
define('SYSTEM_TIMEZONE_OFFSET', '+08:00');

// Helper function to get current time in system timezone
function getCurrentTime($format = 'Y-m-d H:i:s') {
    return date($format);
}

// Helper function to get current timestamp
function getCurrentTimestamp() {
    return time();
}

// Helper function to format time for display
function formatTime($timestamp, $format = 'M j, Y g:i A') {
    return date($format, $timestamp);
}

// Helper function to get timezone info
function getTimezoneInfo() {
    return [
        'timezone' => date_default_timezone_get(),
        'offset' => date('P'),
        'current_time' => getCurrentTime(),
        'timestamp' => getCurrentTimestamp()
    ];
}

// Helper function to convert UTC to local time
function convertUTCToLocal($utc_time, $format = 'Y-m-d H:i:s') {
    $utc = new DateTime($utc_time, new DateTimeZone('UTC'));
    $utc->setTimezone(new DateTimeZone(SYSTEM_TIMEZONE));
    return $utc->format($format);
}

// Helper function to convert local time to UTC
function convertLocalToUTC($local_time, $format = 'Y-m-d H:i:s') {
    $local = new DateTime($local_time, new DateTimeZone(SYSTEM_TIMEZONE));
    $local->setTimezone(new DateTimeZone('UTC'));
    return $local->format($format);
}

// Helper function to get relative time (e.g., "2 hours ago")
function getRelativeTime($timestamp) {
    $time = time() - $timestamp;
    
    if ($time < 60) {
        return 'just now';
    } elseif ($time < 3600) {
        $minutes = floor($time / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($time < 86400) {
        $hours = floor($time / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($time < 2592000) {
        $days = floor($time / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return formatTime($timestamp);
    }
}

// Helper function to validate timezone
function isValidTimezone($timezone) {
    return in_array($timezone, timezone_identifiers_list());
}

// Helper function to get timezone list
function getTimezoneList($region = null) {
    if ($region) {
        return timezone_identifiers_list($region);
    }
    return timezone_identifiers_list();
}

// Auto-include this file in all pages that need timezone support
// This ensures consistent timezone handling across the application
?>
