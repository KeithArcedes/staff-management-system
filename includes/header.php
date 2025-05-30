<?php
require_once __DIR__ . '/functions.php';

// Add this near the top of the file after session_start()
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' | ' : ''; ?><?php echo APP_NAME; ?></title>
    <!-- Local Bootstrap 5.3 CSS -->
    <link href="<?php echo APP_URL; ?>../assets/styles/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/styles/styles.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        #sidebar {
            min-width: 250px;
            max-width: 250px;
            min-height: 100vh;
            background: #f8f9fa;
            position: fixed;
            left: 0;
            top: 0;
            padding: 20px;
            transition: all 0.3s;
            z-index: 1000;
        }
        #content {
            margin-left: 250px;
            padding: 20px;
            width: calc(100% - 250px);
            transition: all 0.3s;
        }
        #sidebarCollapse {
            position: fixed;
            top: 10px;
            left: 10px;
            z-index: 1001;
            display: none;
        }
        @media (max-width: 768px) {
            #sidebar {
                margin-left: -250px;
            }
            #sidebar.active {
                margin-left: 0;
            }
            #content {
                margin-left: 0;
                width: 100%;
            }
            #sidebarCollapse {
                display: block;
            }
            #content.active {
                margin-left: 250px;
            }
        }
    </style>
</head>
<body>
    <!-- Add Toggle Button -->
    <button type="button" id="sidebarCollapse" class="btn btn-primary">
        <i class="fas fa-bars"></i>
    </button>

    <div class="d-flex">
        <!-- Sidebar -->
        <nav id="sidebar" class="shadow-sm">
            <div class="mb-4 text-center">
                <h6><?php echo APP_NAME; ?></h6>
            </div>
            <ul class="nav flex-column">
                <?php if (isLoggedIn()): ?>
                    <li class="nav-item mb-2">
                        <span class="nav-link text-dark">
                            <i class="fas fa-user me-2"></i>
                            <?php echo $_SESSION['full_name']; ?>
                        </span>
                    </li>
                    <?php if (isAdmin()): ?>
                        <li class="nav-item mb-2">
                            <a class="nav-link" href="<?php echo APP_URL; ?>/admin/dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link" href="<?php echo APP_URL; ?>/admin/staff/view.php">
                                <i class="fas fa-users me-2"></i>Manage Staff
                            </a>
                        </li>
                         <li class="nav-item mb-2">
                            <a class="nav-link" href="<?php echo APP_URL; ?>/admin/payroll/view.php">
                                <i class="fa-solid fa-money-bill"></i> Payroll
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item mb-2">
                            <a class="nav-link" href="<?php echo APP_URL; ?>/staff/dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link" href="<?php echo APP_URL; ?>/staff/profile.php">
                                <i class="fas fa-user me-2"></i> Profile
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/login.php">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>

        <!-- Page Content -->
        <div id="content">
            <?php include 'flash.php'; ?>