<?php
// Authored or modified by Claude - 2025-09-25
// Database setup for user system upgrade

require_once 'config.php';

function upgradeDatabase() {
    try {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        echo "Upgrading database for user system...\n";

        // Create new users table (separate from vote-specific users)
        $pdo->exec("CREATE TABLE IF NOT EXISTS global_users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            email TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            display_name TEXT,
            role TEXT DEFAULT 'user',
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            last_login TEXT,
            email_verified BOOLEAN DEFAULT FALSE,
            active BOOLEAN DEFAULT TRUE
        )");

        // Create user_votes junction table (who can participate in which votes)
        $pdo->exec("CREATE TABLE IF NOT EXISTS user_votes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            vote_id INTEGER NOT NULL,
            role TEXT DEFAULT 'participant',
            invited_at TEXT DEFAULT CURRENT_TIMESTAMP,
            has_nominated BOOLEAN DEFAULT FALSE,
            has_ranked BOOLEAN DEFAULT FALSE,
            FOREIGN KEY (user_id) REFERENCES global_users (id),
            FOREIGN KEY (vote_id) REFERENCES votes (id),
            UNIQUE(user_id, vote_id)
        )");

        // Create sessions table for proper session management
        $pdo->exec("CREATE TABLE IF NOT EXISTS user_sessions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            session_token TEXT UNIQUE NOT NULL,
            expires_at TEXT NOT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES global_users (id)
        )");

        // Update nominations table to reference global users
        $pdo->exec("ALTER TABLE nominations ADD COLUMN global_user_id INTEGER REFERENCES global_users(id)");

        // Update rankings table to reference global users
        $pdo->exec("ALTER TABLE rankings ADD COLUMN global_user_id INTEGER REFERENCES global_users(id)");

        // Create first admin user if none exists
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM global_users WHERE role = ?');
        $stmt->execute(['admin']);
        $adminCount = $stmt->fetchColumn();

        if ($adminCount == 0) {
            $adminPassword = 'admin123'; // Change this!
            $adminHash = password_hash($adminPassword, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare('INSERT INTO global_users (username, email, password_hash, display_name, role, email_verified) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute(['admin', 'admin@example.com', $adminHash, 'Site Administrator', 'admin', true]);

            echo "Created admin user:\n";
            echo "Username: admin\n";
            echo "Password: $adminPassword\n";
            echo "Email: admin@example.com\n";
            echo "\n⚠️  CHANGE THE ADMIN PASSWORD IMMEDIATELY!\n\n";
        }

        echo "Database upgrade completed successfully!\n";
        echo "New tables created: global_users, user_votes, user_sessions\n";

    } catch (PDOException $e) {
        echo "Database upgrade failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}

// Run upgrade if called directly
if (php_sapi_name() === 'cli' || basename($_SERVER['SCRIPT_NAME']) === 'setup_users.php') {
    upgradeDatabase();
}
?>