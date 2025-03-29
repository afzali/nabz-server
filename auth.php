<?php

require 'config.php';
require 'logger.php';

/**
 * Validates a username against configuration rules
 * 
 * @param string $username The username to validate
 * @return array Result with success status and message
 */
function validate_username($username) {
    global $auth_config;
    
    // Check minimum length
    if (strlen($username) < $auth_config['min_username_length']) {
        return [
            'success' => false, 
            'message' => 'Username must be at least ' . $auth_config['min_username_length'] . ' characters'
        ];
    }
    
    // Check if username is in restricted list
    if (in_array(strtolower($username), array_map('strtolower', $auth_config['restricted_usernames']))) {
        return [
            'success' => false, 
            'message' => 'This username is not available'
        ];
    }
    
    // Validate format based on login type
    if ($auth_config['login_type'] === 'email' || $auth_config['login_type'] === 'any') {
        // If email is required or allowed, check if it's a valid email when it looks like one
        if (strpos($username, '@') !== false && !filter_var($username, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false, 
                'message' => 'Invalid email format'
            ];
        }
    }
    
    if ($auth_config['login_type'] === 'phone' || $auth_config['login_type'] === 'any') {
        // If phone is required or allowed, check if it's a valid phone when it looks like one
        if (preg_match('/^[0-9+\-\s()]+$/', $username) && !preg_match('/^[0-9+\-\s()]{8,15}$/', $username)) {
            return [
                'success' => false, 
                'message' => 'Invalid phone number format'
            ];
        }
    }
    
    return ['success' => true];
}

/**
 * Validates a password against configuration rules
 * 
 * @param string $password The password to validate
 * @return array Result with success status and message
 */
function validate_password($password) {
    global $auth_config;
    
    // Check minimum length
    if (strlen($password) < $auth_config['min_password_length']) {
        return [
            'success' => false, 
            'message' => 'Password must be at least ' . $auth_config['min_password_length'] . ' characters'
        ];
    }
    
    return ['success' => true];
}

/**
 * Registers a new user
 * 
 * @param string $username The username
 * @param string $password The password
 * @return array Result with success status and message
 */
function registerUser($username, $password) {
    global $main_db, $auth_config;
    
    // Check if registration is enabled
    if (!$auth_config['enable_registration']) {
        return ['success' => false, 'message' => 'Registration is currently disabled'];
    }
    
    // Validate username
    $username_validation = validate_username($username);
    if (!$username_validation['success']) {
        return $username_validation;
    }
    
    // Validate password
    $password_validation = validate_password($password);
    if (!$password_validation['success']) {
        return $password_validation;
    }
    
    $db = connect_db($main_db);
    
    // Check if user exists
    $stmt = $db->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Username already exists'];
    }
    
    // Create user
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare('INSERT INTO users (username, password) VALUES (?, ?)');
    $stmt->execute([$username, $hashed_password]);
    
    // Create user's database
    $user_db_path = get_user_db($username);
    $user_db = connect_db($user_db_path);
    
    // Example: Create a table for user data
    $user_db->exec('CREATE TABLE IF NOT EXISTS activities (
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
    
    // Log the registration event
    log_auth_event($username, 'REGISTRATION', 'SUCCESS');
    
    return ['success' => true, 'message' => 'Registration successful'];
}

/**
 * Logs in a user
 * 
 * @param string $username The username
 * @param string $password The password
 * @return array Result with success status and message
 */
function loginUser($username, $password) {
    global $main_db;
    
    $db = connect_db($main_db);
    
    $stmt = $db->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($password, $user['password'])) {
        // Log failed login attempt
        log_auth_event($username, 'LOGIN', 'FAILED - Invalid credentials');
        return ['success' => false, 'message' => 'Invalid username or password'];
    }
    
    // Log successful login
    log_auth_event($username, 'LOGIN', 'SUCCESS');
    
    return [
        'success' => true, 
        'message' => 'Login successful',
        'user_id' => $user['id'],
        'username' => $user['username']
    ];
}
