<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
redirectIfNotAdmin();

$page_title = 'Manage Staff';
include '../../includes/staff_header.php';

$db = new Database();
$conn = $db->getConnection();

// Handle staff deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    
    try {
        $conn->beginTransaction();
        
        // First delete related attendance records
        $stmt = $conn->prepare("DELETE FROM attendance WHERE user_id = ?");
        $stmt->execute([$id]);
        
        // Then delete related salary records
        $stmt = $conn->prepare("DELETE FROM salary WHERE user_id = ?");
        $stmt->execute([$id]);
        
        // Finally delete the user
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ? AND role_id = 2");
        $stmt->execute([$id]);
        
        $conn->commit();
        $_SESSION['flash']['success'] = 'Staff member and related records deleted successfully';
        
    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['flash']['danger'] = 'Error deleting staff member: ' . $e->getMessage();
    }
    
    header("Location: index.php");
    exit();
}

// Get all staff members
$stmt = $conn->query("
    SELECT u.*, r.role_name 
    FROM users u
    JOIN roles r ON u.role_id = r.role_id
    WHERE u.role_id = 2
    ORDER BY u.created_at DESC
");
$staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card shadow">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0">Staff Members</h4>
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Add Staff
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Hourly Rate</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($staff as $member): ?>
                        <tr>
                            <td><?php echo $member['user_id']; ?></td>
                            <td>
                                <?php echo htmlspecialchars($member['full_name']); ?>
                                <?php if ($member['qr_code']): ?>
                                    <span class="badge bg-info ms-2">QR Generated</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($member['email']); ?></td>
                            <td><?php echo 'Php' . number_format($member['hourly_rate'], 2); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $member['is_active'] ? 'success' : 'secondary'; ?>">
                                    <?php echo $member['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="view.php?id=<?php echo $member['user_id']; ?>" class="btn btn-sm btn-info" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit.php?id=<?php echo $member['user_id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="index.php?delete=<?php echo $member['user_id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <?php if (!$member['qr_code']): ?>
                                        <a href="../qr/generate.php?id=<?php echo $member['user_id']; ?>" class="btn btn-sm btn-success" title="Generate QR">
                                            <i class="fas fa-qrcode"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>