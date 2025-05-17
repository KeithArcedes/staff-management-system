<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../vendor/autoload.php';

redirectIfNotAdmin();

$db = new Database();
$conn = $db->getConnection();

// Filter parameters
$month_year = $_GET['month'] ?? date('Y-m');
$status = $_GET['status'] ?? 'all';

// Build query
$query = "
    SELECT s.*, u.full_name, u.hourly_rate
    FROM salary s
    JOIN users u ON s.user_id = u.user_id
    WHERE DATE_FORMAT(s.month_year, '%Y-%m') = :month_year
";

$params = ['month_year' => $month_year];

if ($status !== 'all') {
    $query .= " AND s.payment_status = :status";
    $params['status'] = $status;
}

$query .= " ORDER BY u.full_name";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$payrolls = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get available months
$months = $conn->query("
    SELECT DISTINCT DATE_FORMAT(month_year, '%Y-%m') AS month 
    FROM salary 
    ORDER BY month_year DESC
")->fetchAll(PDO::FETCH_COLUMN);

$page_title = 'Payroll Management';
include '../../includes/header.php';
?>

<div class="card shadow">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0">Payroll Management</h4>
        <div>
            <a href="generate.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Generate Payroll
            </a>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" class="mb-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <label>Month</label>
                    <select name="month" class="form-select">
                        <?php foreach ($months as $m): ?>
                            <option value="<?php echo $m; ?>" <?php echo $m == $month_year ? 'selected' : ''; ?>>
                                <?php echo date('F Y', strtotime($m . '-01')); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label>Status</label>
                    <select name="status" class="form-select">
                        <option value="all" <?php echo $status == 'all' ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="paid" <?php echo $status == 'paid' ? 'selected' : ''; ?>>Paid</option>
                        <option value="cancelled" <?php echo $status == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Staff</th>
                        <th>Period</th>
                        <th>Basic Salary</th>
                        <th>Overtime</th>
                        <th>Allowances</th>
                        <th>Deductions</th>
                        <th>Tax</th>
                        <th>Net Salary</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payrolls as $payroll): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($payroll['full_name']); ?></td>
                            <td><?php echo date('M Y', strtotime($payroll['month_year'])); ?></td>
                            <td class="text-end">₱<?php echo number_format($payroll['basic_salary'], 2); ?></td>
                            <td class="text-end">₱<?php echo number_format($payroll['overtime_pay'], 2); ?></td>
                            <td class="text-end">₱<?php echo number_format($payroll['allowances'], 2); ?></td>
                            <td class="text-end">₱<?php echo number_format($payroll['deductions'], 2); ?></td>
                            <td class="text-end">₱<?php echo number_format($payroll['tax'], 2); ?></td>
                            <td class="text-end fw-bold">₱<?php echo number_format($payroll['net_salary'], 2); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $payroll['payment_status'] == 'paid' ? 'success' : 
                                        ($payroll['payment_status'] == 'pending' ? 'warning' : 'danger'); 
                                ?>">
                                    <?php echo ucfirst($payroll['payment_status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="view.php?id=<?php echo $payroll['salary_id']; ?>" class="btn btn-sm btn-info" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit.php?id=<?php echo $payroll['salary_id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <!-- In the table row for each payroll -->
                                    <?php if ($payroll['payment_status'] == 'pending'): ?>
                                        <form method="POST" action="mark_paid.php" class="d-inline">
                                            <input type="hidden" name="salary_id" value="<?php echo $payroll['salary_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-success" title="Mark as Paid" onclick="return confirm('Mark this payroll as paid?');">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <?php if (!empty($payrolls)): ?>
                    <tfoot>
                        <tr class="table-active">
                            <th colspan="2">Totals</th>
                            <th class="text-end">₱<?php echo number_format(array_sum(array_column($payrolls, 'basic_salary')), 2); ?></th>
                            <th class="text-end">₱<?php echo number_format(array_sum(array_column($payrolls, 'overtime_pay')), 2); ?></th>
                            <th class="text-end">₱<?php echo number_format(array_sum(array_column($payrolls, 'allowances')), 2); ?></th>
                            <th class="text-end">₱<?php echo number_format(array_sum(array_column($payrolls, 'deductions')), 2); ?></th>
                            <th class="text-end">₱<?php echo number_format(array_sum(array_column($payrolls, 'tax')), 2); ?></th>
                            <th class="text-end">₱<?php echo number_format(array_sum(array_column($payrolls, 'net_salary')), 2); ?></th>
                            <th colspan="2"></th>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>