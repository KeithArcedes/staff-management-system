<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

redirectIfNotAdmin();

$db = new Database();
$conn = $db->getConnection();

$error = '';
$success = '';

// Get payroll ID from URL
$salary_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$salary_id) {
    header('Location: index.php');
    exit();
}

// Get payroll data
$stmt = $conn->prepare("
    SELECT s.*, u.full_name, u.hourly_rate
    FROM salary s
    JOIN users u ON s.user_id = u.user_id
    WHERE s.salary_id = ?
");
$stmt->execute([$salary_id]);
$payroll = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$payroll) {
    header('Location: index.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Start transaction
        $conn->beginTransaction();

        // Update basic info
        $sql = "UPDATE salary SET 
                basic_salary = ?,
                overtime_pay = ?,
                allowances = ?,
                deductions = ?,
                tax = ?,
                net_salary = ?,
                updated_at = NOW()
                WHERE salary_id = ?";
        
        $basic_salary = floatval($_POST['basic_salary']);
        $overtime_pay = floatval($_POST['overtime_pay']);
        $allowances = floatval($_POST['allowances']);
        $deductions = floatval($_POST['deductions']);
        $tax = floatval($_POST['tax']);
        $net_salary = $basic_salary + $overtime_pay + $allowances - $deductions - $tax;

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $basic_salary,
            $overtime_pay,
            $allowances,
            $deductions,
            $tax,
            $net_salary,
            $salary_id
        ]);

        $conn->commit();
        $success = 'Payroll updated successfully';

        // Refresh payroll data
        $stmt = $conn->prepare("
            SELECT s.*, u.full_name, u.hourly_rate
            FROM salary s
            JOIN users u ON s.user_id = u.user_id
            WHERE s.salary_id = ?
        ");
        $stmt->execute([$salary_id]);
        $payroll = $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $conn->rollBack();
        $error = 'Error updating payroll: ' . $e->getMessage();
    }
}

$page_title = 'Edit Payroll';
include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow">
                <div class="card-header">
                    <h4 class="mb-0">Edit Payroll - <?php echo htmlspecialchars($payroll['full_name']); ?></h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <form method="POST" class="needs-validation" novalidate>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Staff Name</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($payroll['full_name']); ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Period</label>
                                <input type="text" class="form-control" value="<?php echo date('F Y', strtotime($payroll['month_year'])); ?>" readonly>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Basic Salary</label>
                                <input type="number" step="0.01" class="form-control" name="basic_salary" 
                                       value="<?php echo $payroll['basic_salary']; ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Overtime Pay</label>
                                <input type="number" step="0.01" class="form-control" name="overtime_pay" 
                                       value="<?php echo $payroll['overtime_pay']; ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Allowances</label>
                                <input type="number" step="0.01" class="form-control" name="allowances" 
                                       value="<?php echo $payroll['allowances']; ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Deductions</label>
                                <input type="number" step="0.01" class="form-control" name="deductions" 
                                       value="<?php echo $payroll['deductions']; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tax</label>
                                <input type="number" step="0.01" class="form-control" name="tax" 
                                       value="<?php echo $payroll['tax']; ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary">Update Payroll</button>
                            <a href="index.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>