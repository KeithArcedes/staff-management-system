<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
redirectIfNotAdmin();

$db = new Database();
$conn = $db->getConnection();

// Get date range from query parameters or default to current month
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Get attendance data
$stmt = $conn->prepare("
    SELECT 
        a.attendance_id,
        a.user_id,
        u.full_name,
        DATE(a.check_in) as date,
        TIME(a.check_in) as check_in_time,
        TIME(a.check_out) as check_out_time,
        TIMESTAMPDIFF(HOUR, a.check_in, a.check_out) as hours
    FROM attendance a
    JOIN users u ON a.user_id = u.user_id
    WHERE DATE(a.check_in) BETWEEN ? AND ?
    ORDER BY a.check_in DESC
");
$stmt->execute([$start_date, $end_date]);
$attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Attendance Report';
include '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="card shadow mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Attendance Report</h5>
            <button onclick="exportToExcel()" class="btn btn-success">
                <i class="fas fa-file-excel me-2"></i>Export to Excel
            </button>
        </div>
        <div class="card-body">
            <!-- Date Range Filter -->
            <form method="GET" class="row mb-4">
                <div class="col-md-4">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" 
                           value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" 
                           value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-2"></i>Filter
                    </button>
                </div>
            </form>

            <!-- Attendance Table -->
            <div class="table-responsive">
                <table class="table table-striped" id="attendanceTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Staff Name</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Hours</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendance as $row): ?>
                            <tr>
                                <td><?php echo date('M j, Y', strtotime($row['date'])); ?></td>
                                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                <td><?php echo date('h:i A', strtotime($row['check_in_time'])); ?></td>
                                <td>
                                    <?php 
                                    echo $row['check_out_time'] 
                                        ? date('h:i A', strtotime($row['check_out_time']))
                                        : 'Not checked out';
                                    ?>
                                </td>
                                <td><?php echo $row['hours'] ?? 'N/A'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/xlsx/dist/xlsx.full.min.js"></script>
<script>
function exportToExcel() {
    const table = document.getElementById('attendanceTable');
    const wb = XLSX.utils.table_to_book(table, {sheet: 'Attendance'});
    const filename = 'attendance_report_<?php echo date("Y-m-d"); ?>.xlsx';
    XLSX.writeFile(wb, filename);
}
</script>

<?php include '../../includes/footer.php'; ?>