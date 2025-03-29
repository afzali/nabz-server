# Nabz Server - v1.0

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

- **Security**
  - Authentication logging
  - Password hashing with PHP's password_hash
  - Protection against common username exploits
  - `.htaccess` protection for sensitive files and directories
  - Prevention of direct database file access
  - Directory listing prevention

## Project Structure

- `index.php` - Main entry point and API routing
- `auth.php` - Authentication functions (login, register)
- `config.php` - Database configuration and connection
- `auth_config.php` - Authentication settings
- `logger.php` - Logging functionality
- `migration.php` - Database migration utilities
- `public/` - Public assets and test HTML files
- `databases/` - User databases (created automatically)
- `logs/` - Authentication logs

## Frontend Features

- Modern, responsive user interface
- Client-side validation matching server requirements
- Centralized API endpoint configuration using constants
- Dynamic form fields based on authentication configuration
- Automatic configuration fetching from server

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

## Configuration

You can modify authentication settings in `auth_config.php`:

- `login_type`: Set to 'username', 'email', 'phone', or 'any'
- `enable_registration`: Set to true/false to enable/disable new registrations
- `min_username_length`: Minimum length for usernames
- `min_password_length`: Minimum length for passwords
- `restricted_usernames`: Array of usernames that cannot be registered
- `use_uuid_for_user_db`: Set to true for more secure database naming
- `enable_login_logs`: Set to true to log authentication events

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
