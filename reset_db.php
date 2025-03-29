<?php
/**
 * Database reset utility for Nabz Server
 * Use this script to reset a user's database if there are structural issues
 */

require_once 'config.php';
require_once 'auth.php';
require_once 'events.php';

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if username is provided
$username = isset($_GET['username']) ? $_GET['username'] : null;

if (!$username) {
    die("Error: Username is required. Use ?username=your_username in the URL.");
}

// Get the database path
$db_path = get_user_db($username);

// Check if the database exists
if (file_exists($db_path)) {
    // Backup the database first
    $backup_path = $db_path . '.backup.' . time();
    copy($db_path, $backup_path);
    echo "Database backed up to: " . basename($backup_path) . "<br>";
    
    // Delete the database
    unlink($db_path);
    echo "Old database deleted.<br>";
}

// Create a new database
$db = connect_db($db_path);

// Create the activities table with the correct schema
$db->exec('CREATE TABLE IF NOT EXISTS activities (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    title TEXT,
    description TEXT,
    category TEXT,
    icon TEXT,
    tags TEXT,
    createDate TEXT NOT NULL,
    updateDate TEXT NOT NULL,
    start TEXT,
    end TEXT,
    state TEXT,
    count REAL,
    countUnit TEXT,
    feedback TEXT,
    countCondition TEXT,
    timeCondition TEXT,
    durationCondition TEXT,
    notif TEXT,
    repetition TEXT,
    error TEXT
)');

echo "New database created with correct schema.<br>";
echo "Database reset complete for user: " . htmlspecialchars($username) . "<br>";
echo "<a href='/nabz-server/public/events-test.html'>Return to Events Test Page</a>";
?>
