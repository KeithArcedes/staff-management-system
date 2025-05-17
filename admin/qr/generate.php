<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../vendor/autoload.php';

redirectIfNotAdmin();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../users/index.php");
    exit();
}

$user_id = $_GET['id'];

// Check if user exists (either admin or staff)
$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION['flash']['danger'] = 'User not found';
    header("Location: ../users/index.php");
    exit();
}

// Generate QR code
require_once '../../vendor/autoload.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

// Create QR directory if it doesn't exist
if (!is_dir(QR_DIR)) {
    mkdir(QR_DIR, 0755, true);
}

// Generate unique identifier for QR
$qr_data = 'STAFF-' . $user_id . '-' . bin2hex(random_bytes(8));

// Create QR code
$qrCode = QrCode::create($qr_data)
    ->setSize(300)
    ->setMargin(10);

$writer = new PngWriter();
$result = $writer->write($qrCode);

// Save QR code to file
$filename = 'staff_' . $user_id . '_' . time() . '.png';
$result->saveToFile(QR_DIR . $filename);

// Update user record with QR code and QR data
$update = $conn->prepare("UPDATE users SET qr_code = ? WHERE user_id = ?");
if ($update->execute([$filename, $user_id])) {
    $_SESSION['flash']['success'] = 'QR code generated successfully';
} else {
    $_SESSION['flash']['danger'] = 'Failed to save QR code';
}

// Redirect back to appropriate page
if ($user['role_id'] == 1) { // Admin
    header("Location: ../profile.php");
} else { // Staff
    header("Location: ../staff/view.php?id=" . $user_id);
}
exit();
?>