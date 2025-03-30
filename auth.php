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
    
    // Simple rate limiting - check for too many failed attempts
    if (checkLoginAttempts($username)) {
        log_auth_event($username, 'LOGIN', 'BLOCKED - Too many attempts');
        return ['success' => false, 'message' => 'Too many login attempts. Please try again later.'];
    }
    
    $db = connect_db($main_db);
    
    $stmt = $db->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($password, $user['password'])) {
        // Log failed login attempt
        log_auth_event($username, 'LOGIN', 'FAILED - Invalid credentials');
        // Record failed attempt
        recordLoginAttempt($username, false);
        return ['success' => false, 'message' => 'Invalid username or password'];
    }
    
    // Generate token with expiration (24 hours from now)
    $expiry = time() + (24 * 60 * 60);
    $token_data = [
        'username' => $user['username'],
        'exp' => $expiry
    ];
    $token = base64_encode(json_encode($token_data));
    
    // Log successful login
    log_auth_event($username, 'LOGIN', 'SUCCESS');
    // Reset failed attempts on successful login
    recordLoginAttempt($username, true);
    
    return [
        'success' => true, 
        'message' => 'Login successful',
        'user' => [
            'username' => $user['username'],
            'token' => $token
        ]
    ];
}

/**
 * Validates a token
 * 
 * @param string $token The token to validate
 * @return array|null User data if valid, null if invalid
 */
function validateToken($token) {
    try {
        $token_data = json_decode(base64_decode($token), true);
        
        // Check if token is expired
        if (!isset($token_data['exp']) || $token_data['exp'] < time()) {
            return null;
        }
        
        // Check if username exists
        if (!isset($token_data['username'])) {
            return null;
        }
        
        global $main_db;
        $db = connect_db($main_db);
        
        $stmt = $db->prepare('SELECT id, username FROM users WHERE username = ?');
        $stmt->execute([$token_data['username']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $user ?: null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Records a login attempt
 * 
 * @param string $username The username
 * @param bool $success Whether the login was successful
 */
function recordLoginAttempt($username, $success) {
    $attempts_file = __DIR__ . '/logs/login_attempts.json';
    
    // Create directory if it doesn't exist
    if (!file_exists(dirname($attempts_file))) {
        mkdir(dirname($attempts_file), 0755, true);
    }
    
    // Initialize or load attempts data
    $attempts = [];
    if (file_exists($attempts_file)) {
        $attempts = json_decode(file_get_contents($attempts_file), true) ?: [];
    }
    
    // Clean up old entries (older than 1 hour)
    $now = time();
    foreach ($attempts as $user => $data) {
        if ($data['timestamp'] < ($now - 3600)) {
            unset($attempts[$user]);
        }
    }
    
    // Update or create entry for this user
    if ($success) {
        // Reset on successful login
        unset($attempts[$username]);
    } else {
        // Increment count on failed login
        if (!isset($attempts[$username])) {
            $attempts[$username] = ['count' => 0, 'timestamp' => $now];
        }
        $attempts[$username]['count']++;
        $attempts[$username]['timestamp'] = $now;
    }
    
    // Save updated attempts data
    file_put_contents($attempts_file, json_encode($attempts));
}

/**
 * Checks if a user has too many failed login attempts
 * 
 * @param string $username The username
 * @return bool True if too many attempts, false otherwise
 */
function checkLoginAttempts($username) {
    $attempts_file = __DIR__ . '/logs/login_attempts.json';
    
    if (!file_exists($attempts_file)) {
        return false;
    }
    
    $attempts = json_decode(file_get_contents($attempts_file), true) ?: [];
    
    // If no attempts for this user, or attempts are old, allow login
    if (!isset($attempts[$username])) {
        return false;
    }
    
    // Check if attempts are recent (within the last hour)
    $now = time();
    if ($attempts[$username]['timestamp'] < ($now - 3600)) {
        unset($attempts[$username]);
        file_put_contents($attempts_file, json_encode($attempts));
        return false;
    }
    
    // Block if more than 5 failed attempts in the last hour
    return $attempts[$username]['count'] >= 5;
}
