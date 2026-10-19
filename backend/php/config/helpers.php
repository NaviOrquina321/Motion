<?php
/* Shared helpers: session start, JSON output, login/role checks. Included by every API file. */
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

set_exception_handler(function ($e) {
    error_log($e->getMessage());
    fail('Server error. Please try again.', 500);
});

function json_out($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}
function fail($message, $code = 400) {
    json_out(['success' => false, 'message' => $message], $code);
}
function only_post() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST request required.', 405);
}
/* Reads JSON body (fetch with JSON) or falls back to normal form fields. */
function body() {
    static $b = null;
    if ($b === null) {
        $j = json_decode(file_get_contents('php://input') ?: '', true);
        $b = is_array($j) ? $j : $_POST;
    }
    return $b;
}
function require_login() {
    if (empty($_SESSION['user'])) fail('Please log in first.', 401);
    return $_SESSION['user'];
}
function require_role($role) {
    $u = require_login();
    if ($u['role'] !== $role) fail('Access denied. Admin only.', 403);
    return $u;
}
function clean($v) {
    return trim(strip_tags((string)$v));
}
function is_duplicate($e) {
    return $e instanceof PDOException && isset($e->errorInfo[1]) && $e->errorInfo[1] == 1062;
}
