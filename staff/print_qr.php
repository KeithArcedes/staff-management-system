<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';


$user_id = $_SESSION['user_id'];

$db = new Database();
$conn = $db->getConnection();

// Get admin data
$stmt = $conn->prepare("SELECT full_name, qr_code FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !$user['qr_code']) {
    exit('QR code not found');
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>QR Code - <?php echo htmlspecialchars($user['full_name']); ?></title>
    <style>
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            .qr-container {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
            }
            .qr-code {
                max-width: 300px;
            }
            .qr-name {
                margin-top: 20px;
                font-family: Arial, sans-serif;
                font-size: 18px;
                text-align: center;
            }
            @page {
                margin: 0;
                size: auto;
            }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="qr-container">
        <img src="<?php echo QR_URL . $user['qr_code']; ?>" class="qr-code" alt="QR Code">
        <div class="qr-name">
            <?php echo htmlspecialchars($user['full_name']); ?>
        </div>
    </div>
</body>
</html>