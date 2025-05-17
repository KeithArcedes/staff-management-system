<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../vendor/autoload.php';

redirectIfNotAdmin();

$db = new Database();
$conn = $db->getConnection();

// Process payroll generation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and format the month_year
    $month_year = !empty($_POST['month_year']) ? $_POST['month_year'] : date('Y-m');
    
    // Ensure proper date format YYYY-MM
    if (!preg_match("/^\d{4}-\d{2}$/", $month_year)) {
        $_SESSION['flash']['danger'] = "Invalid date format. Please use YYYY-MM format.";
        header("Location: index.php");
        exit();
    }
    
    // Format dates for the selected month with proper validation
    try {
        $date = new DateTime($month_year . '-01');
        $period_start = $date->format('Y-m-d');
        $period_end = $date->format('Y-m-t');
    } catch (Exception $e) {
        $_SESSION['flash']['danger'] = "Invalid date selected";
        header("Location: index.php");
        exit();
    }

    // Get all active staff
    $staff = $conn->prepare("
        SELECT user_id, full_name, hourly_rate 
        FROM users 
        WHERE role_id = 2 AND is_active = 1
    ");
    $staff->execute();
    $staff = $staff->fetchAll(PDO::FETCH_ASSOC);
    
    $processed = 0;
    $errors = [];
    
    foreach ($staff as $member) {
        try {
            $conn->beginTransaction();
            
            // Calculate basic hours (8 hours/day * work days)
            $basic_hours = $conn->prepare("
                SELECT 
                    COUNT(DISTINCT DATE(check_in)) AS work_days,
                    SUM(
                        CASE 
                            WHEN TIMESTAMPDIFF(HOUR, check_in, check_out) <= 8 THEN TIMESTAMPDIFF(HOUR, check_in, check_out)
                            ELSE 8
                        END
                    ) AS basic_hours
                FROM attendance
                WHERE 
                    user_id = ? AND
                    check_in >= ? AND 
                    check_out <= ? AND
                    check_out IS NOT NULL
            ");
            $basic_hours->execute([$member['user_id'], $period_start, $period_end]);
            $hours = $basic_hours->fetch(PDO::FETCH_ASSOC);
            
            // Calculate overtime hours
            $overtime = $conn->prepare("
                SELECT SUM(
                    CASE 
                        WHEN TIMESTAMPDIFF(HOUR, check_in, check_out) > 8 THEN TIMESTAMPDIFF(HOUR, check_in, check_out) - 8
                        ELSE 0
                    END
                ) AS overtime_hours
                FROM attendance
                WHERE 
                    user_id = ? AND
                    check_in >= ? AND 
                    check_out <= ? AND
                    check_out IS NOT NULL
            ");
            $overtime->execute([$member['user_id'], $period_start, $period_end]);
            $overtime_hours = $overtime->fetchColumn();
            
            // Calculate basic salary (basic hours * hourly rate)
            $basic_salary = $hours['basic_hours'] * $member['hourly_rate'];
            
            // Calculate overtime pay (overtime hours * 1.25 * hourly rate)
            $overtime_pay = $overtime_hours * $member['hourly_rate'] * 1.25;
            
            // Default allowances and deductions (can be customized)
            $allowances = 0;
            $deductions = 500;
            
            // Simple tax calculation (10% of basic salary)
            $tax = $basic_salary * 0.10;
            
            // Calculate net salary
            $net_salary = $basic_salary + $overtime_pay + $allowances - $deductions - $tax;
            
            // Check if payroll already exists for this period
            $exists = $conn->prepare("
                SELECT salary_id FROM salary 
                WHERE user_id = ? AND month_year = ?
            ");
            $exists->execute([$member['user_id'], $month_year]);
            
            if (!$exists->fetch()) {
                // Insert payroll record
                $insert = $conn->prepare("
                    INSERT INTO salary (
                        user_id, month_year, basic_salary, 
                        overtime_hours, overtime_pay, allowances, 
                        deductions, tax, net_salary,
                        created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $insert->execute([
                    $member['user_id'],
                    $month_year . '-01', // Ensure proper date format
                    $basic_salary,
                    $overtime_hours,
                    $overtime_pay,
                    $allowances,
                    $deductions,
                    $tax,
                    $net_salary
                ]);
                $processed++;
            }
            
            $conn->commit();
        } catch (PDOException $e) {
            $conn->rollBack();
            $errors[] = "Error processing payroll for {$member['full_name']}: " . $e->getMessage();
        }
    }
    
    if (!empty($errors)) {
        $_SESSION['flash']['danger'] = implode('<br>', $errors);
    } else {
        $_SESSION['flash']['success'] = "Payroll generated for $processed staff members";
    }
    
    header("Location: index.php");
    exit();
}

// Set default value for the month input
$default_month = date('Y-m');
$page_title = 'Generate Payroll';
include '../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-header">
                <h4 class="mb-0">Generate Payroll</h4>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="month_year" class="form-label">Select Month</label>
                        <input type="month" class="form-control" id="month_year" name="month_year" 
                               value="<?php echo $default_month; ?>" required>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-calculator me-2"></i>Generate Payroll
                        </button>
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>