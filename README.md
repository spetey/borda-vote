# Borda Count Voting App

A web-based voting system for group decision-making using the Borda count method.

## Quick Start

1. **Setup Database:**
   ```bash
   php setup.php
   ```

2. **Start Local Server:**
   ```bash
   php -S localhost:8000
   ```

3. **Create a Vote:**
   - Visit `http://localhost:8000/admin.php`
   - Fill out the form to create a new vote
   - Note the generated passwords for participants

4. **Vote:**
   - Visit `http://localhost:8000/index.html`
   - Enter your assigned password
   - Submit nominations, then rank them when the phase advances

## Files

- `setup.php` - Database initialization
- `config.php` - Configuration settings
- `admin.php` - Vote creation and management
- `index.html` - Main voting interface
- `api.php` - Backend API endpoints
- `database/` - SQLite database location

## Features

- **Three-phase voting**: Nomination → Ranking → Results
- **Borda count scoring**: Democratic ranking system
- **Mobile-responsive**: Works on all devices
- **Drag-and-drop ranking**: Intuitive interface
- **Real-time updates**: Status monitoring

## Development

The app is built with vanilla PHP, JavaScript, and SQLite for maximum portability across different hosting environments.