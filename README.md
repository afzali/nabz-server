# Nabz Server - v2.0

A lightweight PHP-based API server with SQLite database for authentication and user management.

## Features

- **Authentication System**
  - User registration and login
  - Configurable login types (username, email, or phone)
  - Password hashing and verification
  - Minimum length requirements for usernames and passwords
  - Restricted username list
  - Option to enable/disable registration

- **Database Management**
  - SQLite database for user data
  - Separate database for each user with UUID-based naming
  - Automatic database creation and initialization
  - Improved database compatibility to support both camelCase and snake_case field names

- **Security**
  - Authentication logging
  - Password hashing with PHP's password_hash
  - Protection against common username exploits
  - `.htaccess` protection for sensitive files and directories
  - Prevention of direct database file access
  - Directory listing prevention

- **Event Logging**: Comprehensive event tracking with detailed metadata
  - Modular JavaScript architecture for better maintainability
  - Proper handling of complex data structures (arrays, JSON)
  - Consistent API endpoint access
  - Support for filtering and searching events

## Project Structure

- `index.php` - Main entry point and API routing
- `auth.php` - Authentication functions (login, register)
- `config.php` - Database configuration and connection
- `auth_config.php` - Authentication settings
- `logger.php` - Logging functionality
- `migration.php` - Database migration utilities
- `events.php` - Event management functionality
- `public/` - Public assets and test HTML files
  - `events-test-modular.html` - Modular event management interface
  - `js/` - JavaScript modules for event management
    - `api.js` - API endpoint configuration and request handling
    - `auth.js` - Authentication functionality
    - `utils.js` - Utility functions for UI and messaging
    - `create-event.js` - Event creation functionality
    - `list-events.js` - Event listing and searching
    - `update-event.js` - Event updating functionality
    - `delete-event.js` - Event deletion functionality
- `databases/` - User databases (created automatically)
- `logs/` - Authentication logs

## Frontend Features

- Modern, responsive user interface
- Client-side validation matching server requirements
- Centralized API endpoint configuration using constants
- Dynamic form fields based on authentication configuration
- Automatic configuration fetching from server
- Modular JavaScript architecture for better code organization and maintainability

## Recent Updates

### v2.2 (March 2025)
- Fixed URL query parameter handling in API routing to properly support filtering
- Improved error handling for API requests with query parameters
- Enhanced frontend-backend communication with proper URL formatting
- Fixed event filtering by state, category, and other parameters

### v2.1 (March 2025)
- Refactored event management interface to use modular JavaScript architecture
- Fixed API endpoint handling to ensure proper access from the frontend
- Improved handling of array data in event categories
- Enhanced error handling and debugging for API requests
- Added proper JSON serialization for array fields in events

## Setup

1. Place the project in your web server directory (e.g., xampp/htdocs/nabz-server)
2. Make sure PHP and SQLite are enabled in your web server
3. Ensure the web server has write permissions to the project directory
4. Access the test interface at: http://localhost/nabz-server/public/index.html

## API Endpoints

- **POST /nabz-server/register**
  - Register a new user
  - Parameters: `username`, `password`
  - Returns: JSON with success status and message

- **POST /nabz-server/login**
  - Login a user
  - Parameters: `username`, `password`
  - Returns: JSON with success status, message, and user information

- **GET /nabz-server/auth-config**
  - Get authentication configuration
  - Returns: JSON with login type and requirements

### Event Management

- `GET /nabz-server/events`: List all events for authenticated user
  - Optional query parameters:
    - `category`: Filter by category
    - `state`: Filter by state (done, not done, pending)
    - `startDate`: Filter by start date
    - `endDate`: Filter by end date
    - `search`: Search in title and description
    - `limit`: Maximum number of events to return (default: 100)
    - `offset`: Offset for pagination (default: 0)
  - Returns: List of events matching criteria

- `GET /nabz-server/events/{id}`: Get a specific event by ID
  - Returns: Event details

- `POST /nabz-server/events`: Create a new event
  - Required authentication: Bearer token (username)
  - Request body: Event data in JSON format
  - Returns: Success status and new event ID

- `PUT /nabz-server/events/{id}`: Update an existing event
  - Required authentication: Bearer token (username)
  - Request body: Updated event data in JSON format
  - Returns: Success status and message

- `DELETE /nabz-server/events/{id}`: Delete an event
  - Required authentication: Bearer token (username)
  - Returns: Success status and message

## Event Structure

Events are stored with the following JSON structure:

```json
{
  "title": "Task title",
  "description": "Short summary of the task",
  "category": ["Main activity category", "Secondary category"],
  "icon": "material_icon_name",
  "tags": ["tag1", "tag2", "tag3"],
  "createDate": "2023-05-01T10:00:00+03:30",
  "updateDate": "2023-05-01T10:00:00+03:30",
  "start": "2023-05-01T10:00:00+03:30",
  "end": "2023-05-01T12:00:00+03:30",
  "state": "done",
  "count": 5,
  "countUnit": "pages",
  "feedback": ["option1", "option2"],
  "countCondition": "1:10",
  "timeCondition": "08:00:17:00",
  "durationCondition": "30:120",
  "notif": ["2023-05-01T09:45:00+03:30"],
  "repetition": {
    "type": "daily",
    "interval": 1
  }
}
```

**Note**: Both `category` and `tags` fields can be either a single string or an array of strings. The API will handle both formats appropriately.

## API Usage Guide

### API Connection

All API requests should be made to the base URL where the server is hosted. For local development, this is typically:
```
http://localhost/nabz-server/
```

### Authentication Flow

1. **Registration**:
   ```bash
   curl -X POST http://localhost/nabz-server/register \
     -H "Content-Type: application/json" \
     -d '{"username": "newuser", "password": "securepassword123"}'
   ```
   
   Response:
   ```json
   {
     "success": true,
     "message": "User registered successfully"
   }
   ```

2. **Login**:
   ```bash
   curl -X POST http://localhost/nabz-server/login \
     -H "Content-Type: application/json" \
     -d '{"username": "newuser", "password": "securepassword123"}'
   ```
   
   Response:
   ```json
   {
     "success": true,
     "message": "Login successful",
     "user": {
       "username": "newuser",
       "token": "newuser"
     }
   }
   ```

3. **Using Authentication Token**:
   After login, you'll receive a token (currently the username). Use this token in the Authorization header for all subsequent requests:
   ```
   Authorization: Bearer newuser
   ```

### Event API Examples

#### Creating an Event

```bash
curl -X POST http://localhost/nabz-server/events \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer newuser" \
  -d '{
    "title": "Study Session",
    "description": "Review PHP concepts",
    "category": "Education",
    "icon": "school",
    "tags": ["php", "programming", "study"],
    "start": "2025-03-30T14:00:00+03:30",
    "end": "2025-03-30T16:00:00+03:30",
    "state": "pending"
  }'
```

Response:
```json
{
  "success": true,
  "message": "Event created successfully",
  "id": 1
}
```

#### Retrieving Events

**Get All Events**:
```bash
curl -X GET http://localhost/nabz-server/events \
  -H "Authorization: Bearer newuser"
```

**Filtering Events**:
```bash
# Filter by category
curl -X GET "http://localhost/nabz-server/events?category=Education" \
  -H "Authorization: Bearer newuser"

# Filter by state
curl -X GET "http://localhost/nabz-server/events?state=pending" \
  -H "Authorization: Bearer newuser"

# Filter by date range
curl -X GET "http://localhost/nabz-server/events?startDate=2025-03-01&endDate=2025-03-31" \
  -H "Authorization: Bearer newuser"

# Search in title and description
curl -X GET "http://localhost/nabz-server/events?search=PHP" \
  -H "Authorization: Bearer newuser"

# Pagination
curl -X GET "http://localhost/nabz-server/events?limit=10&offset=0" \
  -H "Authorization: Bearer newuser"

# Combined filters
curl -X GET "http://localhost/nabz-server/events?category=Education&state=pending&limit=5" \
  -H "Authorization: Bearer newuser"
```

#### Updating an Event

```bash
curl -X PUT http://localhost/nabz-server/events/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer newuser" \
  -d '{
    "title": "Study Session",
    "description": "Review PHP and SQLite concepts",
    "state": "done"
  }'
```

Response:
```json
{
  "success": true,
  "message": "Event updated successfully"
}
```

#### Deleting an Event

```bash
curl -X DELETE http://localhost/nabz-server/events/1 \
  -H "Authorization: Bearer newuser"
```

Response:
```json
{
  "success": true,
  "message": "Event deleted successfully"
}
```

### JavaScript API Integration

Here's how to integrate with the API using JavaScript:

```javascript
// Configuration
const API_BASE_URL = 'http://localhost/nabz-server';
let authToken = localStorage.getItem('authToken');

// Login function
async function login(username, password) {
  try {
    const response = await fetch(`${API_BASE_URL}/login`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ username, password })
    });
    
    const data = await response.json();
    
    if (data.success) {
      // Save token for future requests
      localStorage.setItem('authToken', data.user.token);
      authToken = data.user.token;
      return data;
    } else {
      throw new Error(data.message);
    }
  } catch (error) {
    console.error('Login error:', error);
    throw error;
  }
}

// Get events with filtering
async function getEvents(filters = {}) {
  // Build query string from filters
  const queryParams = new URLSearchParams();
  
  for (const [key, value] of Object.entries(filters)) {
    if (value !== undefined && value !== null) {
      queryParams.append(key, value);
    }
  }
  
  const queryString = queryParams.toString() ? `?${queryParams.toString()}` : '';
  
  try {
    const response = await fetch(`${API_BASE_URL}/events${queryString}`, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${authToken}`
      }
    });
    
    return await response.json();
  } catch (error) {
    console.error('Error fetching events:', error);
    throw error;
  }
}

// Create a new event
async function createEvent(eventData) {
  try {
    const response = await fetch(`${API_BASE_URL}/events`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${authToken}`
      },
      body: JSON.stringify(eventData)
    });
    
    return await response.json();
  } catch (error) {
    console.error('Error creating event:', error);
    throw error;
  }
}
```

### Error Handling

All API endpoints return a consistent JSON structure:

```json
{
  "success": true|false,
  "message": "Description of the result or error",
  "data": {} // Optional, contains additional data when applicable
}
```

Common HTTP status codes:
- 200: Success
- 400: Bad request (invalid parameters)
- 401: Unauthorized (missing or invalid token)
- 404: Resource not found
- 500: Server error

## Testing

1. Open http://localhost/nabz-server/public/index.html in your browser
2. The test interface will show current authentication configuration
3. Try registering a new user with the registration form
4. Log in with the created credentials
5. After successful login, you'll be redirected to the dashboard

## Requirements

- PHP 7.0 or higher
- SQLite extension for PHP
- Web server with rewrite capabilities (Apache with mod_rewrite)

## Configuration

You can modify authentication settings in `auth_config.php`:

- `login_type`: Set to 'username', 'email', 'phone', or 'any'
- `enable_registration`: Set to true/false to enable/disable new registrations
- `min_username_length`: Minimum length for usernames
- `min_password_length`: Minimum length for passwords
- `restricted_usernames`: Array of usernames that cannot be registered
- `use_uuid_for_user_db`: Set to true for more secure database naming
- `enable_login_logs`: Set to true to log authentication events

## Version History

### Version 2 (Current)
- Added comprehensive event logging API with CRUD operations
- Improved database compatibility to support both camelCase and snake_case field names
- Added database migration and reset utilities
- Enhanced error logging and debugging

### Version 1
- Initial release with user authentication system
- User registration and login functionality
- Database isolation for each user

## Troubleshooting

### Common Issues

1. **Authentication Failures**
   - Check that you're using the correct username and password
   - Ensure the token is being sent correctly in the Authorization header

2. **Event Creation/Update Failures**
   - Verify that all required fields are included in your request
   - Check that date formats follow ISO 8601 standard (YYYY-MM-DDTHH:MM:SS+HH:MM)
   - Ensure the token in the Authorization header is valid

3. **Query Parameter Issues**
   - URL encode all query parameters properly
   - For date ranges, ensure startDate is before endDate
   - For pagination, offset must be a non-negative integer
