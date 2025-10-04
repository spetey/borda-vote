# Borda Vote System

A web-based democratic group decision-making system using the Borda Count voting method. Perfect for organizations, clubs, or any group that needs to make fair collective decisions.

## 🗳️ What is Borda Count Voting?

The Borda Count is a ranked voting system where voters rank options in order of preference. Points are awarded based on position (1st choice gets the most points, 2nd choice gets fewer, etc.), and the option with the most total points wins. This method tends to select options that are broadly acceptable to the group, even if they're not everyone's first choice.

## ✨ Features

### 📊 **Complete Voting Workflow**
- **Nomination Phase**: Users submit options to vote on
- **Ranking Phase**: Users rank all nominations in order of preference
- **Automatic Tie Resolution**: Handles ties with runoff voting or random selection
- **Results Display**: Clear, transparent results with point calculations

### 👥 **User Management**
- **Invite-only system**: Admins create accounts for participants
- **Role-based access**: Admin and regular user roles
- **Secure authentication**: Session-based login with password requirements
- **Forced password changes**: New users must change temporary passwords

### 🎛️ **Admin Features**
- **Streamlined dashboard**: Organized sections for user/vote management
- **Email notifications**: Automatic emails for vote phase changes
- **Vote archiving**: Hide completed votes without losing data
- **Flexible deadlines**: Optional automatic phase advancement

### 🖱️ **Intuitive Interface**
- **Easy ranking**: Number inputs + arrow buttons (no drag-and-drop issues)
- **Clean design**: Modern, responsive interface
- **Clear feedback**: Status indicators and progress updates

## 🚀 Quick Start

### Prerequisites
- PHP 7.4+ with SQLite support
- Web server (Apache, Nginx, etc.)
- SQLite3

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/spetey/borda-vote.git
   cd borda-vote
   ```

2. **Set up the database**
   ```bash
   sqlite3 borda_vote.db < schema.sql
   chmod 664 borda_vote.db
   ```

3. **Configure the system**
   - Edit `config.php` to set your database path
   - Update email settings in `email_utils.php` if needed

4. **Create your admin user**
   ```bash
   # Generate password hash
   php hash_password.php your_secure_password

   # Insert admin user (replace values)
   sqlite3 borda_vote.db "INSERT INTO global_users (username, email, display_name, password_hash, role) VALUES ('admin', 'your@email.com', 'Your Name', 'HASH_FROM_ABOVE', 'admin');"
   ```

5. **Clean up**
   ```bash
   rm hash_password.php  # Remove for security
   ```

6. **Access your system**
   - Visit your domain/subdomain
   - Log in with your admin credentials
   - Start creating users and votes!

## 📋 How to Use

### For Administrators

1. **Create Users**: Add participants with temporary passwords
2. **Create Votes**: Set up voting topics with deadlines
3. **Manage Process**: Monitor progress and handle any issues

### For Participants

1. **Login**: Use credentials provided by admin
2. **Nominate**: Submit options during nomination phase
3. **Rank**: Order all nominations from most to least preferred
4. **View Results**: See final rankings and point totals

## 🔧 Configuration

### Database Location
Edit `config.php`:
```php
define('DB_PATH', '/path/to/your/borda_vote.db');
```

### Email Notifications
Edit `email_utils.php` to configure SMTP settings for production email sending.

### Debugging
Set `DEBUG = true` in `config.php` to log emails instead of sending them.

## 🏗️ Architecture

- **Backend**: PHP with SQLite database
- **Frontend**: Vanilla JavaScript with modern CSS
- **Authentication**: Session-based with secure password hashing
- **Email**: Configurable SMTP support with HTML templates

### Database Tables
- `global_users` - User accounts and roles
- `votes` - Voting sessions and metadata
- `nominations` - User-submitted options
- `rankings` - User preference rankings
- `user_votes` - Participation tracking
- Additional tables for sessions, tie resolution, etc.

## 🛡️ Security Features

- Password hashing with PHP's `password_hash()`
- Session-based authentication with expiration
- SQL injection protection via prepared statements
- XSS protection with proper output escaping
- CSRF protection for state-changing operations
- Invite-only registration (no public signup)

## 🌐 Deployment

### Web Hosting
This system works on most PHP hosting providers:
- Shared hosting (like Nearly Free Speech)
- VPS/Dedicated servers
- Cloud hosting (AWS, DigitalOcean, etc.)

### File Permissions
Ensure your web server can read/write the database:
```bash
chmod 664 borda_vote.db
chgrp www-data borda_vote.db  # or your web server group
```

## 🤝 Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🐛 Issues & Support

Found a bug or need help? Please [open an issue](https://github.com/spetey/borda-vote/issues) with:
- Your PHP version
- Steps to reproduce the problem
- Any error messages
- Expected vs actual behavior

## 🎯 Use Cases

Perfect for:
- **Organizations**: Board decisions, policy choices
- **Clubs**: Event planning, activity selection
- **Teams**: Project prioritization, feature selection
- **Groups**: Restaurant choices, meeting times
- **Communities**: Resource allocation, rule changes

## 🏆 Why Borda Count?

Unlike simple majority voting, Borda Count:
- ✅ Considers everyone's full preference ranking
- ✅ Tends to find broadly acceptable compromises
- ✅ Reduces strategic voting incentives
- ✅ Provides clearer decision rationale
- ✅ Works well even with many options

---

**Made with ❤️ for democratic decision-making**