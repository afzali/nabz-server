<?php
/**
 * Event management functions for the Nabz Server
 * Handles CRUD operations for user events/activities
 */

// Include guard to prevent multiple inclusions
if (!defined('EVENTS_INCLUDED')) {
    define('EVENTS_INCLUDED', true);

    /**
     * Creates a new event for a user
     * 
     * @param int $user_id The user ID
     * @param array $event_data The event data
     * @return array Result with success status and message
     */
    function create_event($user_id, $event_data) {
        // Get user's database
        $username = get_username_by_id($user_id);
        if (!$username) {
            error_log("User not found: $user_id");
            return ['success' => false, 'message' => 'User not found'];
        }
        
        $db_path = get_user_db($username);
        error_log("Using database: $db_path");
        
        if (!file_exists($db_path)) {
            error_log("Database file does not exist: $db_path");
            return ['success' => false, 'message' => 'Database file not found'];
        }
        
        $db = connect_db($db_path);
        
        // Verify activities table
        if (!verify_activities_table($db)) {
            return ['success' => false, 'message' => 'Failed to verify activities table'];
        }
        
        // Add user_id to event data
        $event_data['user_id'] = $user_id;
        
        // Set default timestamps if not provided
        $now = date('Y-m-d\TH:i:sP', time());
        if (empty($event_data['createDate'])) {
            $event_data['createDate'] = $now;
        }
        if (empty($event_data['updateDate'])) {
            $event_data['updateDate'] = $now;
        }
        
        // Convert arrays and objects to JSON strings
        foreach (['tags', 'feedback', 'notif', 'repetition', 'category'] as $field) {
            if (isset($event_data[$field]) && is_array($event_data[$field])) {
                $event_data[$field] = json_encode($event_data[$field], JSON_UNESCAPED_UNICODE);
            }
        }
        
        // Handle category as JSON array if it's an array
        if (isset($event_data['category']) && is_array($event_data['category'])) {
            $event_data['category'] = json_encode($event_data['category'], JSON_UNESCAPED_UNICODE);
        }
        
        // Check if we need to use snake_case or camelCase field names
        $result = $db->query("PRAGMA table_info(activities)");
        $columns = $result->fetchAll(PDO::FETCH_ASSOC);
        $column_names = array_column($columns, 'name');
        
        // Determine if we're using snake_case or camelCase
        $using_snake_case = in_array('count_unit', $column_names);
        
        // Build SQL query
        $fields = [];
        $placeholders = [];
        $values = [];
        
        foreach ($event_data as $key => $value) {
            $db_field = $key;
            
            // Convert camelCase to snake_case if needed
            if ($using_snake_case && preg_match('/[A-Z]/', $key)) {
                $db_field = preg_replace('/([a-z])([A-Z])/', '$1_$2', $key);
                $db_field = strtolower($db_field);
            }
            
            $fields[] = $db_field;
            $placeholders[] = '?';
            $values[] = $value;
        }
        
        $sql = 'INSERT INTO activities (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $placeholders) . ')';
        error_log("SQL Query: $sql");
        error_log("Values: " . print_r($values, true));
        
        try {
            $stmt = $db->prepare($sql);
            $result = $stmt->execute($values);
            
            if (!$result) {
                error_log("SQL Error: " . print_r($stmt->errorInfo(), true));
                return [
                    'success' => false, 
                    'message' => 'Failed to create event: ' . implode(', ', $stmt->errorInfo())
                ];
            }
            
            $event_id = $db->lastInsertId();
            error_log("Event created with ID: $event_id");
            
            return [
                'success' => true, 
                'message' => 'Event created successfully',
                'event_id' => $event_id
            ];
        } catch (PDOException $e) {
            error_log("PDO Exception: " . $e->getMessage());
            return [
                'success' => false, 
                'message' => 'Failed to create event: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Retrieves events for a user with optional filtering
     * 
     * @param int $user_id The user ID
     * @param array $filters Optional filters (e.g. date range, category)
     * @param int $limit Maximum number of events to return
     * @param int $offset Offset for pagination
     * @return array Result with success status and events data
     */
    function get_events($user_id, $filters = [], $limit = 100, $offset = 0) {
        // Get user's database
        $username = get_username_by_id($user_id);
        if (!$username) {
            return ['success' => false, 'message' => 'User not found'];
        }
        
        $db_path = get_user_db($username);
        $db = connect_db($db_path);
        
        // Verify activities table
        if (!verify_activities_table($db)) {
            return ['success' => false, 'message' => 'Failed to verify activities table'];
        }
        
        // Build SQL query with filters
        $sql = 'SELECT * FROM activities WHERE user_id = ?';
        $params = [$user_id];
        
        // Apply filters
        if (!empty($filters)) {
            foreach ($filters as $key => $value) {
                if ($key === 'startDate' || $key === 'start_date') {
                    $sql .= ' AND createDate >= ?';
                    $params[] = $value;
                } else if ($key === 'endDate' || $key === 'end_date') {
                    $sql .= ' AND createDate <= ?';
                    $params[] = $value;
                } else if ($key === 'category') {
                    // Support for JSON querying of categories
                    // This allows searching in both regular category field and JSON arrays
                    $sql .= ' AND (category = ? OR category LIKE ? OR json_extract(category, \'$[*]\') LIKE ?)';
                    $params[] = $value;
                    $params[] = '%"' . $value . '"%'; // For JSON array format
                    $params[] = '%' . $value . '%';   // For partial matches in JSON
                } else if ($key === 'state') {
                    $sql .= ' AND state = ?';
                    $params[] = $value;
                } else if ($key === 'search' && !empty($value)) {
                    $sql .= ' AND (title LIKE ? OR description LIKE ?)';
                    $params[] = "%$value%";
                    $params[] = "%$value%";
                } else if ($key === 'tag' && !empty($value)) {
                    // Support for searching by tag in the tags JSON array
                    $sql .= ' AND tags LIKE ?';
                    $params[] = '%"' . $value . '"%';
                }
            }
        }
        
        // Add order, limit and offset
        $sql .= ' ORDER BY createDate DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;
        
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Convert JSON strings back to arrays
            foreach ($events as &$event) {
                foreach (['tags', 'feedback', 'notif', 'repetition'] as $field) {
                    if (!empty($event[$field])) {
                        $event[$field] = json_decode($event[$field], true);
                    }
                }
                
                // Convert category to array if it's in JSON format
                if (!empty($event['category']) && $event['category'][0] === '[') {
                    $decoded = json_decode($event['category'], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $event['category'] = $decoded;
                    }
                }
            }
            
            return [
                'success' => true,
                'events' => $events,
                'total' => count($events),
                'limit' => $limit,
                'offset' => $offset
            ];
        } catch (PDOException $e) {
            error_log("Error fetching events: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to retrieve events: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Retrieves a single event by ID
     * 
     * @param int $user_id The user ID
     * @param int $event_id The event ID
     * @return array Result with success status and event data
     */
    function get_event($user_id, $event_id) {
        // Get user's database
        $username = get_username_by_id($user_id);
        if (!$username) {
            return ['success' => false, 'message' => 'User not found'];
        }
        
        $db_path = get_user_db($username);
        $db = connect_db($db_path);
        
        // Verify activities table
        if (!verify_activities_table($db)) {
            return ['success' => false, 'message' => 'Failed to verify activities table'];
        }
        
        try {
            $stmt = $db->prepare('SELECT * FROM activities WHERE id = ? AND user_id = ?');
            $stmt->execute([$event_id, $user_id]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$event) {
                return ['success' => false, 'message' => 'Event not found'];
            }
            
            // Convert JSON strings back to arrays
            foreach (['tags', 'feedback', 'notif', 'repetition'] as $field) {
                if (!empty($event[$field])) {
                    $event[$field] = json_decode($event[$field], true);
                }
            }
            
            return [
                'success' => true,
                'event' => $event
            ];
        } catch (PDOException $e) {
            error_log("Error fetching event: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to retrieve event: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Updates an existing event
     * 
     * @param int $user_id The user ID
     * @param int $event_id The event ID
     * @param array $event_data The updated event data
     * @return array Result with success status and message
     */
    function update_event($user_id, $event_id, $event_data) {
        // Get user's database
        $username = get_username_by_id($user_id);
        if (!$username) {
            return ['success' => false, 'message' => 'User not found'];
        }
        
        $db_path = get_user_db($username);
        $db = connect_db($db_path);
        
        // Verify activities table
        if (!verify_activities_table($db)) {
            return ['success' => false, 'message' => 'Failed to verify activities table'];
        }
        
        // Check if event exists and belongs to user
        $stmt = $db->prepare('SELECT id FROM activities WHERE id = ? AND user_id = ?');
        $stmt->execute([$event_id, $user_id]);
        if (!$stmt->fetch()) {
            return ['success' => false, 'message' => 'Event not found or access denied'];
        }
        
        // Update the updateDate
        $event_data['updateDate'] = date('Y-m-d\TH:i:sP', time());
        
        // Convert arrays and objects to JSON strings
        foreach (['tags', 'feedback', 'notif', 'repetition', 'category'] as $field) {
            if (isset($event_data[$field]) && is_array($event_data[$field])) {
                $event_data[$field] = json_encode($event_data[$field], JSON_UNESCAPED_UNICODE);
            }
        }
        
        // Check if we need to use snake_case or camelCase field names
        $result = $db->query("PRAGMA table_info(activities)");
        $columns = $result->fetchAll(PDO::FETCH_ASSOC);
        $column_names = array_column($columns, 'name');
        
        // Determine if we're using snake_case or camelCase
        $using_snake_case = in_array('count_unit', $column_names);
        
        // Build SQL query
        $updates = [];
        $values = [];
        
        foreach ($event_data as $key => $value) {
            // Skip id field
            if ($key === 'id') continue;
            
            $db_field = $key;
            
            // Convert camelCase to snake_case if needed
            if ($using_snake_case && preg_match('/[A-Z]/', $key)) {
                $db_field = preg_replace('/([a-z])([A-Z])/', '$1_$2', $key);
                $db_field = strtolower($db_field);
            }
            
            $updates[] = "$db_field = ?";
            $values[] = $value;
        }
        
        // Add event_id and user_id to values
        $values[] = $event_id;
        $values[] = $user_id;
        
        $sql = 'UPDATE activities SET ' . implode(', ', $updates) . ' WHERE id = ? AND user_id = ?';
        error_log("Update SQL: $sql");
        error_log("Update values: " . print_r($values, true));
        
        try {
            $stmt = $db->prepare($sql);
            $result = $stmt->execute($values);
            
            if (!$result) {
                error_log("Update error: " . print_r($stmt->errorInfo(), true));
                return [
                    'success' => false,
                    'message' => 'Failed to update event: ' . implode(', ', $stmt->errorInfo())
                ];
            }
            
            if ($stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Event updated successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'No changes were made to the event'
                ];
            }
        } catch (PDOException $e) {
            error_log("Update exception: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update event: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Deletes an event
     * 
     * @param int $user_id The user ID
     * @param int $event_id The event ID
     * @return array Result with success status and message
     */
    function delete_event($user_id, $event_id) {
        // Get user's database
        $username = get_username_by_id($user_id);
        if (!$username) {
            return ['success' => false, 'message' => 'User not found'];
        }
        
        $db_path = get_user_db($username);
        $db = connect_db($db_path);
        
        // Verify activities table
        if (!verify_activities_table($db)) {
            return ['success' => false, 'message' => 'Failed to verify activities table'];
        }
        
        try {
            $stmt = $db->prepare('DELETE FROM activities WHERE id = ? AND user_id = ?');
            $stmt->execute([$event_id, $user_id]);
            
            if ($stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Event deleted successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Event not found or access denied'
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Failed to delete event: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Gets a username by user ID
     * 
     * @param int $user_id The user ID
     * @return string|false The username or false if not found
     */
    function get_username_by_id($user_id) {
        global $main_db;
        $db = connect_db($main_db);
        
        $stmt = $db->prepare('SELECT username FROM users WHERE id = ?');
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['username'] : false;
    }
    
    /**
     * Verifies that the activities table exists in the user database
     * Creates it if it doesn't exist
     * 
     * @param PDO $db Database connection
     * @return bool True if table exists or was created
     */
    function verify_activities_table($db) {
        try {
            // Check if table exists
            $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='activities'");
            $table_exists = ($result && $result->fetch());
            
            if (!$table_exists) {
                error_log("Activities table does not exist, creating it now");
                
                // Create the table with field names matching the JSON structure
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
                
                return true;
            } else {
                // Check if we need to migrate the table structure
                $result = $db->query("PRAGMA table_info(activities)");
                $columns = $result->fetchAll(PDO::FETCH_ASSOC);
                
                // Create a map of column names
                $column_names = [];
                foreach ($columns as $column) {
                    $column_names[] = $column['name'];
                }
                
                // Check if we need to migrate from snake_case to camelCase
                if (in_array('count_unit', $column_names) && !in_array('countUnit', $column_names)) {
                    error_log("Migrating activities table from snake_case to camelCase");
                    migrate_activities_table($db);
                }
            }
            
            return true;
        } catch (PDOException $e) {
            error_log("Error verifying activities table: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Migrates the activities table from snake_case to camelCase column names
     * 
     * @param PDO $db Database connection
     * @return bool True if migration was successful
     */
    function migrate_activities_table($db) {
        try {
            // Start a transaction
            $db->beginTransaction();
            
            // Create the new table with camelCase column names
            $db->exec('CREATE TABLE activities_new (
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
            
            // Copy data from old table to new table
            $db->exec('INSERT INTO activities_new (
                id, user_id, title, description, category, icon, tags,
                createDate, updateDate, start, end, state, count, countUnit,
                feedback, countCondition, timeCondition, durationCondition,
                notif, repetition, error
            ) SELECT
                id, user_id, title, description, category, icon, tags,
                create_date, update_date, start_time, end_time, state, count, count_unit,
                feedback, count_condition, time_condition, duration_condition,
                notif, repetition, error
            FROM activities');
            
            // Drop the old table
            $db->exec('DROP TABLE activities');
            
            // Rename the new table to the original name
            $db->exec('ALTER TABLE activities_new RENAME TO activities');
            
            // Commit the transaction
            $db->commit();
            
            error_log("Activities table migration completed successfully");
            return true;
        } catch (PDOException $e) {
            // Rollback the transaction if an error occurred
            $db->rollBack();
            error_log("Error migrating activities table: " . $e->getMessage());
            return false;
        }
    }
}
