<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php');
}

// Get filters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$building_filter = isset($_GET['building']) ? sanitizeInput($_GET['building']) : '';

try {
    $pdo = getDBConnection();
    
    // Build query with filters
    $where_conditions = [];
    $params = [];
    
    if ($search) {
        $where_conditions[] = "(v.visitor_name LIKE ? OR v.contact_number LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ?)";
        $search_param = "%$search%";
        $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    }
    
    if ($status_filter) {
        if ($status_filter === 'checked_in') {
            $where_conditions[] = "v.time_out IS NULL";
        } elseif ($status_filter === 'checked_out') {
            $where_conditions[] = "v.time_out IS NOT NULL";
        }
    }
    
    if ($building_filter) {
        $where_conditions[] = "b.building_id = ?";
        $params[] = $building_filter;
    }
    
    $where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $query = "
        SELECT v.visitor_name, v.age, v.address, v.contact_number, v.purpose,
               v.time_in, v.time_out, v.notes,
               s.first_name, s.last_name, s.student_id as host_student_id,
               r.room_number, b.building_name
        FROM visitors v
        JOIN students s ON v.student_id = s.user_id
        JOIN rooms r ON v.room_id = r.room_id
        JOIN buildings b ON r.building_id = b.building_id
        $where_clause
        ORDER BY v.time_in DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}

// Set headers for CSV download
$filename = 'visitors_log_' . date('Y-m-d_H-i-s') . '.csv';
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for proper Excel display
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV headers
$headers = [
    'Visitor Name',
    'Age',
    'Address',
    'Contact Number',
    'Purpose',
    'Time In',
    'Time Out',
    'Status',
    'Duration (hours)',
    'Host Student Name',
    'Host Student ID',
    'Room Number',
    'Building',
    'Notes'
];

fputcsv($output, $headers);

// Add data rows
foreach ($visitors as $visitor) {
    // Calculate status and duration
    $status = $visitor['time_out'] ? 'Checked Out' : 'Currently Inside';
    
    if ($visitor['time_out']) {
        $duration = (strtotime($visitor['time_out']) - strtotime($visitor['time_in'])) / 3600;
        $duration_formatted = round($duration, 2);
    } else {
        $duration = (time() - strtotime($visitor['time_in'])) / 3600;
        $duration_formatted = round($duration, 2) . ' (ongoing)';
    }
    
    $row = [
        $visitor['visitor_name'],
        $visitor['age'],
        $visitor['address'],
        $visitor['contact_number'],
        $visitor['purpose'] ?? 'Not specified',
        formatDate($visitor['time_in'], 'Y-m-d H:i:s'),
        $visitor['time_out'] ? formatDate($visitor['time_out'], 'Y-m-d H:i:s') : 'N/A',
        $status,
        $duration_formatted,
        $visitor['first_name'] . ' ' . $visitor['last_name'],
        $visitor['host_student_id'],
        $visitor['room_number'],
        $visitor['building_name'],
        $visitor['notes'] ?? ''
    ];
    
    fputcsv($output, $row);
}

// Close the file
fclose($output);

// Log the export activity
logActivity($_SESSION['user_id'], "Exported visitors log with filters: " . 
           ($search ? "search='$search' " : '') . 
           ($status_filter ? "status='$status_filter' " : '') . 
           ($building_filter ? "building='$building_filter'" : ''));

exit;
?>
