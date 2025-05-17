<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../vendor/autoload.php';

redirectIfNotAdmin();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$salary_id = $_GET['id'];

$db = new Database();
$conn = $db->getConnection();

// Get payroll details
$stmt = $conn->prepare("
    SELECT s.*, u.full_name, u.hourly_rate, u.email
    FROM salary s
    JOIN users u ON s.user_id = u.user_id
    WHERE s.salary_id = ?
");
$stmt->execute([$salary_id]);
$payroll = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$payroll) {
    $_SESSION['flash']['danger'] = 'Payroll record not found';
    header("Location: index.php");
    exit();
}

// Modify the attendance query to ensure proper date filtering and formatting
$attendance = $conn->prepare("
    SELECT 
        DATE(check_in) AS date,
        check_in,
        check_out,
        TIMESTAMPDIFF(HOUR, check_in, check_out) AS total_hours,
        CASE 
            WHEN TIMESTAMPDIFF(HOUR, check_in, check_out) > 8 
            THEN TIMESTAMPDIFF(HOUR, check_in, check_out) - 8
            ELSE 0
        END AS overtime_hours
    FROM attendance
    WHERE 
        user_id = ? 
        AND DATE(check_in) >= DATE(?) 
        AND DATE(check_out) <= DATE(?)
        AND check_out IS NOT NULL
    ORDER BY date ASC
");

// Format the dates properly for the query
$start_date = date('Y-m-01', strtotime($payroll['month_year']));
$end_date = date('Y-m-t', strtotime($payroll['month_year']));

$attendance->execute([
    $payroll['user_id'],
    $start_date,
    $end_date
]);

// Add debug logging
if ($attendance->rowCount() == 0) {
    error_log("No attendance records found for user {$payroll['user_id']} between $start_date and $end_date");
}

$page_title = 'Payroll Details: ' . htmlspecialchars($payroll['full_name']);
include '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card shadow">
            <div class="card-header">
                <h5 class="mb-0">Payroll Information</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <tr>
                            <th>Staff Member</th>
                            <td><?php echo htmlspecialchars($payroll['full_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Pay Period</th>
                            <td><?php echo date('F Y', strtotime($payroll['month_year'])); ?></td>
                        </tr>
                        <tr>
                            <th>Hourly Rate</th>
                            <td>₱<?php echo number_format($payroll['hourly_rate'], 2); ?></td>
                        </tr>
                        <tr>
                            <th>Payment Status</th>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $payroll['payment_status'] == 'paid' ? 'success' : 
                                        ($payroll['payment_status'] == 'pending' ? 'warning' : 'danger'); 
                                ?>">
                                    <?php echo ucfirst($payroll['payment_status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php if ($payroll['payment_status'] == 'paid' && $payroll['payment_date']): ?>
                            <tr>
                                <th>Payment Date</th>
                                <td><?php echo date('M j, Y', strtotime($payroll['payment_date'])); ?></td>
                            </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="card shadow mt-4">
            <div class="card-header">
                <h5 class="mb-0">Salary Breakdown</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <tr>
                            <th>Basic Salary</th>
                            <td class="text-end">₱<?php echo number_format($payroll['basic_salary'], 2); ?></td>
                        </tr>
                        <tr>
                            <th>Overtime Pay (<?php echo $payroll['overtime_hours']; ?> hrs)</th>
                            <td class="text-end">₱<?php echo number_format($payroll['overtime_pay'], 2); ?></td>
                        </tr>
                        <tr>
                            <th>Allowances</th>
                            <td class="text-end">₱<?php echo number_format($payroll['allowances'], 2); ?></td>
                        </tr>
                        <tr class="table-active">
                            <th>Gross Salary</th>
                            <td class="text-end fw-bold">₱<?php echo number_format(
                                $payroll['basic_salary'] + $payroll['overtime_pay'] + $payroll['allowances'], 
                                2
                            ); ?></td>
                        </tr>
                        <tr>
                            <th>Deductions</th>
                            <td class="text-end">-₱<?php echo number_format($payroll['deductions'], 2); ?></td>
                        </tr>
                        <tr>
                            <th>Tax</th>
                            <td class="text-end">-₱<?php echo number_format($payroll['tax'], 2); ?></td>
                        </tr>
                        <tr class="table-active">
                            <th>Net Salary</th>
                            <td class="text-end fw-bold">₱<?php echo number_format($payroll['net_salary'], 2); ?></td>
                        </tr>
                    </table>
                </div>
                
                <!-- Add CSRF token to the form -->
                <?php if ($payroll['payment_status'] == 'pending'): ?>
                    <form method="POST" action="mark_paid.php" onsubmit="return confirm('Are you sure you want to mark this payroll as paid?');">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="salary_id" value="<?php echo $payroll['salary_id']; ?>">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check me-2"></i>Mark as Paid
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-header">
                <h5 class="mb-0">Attendance Details</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Total Hours</th>
                                <th>Overtime</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($attendance->rowCount() > 0): ?>
                                <?php while ($row = $attendance->fetch(PDO::FETCH_ASSOC)): ?>
                                    <tr>
                                        <td><?php echo date('M j, Y', strtotime($row['date'])); ?></td>
                                        <td><?php echo date('h:i A', strtotime($row['check_in'])); ?></td>
                                        <td><?php echo date('h:i A', strtotime($row['check_out'])); ?></td>
                                        <td><?php echo $row['total_hours']; ?> hrs</td>
                                        <td><?php echo $row['overtime_hours']; ?> hrs</td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">No attendance records found for this period</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    <a href="print_payslip.php?id=<?php echo $payroll['salary_id']; ?>" class="btn btn-outline-primary" target="_blank">
                        <i class="fas fa-print me-2"></i>Print Payslip
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>