<?php
/**
 * Middleware and Security Helpers
 */

class AuthMiddleware {
    public static function initSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start([
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax'
            ]);
        }
    }

    public static function requireLogin(): array {
        self::initSession();
        if (empty($_SESSION['user_id'])) {
            header('Location: login.php');
            exit;
        }
        return $_SESSION['user'];
    }

    public static function requireAdmin(): array {
        $user = self::requireLogin();
        if (($user['role'] ?? '') !== 'admin') {
            http_response_code(403);
            die("Access Denied: Administrator permissions required.");
        }
        return $user;
    }

    public static function getCurrentUser(): ?array {
        self::initSession();
        return $_SESSION['user'] ?? null;
    }

    public static function generateCsrfToken(): string {
        self::initSession();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrfToken(?string $token): bool {
        self::initSession();
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}
