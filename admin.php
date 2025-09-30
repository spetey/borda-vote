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

        <div class="card">

            <!-- Create New Vote Form -->
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

            <!-- Existing Votes -->
            <div class="card">
                <h2>Existing Votes</h2>
                <div id="votes-list">
                    <p>Loading votes...</p>
                </div>
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

        function generatePassword() {
            const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            let result = '';
            for (let i = 0; i < 8; i++) {
                result += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            return result;
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
                const response = await fetch('admin_api.php?action=list_votes');
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
                html += `
                    <tr>
                        <td>${vote.title}</td>
                        <td>${vote.phase}</td>
                        <td>${vote.total_users}</td>
                        <td>${vote.total_nominations}</td>
                        <td>
                            <a href="vote.php?id=${vote.id}" class="btn btn-primary">Participate</a>
                            <button onclick="copyVoteLink(${vote.id})" class="btn-secondary">Copy Link</button>
                            <button onclick="advancePhase(${vote.id})" class="btn-success">Advance Phase</button>
                            <button onclick="viewPasswords(${vote.id})" class="btn-secondary">View Users</button>
                            <button onclick="deleteVote(${vote.id})" class="btn-danger">Delete</button>
                        </td>
                    </tr>
                `;
            });

            html += '</tbody></table>';
            container.innerHTML = html;
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

        async function deleteVote(voteId) {
            if (!confirm('Are you sure you want to delete this vote? This cannot be undone.')) {
                return;
            }

            try {
                const response = await fetch('admin_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'delete_vote',
                        vote_id: voteId
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showMessage('Vote deleted successfully!', 'success');
                    loadVotes();
                } else {
                    showMessage(result.error || 'Failed to delete vote', 'error');
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

        async function viewPasswords(voteId) {
            try {
                const response = await fetch(`admin_api.php?action=get_passwords&vote_id=${voteId}`);
                const result = await response.json();

                if (result.success) {
                    let html = `
                        <div id="password-info-${voteId}" class="password-container">
                            <h3>
                                Users for: ${result.data.vote_title}
                                <button onclick="document.getElementById('password-info-${voteId}').remove()" class="close-passwords">Close</button>
                            </h3>
                            <div style="background: #fff3cd; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                                <strong>Note:</strong> ${result.data.message}
                            </div>
                    `;

                    result.data.users.forEach(user => {
                        html += `<div class="password-display">${user.email} (Hash: ${user.password_hash})</div>`;
                    });

                    html += `
                            <div style="margin-top: 15px; padding: 10px; background: #e8f8f5; border-radius: 5px;">
                                <strong>Testing:</strong> If you just created this vote, the passwords were shown above.
                                Otherwise, you'll need to create a new vote to get fresh passwords.
                            </div>
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
        document.getElementById('create-vote-form').addEventListener('submit', createVote);
        loadVotes();
        loadRegisteredUsers(); // Auto-load users when page loads

        // Set default dates to tomorrow
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        tomorrow.setHours(23, 59);
        document.getElementById('nomination-deadline').value = tomorrow.toISOString().slice(0, 16);

        const dayAfter = new Date();
        dayAfter.setDate(dayAfter.getDate() + 2);
        dayAfter.setHours(23, 59);
        document.getElementById('ranking-deadline').value = dayAfter.toISOString().slice(0, 16);

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
    </script>
</body>
</html>
