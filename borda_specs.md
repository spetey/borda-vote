# Borda Count Voting App - Development Specs

## Project Overview
A web-based Borda count voting system for group decision-making. Users nominate options, then rank all nominations. Results calculated using Borda count method (n-1 points for 1st choice, n-2 for 2nd, etc.).

## Core Requirements

### Workflow
1. **Admin Setup**: Create vote with title, participant passwords, nomination limits, deadlines
2. **Nomination Phase**: Participants log in with unique passwords, submit up to N nominations each
3. **Ranking Phase**: After nominations close, participants rank all collected options
4. **Results**: Display Borda count results after ranking deadline

### Technical Architecture
- **Frontend**: Single HTML file with embedded CSS/JavaScript
- **Backend**: PHP API endpoints with SQLite database
- **Deployment**: Portable across shared hosting, VPS, local development

## Database Schema

```sql
-- Votes table: one record per voting session
CREATE TABLE votes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    max_nominations INTEGER DEFAULT 2,
    phase TEXT DEFAULT 'nominating', -- 'nominating', 'ranking', 'finished'
    nomination_deadline TEXT, -- ISO datetime
    ranking_deadline TEXT,    -- ISO datetime  
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- Users table: participants in each vote  
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    vote_id INTEGER NOT NULL,
    password_hash TEXT NOT NULL,
    email TEXT, -- for notifications
    username TEXT, -- optional display name
    has_nominated BOOLEAN DEFAULT FALSE,
    has_ranked BOOLEAN DEFAULT FALSE,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vote_id) REFERENCES votes (id)
);

-- Nominations table: options submitted by users
CREATE TABLE nominations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    vote_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    text TEXT NOT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vote_id) REFERENCES votes (id),
    FOREIGN KEY (user_id) REFERENCES users (id)
);

-- Rankings table: user preferences for all nominations
CREATE TABLE rankings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    vote_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    nomination_id INTEGER NOT NULL,
    rank INTEGER NOT NULL, -- 1 = first choice, 2 = second, etc.
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vote_id) REFERENCES votes (id),
    FOREIGN KEY (user_id) REFERENCES users (id),
    FOREIGN KEY (nomination_id) REFERENCES nominations (id),
    UNIQUE(vote_id, user_id, nomination_id),
    UNIQUE(vote_id, user_id, rank)
);
```

## File Structure
```
borda-vote/
├── README.md
├── config.php.example
├── setup.php              # Database initialization script
├── index.html             # Main voting interface  
├── api.php                # All API endpoints
├── admin.php              # Vote creation/management interface
├── cron.php               # Phase advancement and email notifications
└── database/              # SQLite file location
    └── .gitkeep
```

## API Endpoints (api.php)

### Authentication
- `POST /api.php?action=login` - Authenticate with password, return user session
- Body: `{"password": "user_password"}`
- Response: `{"success": true, "user_id": 123, "vote_info": {...}}`

### Nominations Phase  
- `GET /api.php?action=get_nominations&vote_id=X` - Get current nominations (anonymous list)
- `POST /api.php?action=submit_nomination` - Submit a nomination
- `POST /api.php?action=check_duplicate` - Check if nomination already exists
- Body: `{"user_id": 123, "text": "My nomination"}`

### Ranking Phase
- `GET /api.php?action=get_all_nominations&vote_id=X` - Get all nominations for ranking
- `POST /api.php?action=submit_rankings` - Submit complete ranking
- Body: `{"user_id": 123, "rankings": [{"nomination_id": 5, "rank": 1}, ...]}`

### Results
- `GET /api.php?action=get_results&vote_id=X` - Get Borda count results
- Response: `{"results": [{"nomination": "text", "score": 42, "rank": 1}, ...]}`

### Status
- `GET /api.php?action=get_status&vote_id=X` - Get current phase, deadlines, participation

## Frontend Requirements

### Login Screen
- Password input field
- Submit button  
- Error display for invalid passwords
- Show vote title and current phase after login

### Nomination Phase UI
- List existing nominations (anonymous, to prevent duplicates)
- Text input for new nominations with duplicate checking
- Submit button (disabled after reaching limit)
- Counter showing "X of Y nominations submitted"
- "Mark Complete" button when user is done
- Visual feedback if nomination already exists

### Ranking Phase UI  
- Display all nominations as draggable list (optimized for ~10 items max)
- Visual drag-and-drop interface with clear drop zones
- Real-time validation (no duplicates, all items ranked)
- Submit button (disabled until valid ranking)
- Confirmation before submission

### Results Display
- Table/list showing nominations with Borda scores
- Clear ranking (1st, 2nd, 3rd...)
- Option to show detailed scoring breakdown

## Configuration (config.php)

```php
<?php
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
define('DEBUG', false);
?>
```

## Code Style Requirements

### PHP Code
- Use prepared statements for all database queries
- Hash passwords with `password_hash()` and `password_verify()`
- Validate and sanitize all inputs
- Return JSON responses with consistent structure: `{"success": bool, "data": ..., "error": "..."}`
- Add header comment: `// Authored or modified by Claude - [timestamp]`

### JavaScript Code  
- Use vanilla JavaScript (no frameworks)
- Implement proper error handling for API calls
- Add header comment: `// Authored or modified by Claude - [timestamp]`
- Use async/await for API calls
- Provide user feedback for all actions (loading states, success/error messages)

### HTML/CSS
- Mobile-responsive design
- Clean, accessible interface
- Semantic HTML elements
- CSS Grid/Flexbox for layouts

## Security Considerations
- SQL injection prevention (prepared statements)
- Password hashing (bcrypt)
- Input validation and sanitization
- CSRF protection for state-changing operations
- Rate limiting considerations for production use

## Setup Process
1. Copy `config.php.example` to `config.php` and configure email settings
2. Run `setup.php` to create database tables
3. Set up cron job: `0 * * * * php /path/to/cron.php` (runs hourly)
4. Access `admin.php` to create votes and add participants
5. System automatically advances phases and sends notifications

## Future Enhancements (Optional)
- Real-time updates via polling/WebSockets
- Vote archiving and historical results
- Multiple concurrent votes
- Advanced admin dashboard
- Export results to CSV/PDF
- SMS notifications via Twilio

## Email Notifications

### Automatic Phase Transitions (cron.php)
- Run via cron job every hour: `0 * * * * php /path/to/cron.php`
- Check for votes past nomination deadline → advance to ranking phase
- Check for votes past ranking deadline → advance to finished phase  
- Send email notifications for phase transitions
- Alternative: Run every 30 minutes during active voting periods if faster response needed

### Email Templates
- **Vote Created**: "You're invited to participate in: [Vote Title]. Your password is: [password]. Nomination deadline: [date]"
- **Ranking Phase Started**: "[Vote Title] nominations are complete! Please rank your preferences by [deadline]"
- **Results Available**: "[Vote Title] voting complete! View results: [link]"

### Simple Email Implementation
```php
// Using PHP's mail() function for portability
// More advanced: PHPMailer for SMTP if needed
function send_notification($to, $subject, $message) {
    $headers = 'From: ' . FROM_EMAIL . "\r\n" .
               'Reply-To: ' . FROM_EMAIL . "\r\n" .
               'X-Mailer: PHP/' . phpversion();
    return mail($to, $subject, $message, $headers);
}
```

## Testing Checklist
- [ ] Database creation and schema
- [ ] User authentication with valid/invalid passwords
- [ ] Nomination submission and limits
- [ ] Phase transitions (manual and deadline-based)
- [ ] Ranking interface and validation
- [ ] Borda count calculation accuracy
- [ ] Error handling and user feedback
- [ ] Mobile responsiveness
- [ ] Multi-user concurrent access