<?php
// Logging functions

// Include guard to prevent multiple inclusions
if (!defined('LOGGER_INCLUDED')) {
    define('LOGGER_INCLUDED', true);

    // Function to log authentication events
    function log_auth_event($username, $event_type, $status, $ip_address = null) {
        global $auth_config;
        
        if (!isset($auth_config) || !isset($auth_config['enable_login_logs']) || !$auth_config['enable_login_logs']) {
            return;
        }
        
        $log_file = $auth_config['log_file'];
        $log_dir = dirname($log_file);
        
        // Create log directory if it doesn't exist
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0777, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $ip = $ip_address ?: $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        $log_entry = sprintf(
            "[%s] %s: User '%s' - %s from IP %s\n",
            $timestamp,
            $event_type,
            $username,
            $status,
            $ip
        );
        
        file_put_contents($log_file, $log_entry, FILE_APPEND);
    }
}
