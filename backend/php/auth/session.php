<?php
/* GET -> Returns current user session info and CSRF token. */
require_once __DIR__ . '/../config/helpers.php';
$u = require_login();

json_out([
    'success'    => true,
    'user'       => $u,
    'csrf_token' => $_SESSION['csrf_token'] ?? ''
]);
