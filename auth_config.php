<?php
// Authentication Configuration File

// Login type: 'username', 'email', or 'phone'
$auth_config = [
    // Login settings
    'login_type' => 'username', // Can be 'username', 'email', 'phone', or 'any'
    'enable_registration' => true, // Set to false to disable new registrations
    
    // Password and username requirements
    'min_username_length' => 4,
    'min_password_length' => 8,
    
    // Restricted usernames (cannot be registered)
    'restricted_usernames' => [
        'admin',
        'administrator',
        'root',
        'system',
        'support',
        'webmaster',
        'info',
        'contact',
        'test',
        'user'
    ],
    
    // Database settings
    'use_uuid_for_user_db' => true, // Use UUID instead of username for database names
    
    // Logging settings
    'enable_login_logs' => true,
    'log_file' => __DIR__ . '/logs/auth.log',
    'log_retention_days' => 30, // How many days to keep logs
];
