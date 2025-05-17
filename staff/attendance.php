<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../vendor/autoload.php';

redirectIfNotLoggedIn();

$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

// Handle QR code submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qr_data'])) {
    $qr_data = trim($_POST['qr_data']);
    
    // Add debug logging
    error_log("Scanned QR Data: " . $qr_data);
    
    // Verify QR data format (STAFF-{ID}-{RANDOM})
    if (preg_match('/^STAFF-(\d+)-[A-Fa-f0-9]+$/', $qr_data, $matches)) {
        $scanned_id = $matches[1];
        
        // Debug logging
        error_log("Matched ID: " . $scanned_id . ", User ID: " . $user_id);
        
        // Check if this is the logged in user
        if ($scanned_id == $user_id) {
            // Check if already checked in today
            $stmt = $conn->prepare("
                SELECT * FROM attendance 
                WHERE user_id = ? AND DATE(check_in) = CURDATE() 
                ORDER BY check_in DESC 
                LIMIT 1
            ");
            $stmt->execute([$user_id]);
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
                if ($insert->execute([$user_id])) {
                    $_SESSION['flash']['success'] = 'Successfully checked in at ' . date('h:i A');
                }
            }
        } else {
            $_SESSION['flash']['danger'] = 'Invalid QR code for your account';
            error_log("ID mismatch - Scanned: $scanned_id, User: $user_id");
        }
    } else {
        $_SESSION['flash']['danger'] = 'Invalid QR code format. Expected: STAFF-ID-HASH';
        error_log("QR format validation failed for: " . $qr_data);
    }
    
    header("Location: attendance.php");
    exit();
}

// Get today's status
$today_stmt = $conn->prepare("
    SELECT * FROM attendance 
    WHERE user_id = ? AND DATE(check_in) = CURDATE() 
    ORDER BY check_in DESC LIMIT 1
");
$today_stmt->execute([$user_id]);
$today = $today_stmt->fetch(PDO::FETCH_ASSOC);

// Get attendance history
$history_stmt = $conn->prepare("
    SELECT 
        DATE(check_in) AS date,
        TIME(check_in) AS check_in,
        TIME(check_out) AS check_out,
        TIMESTAMPDIFF(HOUR, check_in, check_out) AS hours
    FROM attendance
    WHERE user_id = ?
    ORDER BY check_in DESC
    LIMIT 30
");
$history_stmt->execute([$user_id]);

$page_title = 'Attendance';
include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card shadow">
            <div class="card-header">
                <h5 class="mb-0">Record Attendance</h5>
            </div>
            <div class="card-body text-center">
                <?php if ($today && !$today['check_out']): ?>
                    <div class="alert alert-warning mb-4">
                        <h5>Currently Checked In</h5>
                        <p class="mb-1">Time: <?php echo date('h:i A', strtotime($today['check_in'])); ?></p>
                        <p class="mb-0">Duration: <?php echo time_elapsed($today['check_in']); ?></p>
                    </div>
                <?php endif; ?>

                <div id="scanner-container" class="mb-4">
                    <video id="qr-video" width="100%" style="max-width: 500px; border: 2px dashed #ccc;"></video>
                </div>
                <form id="qr-form" method="POST">
                    <input type="hidden" name="qr_data" id="qr-data">
                    <button type="button" id="start-scanner" class="btn btn-primary btn-lg">
                        <i class="fas fa-camera me-2"></i><?php echo ($today && !$today['check_out']) ? 'Check Out' : 'Check In'; ?>
                    </button>
                </form>
                
                <div class="mt-4">
                    <h5>Or use your personal QR code:</h5>
                    <?php
                    $stmt = $conn->prepare("SELECT qr_code FROM users WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($user && $user['qr_code']): ?>
                        <img src="<?php echo QR_URL . htmlspecialchars($user['qr_code']); ?>" class="img-fluid" style="max-width: 200px;">
                          <div class="my-3">
                            <a href="print_qr.php" target="_blank" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-print me-2"></i>Print QR Code
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">No QR code generated yet. Please contact admin.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Attendance History</h5>
                <span class="badge bg-primary">Last 30 Days</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Hours</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $history_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td><?php echo date('M j', strtotime($row['date'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($row['check_in'])); ?></td>
                                    <td><?php echo $row['check_out'] ? date('h:i A', strtotime($row['check_out'])) : '--:--'; ?></td>
                                    <td><?php echo $row['hours'] ?? '--'; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
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
            startScanner.innerHTML = '<i class="fas fa-camera me-2"></i><?php echo ($today && !$today['check_out']) ? 'Check Out' : 'Check In'; ?>';
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>