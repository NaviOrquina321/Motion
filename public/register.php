<?php
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Models/SystemSetting.php';

AuthMiddleware::initSession();

if (SystemSetting::get('system_installed') !== 'true') {
    header('Location: install.php');
    exit;
}

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!AuthMiddleware::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $res = AuthController::register($_POST);
        if ($res['success']) {
            header('Location: login.php?registered=1');
            exit;
        } else {
            $error = $res['error'];
        }
    }
}

$csrfToken = AuthMiddleware::generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - CloudVault</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-navy text-light min-vh-100 d-flex align-items-center py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card card-custom border-0 shadow-lg p-4">
                    <div class="text-center mb-4">
                        <a href="index.php" class="text-decoration-none">
                            <h2 class="fw-bold text-primary"><i class="bi bi-cloud-arrow-up-fill me-2"></i>CloudVault</h2>
                        </a>
                        <p class="text-muted">Create your personal account</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="register.php">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control" required placeholder="John Doe">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" required placeholder="johndoe">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" required placeholder="john@example.com">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required minlength="8">
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control" required minlength="8">
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                            Create Account <i class="bi bi-person-plus-fill ms-1"></i>
                        </button>
                    </form>

                    <div class="text-center mt-4">
                        <p class="text-muted">Already have an account? <a href="login.php" class="text-primary text-decoration-none">Log in here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
