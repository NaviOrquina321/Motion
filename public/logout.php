<?php
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';

AuthController::logout();
header('Location: login.php');
exit;
