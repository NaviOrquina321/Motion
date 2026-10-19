<?php
/* POST/GET -> destroys the session. */
require_once __DIR__ . '/../config/helpers.php';
$_SESSION = [];
session_destroy();
json_out(['success' => true]);
