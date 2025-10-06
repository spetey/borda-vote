<?php
// Authored or modified by Claude - 2025-09-25
// Configuration with environment detection

// Detect environment and set configuration
// Check if we're on NFS (via web request or absolute path check)
$isProduction = (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'stevepetersen.net') !== false)
                || (strpos(__DIR__, '/home/public/borda') === 0);

if ($isProduction) {
    // Production settings (NFS)
    define('DB_PATH', '/home/public/borda/borda_vote.db');
    define('DEBUG', false);
} else {
    // Development settings
    define('DB_PATH', __DIR__ . '/database/votes.sqlite');
    define('DEBUG', true);
}

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

// Enable error reporting for debugging
if (DEBUG) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// Session settings (only start if not already started)
if (session_status() === PHP_SESSION_NONE) {
    // Use system temp directory with our own subdirectory
    $sessionPath = sys_get_temp_dir() . '/borda_vote_sessions';
    if (!is_dir($sessionPath)) {
        mkdir($sessionPath, 0777, true);
    }
    session_save_path($sessionPath);
    session_start();
}
?>