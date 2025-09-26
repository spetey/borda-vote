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
        <div class="card">
            <h1>Borda Vote Administration</h1>

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
                        <label for="participants">Participants (one email per line):</label>
                        <textarea id="participants" rows="5" placeholder="user1@example.com&#10;user2@example.com"></textarea>
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
            const participantsText = document.getElementById('participants').value;

            const participants = participantsText.split('\n')
                .map(email => email.trim())
                .filter(email => email && email.includes('@'));

            if (participants.length === 0) {
                showMessage('Please enter at least one valid email address', 'error');
                return;
            }

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
                        participants
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showMessage('Vote created successfully!', 'success');

                    // Display generated passwords in a persistent container
                    let passwordsHtml = `
                        <div id="passwords-container" class="password-container">
                            <h3>
                                Generated Passwords
                                <button onclick="closePasswords()" class="close-passwords">Close</button>
                            </h3>
                            <p><strong>IMPORTANT:</strong> Save these passwords! They won't be shown again.</p>
                    `;

                    result.data.passwords.forEach(p => {
                        passwordsHtml += `
                            <div class="password-display">
                                ${p.email}: <strong>${p.password}</strong>
                                <button onclick="copyPassword('${p.password}')" class="copy-btn">Copy</button>
                            </div>
                        `;
                    });

                    passwordsHtml += `
                            <div style="margin-top: 15px; padding: 10px; background: #fff3cd; border-radius: 5px; border: 1px solid #ffeaa7;">
                                <strong>Quick Test:</strong> You can now go to <a href="index.html" target="_blank">index.html</a> and use any of these passwords to test the voting system.
                            </div>
                        </div>
                    `;

                    // Insert after the create vote form
                    const form = document.getElementById('create-vote-form').closest('.card');
                    form.insertAdjacentHTML('afterend', passwordsHtml);

                    // Reset form
                    document.getElementById('create-vote-form').reset();
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

        // Initialize
        document.getElementById('create-vote-form').addEventListener('submit', createVote);
        loadVotes();

        // Set default dates to tomorrow
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        tomorrow.setHours(23, 59);
        document.getElementById('nomination-deadline').value = tomorrow.toISOString().slice(0, 16);

        const dayAfter = new Date();
        dayAfter.setDate(dayAfter.getDate() + 2);
        dayAfter.setHours(23, 59);
        document.getElementById('ranking-deadline').value = dayAfter.toISOString().slice(0, 16);
    </script>
</body>
</html>

<?php
// Authored or modified by Claude - 2025-09-25
// Admin API endpoints - this would typically be in a separate admin_api.php file

if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    require_once 'config.php';

    header('Content-Type: application/json');

    function jsonResponse($success, $data = null, $error = null) {
        echo json_encode([
            'success' => $success,
            'data' => $data,
            'error' => $error
        ]);
        exit;
    }

    function getDb() {
        try {
            $pdo = new PDO('sqlite:' . DB_PATH);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (PDOException $e) {
            jsonResponse(false, null, 'Database connection failed');
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';

        switch ($action) {
            case 'create_vote':
                $pdo = getDb();

                // Insert vote
                $stmt = $pdo->prepare('INSERT INTO votes (title, max_nominations_per_user, nomination_deadline, ranking_deadline) VALUES (?, ?, ?, ?)');
                $stmt->execute([
                    $input['title'],
                    $input['max_nominations'],
                    $input['nomination_deadline'],
                    $input['ranking_deadline']
                ]);

                $voteId = $pdo->lastInsertId();

                // Create users with random passwords
                $passwords = [];
                foreach ($input['participants'] as $email) {
                    $password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                    $stmt = $pdo->prepare('INSERT INTO users (vote_id, password_hash, email) VALUES (?, ?, ?)');
                    $stmt->execute([$voteId, $passwordHash, $email]);

                    $passwords[] = ['email' => $email, 'password' => $password];
                }

                jsonResponse(true, ['vote_id' => $voteId, 'passwords' => $passwords]);
                break;

            case 'advance_phase':
                $pdo = getDb();
                $stmt = $pdo->prepare('SELECT phase FROM votes WHERE id = ?');
                $stmt->execute([$input['vote_id']]);
                $currentPhase = $stmt->fetchColumn();

                $nextPhase = match($currentPhase) {
                    'nominating' => 'ranking',
                    'ranking' => 'finished',
                    default => null
                };

                if ($nextPhase) {
                    $stmt = $pdo->prepare('UPDATE votes SET phase = ? WHERE id = ?');
                    $stmt->execute([$nextPhase, $input['vote_id']]);
                    jsonResponse(true, 'Phase advanced');
                } else {
                    jsonResponse(false, null, 'Cannot advance phase');
                }
                break;

            case 'delete_vote':
                $pdo = getDb();
                $stmt = $pdo->prepare('DELETE FROM votes WHERE id = ?');
                $stmt->execute([$input['vote_id']]);
                jsonResponse(true, 'Vote deleted');
                break;
        }
    } else {
        $action = $_GET['action'] ?? '';

        if ($action === 'list_votes') {
            $pdo = getDb();
            $stmt = $pdo->query('
                SELECT v.*,
                       COUNT(DISTINCT u.id) as total_users,
                       COUNT(DISTINCT n.id) as total_nominations
                FROM votes v
                LEFT JOIN users u ON v.id = u.vote_id
                LEFT JOIN nominations n ON v.id = n.vote_id
                GROUP BY v.id
                ORDER BY v.created_at DESC
            ');
            $votes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            jsonResponse(true, $votes);
        }
    }
}
?>