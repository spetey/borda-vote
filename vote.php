<?php
// Authored or modified by Claude - 2025-09-27
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borda Vote</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
        .info {
            background: #3498db;
            color: white;
        }
        .voting-phase {
            background: #3498db;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .nomination-form, .ranking-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .nomination-list, .ranking-list {
            list-style: none;
            padding: 0;
        }
        .nomination-item {
            background: white;
            padding: 10px;
            margin: 5px 0;
            border-radius: 3px;
            border-left: 4px solid #3498db;
            cursor: move;
            transition: all 0.2s ease;
        }
        .nomination-item:hover {
            background: #f8f9fa;
            transform: translateX(5px);
        }
        .nomination-item.drag-over {
            border-top: 3px solid #e74c3c;
            transform: translateY(-2px);
        }
        .results-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .results-list {
            margin-bottom: 20px;
        }
        .result-item {
            display: flex;
            align-items: center;
            background: white;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border-left: 4px solid #3498db;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .result-item.winner {
            border-left-color: #f1c40f;
            background: #fffbf0;
            font-weight: bold;
        }
        .result-rank {
            font-size: 1.5em;
            margin-right: 15px;
            min-width: 40px;
        }
        .result-nomination {
            flex: 1;
            font-size: 1.1em;
        }
        .result-score {
            font-weight: bold;
            color: #2ecc71;
            font-size: 1.1em;
        }
        .results-footer {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
        }
        .btn {
            background: #3498db;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background: #2980b9;
        }
        .btn-primary {
            background: #3498db;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 id="vote-title">Loading vote...</h1>
        <div id="message-display"></div>

        <div id="voting-phase" class="voting-phase" style="display: none;"></div>

        <!-- Nomination Phase -->
        <div id="nomination-section" style="display: none;">
            <div class="nomination-form">
                <h3>Submit Your Nominations</h3>
                <p>You can submit up to <span id="max-nominations">2</span> nominations.</p>
                <div>
                    <input type="text" id="nomination-input" placeholder="Enter your nomination..." maxlength="500">
                    <button onclick="submitNomination()">Add Nomination</button>
                </div>
                <p><span id="nomination-count">0</span> of <span id="max-nominations-2">2</span> nominations submitted</p>
                <button onclick="finishNominating()" id="done-nominating-btn" style="margin-top: 10px;">Done Nominating</button>
            </div>

            <div id="current-nominations">
                <h4>Your Nominations:</h4>
                <ul id="user-nominations" class="nomination-list"></ul>
            </div>

            <div id="all-nominations">
                <h4>All Nominations So Far:</h4>
                <ul id="all-nominations-list" class="nomination-list"></ul>
            </div>
        </div>

        <!-- Ranking Phase -->
        <div id="ranking-section" style="display: none;">
            <div class="ranking-section">
                <h3>Rank Your Preferences</h3>
                <p>Drag and drop to rank the nominations from most preferred (top) to least preferred (bottom).</p>
                <ul id="ranking-list" class="ranking-list"></ul>
                <button onclick="submitRankings()">Submit Rankings</button>
            </div>
        </div>

        <!-- Results Phase -->
        <div id="results-section" style="display: none;">
            <h3>Final Results</h3>
            <div id="results-content"></div>
        </div>
    </div>

    <script>
        let voteId = null;
        let voteInfo = null;
        let currentUser = null;
        let userNominations = [];

        // Get vote ID from URL
        const urlParams = new URLSearchParams(window.location.search);
        voteId = urlParams.get('id');

        // Debug: Show what we got from URL
        document.body.insertAdjacentHTML('afterbegin',
            '<div style="background: orange; color: white; padding: 10px; margin: 10px;">DEBUG: URL search: ' + window.location.search + ', voteId: ' + voteId + '</div>'
        );

        if (!voteId || voteId === 'undefined') {
            showMessage('No vote ID provided in URL: ' + window.location.search, 'error');
        } else {
            initializeVote();
        }

        function showMessage(text, type) {
            const messageDiv = document.getElementById('message-display');
            messageDiv.innerHTML = '<div class="message ' + type + '">' + text + '</div>';
        }

        async function initializeVote() {
            try {
                // Check authentication first
                const authResult = await checkAuth();
                if (!authResult) {
                    return;
                }

                // Load vote information
                const voteData = await loadVoteInfo();
                if (voteData) {
                    document.getElementById('vote-title').textContent = voteData.title;

                    // Show current phase
                    showVotingPhase(voteData.phase);

                    // Show appropriate interface based on phase
                    if (voteData.phase === 'nominating') {
                        showNominationInterface();
                    } else if (voteData.phase === 'ranking') {
                        showRankingInterface();
                    } else if (voteData.phase === 'finished') {
                        showResultsInterface();
                    }
                } else {
                    showMessage('Failed to load vote information', 'error');
                }

            } catch (error) {
                showMessage('Error initializing vote: ' + error.message, 'error');
            }
        }

        async function checkAuth() {
            try {
                const response = await fetch('auth_api.php?action=check_session');
                const result = await response.json();

                if (!result.success || !result.data.logged_in) {
                    showMessage('You must be logged in to view this vote. Please log in first.', 'error');
                    return false;
                }

                currentUser = result.data.user;
                return true;
            } catch (error) {
                showMessage('Authentication check failed: ' + error.message, 'error');
                return false;
            }
        }

        async function loadVoteInfo() {
            try {
                const response = await fetch('api.php?action=get_status&vote_id=' + voteId);
                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.error || 'Failed to load vote');
                }

                voteInfo = result.data;
                return result.data;
            } catch (error) {
                throw error;
            }
        }

        function showVotingPhase(phase) {
            const phaseDiv = document.getElementById('voting-phase');
            let phaseText = '';

            if (phase === 'nominating') {
                phaseText = '📝 Nomination Phase - Submit your suggestions';
            } else if (phase === 'ranking') {
                phaseText = '🔀 Ranking Phase - Rank your preferences';
            } else if (phase === 'finished') {
                phaseText = '🏆 Results - Final rankings';
            }

            phaseDiv.textContent = phaseText;
            phaseDiv.style.display = 'block';
        }

        function showNominationInterface() {
            document.getElementById('nomination-section').style.display = 'block';
            document.getElementById('max-nominations').textContent = voteInfo.max_nominations_per_user || 2;
            document.getElementById('max-nominations-2').textContent = voteInfo.max_nominations_per_user || 2;

            // Debug: Check input state
            setTimeout(() => {
                const input = document.getElementById('nomination-input');
                document.body.insertAdjacentHTML('afterbegin',
                    '<div style="background: purple; color: white; padding: 10px; margin: 10px;">DEBUG: Input disabled: ' + input.disabled + ', Input display: ' + window.getComputedStyle(input).display + '</div>'
                );

                // Test if input receives events
                input.addEventListener('focus', () => {
                    document.body.insertAdjacentHTML('afterbegin',
                        '<div style="background: green; color: white; padding: 10px; margin: 10px;">DEBUG: Input focused!</div>'
                    );
                });

                input.addEventListener('click', () => {
                    document.body.insertAdjacentHTML('afterbegin',
                        '<div style="background: blue; color: white; padding: 10px; margin: 10px;">DEBUG: Input clicked!</div>'
                    );
                });
            }, 100);

            // Load existing nominations
            loadNominations();
        }

        async function showRankingInterface() {
            document.getElementById('ranking-section').style.display = 'block';
            await loadRankingNominations();
            showMessage('Ranking phase - drag nominations to rank them', 'info');
        }

        async function loadRankingNominations() {
            try {
                const response = await fetch(`api.php?action=get_all_nominations&vote_id=${voteId}`);
                const result = await response.json();

                if (result.success) {
                    const rankingList = document.getElementById('ranking-list');
                    rankingList.innerHTML = '';

                    (result.data || []).forEach((nom, index) => {
                        const li = document.createElement('li');
                        li.className = 'nomination-item';
                        li.textContent = nom.text;
                        li.draggable = true;
                        li.dataset.nominationId = nom.id;
                        li.dataset.rank = index + 1;

                        // Add drag event handlers
                        li.addEventListener('dragstart', handleDragStart);
                        li.addEventListener('dragover', handleDragOver);
                        li.addEventListener('drop', handleDrop);
                        li.addEventListener('dragenter', handleDragEnter);
                        li.addEventListener('dragleave', handleDragLeave);

                        rankingList.appendChild(li);
                    });

                    if (result.data.length === 0) {
                        rankingList.innerHTML = '<li>No nominations available to rank</li>';
                    }
                } else {
                    showMessage('Could not load nominations for ranking', 'error');
                }
            } catch (error) {
                showMessage('Error loading nominations: ' + error.message, 'error');
            }
        }

        async function showResultsInterface() {
            document.getElementById('results-section').style.display = 'block';
            await loadResults();
            showMessage('Vote completed - view final results', 'info');
        }

        async function loadResults() {
            try {
                console.log('Loading results for vote:', voteId);
                const response = await fetch(`api.php?action=get_results&vote_id=${voteId}`);
                const result = await response.json();
                console.log('Results API response:', result);

                if (result.success) {
                    const resultsContent = document.getElementById('results-content');

                    if (!result.data || result.data.length === 0) {
                        resultsContent.innerHTML = '<p>No results available yet. Make sure all participants have submitted their rankings.</p>';
                        return;
                    }

                    let html = `
                        <div class="results-summary">
                            <h4>🏆 Final Rankings (Borda Count Method)</h4>
                            <p>Rankings calculated using the Borda Count method where each nomination receives points based on its ranking position.</p>
                        </div>
                        <div class="results-list">
                    `;

                    result.data.forEach((item, index) => {
                        const medal = index === 0 ? '🥇' : index === 1 ? '🥈' : index === 2 ? '🥉' : `${index + 1}.`;
                        const isWinner = index === 0;

                        html += `
                            <div class="result-item ${isWinner ? 'winner' : ''}">
                                <div class="result-rank">${medal}</div>
                                <div class="result-nomination">${item.nomination}</div>
                                <div class="result-score">${item.score} points</div>
                            </div>
                        `;
                    });

                    html += `
                        </div>
                        <div class="results-footer">
                            <p><small>Higher scores indicate more preferred options. Points are awarded based on ranking positions across all participants.</small></p>
                            <button onclick="window.location.href='dashboard.php'" class="btn btn-primary">Return to Dashboard</button>
                        </div>
                    `;

                    resultsContent.innerHTML = html;
                } else {
                    showMessage('Could not load results: ' + result.error, 'error');
                }
            } catch (error) {
                showMessage('Error loading results: ' + error.message, 'error');
            }
        }

        async function loadNominations() {
            try {
                // Load user's own nominations from API
                const response = await fetch(`api.php?action=get_user_nominations&vote_id=${voteId}&user_id=${currentUser.id}`);
                const result = await response.json();

                if (result.success) {
                    userNominations = result.data || [];
                    updateNominationDisplay();

                    // Also load all nominations to display
                    await loadAllNominations();

                    showMessage('Ready to submit nominations', 'info');
                } else {
                    showMessage('Could not load existing nominations', 'error');
                }
            } catch (error) {
                showMessage('Error loading nominations: ' + error.message, 'error');
            }
        }

        async function loadAllNominations() {
            try {
                const response = await fetch(`api.php?action=get_all_nominations&vote_id=${voteId}`);
                const result = await response.json();

                if (result.success) {
                    const allNomsList = document.getElementById('all-nominations-list');
                    allNomsList.innerHTML = '';

                    (result.data || []).forEach(nom => {
                        const li = document.createElement('li');
                        li.className = 'nomination-item';
                        li.textContent = nom.text;
                        allNomsList.appendChild(li);
                    });
                }
            } catch (error) {
                console.log('Could not load all nominations:', error.message);
            }
        }

        async function submitNomination() {
            const input = document.getElementById('nomination-input');
            const nomination = input.value.trim();

            if (!nomination) {
                showMessage('Please enter a nomination', 'error');
                return;
            }

            const maxNominations = voteInfo.max_nominations_per_user || 2;
            if (userNominations.length >= maxNominations) {
                showMessage(`You can only submit ${maxNominations} nominations`, 'error');
                return;
            }

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'submit_nomination',
                        user_id: currentUser.id,
                        vote_id: voteId,
                        text: nomination
                    })
                });

                const result = await response.json();

                if (result.success) {
                    userNominations.push({
                        id: result.data.nomination_id,
                        text: nomination
                    });
                    updateNominationDisplay();
                    await loadAllNominations(); // Refresh all nominations
                    showMessage('Nomination submitted: ' + nomination, 'success');
                    input.value = '';

                    // Check if user has reached max nominations
                    const maxNominations = voteInfo.max_nominations_per_user || 2;
                    if (userNominations.length >= maxNominations) {
                        showMessage('Maximum nominations reached! Returning to dashboard...', 'success');
                        setTimeout(() => {
                            window.location.href = 'dashboard.php';
                        }, 2000);
                    }
                } else {
                    showMessage('Failed to submit nomination: ' + result.error, 'error');
                }
            } catch (error) {
                showMessage('Error submitting nomination: ' + error.message, 'error');
            }
        }

        function updateNominationDisplay() {
            // Update count
            document.getElementById('nomination-count').textContent = userNominations.length;

            // Update user nominations list
            const userList = document.getElementById('user-nominations');
            userList.innerHTML = '';

            userNominations.forEach(nom => {
                const li = document.createElement('li');
                li.className = 'nomination-item';
                li.textContent = nom.text;
                userList.appendChild(li);
            });

            // Update button states
            const maxNominations = voteInfo.max_nominations_per_user || 2;
            const addButton = document.querySelector('button[onclick="submitNomination()"]');
            const input = document.getElementById('nomination-input');

            if (userNominations.length >= maxNominations) {
                addButton.disabled = true;
                input.disabled = true;
                addButton.textContent = 'Max nominations reached';
            } else {
                addButton.disabled = false;
                input.disabled = false;
                addButton.textContent = 'Add Nomination';
            }
        }

        async function finishNominating() {
            if (userNominations.length === 0) {
                showMessage('Please submit at least one nomination before finishing', 'error');
                return;
            }

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'mark_nomination_complete',
                        user_id: currentUser.id,
                        vote_id: voteId
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showMessage('Nominations completed! Returning to dashboard...', 'success');
                    document.getElementById('nomination-input').disabled = true;
                    document.querySelector('button[onclick="submitNomination()"]').disabled = true;
                    document.getElementById('done-nominating-btn').disabled = true;

                    // Redirect to dashboard after 2 seconds
                    setTimeout(() => {
                        window.location.href = 'dashboard.php';
                    }, 2000);
                } else {
                    showMessage('Error completing nominations: ' + result.error, 'error');
                }
            } catch (error) {
                showMessage('Error completing nominations: ' + error.message, 'error');
            }
        }

        let draggedElement = null;

        function handleDragStart(e) {
            draggedElement = this;
            this.style.opacity = '0.5';
        }

        function handleDragEnter(e) {
            this.classList.add('drag-over');
        }

        function handleDragLeave(e) {
            this.classList.remove('drag-over');
        }

        function handleDragOver(e) {
            if (e.preventDefault) {
                e.preventDefault();
            }
            return false;
        }

        function handleDrop(e) {
            if (e.stopPropagation) {
                e.stopPropagation();
            }

            if (draggedElement !== this) {
                const rankingList = document.getElementById('ranking-list');
                const allItems = Array.from(rankingList.children);
                const draggedIndex = allItems.indexOf(draggedElement);
                const targetIndex = allItems.indexOf(this);

                if (draggedIndex < targetIndex) {
                    rankingList.insertBefore(draggedElement, this.nextSibling);
                } else {
                    rankingList.insertBefore(draggedElement, this);
                }

                // Update ranks
                updateRankNumbers();
            }

            this.classList.remove('drag-over');
            draggedElement.style.opacity = '1';
            return false;
        }

        function updateRankNumbers() {
            const items = document.querySelectorAll('#ranking-list .nomination-item');
            items.forEach((item, index) => {
                item.dataset.rank = index + 1;
            });
        }

        async function submitRankings() {
            const items = document.querySelectorAll('#ranking-list .nomination-item');
            if (items.length === 0) {
                showMessage('No nominations to rank', 'error');
                return;
            }

            const rankings = [];
            items.forEach((item, index) => {
                rankings.push({
                    nomination_id: parseInt(item.dataset.nominationId),
                    rank: index + 1
                });
            });

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'submit_rankings',
                        user_id: currentUser.id,
                        vote_id: voteId,
                        rankings: rankings
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showMessage('Rankings submitted successfully! Returning to dashboard...', 'success');
                    setTimeout(() => {
                        window.location.href = 'dashboard.php';
                    }, 2000);
                } else {
                    showMessage('Failed to submit rankings: ' + result.error, 'error');
                }
            } catch (error) {
                showMessage('Error submitting rankings: ' + error.message, 'error');
            }
        }
    </script>
</body>
</html>