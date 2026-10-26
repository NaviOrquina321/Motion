<?php
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Views/Layout.php';

$currentUser = AuthMiddleware::requireLogin();
$userObj = User::findById($currentUser['id']);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!AuthMiddleware::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';

        if (empty($fullName) || empty($email)) {
            $error = 'Full name and email are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
        } elseif (User::emailExists($email, $userObj->id)) {
            $error = 'Email address is already in use.';
        } else {
            $userObj->full_name = $fullName;
            $userObj->email = $email;

            // Password update check
            if (!empty($newPassword)) {
                if (empty($currentPassword) || !password_verify($currentPassword, $userObj->password_hash)) {
                    $error = 'Incorrect current password.';
                } elseif (strlen($newPassword) < 8) {
                    $error = 'New password must be at least 8 characters.';
                } else {
                    $userObj->password_hash = password_hash($newPassword, PASSWORD_BCRYPT);
                }
            }

            if (empty($error)) {
                $userObj->save();
                $_SESSION['user']['full_name'] = $userObj->full_name;
                $_SESSION['user']['email'] = $userObj->email;
                $success = 'Account settings updated successfully.';
            }
        }
    }
}

$csrfToken = AuthMiddleware::generateCsrfToken();
renderHeader('Account Settings', 'settings');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-light mb-1"><i class="bi bi-gear text-primary me-2"></i>Account Settings</h2>
        <p class="text-muted mb-0">Update your profile details and security credentials.</p>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card card-custom p-4 mb-4">
            <form method="POST" action="settings.php">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <div class="mb-3">
                    <label class="form-label fw-bold">Full Name</label>
                    <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($userObj->full_name) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Username</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($userObj->username) ?>" disabled>
                    <small class="text-muted">Username cannot be changed.</small>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Email Address</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($userObj->email) ?>" required>
                </div>

                <hr class="border-secondary my-4">

                <h5 class="fw-bold text-light mb-3"><i class="bi bi-shield-lock me-2 text-warning"></i>Change Password</h5>

                <div class="mb-3">
                    <label class="form-label fw-bold">Current Password</label>
                    <input type="password" name="current_password" class="form-control">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">New Password</label>
                    <input type="password" name="new_password" class="form-control" minlength="8">
                </div>

                <button type="submit" class="btn btn-primary fw-bold py-2 px-4">
                    Save Changes <i class="bi bi-check-lg ms-1"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<?php renderFooter(); ?>
