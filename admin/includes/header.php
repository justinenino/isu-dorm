<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-building fa-2x" style="color: var(--primary-green);"></i>
                <h1><?php echo SITE_NAME; ?></h1>
            </div>
            
            <div class="d-flex align-items-center gap-3">
                <!-- Notifications -->
                <div class="dropdown">
                    <button class="btn btn-outline-primary position-relative" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-bell"></i>
                        <?php
                        $notification_count = fetchOne("
                            SELECT COUNT(*) as count FROM (
                                SELECT 'students' as type, COUNT(*) as count FROM students WHERE registration_status = 'pending'
                                UNION ALL
                                SELECT 'reservations' as type, COUNT(*) as count FROM reservations WHERE status = 'pending'
                                UNION ALL
                                SELECT 'maintenance' as type, COUNT(*) as count FROM maintenance_requests WHERE status = 'pending'
                                UNION ALL
                                SELECT 'offenses' as type, COUNT(*) as count FROM offenses WHERE status = 'pending'
                            ) as combined
                        ")['count'];
                        ?>
                        <?php if ($notification_count > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $notification_count; ?>
                            </span>
                        <?php endif; ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header">Notifications</h6></li>
                        <?php if ($notification_count > 0): ?>
                            <li><a class="dropdown-item" href="students/pending.php">
                                <i class="fas fa-user-clock text-warning me-2"></i>
                                <?php echo fetchOne("SELECT COUNT(*) as count FROM students WHERE registration_status = 'pending'")['count']; ?> pending students
                            </a></li>
                            <li><a class="dropdown-item" href="reservations/pending.php">
                                <i class="fas fa-calendar-clock text-info me-2"></i>
                                <?php echo fetchOne("SELECT COUNT(*) as count FROM reservations WHERE status = 'pending'")['count']; ?> pending reservations
                            </a></li>
                            <li><a class="dropdown-item" href="maintenance/pending.php">
                                <i class="fas fa-tools text-warning me-2"></i>
                                <?php echo fetchOne("SELECT COUNT(*) as count FROM maintenance_requests WHERE status = 'pending'")['count']; ?> maintenance requests
                            </a></li>
                            <li><a class="dropdown-item" href="offenses/pending.php">
                                <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                                <?php echo fetchOne("SELECT COUNT(*) as count FROM offenses WHERE status = 'pending'")['count']; ?> pending offenses
                            </a></li>
                        <?php else: ?>
                            <li><span class="dropdown-item-text text-muted">No new notifications</span></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="notifications.php">View all notifications</a></li>
                    </ul>
                </div>
                
                <!-- User Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-outline-success dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-2"></i><?php echo $_SESSION['full_name']; ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php">
                            <i class="fas fa-user-circle me-2"></i>Profile
                        </a></li>
                        <li><a class="dropdown-item" href="change-password.php">
                            <i class="fas fa-key me-2"></i>Change Password
                        </a></li>
                        <li><a class="dropdown-item" href="settings.php">
                            <i class="fas fa-cog me-2"></i>Settings
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a></li>
                    </ul>
                </div>
                
                <!-- Mobile Menu Toggle -->
                <button class="btn btn-outline-primary d-lg-none" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-shield-alt me-2"></i>Admin Panel</h3>
        </div>
        
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="buildings.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'buildings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-building"></i>
                    <span>Buildings & Rooms</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="reservations.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'reservations.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check"></i>
                    <span>Reservations</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="students.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'students.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-graduate"></i>
                    <span>Students</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="visitors.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'visitors.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-friends"></i>
                    <span>Visitors Logs</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="announcements.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'announcements.php' ? 'active' : ''; ?>">
                    <i class="fas fa-bullhorn"></i>
                    <span>Announcements</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="biometrics.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'biometrics.php' ? 'active' : ''; ?>">
                    <i class="fas fa-fingerprint"></i>
                    <span>Biometrics</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="maintenance.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'maintenance.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tools"></i>
                    <span>Maintenance</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="offenses.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'offenses.php' ? 'active' : ''; ?>">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Offenses</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="complaints.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'complaints.php' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Complaints</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="policies.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'policies.php' ? 'active' : ''; ?>">
                    <i class="fas fa-book"></i>
                    <span>Policies</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="room_transfers.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'room_transfers.php' ? 'active' : ''; ?>">
                    <i class="fas fa-exchange-alt"></i>
                    <span>Room Transfers</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="settings.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </li>
            
            <li class="nav-item mt-4">
                <a href="../logout.php" class="nav-link text-danger">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Main Content Wrapper -->
    <div class="main-content" id="mainContent">
        <!-- Page content will be inserted here -->
