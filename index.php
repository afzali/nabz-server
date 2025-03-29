<?php

require 'config.php';
require 'auth.php';
require 'migration.php';
require 'logger.php';

// Handle CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Initialize main database if it doesn't exist
global $main_db;
if (!file_exists($main_db)) {
    $db = connect_db($main_db);
    $db->exec('CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        created_at INTEGER DEFAULT (strftime(\'%s\', \'now\')),
        last_login INTEGER
    )');
}

$request = $_SERVER['REQUEST_URI'];
$request = str_replace('/nabz-server', '', $request);

switch ($request) {
    case '/register':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            if (!isset($data['username']) || !isset($data['password'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Username and password are required']);
                break;
            }
            
            echo json_encode(registerUser($data['username'], $data['password']));
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        }
        break;
        
    case '/login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            if (!isset($data['username']) || !isset($data['password'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Username and password are required']);
                break;
            }
            
            // Get login result
            $result = loginUser($data['username'], $data['password']);
            
            // Update last login time if successful
            if ($result['success']) {
                global $main_db;
                $db = connect_db($main_db);
                $stmt = $db->prepare('UPDATE users SET last_login = strftime(\'%s\', \'now\') WHERE username = ?');
                $stmt->execute([$data['username']]);
            }
            
            echo json_encode($result);
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        }
        break;
        
    case '/auth-config':
        // Only return non-sensitive configuration settings
        global $auth_config;
        echo json_encode([
            'success' => true,
            'config' => [
                'login_type' => $auth_config['login_type'],
                'enable_registration' => $auth_config['enable_registration'],
                'min_username_length' => $auth_config['min_username_length'],
                'min_password_length' => $auth_config['min_password_length']
            ]
        ]);
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
        break;
}
