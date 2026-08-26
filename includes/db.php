<?php
// includes/db.php

$dbDir = __DIR__ . '/../database';
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0777, true);
}

$sqliteFile = $dbDir . '/tutorlink.db';

try {
    // Standard PDO connection to SQLite
    $pdo = new PDO("sqlite:" . $sqliteFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Enable foreign keys in SQLite
    $pdo->exec("PRAGMA foreign_keys = ON;");
} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}

/**
 * Helper function to return DB connection
 */
function getDBConnection() {
    global $pdo;
    return $pdo;
}
