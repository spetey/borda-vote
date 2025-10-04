<?php
// Authored or modified by Claude - 2025-09-25

error_reporting(E_ALL);
ini_set('display_errors', 1);
error_log("DEBUG: auth_api.php started");

require_once 'config.php';

// Only set JSON header if this file is being accessed directly as an API
if (basename($_SERVER['PHP_SELF']) === 'auth_api.php') {
    header('Content-Type: application/json');
}

if (!function_exists('jsonResponse')) {
    function jsonResponse($success, $data = null, $error = null) {
        echo json_encode([
            'success' => $success,
            'data' => $data,
            'error' => $error
        ]);
        exit;
    }
}

if (!function_exists('getDb')) {
    function getDb() {
        try {
            $pdo = new PDO('sqlite:' . DB_PATH);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (PDOException $e) {
            jsonResponse(false, null, 'Database connection failed');
        }
    }
}

function generateSessionToken() {
    return bin2hex(random_bytes(32));
}

function createUserSession($userId) {
    $pdo = getDb();

    // Clean up old sessions for this user
    $stmt = $pdo->prepare('DELETE FROM user_sessions WHERE user_id = ? OR expires_at < datetime("now")');
    $stmt->execute([$userId]);

    // Create new session
    $sessionToken = generateSessionToken();
    $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

    $stmt = $pdo->prepare('INSERT INTO user_sessions (user_id, session_token, expires_at) VALUES (?, ?, ?)');
    $stmt->execute([$userId, $sessionToken, $expiresAt]);

    // Set session cookie
    setcookie('auth_token', $sessionToken, time() + (24 * 60 * 60), '/', '', false, true);

    return $sessionToken;
}

function getCurrentUser() {
    if (!isset($_COOKIE['auth_token'])) {
        return null;
    }

    $pdo = getDb();
    $stmt = $pdo->prepare('
        SELECT gu.*, us.expires_at
        FROM global_users gu
        JOIN user_sessions us ON gu.id = us.user_id
        WHERE us.session_token = ? AND us.expires_at > datetime("now")
    ');
    $stmt->execute([$_COOKIE['auth_token']]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function requireAuth() {
    $user = getCurrentUser();
    if (!$user) {
        jsonResponse(false, null, 'Authentication required');
    }
    return $user;
}

function requireAdmin() {
    $user = requireAuth();
    if ($user['role'] !== 'admin') {
        jsonResponse(false, null, 'Admin privileges required');
    }
    return $user;
}

// Only handle API requests if this file is being accessed directly
if (basename($_SERVER['PHP_SELF']) === 'auth_api.php') {
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $action = $input['action'] ?? $_GET['action'] ?? '';

    switch ($action) {

    case 'login':
        if ($method !== 'POST') {
            jsonResponse(false, null, 'Method not allowed');
        }

        $username = trim($input['username'] ?? '');
        $password = $input['password'] ?? '';

        if (!$username || !$password) {
            jsonResponse(false, null, 'Username and password are required');
        }

        try {
            $pdo = getDb();

            // Find user by username or email
            $stmt = $pdo->prepare('SELECT * FROM global_users WHERE (username = ? OR email = ?) AND active = 1');
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($password, $user['password_hash'])) {
                jsonResponse(false, null, 'Invalid username or password');
            }

            // Update last login
            $stmt = $pdo->prepare('UPDATE global_users SET last_login = datetime("now") WHERE id = ?');
            $stmt->execute([$user['id']]);

            // Create session
            $sessionToken = createUserSession($user['id']);

            // Remove sensitive data
            unset($user['password_hash']);

            jsonResponse(true, [
                'user' => $user,
                'session_token' => $sessionToken
            ]);

        } catch (PDOException $e) {
            jsonResponse(false, null, 'Login failed: ' . $e->getMessage());
        }
        break;

    case 'change_password':
        $currentUser = getCurrentUser();
        if (!$currentUser) {
            jsonResponse(false, null, 'Authentication required');
        }

        $currentPassword = $input['current_password'] ?? '';
        $newPassword = $input['new_password'] ?? '';
        $confirmPassword = $input['confirm_password'] ?? '';

        // Validation
        if (!$currentPassword || !$newPassword || !$confirmPassword) {
            jsonResponse(false, null, 'All fields are required');
        }

        if ($newPassword !== $confirmPassword) {
            jsonResponse(false, null, 'New passwords do not match');
        }

        if (strlen($newPassword) < 6) {
            jsonResponse(false, null, 'New password must be at least 6 characters');
        }

        try {
            $pdo = getDb();

            // Verify current password
            if (!password_verify($currentPassword, $currentUser['password_hash'])) {
                jsonResponse(false, null, 'Current password is incorrect');
            }

            // Add must_change_password column if it doesn't exist
            try {
                $pdo->exec('ALTER TABLE global_users ADD COLUMN must_change_password INTEGER DEFAULT 0');
            } catch (PDOException $e) {
                // Column might already exist, which is fine
            }

            // Update password and clear must_change_password flag
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE global_users SET password_hash = ?, must_change_password = 0 WHERE id = ?');
            $stmt->execute([$newPasswordHash, $currentUser['id']]);

            jsonResponse(true, ['message' => 'Password changed successfully']);

        } catch (PDOException $e) {
            jsonResponse(false, null, 'Database error: ' . $e->getMessage());
        }
        break;

    case 'logout':
        if (!isset($_COOKIE['auth_token'])) {
            jsonResponse(true, ['message' => 'Already logged out']);
        }

        try {
            $pdo = getDb();
            $stmt = $pdo->prepare('DELETE FROM user_sessions WHERE session_token = ?');
            $stmt->execute([$_COOKIE['auth_token']]);

            // Clear cookie
            setcookie('auth_token', '', time() - 3600, '/', '', false, true);

            jsonResponse(true, ['message' => 'Logged out successfully']);

        } catch (PDOException $e) {
            jsonResponse(false, null, 'Logout failed: ' . $e->getMessage());
        }
        break;

    case 'check_session':
        error_log("DEBUG: check_session called");
        $user = getCurrentUser();
        error_log("DEBUG: getCurrentUser returned: " . ($user ? json_encode($user) : 'NULL'));

        if ($user) {
            unset($user['password_hash']);
            jsonResponse(true, [
                'logged_in' => true,
                'user' => $user
            ]);
        } else {
            jsonResponse(true, [
                'logged_in' => false
            ]);
        }
        break;

    case 'get_profile':
        $user = requireAuth();
        unset($user['password_hash']);
        jsonResponse(true, ['user' => $user]);
        break;

    case 'update_profile':
        if ($method !== 'POST') {
            jsonResponse(false, null, 'Method not allowed');
        }

        $user = requireAuth();

        $displayName = trim($input['display_name'] ?? '');
        $email = trim($input['email'] ?? '');

        if (!$displayName || !$email) {
            jsonResponse(false, null, 'Display name and email are required');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(false, null, 'Invalid email address');
        }

        try {
            $pdo = getDb();

            // Check if email is taken by another user
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM global_users WHERE email = ? AND id != ?');
            $stmt->execute([$email, $user['id']]);
            if ($stmt->fetchColumn() > 0) {
                jsonResponse(false, null, 'Email is already taken');
            }

            // Update profile
            $stmt = $pdo->prepare('UPDATE global_users SET display_name = ?, email = ? WHERE id = ?');
            $stmt->execute([$displayName, $email, $user['id']]);

            jsonResponse(true, ['message' => 'Profile updated successfully']);

        } catch (PDOException $e) {
            jsonResponse(false, null, 'Profile update failed: ' . $e->getMessage());
        }
        break;


    default:
        jsonResponse(false, null, 'Invalid action');
    }
}
?>