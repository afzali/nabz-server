<?php
// Include guard to prevent multiple inclusions
if (!defined('CONFIG_INCLUDED')) {
    define('CONFIG_INCLUDED', true);

    // Include authentication configuration
    require_once __DIR__ . '/auth_config.php';

    // Define base directory for all database files
    $base_dir = __DIR__;
    $db_dir = $base_dir . '/databases';
    
    // Create databases directory if it doesn't exist
    if (!file_exists($db_dir)) {
        mkdir($db_dir, 0777, true);
    }
    
    $main_db = $base_dir . '/users.db';

    function get_user_db($username) {
        global $db_dir, $auth_config;
        
        if ($auth_config['use_uuid_for_user_db']) {
            // Generate a UUID based on the username (for consistency)
            $uuid = md5($username);
            return $db_dir . '/' . $uuid . '.db';
        } else {
            return $db_dir . '/' . $username . '.db';
        }
    }

    function connect_db($db_file) {
        try {
            $db = new PDO('sqlite:' . $db_file);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $db;
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }
}
