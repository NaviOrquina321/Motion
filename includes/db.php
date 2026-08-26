<?php
// includes/db.php

$dbDir = __DIR__ . '/../database';
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0777, true);
}

$sqliteFile = $dbDir . '/tutorlink.db';
$pdo = null;

// MySQL Config (default for XAMPP / phpMyAdmin setup)
$host = getenv('DB_HOST') ?: '127.0.0.1';
$dbname = getenv('DB_NAME') ?: 'tutorlink';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';
$port = getenv('DB_PORT') ?: '3306';

try {
    // 1. Try MySQL / MariaDB first if XAMPP MySQL is active
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 2
    ]);
} catch (PDOException $e) {
    // 2. Fall back to SQLite if MySQL is not available or database doesn't exist yet
    try {
        $pdo = new PDO("sqlite:" . $sqliteFile);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec("PRAGMA foreign_keys = ON;");
    } catch (PDOException $sqliteEx) {
        die("Database Connection Error: " . $sqliteEx->getMessage());
    }
}

/**
 * Helper function to return DB connection
 */
function getDBConnection() {
    global $pdo;
    return $pdo;
}
