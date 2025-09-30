# Next Steps: User System Improvements

## Current MVP Status ✅
The MVP is fully functional with:
- Working three-phase voting (nomination → ranking → results)
- Password-based authentication
- Admin interface for vote creation
- Automatic phase advancement
- Real-time status updates

## Current Limitations
The current user system has several areas for improvement:

### 1. User Management
- **Issue**: Users are created only during vote setup with random passwords
- **Impact**: No persistent user accounts, hard to manage across multiple votes
- **Current Workaround**: Admin copies passwords from creation screen

### 2. Password Security
- **Issue**: Passwords are generated randomly and shown in plaintext once
- **Impact**: If passwords are lost, users can't participate
- **Current Workaround**: Admin needs to save passwords externally

### 3. User Experience
- **Issue**: No user profiles, names, or persistent identity
- **Impact**: Users are just email addresses with random passwords
- **Current Workaround**: Use email as identifier

## Proposed Improvements

### Phase 1: Enhanced User Management
- **User Registration**: Allow users to create accounts with chosen passwords
- **Password Reset**: Email-based password reset functionality
- **User Profiles**: Add display names, avatars, preferences
- **Account Dashboard**: Let users see their voting history

### Phase 2: Multi-Vote User System
- **Persistent Users**: Users can participate in multiple votes
- **User Invitations**: Invite existing users to new votes
- **Role Management**: Different user types (admin, participant, observer)
- **Notification System**: Email users about vote phases, deadlines

### Phase 3: Advanced Features
- **Single Sign-On**: OAuth integration (Google, GitHub, etc.)
- **User Groups**: Predefined groups for easy vote setup
- **Advanced Permissions**: Fine-grained access control
- **User Analytics**: Track participation patterns

## Technical Considerations

### Database Changes Needed
```sql
-- Enhanced users table
ALTER TABLE users ADD COLUMN display_name TEXT;
ALTER TABLE users ADD COLUMN created_at TEXT;
ALTER TABLE users ADD COLUMN last_login TEXT;

-- New user_votes junction table
CREATE TABLE user_votes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    vote_id INTEGER NOT NULL,
    role TEXT DEFAULT 'participant', -- admin, participant, observer
    invited_at TEXT DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, vote_id)
);
```

### API Enhancements
- Separate user management from vote-specific operations
- Add user authentication middleware
- Implement proper session management
- Add user preference endpoints

### Security Improvements
- Implement proper session tokens
- Add CSRF protection
- Rate limiting for login attempts
- Email verification for registration

## Implementation Priority

### High Priority (Next Version)
1. **User Registration System** - Let users create their own accounts
2. **Password Reset** - Email-based password recovery
3. **Enhanced Admin Interface** - Better user management tools

### Medium Priority
1. **User Profiles** - Display names and basic customization
2. **Multi-Vote Support** - Users can participate in multiple votes
3. **Email Notifications** - Automated phase transition emails

### Low Priority (Future Versions)
1. **OAuth Integration** - Social login options
2. **Advanced Analytics** - Detailed participation tracking
3. **Mobile App** - Native mobile experience

## Current MVP Assessment
The current system works excellent for:
- ✅ Single-use votes with small, known groups
- ✅ Quick setup and immediate use
- ✅ Testing and demonstration purposes
- ✅ Scenarios where admin manages everything

Consider user system upgrades when:
- 🔄 Running multiple votes with same participants
- 🔄 Need better user experience/branding
- 🔄 Want automated notifications and management
- 🔄 Scaling to larger groups (>20 people)

The MVP provides a solid foundation - user system improvements can be added incrementally without breaking existing functionality.