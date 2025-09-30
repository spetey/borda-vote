<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Authored or modified by Claude - 2025-09-25 -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borda Vote - Dashboard</title>
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

        .header {
            background: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-info {
            color: #7f8c8d;
        }

        .btn {
            background: #3498db;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover {
            background: #2980b9;
        }

        .btn-success {
            background: #27ae60;
        }

        .btn-success:hover {
            background: #219a52;
        }

        .btn-secondary {
            background: #95a5a6;
        }

        .btn-secondary:hover {
            background: #7f8c8d;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .card h2 {
            color: #2c3e50;
            margin-bottom: 20px;
        }

        .vote-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #3498db;
        }

        .vote-title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .vote-meta {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 10px;
        }

        .vote-status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-nominating {
            background: #fff3cd;
            color: #856404;
        }

        .status-ranking {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-finished {
            background: #d4edda;
            color: #155724;
        }

        .empty-state {
            text-align: center;
            color: #6c757d;
            padding: 40px 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }

        .stat-card {
            background: #ecf0f1;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }

        .stat-label {
            font-size: 14px;
            color: #7f8c8d;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .header-content {
                flex-direction: column;
                gap: 10px;
            }

            .user-menu {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">Borda Vote</div>
            <div class="user-menu">
                <div class="user-info">
                    Welcome, <span id="user-display-name">...</span>
                </div>
                <a href="#" onclick="showProfile()" class="btn btn-secondary">Profile</a>
                <a href="#" onclick="logout()" class="btn btn-secondary">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="dashboard-grid">
            <!-- Active Votes -->
            <div class="card">
                <h2>Your Votes</h2>
                <div id="user-votes">
                    <div class="empty-state">Loading your votes...</div>
                </div>
            </div>

            <!-- Stats -->
            <div class="card">
                <h2>Your Statistics</h2>
                <div class="stats-grid" id="user-stats">
                    <div class="stat-card">
                        <div class="stat-number" id="stat-total-votes">-</div>
                        <div class="stat-label">Total Votes</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="stat-pending">-</div>
                        <div class="stat-label">Pending Actions</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="stat-completed">-</div>
                        <div class="stat-label">Completed</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="card">
            <h2>Available Votes</h2>
            <div id="available-votes">
                <div class="empty-state">Loading available votes...</div>
            </div>
        </div>
    </div>

    <script>
        // Authored or modified by Claude - 2025-09-25

        let currentUser = null;

        async function loadUserData() {
            try {
                const response = await fetch('user_api.php?action=get_dashboard');
                const result = await response.json();

                if (result.success) {
                    currentUser = result.data.user;
                    document.getElementById('user-display-name').textContent = currentUser.display_name;

                    displayUserVotes(result.data.user_votes);
                    displayUserStats(result.data.stats);
                    displayAvailableVotes(result.data.available_votes);
                } else {
                    console.error('Failed to load user data:', result.error);
                    // Redirect to login if session expired
                    if (result.error.includes('Authentication')) {
                        window.location.href = 'auth.php';
                    }
                }
            } catch (error) {
                console.error('Error loading user data:', error);
            }
        }

        function displayUserVotes(votes) {
            const container = document.getElementById('user-votes');

            if (!votes || votes.length === 0) {
                container.innerHTML = '<div class="empty-state">You haven\'t participated in any votes yet.</div>';
                return;
            }

            let html = '';
            votes.forEach(vote => {
                const statusClass = `status-${vote.phase}`;
                let buttonText = 'Enter Vote';
                let buttonClass = 'btn';

                if (vote.phase === 'nominating') {
                    buttonText = vote.has_nominated ? 'View Nominations' : 'Submit Nominations';
                } else if (vote.phase === 'ranking') {
                    buttonText = vote.has_ranked ? 'View Rankings' : 'Submit Rankings';
                } else if (vote.phase === 'finished') {
                    buttonText = 'View Results';
                    buttonClass = 'btn btn-success';
                }

                html += `
                    <div class="vote-item">
                        <div class="vote-title">${vote.title}</div>
                        <div class="vote-meta">
                            <span class="vote-status ${statusClass}">${vote.phase}</span>
                            • Role: ${vote.role}
                        </div>
                        <a href="vote.php?id=${vote.vote_id}" class="${buttonClass}">${buttonText}</a>
                    </div>
                `;
            });

            container.innerHTML = html;
        }

        function displayUserStats(stats) {
            document.getElementById('stat-total-votes').textContent = stats.total_votes || 0;
            document.getElementById('stat-pending').textContent = stats.pending_actions || 0;
            document.getElementById('stat-completed').textContent = stats.completed_votes || 0;
        }

        function displayAvailableVotes(votes) {
            const container = document.getElementById('available-votes');

            if (!votes || votes.length === 0) {
                container.innerHTML = '<div class="empty-state">No votes available at this time.</div>';
                return;
            }

            let html = '';
            votes.forEach(vote => {
                const statusClass = `status-${vote.phase}`;
                html += `
                    <div class="vote-item">
                        <div class="vote-title">${vote.title}</div>
                        <div class="vote-meta">
                            <span class="vote-status ${statusClass}">${vote.phase}</span>
                            • ${vote.total_users} participants
                        </div>
                        <a href="vote.php?id=${vote.id}" class="btn">Participate</a>
                    </div>
                `;
            });

            container.innerHTML = html;
        }

        function showProfile() {
            // TODO: Implement profile modal or redirect to profile page
            alert('Profile editing coming soon!');
        }

        async function logout() {
            try {
                const response = await fetch('auth_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ action: 'logout' })
                });

                const result = await response.json();

                if (result.success) {
                    window.location.href = 'auth.php';
                } else {
                    console.error('Logout failed:', result.error);
                }
            } catch (error) {
                console.error('Logout error:', error);
                // Force redirect even on error
                window.location.href = 'auth.php';
            }
        }

        // Initialize dashboard
        window.addEventListener('load', loadUserData);

        // Refresh data every 30 seconds
        setInterval(loadUserData, 30000);
    </script>
</body>
</html>