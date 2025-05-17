
<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../vendor/autoload.php';

redirectIfNotAdmin();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$staff_id = $_GET['id'];

$db = new Database();
$conn = $db->getConnection();

// Get staff details
$stmt = $conn->prepare("
    SELECT u.*, r.role_name 
    FROM users u
    JOIN roles r ON u.role_id = r.role_id
    WHERE u.user_id = ? AND u.role_id = 2
");
$stmt->execute([$staff_id]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$staff) {
    $_SESSION['flash']['danger'] = 'Staff member not found';
    header("Location: index.php");
    exit();
}

// Get attendance summary
$attendance = $conn->prepare("
    SELECT 
        COUNT(*) AS total_days,
        SUM(TIMESTAMPDIFF(HOUR, check_in, check_out)) AS total_hours
    FROM attendance
    WHERE user_id = ?
");
$attendance->execute([$staff_id]);
$stats = $attendance->fetch(PDO::FETCH_ASSOC);

$page_title = 'View Staff: ' . $staff['full_name'];
include '../../includes/staff_header.php';
?>

<div class="row">
    <div class="col-md-4">
        <div class="card shadow mb-4">
            <div class="card-body text-center">
                <?php if ($staff['qr_code']): ?>
                    <img src="<?php echo QR_URL . htmlspecialchars($staff['qr_code']); ?>" class="img-fluid mb-3" style="max-width: 200px;">
                <?php endif; ?>
                <h4><?php echo htmlspecialchars($staff['full_name']); ?></h4>
                <p class="text-muted"><?php echo htmlspecialchars($staff['role_name']); ?></p>
                
                <div class="list-group list-group-flush">
                    <div class="list-group-item">
                        <small class="text-muted">Username</small>
                        <div><?php echo htmlspecialchars($staff['username']); ?></div>
                    </div>
                    <div class="list-group-item">
                        <small class="text-muted">Email</small>
                        <div><?php echo htmlspecialchars($staff['email']); ?></div>
                    </div>
                    <div class="list-group-item">
                        <small class="text-muted">Hourly Rate</small>
                        <div>Php<?php echo number_format($staff['hourly_rate'], 2); ?></div>
                    </div>
                    <div class="list-group-item">
                        <small class="text-muted">Status</small>
                        <div>
                            <span class="badge bg-<?php echo $staff['is_active'] ? 'success' : 'secondary'; ?>">
                                <?php echo $staff['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </div>
                    </div>
                    <div class="list-group-item">
                        <small class="text-muted">Member Since</small>
                        <div><?php echo date('M j, Y', strtotime($staff['created_at'])); ?></div>
                    </div>
                </div>
                
                <div class="mt-3 d-grid gap-2">
                    <a href="edit.php?id=<?php echo $staff_id; ?>" class="btn btn-warning">
                        <i class="fas fa-edit me-1"></i> Edit
                    </a>
                    <?php if (!$staff['qr_code']): ?>
                        <a href="../qr/generate.php?id=<?php echo $staff_id; ?>" class="btn btn-success">
                            <i class="fas fa-qrcode me-1"></i> Generate QR
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Attendance Summary</h4>
                <a href="../attendance/report.php?staff_id=<?php echo $staff_id; ?>" class="btn btn-sm btn-primary">
                    View Full Report
                </a>
            </div>
            <div class="card-body">
                <div class="row text-center mb-4">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title text-muted">Total Days Worked</h6>
                                <h2 class="card-text"><?php echo $stats['total_days'] ?? 0; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title text-muted">Total Hours</h6>
                                <h2 class="card-text"><?php echo $stats['total_hours'] ?? 0; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>
                
                <h5>Recent Attendance</h5>
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
                            $recent->execute([$staff_id]);
                            
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

<?php include '../../includes/footer.php'; ?>