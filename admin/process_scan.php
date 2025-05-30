<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
redirectIfNotAdmin();

$db = new Database();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qr_data'])) {
    $qr_data = trim($_POST['qr_data']);
    
    // Debug logging
    error_log("Scanned QR Data: " . $qr_data);
    
    // Extract user ID from QR code filename format (staff_ID_timestamp.png)
    if (preg_match('/^STAFF-(\d+)-[A-Fa-f0-9]+$/', $qr_data, $matches)) {
        $user_id = $matches[1];

         
        
        // Check if user exists and is active
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = ? AND is_active = 1");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Check if already checked in today
            $attendance = $conn->prepare("
                SELECT * FROM attendance 
                WHERE user_id = ? AND DATE(check_in) = CURDATE() 
                ORDER BY check_in DESC 
                LIMIT 1
            ");
            $attendance->execute([$user_id]);
            $last_attendance = $attendance->fetch(PDO::FETCH_ASSOC);
            
            try {
                if ($last_attendance && !$last_attendance['check_out']) {
                    // Check out
                    $update = $conn->prepare("UPDATE attendance SET check_out = NOW() WHERE attendance_id = ?");
                    $update->execute([$last_attendance['attendance_id']]);
                    $_SESSION['flash']['success'] = 'Successfully checked out at ' . date('h:i A');
                } else {
                    // Check in
                    $insert = $conn->prepare("INSERT INTO attendance (user_id, check_in) VALUES (?, NOW())");
                    $insert->execute([$user_id]);
                    $_SESSION['flash']['success'] = 'Successfully checked in at ' . date('h:i A');
                }
            } catch (PDOException $e) {
                $_SESSION['flash']['danger'] = 'Error processing attendance: ' . $e->getMessage();
            }
        } else {
            $_SESSION['flash']['danger'] = 'Invalid QR code';
        }
    } else {
        $_SESSION['flash']['danger'] = 'Invalid QR code format';
        error_log("Invalid QR format: " . $qr_data);
    }
}

header("Location: scan.php");
exit();