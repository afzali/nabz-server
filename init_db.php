<?php
// Initialize database script
require 'config.php';

// Create main database
global $main_db, $db_dir;

echo "Database directory: $db_dir\n";
echo "Main database file: $main_db\n";

// Make sure the database directory exists
if (!file_exists($db_dir)) {
    if (mkdir($db_dir, 0777, true)) {
        echo "Created database directory: $db_dir\n";
    } else {
        echo "Failed to create database directory: $db_dir\n";
        exit(1);
    }
}

// Initialize main database
try {
    $db = connect_db($main_db);
    $db->exec('CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL
    )');
    echo "Successfully initialized main database\n";
} catch (Exception $e) {
    echo "Error initializing database: " . $e->getMessage() . "\n";
}
