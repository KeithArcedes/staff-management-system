<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../vendor/autoload.php';

redirectIfNotLoggedIn();

$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

// Get current user data
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get today's attendance status
$today_attendance = $conn->prepare("
    SELECT * FROM attendance 
    WHERE user_id = ? AND DATE(check_in) = CURDATE() 
    ORDER BY check_in DESC LIMIT 1
");
$today_attendance->execute([$user_id]);
$attendance = $today_attendance->fetch(PDO::FETCH_ASSOC);

// Get monthly stats
$monthly_stats = $conn->prepare("
    SELECT 
        COUNT(*) AS days_worked,
        SUM(TIMESTAMPDIFF(HOUR, check_in, check_out)) AS hours_worked
    FROM attendance
    WHERE 
        user_id = ? AND
        check_out IS NOT NULL AND
        MONTH(check_in) = MONTH(CURRENT_DATE()) AND
        YEAR(check_in) = YEAR(CURRENT_DATE())
");
$monthly_stats->execute([$user_id]);
$stats = $monthly_stats->fetch(PDO::FETCH_ASSOC);

$page_title = 'Staff Dashboard';
include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card shadow">
            <div class="card-header">
                <h5 class="mb-0">Today's Attendance</h5>
            </div>
            <div class="card-body text-center py-4">
                <?php if ($attendance): ?>
                    <?php if ($attendance['check_out']): ?>
                        <div class="alert alert-success">
                            <h4><i class="fas fa-check-circle"></i> Completed</h4>
                            <p class="mb-1">Checked in: <?php echo date('h:i A', strtotime($attendance['check_in'])); ?></p>
                            <p class="mb-0">Checked out: <?php echo date('h:i A', strtotime($attendance['check_out'])); ?></p>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <h4><i class="fas fa-clock"></i> Active</h4>
                            <p class="mb-1">Checked in at: <?php echo date('h:i A', strtotime($attendance['check_in'])); ?></p>
                            <a href="attendance.php" class="btn btn-primary mt-3">Check Out Now</a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        <h4><i class="fas fa-info-circle"></i> Not Checked In</h4>
                        <p>You haven't checked in today</p>
                        <a href="attendance.php" class="btn btn-primary">Check In Now</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card shadow">
            <div class="card-header">
                <h5 class="mb-0">Monthly Summary</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title text-muted">Days Worked</h6>
                                <h2 class="card-text"><?php echo $stats['days_worked'] ?? 0; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title text-muted">Total Hours</h6>
                                <h2 class="card-text"><?php echo $stats['hours_worked'] ?? 0; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-2">
                    <a href="attendance.php" class="btn btn-outline-primary">View Attendance History</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card shadow">
            <div class="card-header">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="attendance.php" class="btn btn-primary">
                        <i class="fas fa-qrcode me-2"></i>Record Attendance
                    </a>
                    <a href="profile.php" class="btn btn-outline-secondary">
                        <i class="fas fa-user me-2"></i>View Profile
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card shadow">
            <div class="card-header">
                <h5 class="mb-0">Recent Activity</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <?php
                    $recent = $conn->prepare("
                        SELECT DATE(check_in) AS date, TIME(check_in) AS check_in, 
                               TIME(check_out) AS check_out 
                        FROM attendance 
                        WHERE user_id = ? 
                        ORDER BY check_in DESC 
                        LIMIT 3
                    ");
                    $recent->execute([$user_id]);
                    
                    while ($row = $recent->fetch(PDO::FETCH_ASSOC)):
                    ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?php echo date('M j', strtotime($row['date'])); ?></strong>
                                <div class="text-muted small">
                                    <?php echo date('h:i A', strtotime($row['check_in'])); ?> - 
                                    <?php echo $row['check_out'] ? date('h:i A', strtotime($row['check_out'])) : 'Not checked out'; ?>
                                </div>
                            </div>
                            <span class="badge bg-<?php echo $row['check_out'] ? 'success' : 'warning'; ?>">
                                <?php echo $row['check_out'] ? 'Completed' : 'Active'; ?>
                            </span>
                        </li>
                    <?php endwhile; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>