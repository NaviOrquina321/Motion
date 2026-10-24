<?php
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Models/SystemSetting.php';
require_once __DIR__ . '/../Models/ActivityLog.php';
require_once __DIR__ . '/../Middleware/AuthMiddleware.php';

class AuthController {
    public static function install(array $data): array {
        if (SystemSetting::get('system_installed') === 'true') {
            return ['success' => false, 'error' => 'System installation is already completed.'];
        }

        $fullName = trim($data['full_name'] ?? '');
        $username = trim($data['username'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $confirmPassword = $data['confirm_password'] ?? '';

        if (empty($fullName) || empty($username) || empty($email) || empty($password)) {
            return ['success' => false, 'error' => 'All fields are required.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid email address.'];
        }

        if ($password !== $confirmPassword) {
            return ['success' => false, 'error' => 'Passwords do not match.'];
        }

        if (strlen($password) < 8) {
            return ['success' => false, 'error' => 'Password must be at least 8 characters long.'];
        }

        $admin = new User();
        $admin->full_name = $fullName;
        $admin->username = $username;
        $admin->email = $email;
        $admin->password_hash = password_hash($password, PASSWORD_BCRYPT);
        $admin->role = 'admin';
        $admin->storage_quota = 107374182400; // 100GB for Admin

        if ($admin->save()) {
            SystemSetting::set('system_installed', 'true');
            ActivityLog::log($admin->id, 'System initial installation completed');
            return ['success' => true, 'message' => 'Administrator account created successfully! You can now log in.'];
        }

        return ['success' => false, 'error' => 'Failed to create administrator user.'];
    }

    public static function register(array $data): array {
        $fullName = trim($data['full_name'] ?? '');
        $username = trim($data['username'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $confirmPassword = $data['confirm_password'] ?? '';

        if (empty($fullName) || empty($username) || empty($email) || empty($password)) {
            return ['success' => false, 'error' => 'All fields are required.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid email address.'];
        }

        if ($password !== $confirmPassword) {
            return ['success' => false, 'error' => 'Passwords do not match.'];
        }

        if (User::emailExists($email)) {
            return ['success' => false, 'error' => 'Email address is already registered.'];
        }

        if (User::usernameExists($username)) {
            return ['success' => false, 'error' => 'Username is already taken.'];
        }

        $user = new User();
        $user->full_name = $fullName;
        $user->username = $username;
        $user->email = $email;
        $user->password_hash = password_hash($password, PASSWORD_BCRYPT);
        $user->role = 'user';
        $user->storage_quota = (int)SystemSetting::get('default_storage_quota', 10737418240);

        if ($user->save()) {
            ActivityLog::log($user->id, 'Registered new account');
            return ['success' => true, 'message' => 'Registration successful! You may now login.'];
        }

        return ['success' => false, 'error' => 'Registration failed. Please try again.'];
    }

    public static function login(string $identifier, string $password): array {
        $user = User::findByEmailOrUsername($identifier);
        if (!$user) {
            return ['success' => false, 'error' => 'Invalid credentials.'];
        }

        if (!password_verify($password, $user->password_hash)) {
            return ['success' => false, 'error' => 'Invalid credentials.'];
        }

        AuthMiddleware::initSession();
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user->id;
        $_SESSION['user'] = [
            'id' => $user->id,
            'full_name' => $user->full_name,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
            'storage_quota' => $user->storage_quota
        ];

        ActivityLog::log($user->id, 'User logged in');
        return ['success' => true];
    }

    public static function logout(): void {
        AuthMiddleware::initSession();
        $userId = $_SESSION['user_id'] ?? null;
        if ($userId) {
            ActivityLog::log($userId, 'User logged out');
        }
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }
}
