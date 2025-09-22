<?php
require_once '../config/database.php';
requireAdmin();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Admin Dashboard'; ?> - Dormitory Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
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
        
        /* Custom scrollbar styling for sidebar */
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }
        
        .sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
        }
        
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
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
        
        /* Prevent scroll behavior */
        html {
            scroll-behavior: auto !important;
        }
        
        body {
            scroll-behavior: auto !important;
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
        
        .stats-card-modern {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            transition: all 0.3s;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .clickable-card {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .clickable-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            border-color: var(--primary-color);
        }
        
        .clickable-card:hover .stats-icon-modern {
            transform: scale(1.1);
        }
        
        .clickable-card:hover h3 {
            color: var(--primary-color) !important;
        }
        
        .stats-card-modern:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border-color: #dee2e6;
        }
        
        .stats-icon {
            font-size: 3rem;
            opacity: 0.8;
        }
        
        .stats-icon-modern {
            font-size: 2.5rem;
            opacity: 0.7;
        }
        
        .chart-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
        }
        
        .chart-card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .chart-card .card-header {
            background: white;
            border-bottom: 1px solid #e9ecef;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
        }
        
        .chart-card .card-header h5 {
            color: #495057;
            font-weight: 600;
        }
        
        .avatar-sm {
            width: 40px;
            height: 40px;
        }
        
        .avatar-lg {
            width: 60px;
            height: 60px;
        }
        
        .bg-gradient-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        }
        
        .bg-gradient-info {
            background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);
        }
        
        .bg-gradient-warning {
            background: linear-gradient(135deg, #ffc107 0%, #ff8f00 100%);
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.05);
        }
        
        .rounded-pill {
            border-radius: 50rem !important;
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
            
            .stats-card-modern {
                padding: 20px;
                margin-bottom: 15px;
            }
            
            .stats-icon-modern {
                font-size: 2rem;
            }
            
            .chart-card .card-body {
                padding: 15px;
            }
            
            .content-wrapper {
                padding: 0 15px 15px 15px;
            }
            
            .top-navbar {
                padding: 10px 15px;
                margin-bottom: 20px;
            }
        }
        
        @media (max-width: 576px) {
            .stats-card-modern h3 {
                font-size: 1.5rem;
            }
            
            .stats-card-modern p {
                font-size: 0.9rem;
            }
            
            .stats-card-modern small {
                font-size: 0.8rem;
            }
            
            .chart-card .card-header h5 {
                font-size: 1rem;
            }
            
            .chart-card .card-header {
                padding: 15px;
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
        
        .dropdown {
            position: relative;
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
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            z-index: 1000;
            min-width: 200px;
        }
        
        .dropdown-menu.show {
            display: block;
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
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h4><i class="fas fa-building"></i> Dormitory</h4>
            <p class="mb-0">Management System</p>
        </div>
        <ul class="sidebar-menu">
            <li><a href="javascript:void(0)" data-page="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a></li>
            <li><a href="javascript:void(0)" data-page="offense_logs.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'offense_logs.php' ? 'active' : ''; ?>">
                <i class="fas fa-exclamation-triangle"></i> Offense Logs
            </a></li>
            <li><a href="javascript:void(0)" data-page="announcements.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'announcements.php' ? 'active' : ''; ?>">
                <i class="fas fa-bullhorn"></i> Announcements
            </a></li>
            <li><a href="javascript:void(0)" data-page="maintenance_requests.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'maintenance_requests.php' ? 'active' : ''; ?>">
                <i class="fas fa-tools"></i> Maintenance Requests
            </a></li>
            <li><a href="javascript:void(0)" data-page="room_requests.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'room_requests.php' ? 'active' : ''; ?>">
                <i class="fas fa-exchange-alt"></i> Room Request Approval
            </a></li>
            <li><a href="javascript:void(0)" data-page="biometric_management.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'biometric_management.php' ? 'active' : ''; ?>">
                <i class="fas fa-fingerprint"></i> Biometric Management
            </a></li>
            <li><a href="javascript:void(0)" data-page="student_location_logs.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'student_location_logs.php' ? 'active' : ''; ?>">
                <i class="fas fa-map-marker-alt"></i> Student Location Logs
            </a></li>
            <li><a href="javascript:void(0)" data-page="visitor_logs.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'visitor_logs.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> Visitor Logs (View Only)
            </a></li>
            <li><a href="javascript:void(0)" data-page="room_management.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'room_management.php' ? 'active' : ''; ?>">
                <i class="fas fa-bed"></i> Room Management
            </a></li>
            <li><a href="javascript:void(0)" data-page="reservation_management.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'reservation_management.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check"></i> Online Reservations
            </a></li>
            <li><a href="javascript:void(0)" data-page="policies_management.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'policies_management.php' ? 'active' : ''; ?>">
                <i class="fas fa-file-alt"></i> Policies Management
            </a></li>
            <li><a href="javascript:void(0)" data-page="complaints_management.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'complaints_management.php' ? 'active' : ''; ?>">
                <i class="fas fa-comment-alt"></i> Complaints Management
            </a></li>
            <li><a href="javascript:void(0)" data-page="system_backup.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'system_backup.php' ? 'active' : ''; ?>">
                <i class="fas fa-database"></i> System Backup
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
            <div class="dropdown">
                <button class="oval-button oval-button-primary dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown">
                    <i class="fas fa-user-circle"></i> <?php echo $_SESSION['username']; ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="admin_settings.php">
                        <i class="fas fa-cog"></i> Admin Settings
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="../logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a></li>
                </ul>
            </div>
        </nav>

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <!-- Main content will be loaded here -->