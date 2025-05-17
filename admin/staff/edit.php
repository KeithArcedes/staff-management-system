<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../vendor/autoload.php';

redirectIfNotAdmin();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$staff_id = $_GET['id'];

$db = new Database();
$conn = $db->getConnection();

// Get current staff data
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ? AND role_id = 2");
$stmt->execute([$staff_id]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$staff) {
    $_SESSION['flash']['danger'] = 'Staff member not found';
    header("Location: index.php");
    exit();
}

$errors = [];
$formData = [
    'username' => $staff['username'],
    'email' => $staff['email'],
    'full_name' => $staff['full_name'],
    'hourly_rate' => $staff['hourly_rate'],
    'is_active' => $staff['is_active']
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $formData = [
        'username' => trim($_POST['username']),
        'email' => trim($_POST['email']),
        'full_name' => trim($_POST['full_name']),
        'hourly_rate' => trim($_POST['hourly_rate']),
        'is_active' => isset($_POST['is_active']) ? 1 : 0
    ];

    // Validate username
    if (empty($formData['username'])) {
        $errors['username'] = 'Username is required';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $formData['username'])) {
        $errors['username'] = 'Username can only contain letters, numbers and underscores';
    } elseif ($formData['username'] != $staff['username']) {
        // Check if username exists (only if changed)
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
        $stmt->execute([$formData['username'], $staff_id]);
        if ($stmt->fetch()) {
            $errors['username'] = 'Username already taken';
        }
    }

    // Validate email
    if (empty($formData['email'])) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    } elseif ($formData['email'] != $staff['email']) {
        // Check if email exists (only if changed)
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $stmt->execute([$formData['email'], $staff_id]);
        if ($stmt->fetch()) {
            $errors['email'] = 'Email already registered';
        }
    }

    // Validate full name
    if (empty($formData['full_name'])) {
        $errors['full_name'] = 'Full name is required';
    }

    // Validate hourly rate
    if (empty($formData['hourly_rate'])) {
        $errors['hourly_rate'] = 'Hourly rate is required';
    } elseif (!is_numeric($formData['hourly_rate']) || $formData['hourly_rate'] < 0) {
        $errors['hourly_rate'] = 'Hourly rate must be a positive number';
    }

    // If no errors, update staff
    if (empty($errors)) {
        try {
            $conn->beginTransaction();

            $stmt = $conn->prepare("
                UPDATE users SET 
                    username = ?,
                    email = ?,
                    full_name = ?,
                    hourly_rate = ?,
                    is_active = ?,
                    updated_at = NOW()
                WHERE user_id = ?
            ");

            $stmt->execute([
                $formData['username'],
                $formData['email'],
                $formData['full_name'],
                $formData['hourly_rate'],
                $formData['is_active'],
                $staff_id
            ]);

            $conn->commit();

            $_SESSION['flash']['success'] = 'Staff member updated successfully!';
            header("Location: view.php?id=" . $staff_id);
            exit();
        } catch (PDOException $e) {
            $conn->rollBack();
            $errors['database'] = 'Database error: ' . $e->getMessage();
        }
    }
}

$page_title = 'Edit Staff: ' . htmlspecialchars($staff['full_name']);
include '../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header">
                <h4 class="mb-0">Edit Staff Member</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($errors['database'])): ?>
                    <div class="alert alert-danger"><?php echo $errors['database']; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>" 
                               id="username" name="username" value="<?php echo htmlspecialchars($formData['username']); ?>" required>
                        <?php if (isset($errors['username'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['username']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                               id="email" name="email" value="<?php echo htmlspecialchars($formData['email']); ?>" required>
                        <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control <?php echo isset($errors['full_name']) ? 'is-invalid' : ''; ?>" 
                               id="full_name" name="full_name" value="<?php echo htmlspecialchars($formData['full_name']); ?>" required>
                        <?php if (isset($errors['full_name'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['full_name']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="hourly_rate" class="form-label">Hourly Rate (Php)</label>
                        <input type="number" step="0.01" min="0" 
                               class="form-control <?php echo isset($errors['hourly_rate']) ? 'is-invalid' : ''; ?>" 
                               id="hourly_rate" name="hourly_rate" 
                               value="<?php echo htmlspecialchars($formData['hourly_rate']); ?>" required>
                        <?php if (isset($errors['hourly_rate'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['hourly_rate']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" 
                               <?php echo $formData['is_active'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_active">Active Staff Member</label>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Update Staff Member</button>
                        <a href="view.php?id=<?php echo $staff_id; ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>