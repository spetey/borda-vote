<?php
// Authored or modified by Claude - 2025-09-25
// Handle PHP logic before any output

// Include config which handles session starting
require_once 'config.php';

// Require admin authentication to view this page
require_once 'auth_api.php';
$user = getCurrentUser();
if (!$user || $user['role'] !== 'admin') {
    header('Location: auth.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Authored or modified by Claude - 2025-09-25 -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borda Vote Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
            min-height: 100vh;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        h1, h2 {
            color: #2c3e50;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        input[type="text"],
        input[type="number"],
        input[type="datetime-local"],
        input[type="email"],
        textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        button {
            background: #3498db;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-right: 10px;
            margin-bottom: 10px;
        }

        button:hover {
            background: #2980b9;
        }

        .btn-danger {
            background: #e74c3c;
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        .btn-success {
            background: #2ecc71;
        }

        .btn-success:hover {
            background: #27ae60;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #34495e;
            color: white;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: 500;
        }

        .error {
            background: #e74c3c;
            color: white;
        }

        .success {
            background: #2ecc71;
            color: white;
        }

        .password-display {
            background: #ecf0f1;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            margin-top: 10px;
            word-break: break-all;
        }

        .password-container {
            position: relative;
            border: 2px solid #3498db;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            background: #e8f4fd;
        }

        .password-container h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .close-passwords {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
        }

        .close-passwords:hover {
            background: #c0392b;
        }

        .copy-btn {
            background: #27ae60;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
            margin-left: 10px;
        }

        .copy-btn:hover {
            background: #229954;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }

        .btn-warning {
            background: #f39c12;
            color: white;
        }

        .btn-warning:hover {
            background: #d68910;
        }

        .btn-info {
            background: #3498db;
            color: white;
        }

        .btn-info:hover {
            background: #2980b9;
        }

        /* Navigation Styles */
        .nav-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .nav-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .nav-btn {
            background: #ecf0f1;
            color: #2c3e50;
            padding: 15px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-btn:hover {
            background: #bdc3c7;
            transform: translateY(-2px);
        }

        .nav-btn.active {
            background: #3498db;
            color: white;
        }

        .nav-btn.active:hover {
            background: #2980b9;
        }

        .section {
            display: none;
        }

        .section.active {
            display: block;
        }

        @media (max-width: 768px) {
            .nav-buttons {
                flex-direction: column;
            }

            .nav-btn {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h1>Borda Vote Administration</h1>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <span id="admin-welcome" style="color: #7f8c8d;">Welcome, Admin</span>
                    <button onclick="logout()" class="btn-danger">Logout</button>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <div class="nav-container">
            <div class="nav-buttons">
                <button class="nav-btn active" onclick="showSection('add-user')">
                    👥 Add User
                </button>
                <button class="nav-btn" onclick="showSection('manage-users')">
                    ⚙️ Manage Users
                </button>
                <button class="nav-btn" onclick="showSection('add-vote')">
                    🗳️ Add Vote
                </button>
                <button class="nav-btn" onclick="showSection('manage-votes')">
                    📊 Manage Votes
                </button>
                <button class="nav-btn" onclick="showSection('email-testing')">
                    📧 Email Testing
                </button>
            </div>
        </div>

        <!-- Add User Section -->
        <div id="add-user" class="section active">
            <div class="card">
                <h2>Create New User</h2>
                <form id="create-user-form">
                    <div class="grid">
                        <div class="form-group">
                            <label for="user-username">Username:</label>
                            <input type="text" id="user-username" required>
                        </div>
                        <div class="form-group">
                            <label for="user-email">Email:</label>
                            <input type="email" id="user-email" required>
                        </div>
                    </div>

                    <div class="grid">
                        <div class="form-group">
                            <label for="user-display-name">Display Name:</label>
                            <input type="text" id="user-display-name" required>
                        </div>
                        <div class="form-group">
                            <label for="user-role">Role:</label>
                            <select id="user-role">
                                <option value="user">Regular User</option>
                                <option value="admin">Administrator</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="user-temp-password">Temporary Password:</label>
                        <input type="text" id="user-temp-password" required>
                        <button type="button" onclick="generatePassword()" class="btn-secondary" style="margin-left: 10px;">Generate</button>
                    </div>

                    <button type="submit">Create User</button>
                </form>
            </div>
        </div>

        <!-- Manage Users Section -->
        <div id="manage-users" class="section">
            <div class="card">
                <h2>Manage Users</h2>
                <div id="users-list">
                    <!-- Users will be loaded here by JavaScript -->
                </div>
            </div>
        </div>

        <!-- Add Vote Section -->
        <div id="add-vote" class="section">
            <div class="card">
                <h2>Create New Vote</h2>
                <form id="create-vote-form">
                    <div class="form-group">
                        <label for="vote-title">Vote Title:</label>
                        <input type="text" id="vote-title" required>
                    </div>

                    <div class="grid">
                        <div class="form-group">
                            <label for="max-nominations">Max Nominations per User:</label>
                            <input type="number" id="max-nominations" value="2" min="1" required>
                        </div>

                        <div class="form-group">
                            <label for="nomination-deadline">Nomination Deadline:</label>
                            <input type="datetime-local" id="nomination-deadline">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="ranking-deadline">Ranking Deadline:</label>
                        <input type="datetime-local" id="ranking-deadline">
                    </div>

                    <div class="form-group">
                        <label for="participants">Select Participants:</label>
                        <div style="margin-bottom: 10px; color: #7f8c8d; font-size: 14px;">
                            Choose which registered users can participate in this vote
                        </div>

                        <!-- Registered Users Selection -->
                        <div id="user-selection">
                            <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 15px; border-radius: 5px; background: #f9f9f9;">
                                <div id="registered-users-list">Loading registered users...</div>
                            </div>
                        </div>

                        <div id="no-users-message" style="display: none; padding: 15px; background: #fff3cd; border-radius: 5px; margin-top: 10px;">
                            <strong>No participants selected.</strong> You must select at least one registered user to create a vote.
                        </div>
                    </div>

                    <button type="submit">Create Vote</button>
                </form>
            </div>
        </div>

        <!-- Manage Votes Section -->
        <div id="manage-votes" class="section">
            <div class="card">
                <h2>Manage Votes</h2>
                <div style="margin-bottom: 15px;">
                    <label>
                        <input type="checkbox" id="include-archived" onchange="loadVotes()">
                        Show archived votes
                    </label>
                </div>
                <div id="votes-list">
                    <p>Loading votes...</p>
                </div>
            </div>
        </div>

        <!-- Email Testing Section -->
        <div id="email-testing" class="section">
            <div class="card">
                <h2>📧 Email Testing</h2>
                <p style="color: #6c757d; margin-bottom: 15px;">Test email notifications in debug mode (emails will be logged, not sent)</p>

                <div class="form-group">
                    <label for="test-vote-select">Select Vote for Testing:</label>
                    <select id="test-vote-select">
                        <option value="">Select a vote...</option>
                    </select>
                </div>

                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <button onclick="testEmail('phase_advance', 'ranking')" class="btn-secondary">
                        Test Phase Advance Email (Ranking)
                    </button>
                    <button onclick="testEmail('phase_advance', 'finished')" class="btn-secondary">
                        Test Phase Advance Email (Finished)
                    </button>
                    <button onclick="testEmail('results')" class="btn-secondary">
                        Test Results Email
                    </button>
                    <button onclick="checkDeadlineAdvance()" class="btn-info">
                        Check Deadline Advances
                    </button>
                </div>

                <div id="email-test-result" style="margin-top: 15px;"></div>
            </div>
        </div>

        <!-- Message Display -->
        <div id="message-display"></div>
    </div>

    <script>
        // Authored or modified by Claude - 2025-09-25

        function showMessage(text, type = 'success') {
            const messageDiv = document.getElementById('message-display');
            messageDiv.innerHTML = `<div class="message ${type}">${text}</div>`;
            setTimeout(() => messageDiv.innerHTML = '', 5000);
        }

        function showSection(sectionId) {
            // Hide all sections
            document.querySelectorAll('.section').forEach(section => {
                section.classList.remove('active');
            });

            // Remove active class from all nav buttons
            document.querySelectorAll('.nav-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            // Show the selected section
            document.getElementById(sectionId).classList.add('active');

            // Add active class to the clicked button
            event.target.classList.add('active');
        }

        function generatePassword() {
            const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            let result = '';
            for (let i = 0; i < 8; i++) {
                result += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            return result;
        }

        function generatePassword() {
            const chars = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
            let password = '';
            for (let i = 0; i < 8; i++) {
                password += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            document.getElementById('user-temp-password').value = password;
        }

        async function createUser(event) {
            event.preventDefault();

            const username = document.getElementById('user-username').value;
            const email = document.getElementById('user-email').value;
            const displayName = document.getElementById('user-display-name').value;
            const role = document.getElementById('user-role').value;
            const tempPassword = document.getElementById('user-temp-password').value;

            if (!tempPassword || tempPassword.length < 6) {
                showMessage('Temporary password must be at least 6 characters', 'error');
                return;
            }

            try {
                const response = await fetch('admin_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'create_user',
                        username: username,
                        email: email,
                        display_name: displayName,
                        role: role,
                        temp_password: tempPassword
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showMessage(`User created successfully! Share these credentials:\nUsername: ${username}\nPassword: ${tempPassword}`, 'success');

                    // Clear form
                    document.getElementById('create-user-form').reset();

                    // Reload users list
                    loadRegisteredUsers();
                } else {
                    showMessage(result.error || 'Failed to create user', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showMessage('Network error occurred', 'error');
            }
        }

        async function createVote(event) {
            event.preventDefault();

            const title = document.getElementById('vote-title').value;
            const maxNominations = document.getElementById('max-nominations').value;
            const nominationDeadline = document.getElementById('nomination-deadline').value;
            const rankingDeadline = document.getElementById('ranking-deadline').value;

            if (selectedUsers.length === 0) {
                showMessage('Please select at least one registered user to participate in this vote', 'error');
                document.getElementById('no-users-message').style.display = 'block';
                return;
            }

            document.getElementById('no-users-message').style.display = 'none';

            try {
                const response = await fetch('admin_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'create_vote',
                        title,
                        max_nominations: parseInt(maxNominations),
                        nomination_deadline: nominationDeadline || null,
                        ranking_deadline: rankingDeadline || null,
                        participant_user_ids: selectedUsers
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showMessage('Vote created successfully!', 'success');

                    // Display success info
                    let successHtml = `
                        <div id="success-container" class="password-container">
                            <h3>
                                Vote Created Successfully!
                                <button onclick="closeSuccess()" class="close-passwords">Close</button>
                            </h3>
                            <p><strong>Vote:</strong> ${title}</p>
                            <p><strong>Participants:</strong> ${result.data.participant_count} registered users</p>
                            <div style="margin-top: 15px; padding: 10px; background: #e8f8f5; border-radius: 5px;">
                                <strong>Next Steps:</strong>
                                <ul style="margin: 10px 0 0 20px;">
                                    <li>Participants will see this vote on their dashboard</li>
                                    <li>They can access it directly at: <code>vote.php?id=${result.data.vote_id}</code></li>
                                    <li>The vote will automatically advance through phases as users complete their actions</li>
                                </ul>
                            </div>
                        </div>
                    `;

                    // Insert after the create vote form
                    const form = document.getElementById('create-vote-form').closest('.card');
                    form.insertAdjacentHTML('afterend', successHtml);

                    // Reset form
                    document.getElementById('create-vote-form').reset();
                    selectedUsers = [];
                    document.getElementById('user-selection').style.display = 'none';
                    loadVotes();
                } else {
                    showMessage(result.error || 'Failed to create vote', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showMessage('Network error occurred', 'error');
            }
        }

        async function loadVotes() {
            try {
                const includeArchived = document.getElementById('include-archived').checked;
                const url = `admin_api.php?action=list_votes${includeArchived ? '&include_archived=true' : ''}`;
                const response = await fetch(url);
                const result = await response.json();

                if (result.success) {
                    displayVotes(result.data);
                } else {
                    showMessage(result.error || 'Failed to load votes', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('votes-list').innerHTML = '<p>Error loading votes</p>';
            }
        }

        function displayVotes(votes) {
            const container = document.getElementById('votes-list');

            if (votes.length === 0) {
                container.innerHTML = '<p>No votes created yet.</p>';
                return;
            }

            let html = '<table><thead><tr><th>Title</th><th>Phase</th><th>Participants</th><th>Nominations</th><th>Actions</th></tr></thead><tbody>';

            votes.forEach(vote => {
                const rowClass = vote.archived ? 'style="opacity: 0.6; background-color: #f8f9fa;"' : '';
                const archivedBadge = vote.archived ? ' <span style="background: #dc3545; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px;">ARCHIVED</span>' : '';

                html += `
                    <tr ${rowClass}>
                        <td>${vote.title}${archivedBadge}</td>
                        <td>${vote.phase}</td>
                        <td>${vote.total_users}</td>
                        <td>${vote.total_nominations}</td>
                        <td>
                            ${!vote.archived ? `<a href="vote.php?id=${vote.id}" class="btn btn-primary">Participate</a>` : ''}
                            <button onclick="copyVoteLink(${vote.id})" class="btn-secondary">Copy Link</button>
                            ${!vote.archived && vote.phase === 'tie_resolution' ?
                                `<div style="margin: 5px 0;">
                                    <div style="font-weight: bold; color: #e67e22; margin-bottom: 5px;">⚖️ Resolve Tie:</div>
                                    <button onclick="resolveTie(${vote.id}, 'automatic')" class="btn-success" style="font-size: 11px; padding: 3px 6px;">Auto</button>
                                    <button onclick="resolveTie(${vote.id}, 'random')" class="btn-warning" style="font-size: 11px; padding: 3px 6px;">Random</button>
                                    <button onclick="resolveTie(${vote.id}, 'runoff')" class="btn-info" style="font-size: 11px; padding: 3px 6px;">Runoff</button>
                                </div>` :
                                (!vote.archived ? `<button onclick="advancePhase(${vote.id})" class="btn-success">Advance Phase</button>` : '')
                            }
                            <button onclick="viewParticipants(${vote.id})" class="btn-secondary">View Participants</button>
                            ${vote.archived ?
                                `<button onclick="restoreVote(${vote.id})" class="btn-warning">Restore</button>` :
                                `<button onclick="archiveVote(${vote.id})" class="btn-danger">Archive</button>`
                            }
                        </td>
                    </tr>
                `;
            });

            html += '</tbody></table>';
            container.innerHTML = html;

            // Also populate the email test dropdown
            populateVoteSelect(votes);
        }

        async function advancePhase(voteId) {
            if (!confirm('Are you sure you want to advance this vote to the next phase?')) {
                return;
            }

            try {
                const response = await fetch('admin_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'advance_phase',
                        vote_id: voteId
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showMessage('Phase advanced successfully!', 'success');
                    loadVotes();
                } else {
                    showMessage(result.error || 'Failed to advance phase', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showMessage('Network error occurred', 'error');
            }
        }

        async function archiveVote(voteId) {
            if (!confirm('Are you sure you want to archive this vote? It will be hidden but can be restored later.')) {
                return;
            }

            try {
                const response = await fetch('admin_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'archive_vote',
                        vote_id: voteId
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showMessage('Vote archived successfully!', 'success');
                    loadVotes();
                } else {
                    showMessage(result.error || 'Failed to archive vote', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showMessage('Network error occurred', 'error');
            }
        }

        async function restoreVote(voteId) {
            if (!confirm('Are you sure you want to restore this archived vote?')) {
                return;
            }

            try {
                const response = await fetch('admin_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'restore_vote',
                        vote_id: voteId
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showMessage('Vote restored successfully!', 'success');
                    loadVotes();
                } else {
                    showMessage(result.error || 'Failed to restore vote', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showMessage('Network error occurred', 'error');
            }
        }

        function closePasswords() {
            const container = document.getElementById('passwords-container');
            if (container) {
                container.remove();
            }
        }

        function closeSuccess() {
            const container = document.getElementById('success-container');
            if (container) {
                container.remove();
            }
        }

        function copyPassword(password) {
            navigator.clipboard.writeText(password).then(() => {
                showMessage('Password copied to clipboard!', 'success');
            }).catch(() => {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = password;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showMessage('Password copied to clipboard!', 'success');
            });
        }

        async function viewParticipants(voteId) {
            try {
                const response = await fetch(`admin_api.php?action=get_vote_participants&vote_id=${voteId}`);
                const result = await response.json();

                if (result.success) {
                    let html = `
                        <div id="participants-info-${voteId}" class="password-container">
                            <h3>
                                Participants for: ${result.data.vote_title}
                                <button onclick="document.getElementById('participants-info-${voteId}').remove()" class="close-passwords">Close</button>
                            </h3>
                            <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                                <thead>
                                    <tr style="background: #f8f9fa;">
                                        <th style="padding: 8px; border: 1px solid #ddd;">Name</th>
                                        <th style="padding: 8px; border: 1px solid #ddd;">Email</th>
                                        <th style="padding: 8px; border: 1px solid #ddd;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                    `;

                    result.data.participants.forEach(user => {
                        let status = [];
                        if (user.has_nominated) status.push('✅ Nominated');
                        if (user.has_ranked) status.push('✅ Ranked');
                        if (user.has_runoff_ranked) status.push('✅ Runoff Ranked');

                        const statusText = status.length > 0 ? status.join(', ') : '⏳ Pending';

                        html += `
                            <tr>
                                <td style="padding: 8px; border: 1px solid #ddd;">${user.display_name}</td>
                                <td style="padding: 8px; border: 1px solid #ddd;">${user.email}</td>
                                <td style="padding: 8px; border: 1px solid #ddd;">${statusText}</td>
                            </tr>
                        `;
                    });

                    html += `
                                </tbody>
                            </table>
                        </div>
                    `;

                    // Insert after votes list
                    const votesList = document.getElementById('votes-list');
                    votesList.insertAdjacentHTML('afterend', html);

                    showMessage('User information loaded', 'success');
                } else {
                    showMessage(result.error || 'Failed to load user information', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showMessage('Network error occurred', 'error');
            }
        }

        let selectedUsers = [];
        let registeredUsers = [];

        async function checkAuth() {
            try {
                const response = await fetch('auth_api.php?action=check_session');
                const result = await response.json();

                if (!result.success || !result.data.logged_in || result.data.user.role !== 'admin') {
                    window.location.href = 'auth.php';
                    return;
                }

                document.getElementById('admin-welcome').textContent = `Welcome, ${result.data.user.display_name}`;
            } catch (error) {
                console.error('Auth check failed:', error);
                window.location.href = 'auth.php';
            }
        }

        async function logout() {
            try {
                await fetch('auth_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'logout' })
                });
            } catch (error) {
                console.error('Logout error:', error);
            }
            window.location.href = 'auth.php';
        }

        async function loadRegisteredUsers() {
            try {
                console.log('Loading registered users...');
                const response = await fetch('admin_api.php?action=get_users');

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const responseText = await response.text();
                console.log('Raw response:', responseText);

                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('JSON Parse Error:', parseError);
                    document.getElementById('registered-users-list').innerHTML =
                        '<p style="color: #e74c3c; text-align: center;">Error: Invalid response from server</p>';
                    return;
                }

                console.log('Parsed result:', result);

                if (result.success) {
                    registeredUsers = result.data;
                    if (registeredUsers.length === 0) {
                        document.getElementById('registered-users-list').innerHTML =
                            '<p style="color: #e74c3c; text-align: center;">No registered users found. Users must register before they can participate in votes.</p>';
                    } else {
                        displayRegisteredUsers();
                        displayUsersManagement();
                    }
                } else {
                    console.error('API Error:', result.error);
                    document.getElementById('registered-users-list').innerHTML =
                        `<p style="color: #e74c3c; text-align: center;">Error: ${result.error}</p>`;
                }
            } catch (error) {
                console.error('Error loading users:', error);
                document.getElementById('registered-users-list').innerHTML =
                    `<p style="color: #e74c3c; text-align: center;">Network error: ${error.message}</p>`;
            }
        }

        function displayRegisteredUsers() {
            const container = document.getElementById('registered-users-list');
            let html = '';

            registeredUsers.forEach(user => {
                const isSelected = selectedUsers.includes(user.id);
                html += `
                    <div style="display: flex; align-items: center; margin-bottom: 8px;">
                        <input type="checkbox" id="user-${user.id}" ${isSelected ? 'checked' : ''}
                               onchange="toggleUserSelection(${user.id})">
                        <label for="user-${user.id}" style="margin-left: 8px; flex: 1;">
                            ${user.display_name} (${user.username}) - ${user.email}
                        </label>
                    </div>
                `;
            });

            container.innerHTML = html;
        }

        function displayUsersManagement() {
            const container = document.getElementById('users-list');
            if (!registeredUsers || registeredUsers.length === 0) {
                container.innerHTML = '<p style="color: #6c757d; text-align: center;">No users found.</p>';
                return;
            }

            let html = '<div style="overflow-x: auto;"><table style="width: 100%; border-collapse: collapse;">';
            html += '<thead><tr style="background: #f8f9fa; border-bottom: 2px solid #dee2e6;">';
            html += '<th style="padding: 12px; text-align: left;">Display Name</th>';
            html += '<th style="padding: 12px; text-align: left;">Username</th>';
            html += '<th style="padding: 12px; text-align: left;">Email</th>';
            html += '<th style="padding: 12px; text-align: left;">Role</th>';
            html += '<th style="padding: 12px; text-align: left;">Status</th>';
            html += '<th style="padding: 12px; text-align: left;">Last Login</th>';
            html += '</tr></thead><tbody>';

            registeredUsers.forEach(user => {
                const statusText = user.active ? 'Active' : 'Inactive';
                const statusColor = user.active ? '#28a745' : '#dc3545';
                const lastLogin = user.last_login ? new Date(user.last_login).toLocaleDateString() : 'Never';

                html += `<tr style="border-bottom: 1px solid #dee2e6;">
                    <td style="padding: 12px;">${user.display_name}</td>
                    <td style="padding: 12px;">${user.username}</td>
                    <td style="padding: 12px;">${user.email}</td>
                    <td style="padding: 12px;"><span style="background: ${user.role === 'admin' ? '#007bff' : '#6c757d'}; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">${user.role}</span></td>
                    <td style="padding: 12px;"><span style="color: ${statusColor}; font-weight: 500;">${statusText}</span></td>
                    <td style="padding: 12px;">${lastLogin}</td>
                </tr>`;
            });

            html += '</tbody></table></div>';
            container.innerHTML = html;
        }

        function toggleUserSelection(userId) {
            const index = selectedUsers.indexOf(userId);
            if (index === -1) {
                selectedUsers.push(userId);
            } else {
                selectedUsers.splice(index, 1);
            }
        }

        // Removed toggleManualEntry - only using registered users now

        // Initialize
        checkAuth();
        document.getElementById('create-user-form').addEventListener('submit', createUser);
        document.getElementById('create-vote-form').addEventListener('submit', createVote);
        loadVotes();
        loadRegisteredUsers(); // Auto-load users when page loads

        // Set default dates to tomorrow at 11:59 PM local time
        function formatLocalDatetime(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            return `${year}-${month}-${day}T${hours}:${minutes}`;
        }

        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        tomorrow.setHours(23, 59, 0, 0);
        document.getElementById('nomination-deadline').value = formatLocalDatetime(tomorrow);

        const dayAfter = new Date();
        dayAfter.setDate(dayAfter.getDate() + 2);
        dayAfter.setHours(23, 59, 0, 0);
        document.getElementById('ranking-deadline').value = formatLocalDatetime(dayAfter);

        // Periodically check for deadline advances (every 5 minutes)
        setInterval(() => {
            checkDeadlineAdvance();
        }, 5 * 60 * 1000);

        function copyVoteLink(voteId) {
            const voteUrl = `${window.location.origin}/vote.php?id=${voteId}`;

            // Create a temporary textarea to copy to clipboard
            const textarea = document.createElement('textarea');
            textarea.value = voteUrl;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);

            // Show confirmation
            alert(`Vote link copied to clipboard:\n${voteUrl}`);
        }

        async function testEmail(emailType, phase = null) {
            const voteId = document.getElementById('test-vote-select').value;
            if (!voteId) {
                document.getElementById('email-test-result').innerHTML =
                    '<div style="color: #e74c3c;">Please select a vote first</div>';
                return;
            }

            try {
                const payload = {
                    action: 'test_email',
                    vote_id: voteId,
                    email_type: emailType
                };

                if (phase) {
                    payload.phase = phase;
                }

                const response = await fetch('admin_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (result.success) {
                    document.getElementById('email-test-result').innerHTML =
                        `<div style="color: #27ae60;">✅ ${result.data.message}</div>`;
                } else {
                    document.getElementById('email-test-result').innerHTML =
                        `<div style="color: #e74c3c;">❌ ${result.error}</div>`;
                }
            } catch (error) {
                document.getElementById('email-test-result').innerHTML =
                    `<div style="color: #e74c3c;">❌ Error: ${error.message}</div>`;
            }
        }

        async function checkDeadlineAdvance() {
            try {
                const response = await fetch('admin_api.php?action=check_deadline_advance');
                const result = await response.json();

                if (result.success) {
                    const data = result.data;
                    let message = `<div style="color: #27ae60;">✅ ${data.message}</div>`;

                    if (data.advanced_votes && data.advanced_votes.length > 0) {
                        message += '<div style="margin-top: 10px;"><strong>Advanced Votes:</strong><ul>';
                        data.advanced_votes.forEach(vote => {
                            message += `<li>${vote.title}: ${vote.from} → ${vote.to} (${vote.reason})</li>`;
                        });
                        message += '</ul></div>';
                    }

                    document.getElementById('email-test-result').innerHTML = message;

                    // Refresh the vote list if any votes were advanced
                    if (data.advanced_count > 0) {
                        loadVotes();
                    }
                } else {
                    document.getElementById('email-test-result').innerHTML =
                        `<div style="color: #e74c3c;">❌ ${result.error}</div>`;
                }
            } catch (error) {
                document.getElementById('email-test-result').innerHTML =
                    `<div style="color: #e74c3c;">❌ Error: ${error.message}</div>`;
            }
        }

        function populateVoteSelect(votes) {
            const select = document.getElementById('test-vote-select');
            select.innerHTML = '<option value="">Select a vote...</option>';

            votes.forEach(vote => {
                const option = document.createElement('option');
                option.value = vote.id;
                option.textContent = `${vote.title} (${vote.phase})`;
                select.appendChild(option);
            });
        }

        async function resolveTie(voteId, method) {
            const methodNames = {
                'automatic': 'automatic tiebreaking (first-place votes, etc.)',
                'random': 'random selection',
                'runoff': 'runoff vote'
            };

            if (!confirm(`Resolve tie using ${methodNames[method]}?`)) {
                return;
            }

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'resolve_tie',
                        vote_id: voteId,
                        method: method
                    })
                });

                const result = await response.json();

                if (result.success) {
                    alert('✅ ' + result.data);
                    loadVotes(); // Refresh the votes list
                } else {
                    alert('❌ ' + result.error);
                }
            } catch (error) {
                alert('❌ Error: ' + error.message);
            }
        }
    </script>
</body>
</html>
