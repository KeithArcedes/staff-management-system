<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header("Location: " . (isAdmin() ? 'admin/dashboard.php' : 'staff/dashboard.php'));
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = ($user['role_id'] == 1) ? 'admin' : 'staff';

        header("Location: " . (isAdmin() ? 'admin/dashboard.php' : 'staff/dashboard.php'));
        exit();
    } else {
        $error = 'Invalid username or password';
    }
}

$page_title = 'Login';
include 'includes/login_header.php';
?>
<style>
        .gradient-background {
        background: linear-gradient(135deg, #0061f2 0%, #6610f2 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>
<div class="row min-vh-100 justify-content-center align-items-center gradient-background">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow">
            <div class="card-body">
                <h2 class="card-title text-center mb-4"><?php echo APP_NAME; ?></h2>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>
                <div class="text-center mt-3">
                    <p>Or <a href="scan-attendance.php">scan your QR code</a> for quick attendance</p>
                </div>
                <a href="index.php" class="btn btn-secondary w-100 mt-3">Return</a>
            </div>
        </div>
    </div>
</div>
