<?php
/* GET -> returns the logged-in user (or 401). Every page calls this on load. */
require_once __DIR__ . '/../config/helpers.php';
json_out(['success' => true, 'user' => require_login()]);
