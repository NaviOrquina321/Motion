<?php
// login.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];

            if ($user['role'] === 'student') {
                header("Location: student/dashboard.php");
            } elseif ($user['role'] === 'tutor') {
                header("Location: tutor/dashboard.php");
            } elseif ($user['role'] === 'admin') {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: index.php");
            }
            exit;
        } else {
            $error = 'Invalid email address or password.';
        }
    } else {
        $error = 'Please fill in all required fields.';
    }
}

$pageTitle = "Log In";
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card-static p-4 p-md-5 shadow-lg border-0">
                <div class="text-center mb-4">
                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 56px; height: 56px;">
                        <i class="fa-solid fa-graduation-cap fs-3"></i>
                    </div>
                    <h3 class="fw-bold text-dark">Welcome Back</h3>
                    <p class="text-muted">Sign in to access your TutorLink account</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger rounded-3" role="alert">
                        <i class="fa-solid fa-circle-exclamation me-2"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email Address</label>
                        <input type="email" name="email" class="form-control form-control-lg rounded-3" placeholder="name@example.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-semibold mb-0">Password</label>
                            <a href="#" class="font-size-xs text-primary text-decoration-none">Forgot password?</a>
                        </div>
                        <input type="password" name="password" class="form-control form-control-lg rounded-3" placeholder="••••••••" required>
                    </div>

                    <button type="submit" class="btn btn-primary-custom w-100 py-3 font-weight-bold">
                        Log In <i class="fa-solid fa-arrow-right ms-2"></i>
                    </button>
                </form>

                <div class="mt-4 pt-3 border-top text-center text-muted font-size-sm">
                    Don't have an account yet? <a href="register.php" class="text-primary fw-bold text-decoration-none">Create an Account</a>
                </div>

                <!-- Demo Accounts Hint -->
                <div class="mt-4 p-3 bg-light rounded-3 text-start font-size-xs">
                    <strong class="d-block text-dark mb-1"><i class="fa-solid fa-key text-warning me-1"></i> Demo Login Credentials:</strong>
                    <div class="text-muted mb-1">Student: <code>john.doe@student.com</code> / <code>password123</code></div>
                    <div class="text-muted mb-1">Tutor: <code>maria.santos@tutorlink.com</code> / <code>password123</code></div>
                    <div class="text-muted">Admin: <code>admin@tutorlink.com</code> / <code>password123</code></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
