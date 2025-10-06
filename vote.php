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
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border: 2px solid #ddd;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .nomination-item:hover {
            background: #f8f9fa;
        }
        .nomination-text {
            flex: 1;
            padding-left: 10px;
        }
        .ranking-controls {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-left: 10px;
        }
        .rank-input {
            width: 50px;
            padding: 4px;
            border: 2px solid #ddd;
            border-radius: 3px;
            text-align: center;
            font-size: 14px;
        }
        .rank-input:focus {
            outline: none;
            border-color: #3498db;
        }
        .rank-btn {
            background: #3498db;
            color: white;
            border: none;
            border-radius: 3px;
            padding: 2px 6px;
            cursor: pointer;
            font-size: 12px;
            min-width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .rank-btn:hover {
            background: #2980b9;
        }
        .rank-btn:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
        }
        .rank-number {
            background: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: bold;
            margin-right: 10px;
            min-width: 30px;
        }
        .results-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .runoff-notice {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            padding: 10px;
            border-radius: 5px;
            margin: 15px 0;
            color: #0c5460;
            font-size: 14px;
        }

        .random-notice {
            background: #e2e3e5;
            border: 1px solid #d1ecf1;
            padding: 10px;
            border-radius: 5px;
            margin: 15px 0;
            color: #383d41;
            font-size: 14px;
        }

        .tiebreaker-notice {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 10px;
            border-radius: 5px;
            margin: 15px 0;
            color: #856404;
            font-size: 14px;
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
        .tie-resolution-section {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
        }
        .tie-summary {
            background: white;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            text-align: left;
        }
        .tie-group {
            margin: 10px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 3px;
        }
        .tie-actions {
            margin-top: 20px;
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
                <p>Rank nominations from most preferred (top) to least preferred (bottom). Use the ↑ and ↓ buttons to move items, or type a number directly to set the rank.</p>
                <ul id="ranking-list" class="ranking-list"></ul>
                <button onclick="submitRankings()">Submit Rankings</button>
            </div>
        </div>

        <!-- Tie Resolution Phase -->
        <div id="tie-resolution-section" style="display: none;">
            <div class="tie-resolution-section">
                <h3>⚖️ Tie Detected</h3>
                <p>Multiple nominations received the same score. An administrator will decide how to resolve the tie.</p>
                <div id="tie-details"></div>
                <div class="tie-actions">
                    <p>Please wait for the administrator to resolve the tie, or check back later for final results.</p>
                    <button onclick="window.location.href='dashboard.php'" class="btn btn-primary">Return to Dashboard</button>
                </div>
            </div>
        </div>

        <!-- Runoff Phase -->
        <div id="runoff-section" style="display: none;">
            <div class="runoff-section">
                <h3>🔄 Runoff Vote</h3>
                <p>The top nominations were tied. Please rank only the tied options to break the tie.</p>
                <div id="runoff-details"></div>
                <ul id="runoff-ranking-list" class="ranking-list"></ul>
                <button onclick="submitRunoffRankings()" class="btn btn-primary">Submit Runoff Rankings</button>
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

        // URL parameter parsing (debug info removed)

        if (!voteId || voteId === 'undefined') {
            showMessage('No vote ID provided in URL: ' + window.location.search, 'error');
        } else {
            initializeVote();
        }

        function showMessage(text, type) {
            const messageDiv = document.getElementById('message-display');
            messageDiv.innerHTML = '<div class="message ' + type + '">' + text + '</div>';
        }

        function autoLinkUrls(text) {
            // Escape HTML to prevent XSS
            const escapeHtml = (str) => {
                const div = document.createElement('div');
                div.textContent = str;
                return div.innerHTML;
            };

            const escapedText = escapeHtml(text);

            // URL regex pattern - matches http://, https://, and www. URLs
            const urlPattern = /(https?:\/\/[^\s]+)|(www\.[^\s]+)/g;

            // Replace URLs with clickable links
            return escapedText.replace(urlPattern, (url) => {
                // Add protocol if missing (for www. links)
                const href = url.startsWith('www.') ? 'http://' + url : url;
                return `<a href="${href}" target="_blank" rel="noopener noreferrer">${url}</a>`;
            });
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
                    } else if (voteData.phase === 'tie_resolution') {
                        showTieResolutionInterface();
                    } else if (voteData.phase === 'runoff') {
                        showRunoffInterface();
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
                    showMessage('You must be logged in to view this vote. <a href="auth.php" style="color: #fff; text-decoration: underline;">Click here to log in</a>.', 'error');
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
            } else if (phase === 'tie_resolution') {
                phaseText = '⚖️ Tie Resolution - Awaiting administrator decision';
            } else if (phase === 'runoff') {
                phaseText = '🔄 Runoff Vote - Rank the tied nominations';
            } else if (phase === 'finished') {
                phaseText = '🏆 Results - Final rankings';
            }

            phaseDiv.textContent = phaseText;
            phaseDiv.style.display = 'block';
        }

        function showNominationInterface() {
            document.getElementById('nomination-section').style.display = 'block';
            document.getElementById('max-nominations').textContent = voteInfo.max_nominations || 2;
            document.getElementById('max-nominations-2').textContent = voteInfo.max_nominations || 2;

            // Input setup completed

            // Load existing nominations
            loadNominations();
        }

        async function showRankingInterface() {
            document.getElementById('ranking-section').style.display = 'block';
            await loadRankingNominations();
            showMessage('Ranking phase - use arrow buttons to rank nominations', 'info');
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
                        li.dataset.nominationId = nom.id;
                        li.dataset.rank = index + 1;

                        // Create rank number display
                        const rankNumber = document.createElement('div');
                        rankNumber.className = 'rank-number';
                        rankNumber.textContent = index + 1;

                        // Create nomination text
                        const nominationText = document.createElement('div');
                        nominationText.className = 'nomination-text';
                        nominationText.innerHTML = autoLinkUrls(nom.text);

                        // Create ranking controls
                        const controls = document.createElement('div');
                        controls.className = 'ranking-controls';

                        // Add manual rank input
                        const rankInput = document.createElement('input');
                        rankInput.type = 'number';
                        rankInput.className = 'rank-input';
                        rankInput.min = '1';
                        rankInput.max = result.data.length;
                        rankInput.value = index + 1;
                        rankInput.title = 'Set rank directly';
                        rankInput.onchange = () => setNominationRank(li, parseInt(rankInput.value));

                        const upBtn = document.createElement('button');
                        upBtn.className = 'rank-btn';
                        upBtn.innerHTML = '↑';
                        upBtn.title = 'Move up';
                        upBtn.onclick = () => moveNomination(li, 'up');

                        const downBtn = document.createElement('button');
                        downBtn.className = 'rank-btn';
                        downBtn.innerHTML = '↓';
                        downBtn.title = 'Move down';
                        downBtn.onclick = () => moveNomination(li, 'down');

                        controls.appendChild(rankInput);
                        controls.appendChild(upBtn);
                        controls.appendChild(downBtn);

                        li.appendChild(rankNumber);
                        li.appendChild(nominationText);
                        li.appendChild(controls);

                        rankingList.appendChild(li);
                    });

                    updateRankingButtons();

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

        async function showTieResolutionInterface() {
            document.getElementById('tie-resolution-section').style.display = 'block';
            await loadTieDetails();
            showMessage('Tie detected - awaiting administrator decision', 'info');
        }

        async function loadTieDetails() {
            try {
                const response = await fetch(`api.php?action=get_results&vote_id=${voteId}`);
                const result = await response.json();

                if (result.success && result.data.ties_detected) {
                    const tieDetails = document.getElementById('tie-details');
                    let html = '<div class="tie-summary"><h4>Tied Nominations:</h4>';

                    result.data.ties_detected.forEach(tie => {
                        html += `<div class="tie-group">
                            <strong>Rank ${tie.rank} (${tie.score} points each):</strong>
                            <ul>`;
                        tie.nominees.forEach(nominee => {
                            html += `<li>${nominee.nomination}</li>`;
                        });
                        html += '</ul></div>';
                    });

                    html += '</div>';
                    tieDetails.innerHTML = html;
                }
            } catch (error) {
                console.error('Error loading tie details:', error);
            }
        }

        async function showRunoffInterface() {
            document.getElementById('runoff-section').style.display = 'block';
            await loadRunoffNominations();
            showMessage('Runoff voting - rank only the tied nominations', 'info');
        }

        async function loadRunoffNominations() {
            try {
                const response = await fetch(`api.php?action=get_runoff_nominations&vote_id=${voteId}`);
                const result = await response.json();

                if (result.success) {
                    const runoffList = document.getElementById('runoff-ranking-list');
                    runoffList.innerHTML = '';

                    if (result.data.length === 0) {
                        runoffList.innerHTML = '<li>No runoff nominations available</li>';
                        return;
                    }

                    const detailsDiv = document.getElementById('runoff-details');
                    detailsDiv.innerHTML = `<p><strong>Rank these ${result.data.length} tied nominations</strong> from most preferred (top) to least preferred (bottom):</p>`;

                    result.data.forEach((nom, index) => {
                        const li = document.createElement('li');
                        li.className = 'nomination-item';
                        li.dataset.nominationId = nom.id;
                        li.dataset.rank = index + 1;

                        // Create rank number display
                        const rankNumber = document.createElement('div');
                        rankNumber.className = 'rank-number';
                        rankNumber.textContent = index + 1;

                        // Create nomination text
                        const nominationText = document.createElement('div');
                        nominationText.className = 'nomination-text';
                        nominationText.innerHTML = autoLinkUrls(nom.text);

                        // Create ranking controls
                        const controls = document.createElement('div');
                        controls.className = 'ranking-controls';

                        // Add manual rank input
                        const rankInput = document.createElement('input');
                        rankInput.type = 'number';
                        rankInput.className = 'rank-input';
                        rankInput.min = '1';
                        rankInput.max = result.data.length;
                        rankInput.value = index + 1;
                        rankInput.title = 'Set rank directly';
                        rankInput.onchange = () => setRunoffNominationRank(li, parseInt(rankInput.value));

                        const upBtn = document.createElement('button');
                        upBtn.className = 'rank-btn';
                        upBtn.innerHTML = '↑';
                        upBtn.title = 'Move up';
                        upBtn.onclick = () => moveRunoffNomination(li, 'up');

                        const downBtn = document.createElement('button');
                        downBtn.className = 'rank-btn';
                        downBtn.innerHTML = '↓';
                        downBtn.title = 'Move down';
                        downBtn.onclick = () => moveRunoffNomination(li, 'down');

                        controls.appendChild(rankInput);
                        controls.appendChild(upBtn);
                        controls.appendChild(downBtn);

                        li.appendChild(rankNumber);
                        li.appendChild(nominationText);
                        li.appendChild(controls);

                        runoffList.appendChild(li);
                    });

                    updateRunoffRankingButtons();

                } else {
                    showMessage('Could not load runoff nominations', 'error');
                }
            } catch (error) {
                showMessage('Error loading runoff nominations: ' + error.message, 'error');
            }
        }

        async function submitRunoffRankings() {
            const items = document.querySelectorAll('#runoff-ranking-list .nomination-item');
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
                        action: 'submit_runoff_rankings',
                        user_id: currentUser.id,
                        vote_id: voteId,
                        rankings: rankings
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showMessage('Runoff rankings submitted successfully! Returning to dashboard...', 'success');
                    setTimeout(() => {
                        window.location.href = 'dashboard.php';
                    }, 2000);
                } else {
                    showMessage('Failed to submit runoff rankings: ' + result.error, 'error');
                }
            } catch (error) {
                showMessage('Error submitting runoff rankings: ' + error.message, 'error');
            }
        }

        async function showResultsInterface() {
            document.getElementById('results-section').style.display = 'block';
            await loadResults();
            showMessage('Vote completed - view final results', 'info');
        }

        async function loadResults() {
            try {
                const response = await fetch(`api.php?action=get_results&vote_id=${voteId}`);
                const result = await response.json();

                if (result.success) {
                    const resultsContent = document.getElementById('results-content');
                    const data = result.data;

                    if (!data.results || data.results.length === 0) {
                        resultsContent.innerHTML = '<p>No results available yet. Make sure all participants have submitted their rankings.</p>';
                        return;
                    }

                    let html = `
                        <div class="results-summary">
                            <h4>🏆 Final Rankings (Borda Count Method)</h4>
                            <p>Rankings calculated using the Borda Count method where each nomination receives points based on its ranking position.</p>
                    `;

                    if (data.using_runoff_results) {
                        html += `
                            <div class="runoff-notice">
                                <strong>🗳️ Runoff Results:</strong> These results are based on the runoff vote that resolved ties among the top contenders.
                            </div>
                        `;
                    }

                    if (data.using_random_resolution) {
                        html += `
                            <div class="random-notice">
                                <strong>🎲 Random Resolution:</strong> Ties were resolved using random selection to determine final rankings.
                            </div>
                        `;
                    }

                    if (data.tiebreaking_applied) {
                        html += `
                            <div class="tiebreaker-notice">
                                <strong>⚖️ Tiebreaking Applied:</strong> Some nominations had equal Borda scores.
                                Ties broken by: ${data.tiebreaking_method}
                            </div>
                        `;
                    }

                    html += `
                        </div>
                        <div class="results-list">
                    `;

                    data.results.forEach((item, index) => {
                        const medal = index === 0 ? '🥇' : index === 1 ? '🥈' : index === 2 ? '🥉' : `${index + 1}.`;
                        const isWinner = index === 0;

                        let scoreDisplay = `${item.score} points`;
                        if (data.tiebreaking_applied && item.first_place_votes > 0) {
                            scoreDisplay += ` (${item.first_place_votes} first-place votes)`;
                        }

                        html += `
                            <div class="result-item ${isWinner ? 'winner' : ''}">
                                <div class="result-rank">${medal}</div>
                                <div class="result-nomination">${autoLinkUrls(item.nomination)}</div>
                                <div class="result-score">${scoreDisplay}</div>
                            </div>
                        `;
                    });

                    html += `
                        </div>
                        <div class="results-footer">
                            <p><small>Higher scores indicate more preferred options. Points are awarded based on ranking positions across all participants.</small></p>
                            <div style="display: flex; gap: 10px; justify-content: center;">
                                <button onclick="window.location.href='dashboard.php'" class="btn btn-primary">Return to Dashboard</button>
                                <button onclick="window.location.href='admin.php'" class="btn btn-secondary">Admin Panel</button>
                            </div>
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
                        li.innerHTML = autoLinkUrls(nom.text);
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

            const maxNominations = voteInfo.max_nominations || 2;
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
                    const maxNominations = voteInfo.max_nominations || 2;
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
                li.innerHTML = autoLinkUrls(nom.text);
                userList.appendChild(li);
            });

            // Update button states
            const maxNominations = voteInfo.max_nominations || 2;
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


        function updateRankNumbers() {
            const items = document.querySelectorAll('#ranking-list .nomination-item');
            items.forEach((item, index) => {
                item.dataset.rank = index + 1;
                const rankNumber = item.querySelector('.rank-number');
                const rankInput = item.querySelector('.rank-input');
                if (rankNumber) {
                    rankNumber.textContent = index + 1;
                }
                if (rankInput) {
                    rankInput.value = index + 1;
                }
            });
        }

        function setNominationRank(item, newRank) {
            const rankingList = document.getElementById('ranking-list');
            const allItems = Array.from(rankingList.children);
            const totalItems = allItems.length;

            // Validate input
            if (newRank < 1 || newRank > totalItems) {
                updateRankNumbers(); // Reset to current values
                return;
            }

            const currentIndex = allItems.indexOf(item);
            const targetIndex = newRank - 1;

            if (currentIndex === targetIndex) {
                return; // No change needed
            }

            // Remove item from current position
            rankingList.removeChild(item);

            // Insert at new position
            if (targetIndex === 0) {
                rankingList.insertBefore(item, allItems[0]);
            } else if (targetIndex >= totalItems - 1) {
                rankingList.appendChild(item);
            } else {
                const targetItem = allItems[targetIndex];
                if (currentIndex < targetIndex) {
                    rankingList.insertBefore(item, targetItem.nextSibling);
                } else {
                    rankingList.insertBefore(item, targetItem);
                }
            }

            updateRankNumbers();
            updateRankingButtons();
        }

        function moveNomination(item, direction) {
            const rankingList = document.getElementById('ranking-list');
            const allItems = Array.from(rankingList.children);
            const currentIndex = allItems.indexOf(item);

            if (direction === 'up' && currentIndex > 0) {
                // Move up (swap with previous item)
                rankingList.insertBefore(item, allItems[currentIndex - 1]);
            } else if (direction === 'down' && currentIndex < allItems.length - 1) {
                // Move down (swap with next item)
                rankingList.insertBefore(allItems[currentIndex + 1], item);
            }

            updateRankNumbers();
            updateRankingButtons();
        }

        function updateRankingButtons() {
            const items = document.querySelectorAll('#ranking-list .nomination-item');
            items.forEach((item, index) => {
                const upBtn = item.querySelector('.rank-btn:nth-child(2)'); // First button after input
                const downBtn = item.querySelector('.rank-btn:nth-child(3)'); // Second button after input

                if (upBtn) {
                    upBtn.disabled = (index === 0);
                }
                if (downBtn) {
                    downBtn.disabled = (index === items.length - 1);
                }
            });
        }



        function updateRunoffRankNumbers() {
            const items = document.querySelectorAll('#runoff-ranking-list .nomination-item');
            items.forEach((item, index) => {
                item.dataset.rank = index + 1;
                const rankNumber = item.querySelector('.rank-number');
                const rankInput = item.querySelector('.rank-input');
                if (rankNumber) {
                    rankNumber.textContent = index + 1;
                }
                if (rankInput) {
                    rankInput.value = index + 1;
                }
            });
        }

        function setRunoffNominationRank(item, newRank) {
            const runoffList = document.getElementById('runoff-ranking-list');
            const allItems = Array.from(runoffList.children);
            const totalItems = allItems.length;

            // Validate input
            if (newRank < 1 || newRank > totalItems) {
                updateRunoffRankNumbers(); // Reset to current values
                return;
            }

            const currentIndex = allItems.indexOf(item);
            const targetIndex = newRank - 1;

            if (currentIndex === targetIndex) {
                return; // No change needed
            }

            // Remove item from current position
            runoffList.removeChild(item);

            // Insert at new position
            if (targetIndex === 0) {
                runoffList.insertBefore(item, allItems[0]);
            } else if (targetIndex >= totalItems - 1) {
                runoffList.appendChild(item);
            } else {
                const targetItem = allItems[targetIndex];
                if (currentIndex < targetIndex) {
                    runoffList.insertBefore(item, targetItem.nextSibling);
                } else {
                    runoffList.insertBefore(item, targetItem);
                }
            }

            updateRunoffRankNumbers();
            updateRunoffRankingButtons();
        }

        function moveRunoffNomination(item, direction) {
            const runoffList = document.getElementById('runoff-ranking-list');
            const allItems = Array.from(runoffList.children);
            const currentIndex = allItems.indexOf(item);

            if (direction === 'up' && currentIndex > 0) {
                // Move up (swap with previous item)
                runoffList.insertBefore(item, allItems[currentIndex - 1]);
            } else if (direction === 'down' && currentIndex < allItems.length - 1) {
                // Move down (swap with next item)
                runoffList.insertBefore(allItems[currentIndex + 1], item);
            }

            updateRunoffRankNumbers();
            updateRunoffRankingButtons();
        }

        function updateRunoffRankingButtons() {
            const items = document.querySelectorAll('#runoff-ranking-list .nomination-item');
            items.forEach((item, index) => {
                const upBtn = item.querySelector('.rank-btn:nth-child(2)'); // First button after input
                const downBtn = item.querySelector('.rank-btn:nth-child(3)'); // Second button after input

                if (upBtn) {
                    upBtn.disabled = (index === 0);
                }
                if (downBtn) {
                    downBtn.disabled = (index === items.length - 1);
                }
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