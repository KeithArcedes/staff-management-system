<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../vendor/autoload.php';

redirectIfNotAdmin();

$db = new Database();
$conn = $db->getConnection();

$errors = [];
$formData = [
    'username' => '',
    'email' => '',
    'full_name' => '',
    'hourly_rate' => ''
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $formData = [
        'username' => trim($_POST['username']),
        'email' => trim($_POST['email']),
        'full_name' => trim($_POST['full_name']),
        'hourly_rate' => trim($_POST['hourly_rate'])
    ];

    // Validate username
    if (empty($formData['username'])) {
        $errors['username'] = 'Username is required';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $formData['username'])) {
        $errors['username'] = 'Username can only contain letters, numbers and underscores';
    } else {
        // Check if username exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->execute([$formData['username']]);
        if ($stmt->fetch()) {
            $errors['username'] = 'Username already taken';
        }
    }

    // Validate email
    if (empty($formData['email'])) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$formData['email']]);
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

    // If no errors, create staff
    if (empty($errors)) {
        // Use default password instead of random
        $password = "123456"; // Default password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        try {
            $conn->beginTransaction();

            // Insert new staff
            $stmt = $conn->prepare("
                INSERT INTO users (
                    username, 
                    password, 
                    email, 
                    full_name, 
                    role_id, 
                    hourly_rate, 
                    is_active, 
                    created_at, 
                    updated_at
                ) VALUES (?, ?, ?, ?, 2, ?, 1, NOW(), NOW())
            ");

            $stmt->execute([
                $formData['username'],
                $hashedPassword,
                $formData['email'],
                $formData['full_name'],
                $formData['hourly_rate']
            ]);

            $staff_id = $conn->lastInsertId();
            
            $conn->commit();

            // Send welcome email
            if (sendStaffWelcomeEmail(
                $formData['email'], 
                $formData['username'], 
                $password, 
                $formData['full_name']
            )) {
                $_SESSION['flash']['success'] = 'Staff added and welcome email sent';
            } else {
                $_SESSION['flash']['warning'] = 'Staff added but email failed to send';
            }
            
            header("Location: view.php?id=" . $staff_id);
            exit();
        } catch (PDOException $e) {
            $conn->rollBack();
            $errors['database'] = 'Database error: ' . $e->getMessage();
        }
    }
}

$page_title = 'Add New Staff';
include '../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header">
                <h4 class="mb-0">Add New Staff Member</h4>
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

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Add Staff Member</button>
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>