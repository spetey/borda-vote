-- Borda Vote System Database Schema
-- Generated for deployment - run this to create a fresh database

-- Global users table
CREATE TABLE IF NOT EXISTS global_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'user',
    active INTEGER DEFAULT 1,
    must_change_password INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME
);

-- User sessions table
CREATE TABLE IF NOT EXISTS user_sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    session_token VARCHAR(64) UNIQUE NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES global_users(id) ON DELETE CASCADE
);

-- Votes table
CREATE TABLE IF NOT EXISTS votes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    description TEXT,
    max_nominations INTEGER DEFAULT 2,
    phase VARCHAR(20) DEFAULT 'nominating',
    nomination_deadline DATETIME,
    ranking_deadline DATETIME,
    archived INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- User-vote relationships table
CREATE TABLE IF NOT EXISTS user_votes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    vote_id INTEGER NOT NULL,
    role VARCHAR(20) DEFAULT 'participant',
    has_nominated INTEGER DEFAULT 0,
    has_ranked INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES global_users(id) ON DELETE CASCADE,
    FOREIGN KEY (vote_id) REFERENCES votes(id) ON DELETE CASCADE,
    UNIQUE(user_id, vote_id)
);

-- Nominations table
CREATE TABLE IF NOT EXISTS nominations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    vote_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    text TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vote_id) REFERENCES votes(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES global_users(id) ON DELETE CASCADE
);

-- Rankings table
CREATE TABLE IF NOT EXISTS rankings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    vote_id INTEGER NOT NULL,
    nomination_id INTEGER NOT NULL,
    rank INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES global_users(id) ON DELETE CASCADE,
    FOREIGN KEY (vote_id) REFERENCES votes(id) ON DELETE CASCADE,
    FOREIGN KEY (nomination_id) REFERENCES nominations(id) ON DELETE CASCADE,
    UNIQUE(user_id, vote_id, nomination_id),
    UNIQUE(user_id, vote_id, rank)
);

-- Tie resolution table (created dynamically when needed)
CREATE TABLE IF NOT EXISTS tie_resolution (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    vote_id INTEGER NOT NULL,
    tied_nominations TEXT NOT NULL,
    resolution_method VARCHAR(50),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vote_id) REFERENCES votes(id) ON DELETE CASCADE
);

-- Random tie resolution table (created dynamically when needed)
CREATE TABLE IF NOT EXISTS random_tie_resolution (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    vote_id INTEGER NOT NULL,
    winner_nomination_id INTEGER NOT NULL,
    tied_nominations TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vote_id) REFERENCES votes(id) ON DELETE CASCADE,
    FOREIGN KEY (winner_nomination_id) REFERENCES nominations(id) ON DELETE CASCADE
);

-- Runoff rankings table (created dynamically when needed)
CREATE TABLE IF NOT EXISTS runoff_rankings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    vote_id INTEGER NOT NULL,
    nomination_id INTEGER NOT NULL,
    rank INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES global_users(id) ON DELETE CASCADE,
    FOREIGN KEY (vote_id) REFERENCES votes(id) ON DELETE CASCADE,
    FOREIGN KEY (nomination_id) REFERENCES nominations(id) ON DELETE CASCADE,
    UNIQUE(user_id, vote_id, nomination_id),
    UNIQUE(user_id, vote_id, rank)
);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_user_sessions_token ON user_sessions(session_token);
CREATE INDEX IF NOT EXISTS idx_user_sessions_expires ON user_sessions(expires_at);
CREATE INDEX IF NOT EXISTS idx_user_votes_user ON user_votes(user_id);
CREATE INDEX IF NOT EXISTS idx_user_votes_vote ON user_votes(vote_id);
CREATE INDEX IF NOT EXISTS idx_nominations_vote ON nominations(vote_id);
CREATE INDEX IF NOT EXISTS idx_rankings_vote ON rankings(vote_id);
CREATE INDEX IF NOT EXISTS idx_rankings_user_vote ON rankings(user_id, vote_id);
CREATE INDEX IF NOT EXISTS idx_runoff_rankings_vote ON runoff_rankings(vote_id);