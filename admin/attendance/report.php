<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../vendor/autoload.php';

redirectIfNotAdmin();

$db = new Database();
$conn = $db->getConnection();

// Filter parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$staff_id = $_GET['staff_id'] ?? null;

// Build query
$query = "
    SELECT a.*, u.full_name, u.hourly_rate
    FROM attendance a
    JOIN users u ON a.user_id = u.user_id
    WHERE DATE(a.check_in) BETWEEN :start_date AND :end_date
";

$params = [
    'start_date' => $start_date,
    'end_date' => $end_date
];

if ($staff_id) {
    $query .= " AND a.user_id = :staff_id";
    $params['staff_id'] = $staff_id;
}

$query .= " ORDER BY a.check_in DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get staff list for filter
$staff = $conn->query("SELECT user_id, full_name FROM users WHERE role_id = 2 ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Attendance Report';
include '../../includes/header.php';
?>

<div class="card shadow">
    <div class="card-header">
        <h4 class="mb-0">Attendance Report</h4>
    </div>
    <div class="card-body">
        <form method="GET" class="mb-4">
            <div class="row">
                <div class="col-md-3">
                    <label>Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-3">
                    <label>End Date</label>
                    <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-4">
                    <label>Staff Member</label>
                    <select name="staff_id" class="form-select">
                        <option value="">All Staff</option>
                        <?php foreach ($staff as $member): ?>
                            <option value="<?php echo $member['user_id']; ?>" <?php echo $staff_id == $member['user_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($member['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Staff</th>
                        <th>Date</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Hours Worked</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attendance as $record): 
                        $check_in = new DateTime($record['check_in']);
                        $check_out = $record['check_out'] ? new DateTime($record['check_out']) : null;
                        
                        $hours_worked = $check_out 
                            ? number_format(($check_out->getTimestamp() - $check_in->getTimestamp()) / 3600, 2)
                            : 'N/A';
                            
                        $status = 'Present';
                        if (!$check_out) {
                            $status = 'Active';
                        }
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($record['full_name']); ?></td>
                            <td><?php echo $check_in->format('M j, Y'); ?></td>
                            <td><?php echo $check_in->format('h:i A'); ?></td>
                            <td><?php echo $check_out ? $check_out->format('h:i A') : 'N/A'; ?></td>
                            <td><?php echo $hours_worked; ?></td>
                            <td><?php echo $status; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>