<?php
/* Database + app settings. Change these to match your MySQL / Python setup. */
const DB_HOST = '127.0.0.1';
const DB_NAME = 'quick_buy_retail';
const DB_USER = 'root';
const DB_PASS = '';

// Command used to run Python ("python" on Windows, "python3" on Mac/Linux)
const PYTHON_BIN = 'python3';

function db() {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,   // real prepared statements
            ]
        );
    }
    return $pdo;
}
