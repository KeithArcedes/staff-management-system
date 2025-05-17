<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'vendor/autoload.php';

$db = new Database();
$conn = $db->getConnection();

// Handle QR code submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qr_data'])) {
    $qr_data = trim($_POST['qr_data']);
    
    // Verify QR data format (STAFF-{ID}-{RANDOM})
        if (preg_match('/^(STAFF|USER)-(\d+)-[a-f0-9]+$/', $qr_data, $matches)) {
            $user_id = $matches[2];
            
            // Check if user exists and is active (staff or admin)
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = ? AND is_active = 1");
            $stmt->execute([$user_id]);
            
            if ($stmt->fetch()) {
            // Check if already checked in today
            $attendance = $conn->prepare("
                SELECT * FROM attendance 
                WHERE user_id = ? AND DATE(check_in) = CURDATE() 
                ORDER BY check_in DESC 
                LIMIT 1
            ");
            $attendance->execute([$staff_id]);
            $last_attendance = $attendance->fetch(PDO::FETCH_ASSOC);
            
            if ($last_attendance && !$last_attendance['check_out']) {
                // Check out
                $update = $conn->prepare("UPDATE attendance SET check_out = NOW() WHERE attendance_id = ?");
                if ($update->execute([$last_attendance['attendance_id']])) {
                    $message = 'Successfully checked out at ' . date('h:i A');
                    $message_type = 'success';
                }
            } else {
                // Check in
                $insert = $conn->prepare("INSERT INTO attendance (user_id, check_in) VALUES (?, NOW())");
                if ($insert->execute([$staff_id])) {
                    $message = 'Successfully checked in at ' . date('h:i A');
                    $message_type = 'success';
                }
            }
        } else {
            $message = 'Invalid staff QR code';
            $message_type = 'danger';
        }
    } else {
        $message = 'Invalid QR code format';
        $message_type = 'danger';
    }
}

$page_title = 'Scan Attendance';
include 'includes/header-public.php'; // We'll create this special header
?>

<div class="row justify-content-center pt-5">
    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Staff Attendance Scanner</h4>
            </div>
            <div class="card-body text-center">
                <?php if (isset($message)): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <div id="scanner-container" class="mb-4">
                    <video id="qr-video" width="100%" style="max-width: 500px; border: 2px dashed #ccc;"></video>
                </div>
                <form id="qr-form" method="POST">
                    <input type="hidden" name="qr_data" id="qr-data">
                    <button type="button" id="start-scanner" class="btn btn-primary btn-lg">
                        <i class="fas fa-camera me-2"></i>Start Scanner
                    </button>
                </form>
                
                <div class="mt-4">
                    <p class="text-muted">Or <a href="login.php">login to your account</a> for more features</p>
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

<?php include 'includes/footer-public.php'; ?>