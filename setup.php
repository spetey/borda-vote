<?php
// Authored or modified by Claude - 2025-09-25

require_once 'config.php';

function createDatabase() {
    try {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create votes table
        $pdo->exec("CREATE TABLE IF NOT EXISTS votes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            max_nominations INTEGER DEFAULT 2,
            phase TEXT DEFAULT 'nominating',
            nomination_deadline TEXT,
            ranking_deadline TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )");

        // Create users table
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            vote_id INTEGER NOT NULL,
            password_hash TEXT NOT NULL,
            email TEXT,
            username TEXT,
            has_nominated BOOLEAN DEFAULT FALSE,
            has_ranked BOOLEAN DEFAULT FALSE,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (vote_id) REFERENCES votes (id)
        )");

        // Create nominations table
        $pdo->exec("CREATE TABLE IF NOT EXISTS nominations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            vote_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            text TEXT NOT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (vote_id) REFERENCES votes (id),
            FOREIGN KEY (user_id) REFERENCES users (id)
        )");

        // Create rankings table
        $pdo->exec("CREATE TABLE IF NOT EXISTS rankings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            vote_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            nomination_id INTEGER NOT NULL,
            rank INTEGER NOT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (vote_id) REFERENCES votes (id),
            FOREIGN KEY (user_id) REFERENCES users (id),
            FOREIGN KEY (nomination_id) REFERENCES nominations (id),
            UNIQUE(vote_id, user_id, nomination_id),
            UNIQUE(vote_id, user_id, rank)
        )");

        echo "Database setup completed successfully!\n";
        echo "Database created at: " . DB_PATH . "\n";

    } catch (PDOException $e) {
        echo "Database setup failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}

// Run setup if called directly
if (php_sapi_name() === 'cli' || basename($_SERVER['SCRIPT_NAME']) === 'setup.php') {
    createDatabase();
}
?>