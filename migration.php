<?php

require 'config.php';

function migrate_all_databases() {
    global $main_db;
    $db = connect_db($main_db);
    
    // Get all users
    $stmt = $db->query('SELECT username FROM users');
    $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($users as $username) {
        $user_db = connect_db(get_user_db($username));
        
        // Example migration: Add a new column if it doesn't exist
        try {
            $user_db->exec('ALTER TABLE activities ADD COLUMN duration INTEGER DEFAULT 0');
        } catch (PDOException $e) {
            // Column might already exist
        }
    }
}
