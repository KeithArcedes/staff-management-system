<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Redirect logged in users to their dashboard
if (isLoggedIn()) {
    header("Location: " . (isAdmin() ? 'admin/dashboard.php' : 'staff/dashboard.php'));
    exit();
}

$page_title = 'Welcome';
include 'includes/login_header.php';
?>

<style>
    .gradient-background {
        background: linear-gradient(135deg, #0061f2 0%, #6610f2 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .hero-content {
        text-align: center;
        color: white;
        padding: 2rem;
    }
    .hero-title {
        font-size: 3.5rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
    }
    .hero-subtitle {
        font-size: 1.25rem;
        margin-bottom: 2rem;
        opacity: 0.9;
    }
    .cta-button {
        padding: 1rem 2.5rem;
        font-size: 1.1rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    .cta-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }
</style>

<div class="gradient-background">
    <div class="hero-content">
        <h1 class="hero-title"><?php echo APP_NAME; ?></h1>
        <p class="hero-subtitle">Staff Management and Attendance System</p>
        <a href="login.php" class="btn btn-light cta-button">
            <i class="fas fa-sign-in-alt me-2"></i>Login
        </a>
    </div>
</div>
