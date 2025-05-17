<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../vendor/autoload.php';

redirectIfNotLoggedIn();

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

$db = new Database();
$conn = $db->getConnection();

// Handle QR code submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qr_data'])) {
    $qr_data = trim($_POST['qr_data']);
    
    // Verify QR data format (STAFF-{ID}-{RANDOM})
    if (preg_match('/^STAFF-(\d+)-[a-f0-9]+$/', $qr_data, $matches)) {
        $staff_id = $matches[1];
        
        // Check if this is the logged in user
        if ($staff_id == $_SESSION['user_id']) {
            // Check if already checked in today
            $stmt = $conn->prepare("
                SELECT * FROM attendance 
                WHERE user_id = ? AND DATE(check_in) = CURDATE() 
                ORDER BY check_in DESC 
                LIMIT 1
            ");
            $stmt->execute([$staff_id]);
            $last_attendance = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($last_attendance && !$last_attendance['check_out']) {
                // Check out
                $update = $conn->prepare("UPDATE attendance SET check_out = NOW() WHERE attendance_id = ?");
                if ($update->execute([$last_attendance['attendance_id']])) {
                    $_SESSION['flash']['success'] = 'Successfully checked out at ' . date('h:i A');
                }
            } else {
                // Check in
                $insert = $conn->prepare("INSERT INTO attendance (user_id, check_in) VALUES (?, NOW())");
                if ($insert->execute([$staff_id])) {
                    $_SESSION['flash']['success'] = 'Successfully checked in at ' . date('h:i A');
                }
            }
        } else {
            $_SESSION['flash']['danger'] = 'Invalid QR code for your account';
        }
    } else {
        $_SESSION['flash']['danger'] = 'Invalid QR code format';
    }
    
    header("Location: scan.php");
    exit();
}

$page_title = 'Scan QR Code';
include '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header">
                <h4 class="mb-0">Attendance Scanner</h4>
            </div>
            <div class="card-body text-center">
                <div id="scanner-container" class="mb-4">
                    <video id="qr-video" width="100%" style="max-width: 500px;"></video>
                </div>
                <form id="qr-form" method="POST">
                    <input type="hidden" name="qr_data" id="qr-data">
                    <button type="button" id="start-scanner" class="btn btn-primary">
                        <i class="fas fa-camera me-2"></i>Start Scanner
                    </button>
                </form>
                
                <div class="mt-4">
                    <h5>Or use your personal QR code:</h5>
                    <?php
                    // Display staff's QR code if exists
                    $stmt = $conn->prepare("SELECT qr_code FROM users WHERE user_id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($user && $user['qr_code']) {
                        echo '<img src="' . QR_URL . htmlspecialchars($user['qr_code']) . '" class="img-fluid" style="max-width: 200px;">';
                    } else {
                        echo '<div class="alert alert-warning">No QR code generated yet. Please contact admin.</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include QR scanner library -->
<script src="https://rawgit.com/schmich/instascan-builds/master/instascan.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const scannerContainer = document.getElementById('scanner-container');
    const qrVideo = document.getElementById('qr-video');
    const qrData = document.getElementById('qr-data');
    const qrForm = document.getElementById('qr-form');
    const startScanner = document.getElementById('start-scanner');
    
    let scanner = null;
    
    startScanner.addEventListener('click', function() {
        if (!scanner) {
            scanner = new Instascan.Scanner({ video: qrVideo });
            
            scanner.addListener('scan', function(content) {
                qrData.value = content;
                qrForm.submit();
            });
            
            Instascan.Camera.getCameras().then(function(cameras) {
                if (cameras.length > 0) {
                    scanner.start(cameras[0]);
                    startScanner.innerHTML = '<i class="fas fa-stop me-2"></i>Stop Scanner';
                } else {
                    alert('No cameras found');
                }
            }).catch(function(e) {
                console.error(e);
                alert('Error accessing camera');
            });
        } else {
            scanner.stop();
            scanner = null;
            startScanner.innerHTML = '<i class="fas fa-camera me-2"></i>Start Scanner';
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>