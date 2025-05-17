<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../vendor/autoload.php';

redirectIfNotAdmin();

$db = new Database();
$conn = $db->getConnection();

$user_id = $_SESSION['user_id'];

// Get admin data
$stmt = $conn->prepare("
    SELECT u.*, r.role_name 
    FROM users u
    JOIN roles r ON u.role_id = r.role_id
    WHERE u.user_id = ?
");
$stmt->execute([$user_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// Get attendance summary
$attendance = $conn->prepare("
    SELECT 
        COUNT(*) AS total_days,
        SUM(TIMESTAMPDIFF(HOUR, check_in, check_out)) AS total_hours
    FROM attendance
    WHERE 
        user_id = ? AND
        check_out IS NOT NULL AND
        MONTH(check_in) = MONTH(CURRENT_DATE()) AND
        YEAR(check_in) = YEAR(CURRENT_DATE())
");
$attendance->execute([$user_id]);
$stats = $attendance->fetch(PDO::FETCH_ASSOC);

$page_title = 'Admin Profile';
include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-4">
        <div class="card shadow mb-4">
            <div class="card-body text-center">
                <?php if ($admin['qr_code']): ?>
                    <img src="<?php echo QR_URL . htmlspecialchars($admin['qr_code']); ?>" class="img-fluid mb-3" style="max-width: 200px;">
                    <div class="mb-3">
                        <a href="print_qr.php" target="_blank" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-print me-2"></i>Print QR Code
                        </a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">No QR code generated yet</div>
                    <a href="qr/generate.php?id=<?php echo $user_id; ?>" class="btn btn-primary">
                        Generate QR Code
                    </a>
                <?php endif; ?>
                
                <h4><?php echo htmlspecialchars($admin['full_name']); ?></h4>
                <p class="text-muted"><?php echo htmlspecialchars($admin['role_name']); ?></p>
                
                <div class="list-group list-group-flush">
                    <div class="list-group-item">
                        <small class="text-muted">Email</small>
                        <div><?php echo htmlspecialchars($admin['email']); ?></div>
                    </div>
                    <div class="list-group-item">
                        <small class="text-muted">Username</small>
                        <div><?php echo htmlspecialchars($admin['username']); ?></div>
                    </div>
                    <div class="list-group-item">
                        <small class="text-muted">Member Since</small>
                        <div><?php echo date('M j, Y', strtotime($admin['created_at'])); ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card shadow">
            <div class="card-header">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="staff/add.php" class="btn btn-primary">
                        <i class="fas fa-user-plus me-2"></i>Add Staff
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Monthly Summary</h4>
                <span class="badge bg-primary"><?php echo date('F Y'); ?></span>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-6 mb-4">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title text-muted">Days Present</h6>
                                <h2 class="card-text"><?php echo $stats['total_days'] ?? 0; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title text-muted">Total Hours</h6>
                                <h2 class="card-text"><?php echo $stats['total_hours'] ?? 0; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>
                
                <h5 class="mt-4">Recent Attendance</h5>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Hours</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $recent = $conn->prepare("
                                SELECT 
                                    DATE(check_in) AS date,
                                    TIME(check_in) AS check_in,
                                    TIME(check_out) AS check_out,
                                    TIMESTAMPDIFF(HOUR, check_in, check_out) AS hours
                                FROM attendance
                                WHERE user_id = ?
                                ORDER BY check_in DESC
                                LIMIT 5
                            ");
                            $recent->execute([$user_id]);
                            
                            while ($row = $recent->fetch(PDO::FETCH_ASSOC)):
                            ?>
                                <tr>
                                    <td><?php echo date('M j', strtotime($row['date'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($row['check_in'])); ?></td>
                                    <td><?php echo $row['check_out'] ? date('h:i A', strtotime($row['check_out'])) : 'N/A'; ?></td>
                                    <td><?php echo $row['hours'] ?? 'N/A'; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>