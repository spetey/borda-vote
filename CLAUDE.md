# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview
This is a Borda Count Voting App - a web-based voting system for group decision-making using the Borda count method. The project is deployed and running in production.

## Deployment Process (Steve's NFS Setup)
This project deploys to stevepetersen.net/borda/ using git subtree integration:

1. **Make changes in this repo** (`/home/spetey/Documents/github/online-borda/`)
2. **Commit and push to GitHub** as normal
3. **Go to main website repo** (wherever that is)
4. **Pull updates using subtree**:
   ```bash
   git subtree pull --prefix=borda /home/spetey/Documents/github/online-borda master --squash
   ```
5. **Push website repo to NFS** as normal

**Why `--squash`?** This combines all commits from the borda repo into a single commit in the main website repo, keeping the history clean and avoiding merge complexity.

## Architecture
- **Frontend**: Single HTML file with embedded CSS/JavaScript (vanilla JS, no frameworks)
- **Backend**: PHP API endpoints with SQLite database
- **Database**: SQLite with tables for votes, users, nominations, and rankings
- **Deployment**: Designed to be portable across shared hosting, VPS, and local development

## Key Technical Details
- **Database Schema**: Four main tables (votes, users, nominations, rankings) with proper foreign key relationships
- **Authentication**: Password-based user authentication with bcrypt hashing
- **API Structure**: RESTful endpoints in api.php handling login, nominations, rankings, and results
- **Phase Management**: Votes progress through phases (nominating → ranking → finished) with deadline-based transitions
- **Scoring**: Implements Borda count method (n-1 points for 1st choice, n-2 for 2nd, etc.)

## File Structure (Planned)
```
borda-vote/
├── index.html          # Main voting interface
├── api.php            # All API endpoints
├── admin.php          # Vote creation/management interface
├── setup.php          # Database initialization script
├── cron.php           # Phase advancement and notifications
├── config.php         # Configuration (from config.php.example)
└── database/          # SQLite file location
```

## Development Requirements
- **PHP**: Use prepared statements, password hashing, JSON responses with consistent structure
- **JavaScript**: Vanilla JS with async/await, proper error handling, user feedback
- **Security**: SQL injection prevention, input validation, CSRF protection
- **Code Comments**: Add "// Authored or modified by Claude - [timestamp]" header to modified files
- **UI**: Mobile-responsive design with drag-and-drop ranking interface

## Testing Areas
Focus testing on:
- User authentication flows
- Nomination submission and limits
- Phase transitions and deadlines
- Ranking interface validation
- Borda count calculation accuracy
- Multi-user concurrent access

## Setup Process
1. Configure database connection in config.php
2. Run setup.php to create database schema
3. Set up cron job for automatic phase transitions
4. Use admin.php to create votes and manage participants