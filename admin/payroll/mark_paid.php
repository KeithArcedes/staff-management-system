<?php
// mark_paid.php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../vendor/autoload.php';

redirectIfNotAdmin();

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['flash']['danger'] = 'Invalid request method';
    header("Location: index.php");
    exit();
}

if (!isset($_POST['salary_id']) || !is_numeric($_POST['salary_id'])) {
    $_SESSION['flash']['danger'] = 'Invalid payroll ID';
    header("Location: index.php");
    exit();
}

$salary_id = (int)$_POST['salary_id'];

$db = new Database();
$conn = $db->getConnection();

try {
    $conn->beginTransaction();
    
    // Verify payroll exists and is pending
    $check = $conn->prepare("
        SELECT salary_id 
        FROM salary 
        WHERE salary_id = ? AND payment_status = 'pending'
    ");
    $check->execute([$salary_id]);
    
    if (!$check->fetch()) {
        throw new Exception('Payroll not found or already paid');
    }

    // Update payroll status
    $stmt = $conn->prepare("
        UPDATE salary 
        SET payment_status = 'paid', 
            payment_date = CURDATE(), 
            updated_at = NOW() 
        WHERE salary_id = ?
    ");
    
    $stmt->execute([$salary_id]);
    
    if ($stmt->rowCount() > 0) {
        $conn->commit();
        $_SESSION['flash']['success'] = 'Payroll #' . $salary_id . ' marked as paid';
    } else {
        throw new Exception('No changes made to payroll');
    }
    
} catch (Exception $e) {
    $conn->rollBack();
    $_SESSION['flash']['danger'] = $e->getMessage();
}

// Redirect back to the previous page or index
header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
exit();
?>