<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

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
    exit('Payroll not found');
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Payslip - <?php echo htmlspecialchars($payroll['full_name']); ?></title>
    <style>
        @media print {
            body {
                font-family: Arial, sans-serif;
                font-size: 12pt;
                line-height: 1.4;
            }
            .payslip {
                max-width: 800px;
                margin: 0 auto;
                padding: 20px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 10px 0;
            }
            th, td {
                padding: 8px;
                border: 1px solid #ddd;
            }
            .header {
                text-align: center;
                margin-bottom: 20px;
            }
            .footer {
                margin-top: 30px;
                text-align: center;
            }
            .text-end {
                text-align: right;
            }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="payslip">
        <div class="header">
            <h2><?php echo APP_NAME; ?></h2>
            <h3>PAYSLIP</h3>
            <p>For the month of <?php echo date('F Y', strtotime($payroll['month_year'])); ?></p>
        </div>

        <table>
            <tr>
                <th>Employee Name:</th>
                <td><?php echo htmlspecialchars($payroll['full_name']); ?></td>
                <th>Payment Date:</th>
                <td><?php echo $payroll['payment_date'] ? date('M j, Y', strtotime($payroll['payment_date'])) : 'Pending'; ?></td>
            </tr>
        </table>

        <h4>Earnings</h4>
        <table>
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
            <tr>
                <th>Gross Salary</th>
                <td class="text-end">₱<?php echo number_format(
                    $payroll['basic_salary'] + $payroll['overtime_pay'] + $payroll['allowances'], 
                    2
                ); ?></td>
            </tr>
        </table>

        <h4>Deductions</h4>
        <table>
            <tr>
                <th>Deductions</th>
                <td class="text-end">₱<?php echo number_format($payroll['deductions'], 2); ?></td>
            </tr>
            <tr>
                <th>Tax</th>
                <td class="text-end">₱<?php echo number_format($payroll['tax'], 2); ?></td>
            </tr>
            <tr>
                <th>Total Deductions</th>
                <td class="text-end">₱<?php echo number_format($payroll['deductions'] + $payroll['tax'], 2); ?></td>
            </tr>
        </table>

        <h4>Net Pay</h4>
        <table>
            <tr>
                <th>Net Salary</th>
                <td class="text-end">₱<?php echo number_format($payroll['net_salary'], 2); ?></td>
            </tr>
        </table>

        <div class="footer">
            <p>This is a computer-generated document. No signature is required.</p>
        </div>
    </div>
</body>
</html>