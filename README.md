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

The system sends automatic email notifications for:
- New user account creation (with temporary passwords)
- Vote creation announcements
- Vote phase changes (nomination → ranking → results)
- Final results notifications

#### Option 1: Basic Email (Shared Hosting)
For most shared hosting providers (like Nearly Free Speech), use PHP's built-in `mail()` function.

Edit the `sendEmail()` function in `email_utils.php`:

```php
public static function sendEmail($to, $subject, $htmlBody, $textBody = null) {
    // For development/testing - log emails instead of sending
    if (DEBUG) {
        error_log("EMAIL: To: $to, Subject: $subject");
        error_log("EMAIL BODY: " . strip_tags($htmlBody));
        return true;
    }

    // Production email using built-in mail()
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: noreply@yourdomain.com\r\n";
    $headers .= "Reply-To: noreply@yourdomain.com\r\n";

    return mail($to, $subject, $htmlBody, $headers);
}
```

**Important**: Replace `yourdomain.com` with your actual domain name.

#### Option 2: SMTP Email (VPS/Dedicated Servers)
For more reliable email delivery, use SMTP with PHPMailer:

1. **Install PHPMailer**:
   ```bash
   composer require phpmailer/phpmailer
   ```

2. **Update `sendEmail()` in `email_utils.php`**:
   ```php
   use PHPMailer\PHPMailer\PHPMailer;
   use PHPMailer\PHPMailer\SMTP;

   public static function sendEmail($to, $subject, $htmlBody, $textBody = null) {
       if (DEBUG) {
           error_log("EMAIL: To: $to, Subject: $subject");
           return true;
       }

       $mail = new PHPMailer(true);
       try {
           // SMTP configuration
           $mail->isSMTP();
           $mail->Host = 'smtp.gmail.com'; // or your SMTP server
           $mail->SMTPAuth = true;
           $mail->Username = 'your-email@gmail.com';
           $mail->Password = 'your-app-password';
           $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
           $mail->Port = 587;

           // Email content
           $mail->setFrom('noreply@yourdomain.com', 'Borda Vote System');
           $mail->addAddress($to);
           $mail->isHTML(true);
           $mail->Subject = $subject;
           $mail->Body = $htmlBody;
           $mail->AltBody = $textBody ?: strip_tags($htmlBody);

           $mail->send();
           return true;
       } catch (Exception $e) {
           error_log("Email error: " . $mail->ErrorInfo);
           return false;
       }
   }
   ```

#### Update Domain URLs
Update hardcoded URLs in `email_utils.php` to match your domain:
- Line ~156: Change `http://localhost:8000/vote.php` to `https://yourdomain.com/path/vote.php`
- Line ~201: Same for results emails
- Line ~336: Change `http://localhost:8000/auth.php` to `https://yourdomain.com/path/auth.php`

#### Testing Email Setup

**Local Testing:**
1. Keep `DEBUG = true` in `config.php`
2. Create a test user or vote
3. Check your error logs for email output

**Production Testing:**
1. Set `DEBUG = false` in `config.php`
2. Create a test user with your email address
3. Verify you receive the welcome email
4. Test vote notifications by creating a test vote

#### Troubleshooting

**Emails not sending:**
- Verify `DEBUG = false` in production
- Check error logs for specific errors
- Ensure your domain's From address matches your hosting
- For SMTP: verify credentials and server settings

**Emails going to spam:**
- Use a From address matching your domain
- Add SPF/DKIM records to your domain's DNS
- Avoid spam trigger words in email content

**Permission errors:**
- Ensure your web server can execute the `mail()` function
- Check hosting provider's email sending policies

### Debugging
Set `DEBUG = true` in `config.php` to log emails instead of sending them. This is useful for local development and testing email templates without actually sending emails.

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

This project is licensed under the GNU General Public License v3.0 - see the [LICENSE](LICENSE) file for details.

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