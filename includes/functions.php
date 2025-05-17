<?php
require_once 'config.php';
require_once 'db.php';

/**
 * Send welcome email to new staff members
 */
function sendStaffWelcomeEmail($email, $username, $password, $full_name) {
    $subject = APP_NAME . ' - Welcome!';
    
    $message = "
    <html>
    <body>
        <h2>Welcome to " . APP_NAME . "</h2>
        <p>Dear {$full_name},</p>
        <p>Your account has been created successfully. Here are your login credentials:</p>
        <p><strong>Username:</strong> {$username}</p>
        <p><strong>Password:</strong> {$password}</p>
        <p>Please login and change your password as soon as possible.</p>
        <p>Best regards,<br>The " . APP_NAME . " Team</p>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: ' . ADMIN_EMAIL . "\r\n";
    
    return mail($email, $subject, $message, $headers);
}

/**
 * Generate default password
 */
function generateRandomPassword($length = 6) {
    return "staff123";
}

/**
 * Format date for display
 */
function formatDate($dateString, $format = 'M j, Y') {
    $date = new DateTime($dateString);
    return $date->format($format);
}

/**
 * Check if a string contains HTML
 */
function containsHTML($string) {
    return $string !== strip_tags($string);
}

/**
 * Calculate time elapsed since a given datetime
 */
function time_elapsed($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = [
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    ];
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}