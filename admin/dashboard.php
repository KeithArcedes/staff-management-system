<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
redirectIfNotAdmin();

$page_title = 'Admin Dashboard';
include '../includes/header.php';

$db = new Database();
$conn = $db->getConnection();

// Get counts for dashboard
$staff_count = $conn->query("SELECT COUNT(*) FROM users WHERE role_id = 2")->fetchColumn();
$active_staff = $conn->query("SELECT COUNT(*) FROM users WHERE role_id = 2 AND is_active = 1")->fetchColumn();
$today_attendance = $conn->query("SELECT COUNT(*) FROM attendance WHERE DATE(check_in) = CURDATE()")->fetchColumn();
$pending_payroll = $conn->query("SELECT COUNT(*) FROM salary WHERE payment_status = 'pending'")->fetchColumn();
?>

<!-- Add container class for better spacing -->
<div class="container-fluid py-4">
    <!-- First row with statistics cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-4">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">Total Staff</h5>
                    <h2 class="card-text"><?php echo $staff_count; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">Active Staff</h5>
                    <h2 class="card-text"><?php echo $active_staff; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card bg-info text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">Today's Attendance</h5>
                    <h2 class="card-text"><?php echo $today_attendance; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card bg-warning text-dark h-100">
                <div class="card-body">
                    <h5 class="card-title">Pending Payroll</h5>
                    <h2 class="card-text"><?php echo $pending_payroll; ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Second row with tables -->
    <div class="row mb-4">
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-light py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Attendance</h5>
                        <a href="attendance/index.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-calendar me-2"></i>View Full Report
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Staff</th>
                                    <th>Check-in</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $conn->query("
                                    SELECT u.full_name, a.check_in, a.status 
                                    FROM attendance a
                                    JOIN users u ON a.user_id = u.user_id
                                    ORDER BY a.check_in DESC
                                    LIMIT 5
                                ");
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
                                    echo "<td>" . date('h:i A', strtotime($row['check_in'])) . "</td>";
                                    echo "<td><span class='badge bg-" . ($row['status'] == 'present' ? 'success' : 'warning') . "'>" . ucfirst($row['status']) . "</span></td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-light py-3">
                    <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Staff List</h5>
                     <a href="staff/index.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-calendar me-2"></i>View All Staff
                    </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $conn->query("
                                    SELECT full_name, email, is_active 
                                    FROM users 
                                    WHERE role_id = 2
                                    ORDER BY created_at DESC
                                    LIMIT 5
                                ");
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                                    echo "<td><span class='badge bg-" . ($row['is_active'] ? 'success' : 'secondary') . "'>" . ($row['is_active'] ? 'Active' : 'Inactive') . "</span></td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>