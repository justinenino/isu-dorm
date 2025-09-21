<?php
require_once '../config/database.php';
requireStudent();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Student Dashboard'; ?> - Dormitory Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="includes/mobile-responsive.css" rel="stylesheet">
    
    <!-- Load jQuery first -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --accent-color: #f093fb;
            --success-color: #4facfe;
            --warning-color: #43e97b;
            --danger-color: #fa709a;
            --info-color: #00f2fe;
            --dark-color: #2c3e50;
            --light-color: #f8f9fa;
            --sidebar-width: 280px;
            --border-radius: 12px;
            --border-radius-lg: 20px;
            --shadow-soft: 0 4px 20px rgba(0, 0, 0, 0.08);
            --shadow-medium: 0 8px 30px rgba(0, 0, 0, 0.12);
            --shadow-strong: 0 15px 40px rgba(0, 0, 0, 0.15);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --gradient-primary: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            --gradient-success: linear-gradient(135deg, var(--success-color) 0%, var(--info-color) 100%);
            --gradient-warning: linear-gradient(135deg, var(--warning-color) 0%, #38f9d7 100%);
            --gradient-danger: linear-gradient(135deg, var(--danger-color) 0%, #fee140 100%);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 10% 20%, rgba(102, 126, 234, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 90% 80%, rgba(118, 75, 162, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 50% 50%, rgba(240, 147, 251, 0.03) 0%, transparent 50%);
            pointer-events: none;
            z-index: -1;
        }
        
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: var(--gradient-primary);
            color: white;
            z-index: 1000;
            overflow-y: auto;
            transition: var(--transition);
            box-shadow: var(--shadow-strong);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-header {
            padding: 25px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
        }

        .sidebar-header h4 {
            font-weight: 700;
            font-size: 1.3rem;
            margin-bottom: 5px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .sidebar-header p {
            font-size: 0.9rem;
            opacity: 0.8;
            margin: 0;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-menu li {
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            position: relative;
        }

        .sidebar-menu li::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 3px;
            background: rgba(255, 255, 255, 0.3);
            transform: scaleY(0);
            transition: var(--transition);
        }

        .sidebar-menu li:hover::before,
        .sidebar-menu li.active::before {
            transform: scaleY(1);
        }
        
        .sidebar-menu a {
            display: block;
            padding: 18px 25px;
            color: white;
            text-decoration: none;
            transition: var(--transition);
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }

        .sidebar-menu a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s;
        }

        .sidebar-menu a:hover::before {
            left: 100%;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.15);
            padding-left: 35px;
            transform: translateX(5px);
        }

        .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.2);
            font-weight: 600;
        }
        
        .sidebar-menu i {
            width: 20px;
            margin-right: 10px;
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }
        
        .top-navbar {
            background: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 15px 30px;
            margin-bottom: 30px;
        }
        
        .content-wrapper {
            padding: 0 30px 30px 30px;
        }
        
        .card {
            border: none;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-soft);
            margin-bottom: 30px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: var(--transition);
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
        }

        .card-header {
            background: var(--gradient-primary);
            color: white;
            border: none;
            padding: 20px 25px;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .card-body {
            padding: 25px;
        }

        .card-footer {
            background: rgba(248, 249, 250, 0.8);
            border: none;
            padding: 20px 25px;
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
        }
        
        .stats-card {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        .stats-icon {
            font-size: 3rem;
            opacity: 0.8;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
        }
        
        /* Oval Button Styles */
        .oval-button {
            border-radius: 50px;
            padding: 8px 20px;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
        }
        
        .oval-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .oval-button:hover::before {
            left: 100%;
        }
        
        .oval-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .oval-button-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
        }
        
        .oval-button-primary:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
            color: white;
        }
        
        .oval-button-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
        }
        
        .oval-button-secondary:hover {
            background: linear-gradient(135deg, #5a6268 0%, #3d4449 100%);
            color: white;
        }
        
        .oval-button-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }
        
        .oval-button-danger:hover {
            background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
            color: white;
        }
        
        .profile-buttons {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .profile-buttons .oval-button {
            white-space: nowrap;
        }
        
        .oval-button.dropdown-toggle::after {
            margin-left: 8px;
            border-top: 0.3em solid;
            border-right: 0.3em solid transparent;
            border-bottom: 0;
            border-left: 0.3em solid transparent;
        }
        
        .dropdown-menu {
            border: none;
            border-radius: 12px;
            box-shadow: var(--shadow-medium);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .dropdown-item {
            padding: 12px 20px;
            transition: var(--transition);
            border-radius: 8px;
            margin: 4px 8px;
        }
        
        .dropdown-item:hover {
            background: var(--gradient-primary);
            color: white;
            transform: translateX(5px);
        }
        
        .dropdown-item i {
            width: 16px;
            margin-right: 8px;
        }
        
        @media (max-width: 768px) {
            .profile-buttons {
                flex-direction: column;
                gap: 8px;
            }
            
            .oval-button {
                padding: 6px 16px;
                font-size: 0.8rem;
            }
        }
        
        /* Notification Styles */
        .notification-dropdown {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
        }
        
        .notification-item {
            padding: 12px 16px;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .notification-item:hover {
            background-color: #f8f9fa;
        }
        
        .notification-item.unread {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
        }
        
        .notification-time {
            font-size: 0.75rem;
            color: #6c757d;
        }
        
        .notification-badge {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
    </style>
    
    <script>
    // Notification System
    let notificationCount = 0;
    
    // Simulate notifications (in a real app, this would come from the server)
    function loadNotifications() {
        // This would typically make an AJAX call to get notifications
        // For demo purposes, we'll simulate some notifications
        const notifications = [
            {
                id: 1,
                title: "New Announcement",
                message: "Important dormitory maintenance scheduled for tomorrow",
                time: "2 minutes ago",
                type: "announcement",
                unread: true
            },
            {
                id: 2,
                title: "Room Request Update",
                message: "Your room change request has been approved",
                time: "1 hour ago",
                type: "room_request",
                unread: true
            },
            {
                id: 3,
                title: "Maintenance Complete",
                message: "Your maintenance request has been completed",
                time: "3 hours ago",
                type: "maintenance",
                unread: false
            }
        ];
        
        displayNotifications(notifications);
    }
    
    function displayNotifications(notifications) {
        const notificationList = document.getElementById('notificationList');
        const notificationBadge = document.getElementById('notificationBadge');
        
        if (notifications.length === 0) {
            notificationList.innerHTML = '<li class="dropdown-item text-center text-muted py-3"><i class="fas fa-bell-slash me-2"></i>No new notifications</li>';
            notificationBadge.style.display = 'none';
            return;
        }
        
        const unreadCount = notifications.filter(n => n.unread).length;
        notificationCount = unreadCount;
        
        if (unreadCount > 0) {
            notificationBadge.textContent = unreadCount;
            notificationBadge.style.display = 'block';
            notificationBadge.classList.add('notification-badge');
        } else {
            notificationBadge.style.display = 'none';
        }
        
        let html = '';
        notifications.forEach(notification => {
            const iconClass = getNotificationIcon(notification.type);
            const unreadClass = notification.unread ? 'unread' : '';
            
            html += `
                <li class="notification-item ${unreadClass}" onclick="markAsRead(${notification.id})">
                    <div class="d-flex align-items-start">
                        <div class="me-3">
                            <i class="${iconClass} text-primary"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">${notification.title}</h6>
                            <p class="mb-1 text-muted small">${notification.message}</p>
                            <small class="notification-time">${notification.time}</small>
                        </div>
                        ${notification.unread ? '<div class="ms-2"><span class="badge bg-primary rounded-pill" style="width: 8px; height: 8px;"></span></div>' : ''}
                    </div>
                </li>
            `;
        });
        
        notificationList.innerHTML = html;
    }
    
    function getNotificationIcon(type) {
        switch(type) {
            case 'announcement': return 'fas fa-bullhorn';
            case 'room_request': return 'fas fa-exchange-alt';
            case 'maintenance': return 'fas fa-tools';
            case 'complaint': return 'fas fa-comment-alt';
            default: return 'fas fa-bell';
        }
    }
    
    function markAsRead(notificationId) {
        // This would typically make an AJAX call to mark the notification as read
        console.log('Marking notification as read:', notificationId);
        
        // Update the UI
        const notificationItem = document.querySelector(`[onclick="markAsRead(${notificationId})"]`);
        if (notificationItem) {
            notificationItem.classList.remove('unread');
            const badge = notificationItem.querySelector('.badge');
            if (badge) {
                badge.remove();
            }
            
            // Update count
            notificationCount--;
            updateNotificationBadge();
        }
    }
    
    function markAllAsRead() {
        // This would typically make an AJAX call to mark all notifications as read
        console.log('Marking all notifications as read');
        
        // Update the UI
        const unreadItems = document.querySelectorAll('.notification-item.unread');
        unreadItems.forEach(item => {
            item.classList.remove('unread');
            const badge = item.querySelector('.badge');
            if (badge) {
                badge.remove();
            }
        });
        
        notificationCount = 0;
        updateNotificationBadge();
    }
    
    function updateNotificationBadge() {
        const notificationBadge = document.getElementById('notificationBadge');
        if (notificationCount > 0) {
            notificationBadge.textContent = notificationCount;
            notificationBadge.style.display = 'block';
        } else {
            notificationBadge.style.display = 'none';
        }
    }
    
    // Load notifications when the page loads
    document.addEventListener('DOMContentLoaded', function() {
        loadNotifications();
        
        // Refresh notifications every 30 seconds
        setInterval(loadNotifications, 30000);
    });
    </script>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h4><i class="fas fa-user-graduate"></i> Student</h4>
            <p class="mb-0">Portal</p>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a></li>
            <li><a href="announcements.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'announcements.php' ? 'active' : ''; ?>">
                <i class="fas fa-bullhorn"></i> Announcements
            </a></li>
            <li><a href="maintenance_requests.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'maintenance_requests.php' ? 'active' : ''; ?>">
                <i class="fas fa-tools"></i> Maintenance Requests
            </a></li>
            <li><a href="room_requests.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'room_requests.php' ? 'active' : ''; ?>">
                <i class="fas fa-exchange-alt"></i> Room Change Request
            </a></li>
            <li><a href="biometric_logs.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'biometric_logs.php' ? 'active' : ''; ?>">
                <i class="fas fa-fingerprint"></i> Biometric Logs
            </a></li>
            <li><a href="visitor_logs.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'visitor_logs.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> Visitor Registration
            </a></li>
            <li><a href="complaints.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'complaints.php' ? 'active' : ''; ?>">
                <i class="fas fa-comment-alt"></i> Submit Complaint
            </a></li>
            <li><a href="offense_records.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'offense_records.php' ? 'active' : ''; ?>">
                <i class="fas fa-exclamation-triangle"></i> Offense Records
            </a></li>
            <li><a href="dorm_policies.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dorm_policies.php' ? 'active' : ''; ?>">
                <i class="fas fa-file-alt"></i> Dorm Policies
            </a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <nav class="top-navbar d-flex justify-content-between align-items-center">
            <div>
                <button class="btn btn-link d-md-none" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h5 class="mb-0"><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h5>
            </div>
            <div class="d-flex align-items-center gap-3">
                <!-- Notification Bell -->
                <div class="dropdown">
                    <button class="btn btn-outline-primary position-relative" type="button" id="notificationDropdown" data-bs-toggle="dropdown">
                        <i class="fas fa-bell"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notificationBadge" style="display: none;">
                            0
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end notification-dropdown" style="width: 350px; max-height: 400px; overflow-y: auto;">
                        <li class="dropdown-header d-flex justify-content-between align-items-center">
                            <span>Notifications</span>
                            <button class="btn btn-sm btn-outline-secondary" onclick="markAllAsRead()">Mark all as read</button>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <div id="notificationList">
                            <li class="dropdown-item text-center text-muted py-3">
                                <i class="fas fa-bell-slash me-2"></i>No new notifications
                            </li>
                        </div>
                    </ul>
                </div>
                
                <!-- User Dropdown -->
                <div class="dropdown">
                    <button class="oval-button oval-button-primary dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle"></i> <?php echo $_SESSION['student_name']; ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="../logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Content Wrapper -->
        <div class="content-wrapper">