<?php
// Authored or modified by Claude - 2025-09-25
// Development configuration

// Database configuration
define('DB_PATH', __DIR__ . '/database/votes.sqlite');

// Email configuration (for notifications)
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your_email@example.com');
define('SMTP_PASSWORD', 'your_password');
define('FROM_EMAIL', 'noreply@example.com');
define('FROM_NAME', 'Borda Vote System');

// Security settings
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_NOMINATION_LENGTH', 500);
define('BCRYPT_ROUNDS', 12);

// Debug mode
define('DEBUG', true);

// Enable error reporting for debugging
if (DEBUG) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// Session settings
if (!session_id()) {
    session_start();
}
?>