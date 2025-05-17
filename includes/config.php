<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'salary');

// Application Configuration
define('APP_NAME', 'Staff Management System');
define('APP_ROOT', dirname(dirname(__FILE__)));
define('APP_URL', 'http://localhost/qr');

// QR Code Configuration
define('QR_DIR', APP_ROOT . '/qr_codes/');
define('QR_URL', APP_URL . '/qr_codes/');

// Email Configuration
define('ADMIN_EMAIL', 'keith.arcedes@csav.edu.ph');

// Company Information for Payslips
define('COMPANY_NAME', 'Colegio De Sta Ana De Victorias');
define('COMPANY_ADDRESS', 'Victorias City, Philippines');

// Include utility functions
require_once __DIR__ . '/functions.php';