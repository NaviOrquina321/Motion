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
$success = isset($_GET['installed']) ? 'System setup complete! Please log in with your administrator account.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!AuthMiddleware::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $identifier = trim($_POST['identifier'] ?? '');
        $password = $_POST['password'] ?? '';

        $res = AuthController::login($identifier, $password);
        if ($res['success']) {
            header('Location: dashboard.php');
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
    <title>Login - CloudVault</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-navy text-light min-vh-100 d-flex align-items-center py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card card-custom border-0 shadow-lg p-4">
                    <div class="text-center mb-4">
                        <a href="index.php" class="text-decoration-none">
                            <h2 class="fw-bold text-primary"><i class="bi bi-cloud-arrow-up-fill me-2"></i>CloudVault</h2>
                        </a>
                        <p class="text-muted">Sign in to your account</p>
                    </div>

                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="login.php">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                        <div class="mb-3">
                            <label class="form-label">Email or Username</label>
                            <input type="text" name="identifier" class="form-control" required placeholder="name@example.com">
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                            Log In <i class="bi bi-box-arrow-in-right ms-1"></i>
                        </button>
                    </form>

                    <div class="text-center mt-4">
                        <p class="text-muted">Don't have an account? <a href="register.php" class="text-primary text-decoration-none">Register here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
