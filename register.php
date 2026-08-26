<?php
// register.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'student';

    if (!empty($name) && !empty($email) && !empty($password) && in_array($role, ['student', 'tutor'])) {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $error = 'Email address is already registered.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $photo = 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&q=80&w=200';

            $stmtIns = $pdo->prepare("INSERT INTO users (name, email, password, role, profile_photo) VALUES (?, ?, ?, ?, ?)");
            if ($stmtIns->execute([$name, $email, $hashedPassword, $role, $photo])) {
                $userId = $pdo->lastInsertId();

                if ($role === 'student') {
                    $stmtSt = $pdo->prepare("INSERT INTO students (user_id) VALUES (?)");
                    $stmtSt->execute([$userId]);
                } else {
                    $stmtTu = $pdo->prepare("INSERT INTO tutors (user_id, title, bio, hourly_rate) VALUES (?, ?, ?, ?)");
                    $stmtTu->execute([$userId, 'Certified Educator', 'Passionate about helping students achieve academic success.', 250.00]);
                }

                $_SESSION['user_id'] = $userId;
                $_SESSION['role'] = $role;
                $_SESSION['name'] = $name;

                if ($role === 'student') {
                    header("Location: student/dashboard.php");
                } else {
                    header("Location: tutor/dashboard.php");
                }
                exit;
            } else {
                $error = 'Failed to create account. Please try again.';
            }
        }
    } else {
        $error = 'Please fill out all required fields properly.';
    }
}

$pageTitle = "Create an Account";
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card-static p-4 p-md-5 shadow-lg border-0">
                <div class="text-center mb-4">
                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 56px; height: 56px;">
                        <i class="fa-solid fa-user-plus fs-3"></i>
                    </div>
                    <h3 class="fw-bold text-dark">Join TutorLink</h3>
                    <p class="text-muted">Create your account to start learning or teaching</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger rounded-3" role="alert">
                        <i class="fa-solid fa-circle-exclamation me-2"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="register.php">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">I want to join as</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="role" id="role_student" value="student" checked>
                                <label class="btn btn-outline-primary w-100 py-2 font-weight-semibold rounded-3" for="role_student">
                                    <i class="fa-solid fa-user-graduate me-1"></i> Student
                                </label>
                            </div>
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="role" id="role_tutor" value="tutor">
                                <label class="btn btn-outline-primary w-100 py-2 font-weight-semibold rounded-3" for="role_tutor">
                                    <i class="fa-solid fa-chalkboard-user me-1"></i> Tutor
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Full Name</label>
                        <input type="text" name="name" class="form-control form-control-lg rounded-3" placeholder="e.g. Maria Santos" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email Address</label>
                        <input type="email" name="email" class="form-control form-control-lg rounded-3" placeholder="name@example.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Password</label>
                        <input type="password" name="password" class="form-control form-control-lg rounded-3" placeholder="Create a strong password" required>
                    </div>

                    <button type="submit" class="btn btn-primary-custom w-100 py-3 font-weight-bold">
                        Create Account <i class="fa-solid fa-arrow-right ms-2"></i>
                    </button>
                </form>

                <div class="mt-4 pt-3 border-top text-center text-muted font-size-sm">
                    Already have an account? <a href="login.php" class="text-primary fw-bold text-decoration-none">Log In</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
