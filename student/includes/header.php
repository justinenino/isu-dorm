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
                <i class="fas fa-user-graduate fa-2x" style="color: var(--primary-green);"></i>
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
                                SELECT 'announcements' as type, COUNT(*) as count FROM announcements 
                                WHERE status = 'published' AND (publish_at IS NULL OR publish_at <= NOW())
                                UNION ALL
                                SELECT 'maintenance' as type, COUNT(*) as count FROM maintenance_requests 
                                WHERE student_id = ? AND status IN ('assigned', 'in_progress')
                            ) as combined
                        ", [$student['id']])['count'];
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
                            <li><a class="dropdown-item" href="announcements.php">
                                <i class="fas fa-bullhorn text-primary me-2"></i>
                                <?php echo fetchOne("SELECT COUNT(*) as count FROM announcements WHERE status = 'published' AND (publish_at IS NULL OR publish_at <= NOW())")['count']; ?> new announcements
                            </a></li>
                            <li><a class="dropdown-item" href="maintenance-requests.php">
                                <i class="fas fa-tools text-warning me-2"></i>
                                <?php echo fetchOne("SELECT COUNT(*) as count FROM maintenance_requests WHERE student_id = ? AND status IN ('assigned', 'in_progress')", [$student['id']])['count']; ?> maintenance updates
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
                            <i class="fas fa-user-circle me-2"></i>My Profile
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
            <h3><i class="fas fa-user-graduate me-2"></i>Student Portal</h3>
        </div>
        
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="profile.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i>
                    <span>My Profile</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="change_password.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'change_password.php' ? 'active' : ''; ?>">
                    <i class="fas fa-key"></i>
                    <span>Change Password</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="settings.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="announcements.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'announcements') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-bullhorn"></i>
                    <span>Announcements</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="reserve_room.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'reserve_room.php' ? 'active' : ''; ?>">
                    <i class="fas fa-bed"></i>
                    <span>Reserve Room / Bedspace</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="my_reservations.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'my_reservations.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check"></i>
                    <span>My Reservations</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="room_transfer.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'room_transfer.php' ? 'active' : ''; ?>">
                    <i class="fas fa-exchange-alt"></i>
                    <span>Room Transfer</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="maintenance-request.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'maintenance-request.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tools"></i>
                    <span>Maintenance Request</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="complaints.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'complaints.php' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Complaints</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="visitor-log.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'visitor-log') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-user-friends"></i>
                    <span>Visitor Log</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="biometrics.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'biometrics') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-fingerprint"></i>
                    <span>Biometrics</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="offense-records.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'offense-records.php' ? 'active' : ''; ?>">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Offense Records</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="policies.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'policies') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-book"></i>
                    <span>Dorm Policies</span>
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
