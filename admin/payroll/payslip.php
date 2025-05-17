<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../vendor/autoload.php';

redirectIfNotLoggedIn();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: payroll.php");
    exit();
}

$salary_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

$db = new Database();
$conn = $db->getConnection();

// Get payroll details
$stmt = $conn->prepare("
    SELECT s.*, u.full_name, u.email, u.hourly_rate
    FROM salary s
    JOIN users u ON s.user_id = u.user_id
    WHERE s.salary_id = ? AND s.user_id = ?
");
$stmt->execute([$salary_id, $user_id]);
$payroll = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$payroll) {
    $_SESSION['flash']['danger'] = 'Payslip not found';
    header("Location: payroll.php");
    exit();
}

$page_title = 'Payslip: ' . date('F Y', strtotime($payroll['month_year']));
include '../includes/header.php';
?>

<div class="card shadow">
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-6">
                <h2 class="mb-0">PAYSLIP</h2>
                <p class="text-muted mb-0"><?php echo date('F Y', strtotime($payroll['month_year'])); ?></p>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="mb-1"><strong>Status:</strong> 
                    <span class="badge bg-<?php 
                        echo $payroll['payment_status'] == 'paid' ? 'success' : 
                            ($payroll['payment_status'] == 'pending' ? 'warning' : 'danger'); 
                    ?>">
                        <?php echo ucfirst($payroll['payment_status']); ?>
                    </span>
                </p>
                <?php if ($payroll['payment_status'] == 'paid'): ?>
                    <p class="mb-0"><strong>Payment Date:</strong> <?php echo date('M j, Y', strtotime($payroll['payment_date'])); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Employee Information</h5>
                        <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($payroll['full_name']); ?></p>
                        <p class="mb0"><strong>Hourly Rate:</strong> ₱<?php echo number_format($payroll['hourly_rate'], 2); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Company Information</h5>
                        <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars(COMPANY_NAME); ?></p>
                        <p class="mb0"><strong>Address:</strong> <?php echo htmlspecialchars(COMPANY_ADDRESS); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Earnings</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <tr>
                                <th>Basic Salary</th>
                                <td class="text-end">₱<?php echo number_format($payroll['basic_salary'], 2); ?></td>
                            </tr>
                            <tr>
                                <th>Overtime Pay</th>
                                <td class="text-end">₱<?php echo number_format($payroll['overtime_pay'], 2); ?></td>
                            </tr>
                            <tr>
                                <th>Allowances</th>
                                <td class="text-end">₱<?php echo number_format($payroll['allowances'], 2); ?></td>
                            </tr>
                            <tr class="table-active">
                                <th>Total Earnings</th>
                                <td class="text-end fw-bold">₱<?php echo number_format(
                                    $payroll['basic_salary'] + $payroll['overtime_pay'] + $payroll['allowances'], 
                                    2
                                ); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Deductions</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <tr>
                                <th>Tax</th>
                                <td class="text-end">₱<?php echo number_format($payroll['tax'], 2); ?></td>
                            </tr>
                            <tr>
                                <th>Other Deductions</th>
                                <td class="text-end">₱<?php echo number_format($payroll['deductions'], 2); ?></td>
                            </tr>
                            <tr class="table-active">
                                <th>Total Deductions</th>
                                <td class="text-end fw-bold">₱<?php echo number_format(
                                    $payroll['tax'] + $payroll['deductions'], 
                                    2
                                ); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card bg-light">
            <div class="card-body text-center">
                <h3 class="mb-0">NET PAY: ₱<?php echo number_format($payroll['net_salary'], 2); ?></h3>
            </div>
        </div>
        
        <div class="mt-4 d-print-none">
            <button class="btn btn-primary" onclick="window.print()">
                <i class="fas fa-print me-2"></i>Print Payslip
            </button>
            <a href="payroll.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Payroll
            </a>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>