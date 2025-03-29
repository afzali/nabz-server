<?php

require 'config.php';
require 'auth.php';
require 'migration.php';
require 'logger.php';
require 'events.php';

// Handle CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
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

// Helper function to get authenticated user
function get_authenticated_user() {
    $headers = getallheaders();
    $auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    
    if (empty($auth_header) || !preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
        return null;
    }
    
    $token = $matches[1];
    
    // For simplicity, we're using the username as the token
    // In a production environment, you would use JWT or another token system
    global $main_db;
    $db = connect_db($main_db);
    
    $stmt = $db->prepare('SELECT id, username FROM users WHERE username = ?');
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $user ?: null;
}

// Helper function to require authentication
function require_auth() {
    $user = get_authenticated_user();
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentication required']);
        exit;
    }
    
    return $user;
}

$request = $_SERVER['REQUEST_URI'];
$request = str_replace('/nabz-server', '', $request);

// Extract API version and endpoint
$parts = explode('/', trim($request, '/'));
$endpoint = $parts[0] ?? '';

// Handle different endpoints
switch ($endpoint) {
    case 'register':
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
        
    case 'login':
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
        
    case 'auth-config':
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
        
    case 'events':
        // All event endpoints require authentication
        $user = require_auth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // Check if specific event ID is requested
            $event_id = isset($parts[1]) && is_numeric($parts[1]) ? intval($parts[1]) : null;
            
            if ($event_id) {
                // Get specific event
                echo json_encode(get_event($user['id'], $event_id));
            } else {
                // Get list of events with filters
                $filters = [];
                
                // Parse query parameters for filtering
                if (isset($_GET['category'])) $filters['category'] = $_GET['category'];
                if (isset($_GET['state'])) $filters['state'] = $_GET['state'];
                if (isset($_GET['startDate'])) $filters['startDate'] = $_GET['startDate'];
                if (isset($_GET['endDate'])) $filters['endDate'] = $_GET['endDate'];
                if (isset($_GET['search'])) $filters['search'] = $_GET['search'];
                
                // Pagination
                $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
                $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
                
                echo json_encode(get_events($user['id'], $filters, $limit, $offset));
            }
        } 
        else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Create new event
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Debug incoming data
            error_log("Creating event with data: " . print_r($data, true));
            
            // Check if data is valid
            if (!is_array($data)) {
                error_log("Invalid data format: not an array");
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid data format']);
                break;
            }
            
            // Fix field names - convert camelCase to snake_case for database compatibility
            $fixed_data = [];
            foreach ($data as $key => $value) {
                // Convert camelCase to snake_case
                $db_field = preg_replace('/([a-z])([A-Z])/', '$1_$2', $key);
                $db_field = strtolower($db_field);
                $fixed_data[$db_field] = $value;
            }
            
            // Add user_id to data
            $fixed_data['user_id'] = $user['id'];
            
            // Make sure the database directory exists
            global $db_dir;
            if (!file_exists($db_dir)) {
                error_log("Creating database directory: $db_dir");
                mkdir($db_dir, 0777, true);
            }
            
            // Get the user's database path
            $db_path = get_user_db($user['username']);
            error_log("User database path: $db_path");
            
            // Create the user database if it doesn't exist
            if (!file_exists($db_path)) {
                error_log("User database does not exist, creating it");
                $user_db = connect_db($db_path);
                
                // Create the activities table with field names matching the JSON structure
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
            }
            
            // Call the create_event function
            $result = create_event($user['id'], $data);
            
            // Log the result
            error_log("Create event result: " . print_r($result, true));
            
            echo json_encode($result);
        } 
        else if ($_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'PATCH') {
            // Update existing event
            if (!isset($parts[1]) || !is_numeric($parts[1])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Event ID is required']);
                break;
            }
            
            $event_id = intval($parts[1]);
            $data = json_decode(file_get_contents('php://input'), true);
            
            echo json_encode(update_event($user['id'], $event_id, $data));
        } 
        else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            // Delete event
            if (!isset($parts[1]) || !is_numeric($parts[1])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Event ID is required']);
                break;
            }
            
            $event_id = intval($parts[1]);
            echo json_encode(delete_event($user['id'], $event_id));
        } 
        else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        }
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
        break;
}
