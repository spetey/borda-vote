<?php
// Authored or modified by Claude - 2025-09-25

require_once 'config.php';
require_once 'email_utils.php';

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

function validateInput($data, $required_fields) {
    foreach ($required_fields as $field) {
        if (!isset($data[$field])) {
            return false;
        }

        // Handle string fields (trim and check if empty)
        if (is_string($data[$field])) {
            if (trim($data[$field]) === '') {
                return false;
            }
        }
        // Handle array fields (check if empty array)
        elseif (is_array($data[$field])) {
            if (empty($data[$field])) {
                return false;
            }
        }
        // Handle other types (null, false, etc.)
        else {
            if (empty($data[$field])) {
                return false;
            }
        }
    }
    return true;
}

function applyTiebreaking($results) {
    // Sort using multiple criteria for tiebreaking
    usort($results, function($a, $b) {
        // Primary sort: Borda score (descending)
        $scoreComparison = $b['score'] - $a['score'];
        if ($scoreComparison !== 0) {
            return $scoreComparison;
        }

        // Tiebreaker 1: First place votes (descending)
        $firstPlaceComparison = $b['first_place_votes'] - $a['first_place_votes'];
        if ($firstPlaceComparison !== 0) {
            return $firstPlaceComparison;
        }

        // Tiebreaker 2: Second place votes (descending)
        $secondPlaceComparison = $b['second_place_votes'] - $a['second_place_votes'];
        if ($secondPlaceComparison !== 0) {
            return $secondPlaceComparison;
        }

        // Tiebreaker 3: Third place votes (descending)
        $thirdPlaceComparison = $b['third_place_votes'] - $a['third_place_votes'];
        if ($thirdPlaceComparison !== 0) {
            return $thirdPlaceComparison;
        }

        // Tiebreaker 4: Fourth place votes (descending)
        $fourthPlaceComparison = $b['fourth_place_votes'] - $a['fourth_place_votes'];
        if ($fourthPlaceComparison !== 0) {
            return $fourthPlaceComparison;
        }

        // Tiebreaker 5: Fifth place votes (descending)
        $fifthPlaceComparison = $b['fifth_place_votes'] - $a['fifth_place_votes'];
        if ($fifthPlaceComparison !== 0) {
            return $fifthPlaceComparison;
        }

        // Final tiebreaker: Lexicographic (alphabetical) order
        return strcmp($a['nomination'], $b['nomination']);
    });

    return $results;
}

function checkForTies($vote_id) {
    $pdo = getDb();

    // Get total nominations for Borda calculation
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM nominations WHERE vote_id = ?');
    $stmt->execute([$vote_id]);
    $total_nominations = $stmt->fetchColumn();

    // Get basic results to check for ties
    $stmt = $pdo->prepare('
        SELECT n.text as nomination,
               SUM(? - r.rank + 1) as score
        FROM nominations n
        LEFT JOIN rankings r ON n.id = r.nomination_id
        WHERE n.vote_id = ?
        GROUP BY n.id, n.text
        ORDER BY score DESC
    ');
    $stmt->execute([$total_nominations, $vote_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Check for ties in top positions
    $has_ties = false;
    $tied_groups = [];

    if (count($results) > 1) {
        for ($i = 1; $i < count($results); $i++) {
            if ($results[$i]['score'] === $results[$i-1]['score']) {
                $has_ties = true;
                break;
            }
        }
    }

    return ['has_ties' => $has_ties, 'results' => $results];
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $input['action'] ?? $_GET['action'] ?? '';

error_log("DEBUG api.php: action='$action', method=" . $_SERVER['REQUEST_METHOD'] . ", input=" . json_encode($input));

switch ($action) {
    case 'login':
        if (!validateInput($input, ['password'])) {
            jsonResponse(false, null, 'Password is required');
        }

        $pdo = getDb();
        $stmt = $pdo->prepare('SELECT u.*, v.title, v.phase, v.nomination_deadline, v.ranking_deadline FROM users u JOIN votes v ON u.vote_id = v.id WHERE u.password_hash = ?');
        $stmt->execute([password_hash($input['password'], PASSWORD_DEFAULT)]);

        // For development, also try direct password match
        if (!$stmt->rowCount()) {
            $stmt = $pdo->prepare('SELECT u.*, v.title, v.phase, v.nomination_deadline, v.ranking_deadline FROM users u JOIN votes v ON u.vote_id = v.id');
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($users as $user) {
                if (password_verify($input['password'], $user['password_hash'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['vote_id'] = $user['vote_id'];

                    jsonResponse(true, [
                        'user_id' => $user['id'],
                        'vote_info' => [
                            'vote_id' => $user['vote_id'],
                            'title' => $user['title'],
                            'phase' => $user['phase'],
                            'nomination_deadline' => $user['nomination_deadline'],
                            'ranking_deadline' => $user['ranking_deadline']
                        ]
                    ]);
                }
            }
        }

        jsonResponse(false, null, 'Invalid password');
        break;

    case 'get_nominations':
        $vote_id = $_GET['vote_id'] ?? null;
        if (!$vote_id) {
            jsonResponse(false, null, 'Vote ID is required');
        }

        $pdo = getDb();
        $stmt = $pdo->prepare('SELECT text FROM nominations WHERE vote_id = ? ORDER BY created_at');
        $stmt->execute([$vote_id]);
        $nominations = $stmt->fetchAll(PDO::FETCH_COLUMN);

        jsonResponse(true, $nominations);
        break;

    case 'get_user_nominations':
        $vote_id = $_GET['vote_id'] ?? null;
        $user_id = $_GET['user_id'] ?? null;

        if (!$vote_id || !$user_id) {
            jsonResponse(false, null, 'Vote ID and user ID are required');
        }

        $pdo = getDb();
        $stmt = $pdo->prepare('SELECT id, text FROM nominations WHERE vote_id = ? AND user_id = ? ORDER BY created_at');
        $stmt->execute([$vote_id, $user_id]);
        $nominations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        jsonResponse(true, $nominations);
        break;

    case 'get_past_nominations':
        $user_id = $_GET['user_id'] ?? null;
        $current_vote_id = $_GET['vote_id'] ?? null;

        if (!$user_id || !$current_vote_id) {
            jsonResponse(false, null, 'User ID and vote ID are required');
        }

        $pdo = getDb();
        // Get distinct past nominations from this user, excluding current vote
        // Order by most recent usage
        $stmt = $pdo->prepare('
            SELECT DISTINCT text, MAX(created_at) as last_used
            FROM nominations
            WHERE user_id = ? AND vote_id != ?
            GROUP BY LOWER(text)
            ORDER BY last_used DESC
            LIMIT 20
        ');
        $stmt->execute([$user_id, $current_vote_id]);
        $past_nominations = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

        jsonResponse(true, $past_nominations);
        break;

    case 'submit_nomination':
        if (!validateInput($input, ['user_id', 'vote_id', 'text'])) {
            jsonResponse(false, null, 'User ID, vote ID, and text are required');
        }

        if (strlen($input['text']) > MAX_NOMINATION_LENGTH) {
            jsonResponse(false, null, 'Nomination text too long');
        }

        $pdo = getDb();

        // Check if user is participant and has reached nomination limit
        $stmt = $pdo->prepare('
            SELECT uv.vote_id, v.max_nominations, COUNT(n.id) as current_count
            FROM user_votes uv
            JOIN votes v ON uv.vote_id = v.id
            LEFT JOIN nominations n ON n.user_id = uv.user_id AND n.vote_id = uv.vote_id
            WHERE uv.user_id = ? AND uv.vote_id = ?
            GROUP BY uv.vote_id, v.max_nominations
        ');
        error_log("DEBUG: Executing query with user_id=" . $input['user_id'] . ", vote_id=" . $input['vote_id']);
        $stmt->execute([$input['user_id'], $input['vote_id']]);
        $user_info = $stmt->fetch(PDO::FETCH_ASSOC);

        error_log("DEBUG: user_info = " . json_encode($user_info));

        if (!$user_info) {
            jsonResponse(false, null, 'User is not a participant in this vote');
        }

        if ($user_info['current_count'] >= $user_info['max_nominations']) {
            jsonResponse(false, null, 'Maximum nominations reached');
        }

        // Check for duplicate nomination
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM nominations WHERE vote_id = ? AND LOWER(text) = LOWER(?)');
        $stmt->execute([$user_info['vote_id'], $input['text']]);
        if ($stmt->fetchColumn() > 0) {
            jsonResponse(false, null, 'This nomination already exists');
        }

        // Insert nomination
        $stmt = $pdo->prepare('INSERT INTO nominations (vote_id, user_id, text) VALUES (?, ?, ?)');
        $stmt->execute([$user_info['vote_id'], $input['user_id'], $input['text']]);
        $nominationId = $pdo->lastInsertId();

        // Only mark as complete if they've reached the maximum
        $newCount = $user_info['current_count'] + 1;
        $autoAdvanced = false;
        if ($newCount >= $user_info['max_nominations']) {
            $stmt = $pdo->prepare('UPDATE user_votes SET has_nominated = TRUE WHERE user_id = ? AND vote_id = ?');
            $stmt->execute([$input['user_id'], $user_info['vote_id']]);

            // Check if all users have completed nominations for this vote
            $stmt = $pdo->prepare('
                SELECT v.phase,
                       COUNT(DISTINCT uv.user_id) as total_users,
                       COUNT(DISTINCT CASE WHEN uv.has_nominated = 1 THEN uv.user_id END) as completed_users
                FROM votes v
                JOIN user_votes uv ON v.id = uv.vote_id
                WHERE v.id = ?
                GROUP BY v.id, v.phase
            ');
            $stmt->execute([$user_info['vote_id']]);
            $voteStatus = $stmt->fetch(PDO::FETCH_ASSOC);

            // Auto-advance if all users have completed nominations
            if ($voteStatus && $voteStatus['phase'] === 'nominating' &&
                $voteStatus['total_users'] === $voteStatus['completed_users'] &&
                $voteStatus['total_users'] > 0) {

                $stmt = $pdo->prepare('UPDATE votes SET phase = "ranking" WHERE id = ?');
                $stmt->execute([$user_info['vote_id']]);
                $autoAdvanced = true;

                // Send email notification
                EmailUtils::notifyVotePhaseAdvanced($user_info['vote_id'], 'ranking');
            }
        }

        $message = 'Nomination submitted successfully';
        if ($autoAdvanced) {
            $message .= ' - All users finished, vote advanced to ranking phase!';
        }

        jsonResponse(true, ['nomination_id' => $nominationId, 'message' => $message]);
        break;

    case 'get_all_nominations':
        $vote_id = $_GET['vote_id'] ?? null;
        if (!$vote_id) {
            jsonResponse(false, null, 'Vote ID is required');
        }

        $pdo = getDb();
        $stmt = $pdo->prepare('SELECT id, text FROM nominations WHERE vote_id = ? ORDER BY created_at');
        $stmt->execute([$vote_id]);
        $nominations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        jsonResponse(true, $nominations);
        break;

    case 'submit_rankings':
        try {
            if (!validateInput($input, ['user_id', 'vote_id', 'rankings'])) {
                jsonResponse(false, null, 'User ID, vote ID and rankings are required');
            }

            if (!is_array($input['rankings'])) {
                jsonResponse(false, null, 'Rankings must be an array');
            }

            $pdo = getDb();
            $vote_id = $input['vote_id'];

            // Clear existing rankings for this user
            $stmt = $pdo->prepare('DELETE FROM rankings WHERE vote_id = ? AND user_id = ?');
            $stmt->execute([$vote_id, $input['user_id']]);

            // Insert new rankings
            $stmt = $pdo->prepare('INSERT INTO rankings (vote_id, user_id, nomination_id, rank) VALUES (?, ?, ?, ?)');
            foreach ($input['rankings'] as $ranking) {
                if (!isset($ranking['nomination_id']) || !isset($ranking['rank'])) {
                    jsonResponse(false, null, 'Invalid ranking data: missing nomination_id or rank');
                }

                $stmt->execute([$vote_id, $input['user_id'], $ranking['nomination_id'], $ranking['rank']]);
            }

            // Update user ranking status
            $stmt = $pdo->prepare('UPDATE user_votes SET has_ranked = TRUE WHERE user_id = ? AND vote_id = ?');
            $stmt->execute([$input['user_id'], $vote_id]);

            // Check if all users have completed rankings for this vote
            $stmt = $pdo->prepare('
                SELECT v.phase,
                       COUNT(DISTINCT uv.user_id) as total_users,
                       COUNT(DISTINCT CASE WHEN uv.has_ranked = 1 THEN uv.user_id END) as completed_users
                FROM votes v
                JOIN user_votes uv ON v.id = uv.vote_id
                WHERE v.id = ?
                GROUP BY v.id, v.phase
            ');
            $stmt->execute([$vote_id]);
            $voteStatus = $stmt->fetch(PDO::FETCH_ASSOC);

            // Auto-advance if all users have completed rankings
            $autoAdvanced = false;
            $message = 'Rankings submitted successfully';

            if ($voteStatus && $voteStatus['phase'] === 'ranking' &&
                $voteStatus['total_users'] === $voteStatus['completed_users'] &&
                $voteStatus['total_users'] > 0) {

                // Check if there are ties that need resolution
                $tieStatus = checkForTies($vote_id);

                if ($tieStatus['has_ties']) {
                    // Set to tie-resolution phase instead of finished
                    $stmt = $pdo->prepare('UPDATE votes SET phase = "tie_resolution" WHERE id = ?');
                    $stmt->execute([$vote_id]);
                    $autoAdvanced = true;
                    $message .= ' - Ties detected, awaiting admin decision on tiebreaking method';
                } else {
                    // No ties, proceed to finished
                    $stmt = $pdo->prepare('UPDATE votes SET phase = "finished" WHERE id = ?');
                    $stmt->execute([$vote_id]);
                    $autoAdvanced = true;
                    $message .= ' - All users finished, vote completed!';

                    // Send results email notification
                    EmailUtils::notifyVoteCompleted($vote_id);
                }
            }

            jsonResponse(true, $message);
        } catch (Exception $e) {
            error_log("Rankings submission error: " . $e->getMessage());
            jsonResponse(false, null, 'Database error: ' . $e->getMessage());
        }
        break;

    case 'get_results':
        $vote_id = $_GET['vote_id'] ?? null;
        if (!$vote_id) {
            jsonResponse(false, null, 'Vote ID is required');
        }

        $pdo = getDb();

        // Check if this vote was completed via runoff
        $stmt = $pdo->prepare('SELECT phase FROM votes WHERE id = ?');
        $stmt->execute([$vote_id]);
        $phase = $stmt->fetchColumn();

        // Check if we have runoff rankings to use instead
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM runoff_rankings WHERE vote_id = ?');
        $stmt->execute([$vote_id]);
        $has_runoff_data = $stmt->fetchColumn() > 0;

        // Check if we have random tie resolution data
        $has_random_resolution = false;
        try {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM random_tie_resolution WHERE vote_id = ?');
            $stmt->execute([$vote_id]);
            $has_random_resolution = $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            // Table doesn't exist yet, which is fine
            $has_random_resolution = false;
        }

        if ($has_runoff_data && $phase === 'finished') {
            // Use runoff results instead of original rankings
            $stmt = $pdo->prepare('SELECT COUNT(DISTINCT nomination_id) FROM runoff_rankings WHERE vote_id = ?');
            $stmt->execute([$vote_id]);
            $total_nominations = $stmt->fetchColumn();

            // Calculate Borda scores from runoff rankings
            $stmt = $pdo->prepare('
                SELECT n.id, n.text as nomination,
                       SUM(? - rr.rank + 1) as score,
                       COUNT(CASE WHEN rr.rank = 1 THEN 1 END) as first_place_votes,
                       COUNT(CASE WHEN rr.rank = 2 THEN 1 END) as second_place_votes,
                       COUNT(CASE WHEN rr.rank = 3 THEN 1 END) as third_place_votes,
                       COUNT(CASE WHEN rr.rank = 4 THEN 1 END) as fourth_place_votes,
                       COUNT(CASE WHEN rr.rank = 5 THEN 1 END) as fifth_place_votes
                FROM nominations n
                LEFT JOIN runoff_rankings rr ON n.id = rr.nomination_id
                WHERE n.vote_id = ? AND rr.vote_id = ?
                GROUP BY n.id, n.text
            ');
            $stmt->execute([$total_nominations, $vote_id, $vote_id]);
            $raw_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($has_random_resolution && $phase === 'finished') {
            // Use random tie resolution order
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM nominations WHERE vote_id = ?');
            $stmt->execute([$vote_id]);
            $total_nominations = $stmt->fetchColumn();

            // Get results ordered by random resolution
            $stmt = $pdo->prepare('
                SELECT n.id, n.text as nomination,
                       SUM(? - r.rank + 1) as score,
                       COUNT(CASE WHEN r.rank = 1 THEN 1 END) as first_place_votes,
                       COUNT(CASE WHEN r.rank = 2 THEN 1 END) as second_place_votes,
                       COUNT(CASE WHEN r.rank = 3 THEN 1 END) as third_place_votes,
                       COUNT(CASE WHEN r.rank = 4 THEN 1 END) as fourth_place_votes,
                       COUNT(CASE WHEN r.rank = 5 THEN 1 END) as fifth_place_votes,
                       rtr.random_rank
                FROM nominations n
                LEFT JOIN rankings r ON n.id = r.nomination_id
                LEFT JOIN random_tie_resolution rtr ON n.vote_id = rtr.vote_id AND n.text = rtr.nomination_text
                WHERE n.vote_id = ?
                GROUP BY n.id, n.text, rtr.random_rank
                ORDER BY rtr.random_rank ASC, SUM(? - r.rank + 1) DESC
            ');
            $stmt->execute([$total_nominations, $vote_id, $total_nominations]);
            $raw_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Use original rankings
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM nominations WHERE vote_id = ?');
            $stmt->execute([$vote_id]);
            $total_nominations = $stmt->fetchColumn();

            // Calculate Borda scores with tiebreaking data
            $stmt = $pdo->prepare('
                SELECT n.id, n.text as nomination,
                       SUM(? - r.rank + 1) as score,
                       COUNT(CASE WHEN r.rank = 1 THEN 1 END) as first_place_votes,
                       COUNT(CASE WHEN r.rank = 2 THEN 1 END) as second_place_votes,
                       COUNT(CASE WHEN r.rank = 3 THEN 1 END) as third_place_votes,
                       COUNT(CASE WHEN r.rank = 4 THEN 1 END) as fourth_place_votes,
                       COUNT(CASE WHEN r.rank = 5 THEN 1 END) as fifth_place_votes
                FROM nominations n
                LEFT JOIN rankings r ON n.id = r.nomination_id
                WHERE n.vote_id = ?
                GROUP BY n.id, n.text
            ');
            $stmt->execute([$total_nominations, $vote_id]);
            $raw_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Apply tiebreaking rules and sort
        $results = applyTiebreaking($raw_results);

        // Detect ties - specifically for winning positions
        $ties_detected = [];
        $current_score = null;
        $current_rank = 1;
        $tied_positions = [];

        foreach ($results as $index => $result) {
            $score = (int)$result['score'];

            if ($current_score === null) {
                $current_score = $score;
                $tied_positions = [$index];
            } elseif ($score === $current_score) {
                $tied_positions[] = $index;
            } else {
                // Score changed, check if we had ties in previous group
                if (count($tied_positions) > 1) {
                    $ties_detected[] = [
                        'rank' => $current_rank,
                        'score' => $current_score,
                        'positions' => $tied_positions,
                        'nominees' => array_map(function($pos) use ($results) {
                            return $results[$pos];
                        }, $tied_positions)
                    ];
                }
                $current_rank += count($tied_positions);
                $current_score = $score;
                $tied_positions = [$index];
            }
        }

        // Check the last group
        if (count($tied_positions) > 1) {
            $ties_detected[] = [
                'rank' => $current_rank,
                'score' => $current_score,
                'positions' => $tied_positions,
                'nominees' => array_map(function($pos) use ($results) {
                    return $results[$pos];
                }, $tied_positions)
            ];
        }

        $has_meaningful_ties = !empty($ties_detected);

        // Add final ranking position and clean up
        foreach ($results as $index => &$result) {
            $result['rank'] = $index + 1;
            $result['score'] = (int)$result['score'];
            // Keep first place votes for display purposes in case of ties
            $result['first_place_votes'] = (int)$result['first_place_votes'];
            // Remove other tiebreaking fields from final output
            unset($result['id'], $result['second_place_votes'], $result['third_place_votes'],
                  $result['fourth_place_votes'], $result['fifth_place_votes']);
        }

        // Determine tiebreaking method used
        $tiebreaking_method_used = '';
        if ($has_runoff_data && $phase === 'finished') {
            $tiebreaking_method_used = 'Runoff vote among tied contenders';
        } elseif ($has_random_resolution && $phase === 'finished') {
            $tiebreaking_method_used = 'Random selection among tied results';
        } elseif ($has_meaningful_ties) {
            $tiebreaking_method_used = 'First place votes, then subsequent ranks, then alphabetical';
        }

        // Add metadata about tiebreaking
        $response_data = [
            'results' => $results,
            'ties_detected' => $ties_detected,
            'has_ties' => $has_meaningful_ties,
            'tiebreaking_applied' => !empty($tiebreaking_method_used),
            'tiebreaking_method' => $tiebreaking_method_used ?: 'No tiebreaking needed',
            'using_runoff_results' => ($has_runoff_data && $phase === 'finished'),
            'using_random_resolution' => ($has_random_resolution && $phase === 'finished'),
            'vote_phase' => $phase
        ];

        jsonResponse(true, $response_data);
        break;

    case 'get_status':
        error_log("DEBUG: get_status called with vote_id: " . ($_GET['vote_id'] ?? 'NULL'));
        $vote_id = $_GET['vote_id'] ?? null;
        if (!$vote_id) {
            error_log("DEBUG: No vote_id provided");
            jsonResponse(false, null, 'Vote ID is required');
        }

        $pdo = getDb();
        $stmt = $pdo->prepare('
            SELECT v.title, v.phase, v.max_nominations, v.nomination_deadline, v.ranking_deadline,
                   COUNT(DISTINCT uv.user_id) as total_users,
                   COUNT(DISTINCT CASE WHEN uv.has_nominated = 1 THEN uv.user_id END) as users_nominated,
                   COUNT(DISTINCT CASE WHEN uv.has_ranked = 1 THEN uv.user_id END) as users_ranked,
                   COUNT(DISTINCT n.id) as total_nominations
            FROM votes v
            LEFT JOIN user_votes uv ON v.id = uv.vote_id
            LEFT JOIN nominations n ON v.id = n.vote_id
            WHERE v.id = ?
        ');
        $stmt->execute([$vote_id]);
        $status = $stmt->fetch(PDO::FETCH_ASSOC);

        error_log("DEBUG: Query result for vote $vote_id: " . json_encode($status));

        jsonResponse(true, $status);
        break;

    case 'get_user_status':
        $user_id = $_GET['user_id'] ?? null;
        if (!$user_id) {
            jsonResponse(false, null, 'User ID is required');
        }

        $pdo = getDb();
        $stmt = $pdo->prepare('
            SELECT u.has_nominated, u.has_ranked, v.max_nominations,
                   COUNT(n.id) as current_nominations
            FROM users u
            JOIN votes v ON u.vote_id = v.id
            LEFT JOIN nominations n ON n.user_id = u.id
            WHERE u.id = ?
        ');
        $stmt->execute([$user_id]);
        $userStatus = $stmt->fetch(PDO::FETCH_ASSOC);

        jsonResponse(true, $userStatus);
        break;

    case 'mark_nomination_complete':
        if (!validateInput($input, ['user_id', 'vote_id'])) {
            jsonResponse(false, null, 'User ID and vote ID are required');
        }

        $pdo = getDb();
        $stmt = $pdo->prepare('UPDATE user_votes SET has_nominated = TRUE WHERE user_id = ? AND vote_id = ?');
        $stmt->execute([$input['user_id'], $input['vote_id']]);

        // Check if all users have completed nominations for this vote
        $stmt = $pdo->prepare('
            SELECT v.phase,
                   COUNT(DISTINCT uv.user_id) as total_users,
                   COUNT(DISTINCT CASE WHEN uv.has_nominated = 1 THEN uv.user_id END) as completed_users
            FROM votes v
            JOIN user_votes uv ON v.id = uv.vote_id
            WHERE v.id = ?
            GROUP BY v.id, v.phase
        ');
        $stmt->execute([$input['vote_id']]);
        $voteStatus = $stmt->fetch(PDO::FETCH_ASSOC);

        // Auto-advance if all users have completed nominations
        if ($voteStatus && $voteStatus['phase'] === 'nominating' &&
            $voteStatus['total_users'] === $voteStatus['completed_users'] &&
            $voteStatus['total_users'] > 0) {

            $stmt = $pdo->prepare('UPDATE votes SET phase = "ranking" WHERE id = ?');
            $stmt->execute([$input['vote_id']]);

            // Send email notification
            EmailUtils::notifyVotePhaseAdvanced($input['vote_id'], 'ranking');

            jsonResponse(true, 'Marked as complete. All users finished - vote advanced to ranking phase!');
        } else {
            jsonResponse(true, 'Marked as complete');
        }
        break;

    case 'resolve_tie':
        if (!validateInput($input, ['vote_id', 'method'])) {
            jsonResponse(false, null, 'Vote ID and tiebreaking method are required');
        }

        $pdo = getDb();
        $vote_id = $input['vote_id'];
        $method = $input['method'];

        try {
            if ($method === 'random') {
                // Get tied nominations and randomly resolve ties
                $tieStatus = checkForTies($vote_id);

                if (!$tieStatus['has_ties']) {
                    jsonResponse(false, null, 'No ties to resolve');
                }

                // Create random_tie_resolution table if it doesn't exist
                $pdo->exec('CREATE TABLE IF NOT EXISTS random_tie_resolution (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    vote_id INTEGER NOT NULL,
                    nomination_text TEXT NOT NULL,
                    original_rank INTEGER NOT NULL,
                    random_rank INTEGER NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (vote_id) REFERENCES votes (id)
                )');

                // Clear any previous random resolutions for this vote
                $stmt = $pdo->prepare('DELETE FROM random_tie_resolution WHERE vote_id = ?');
                $stmt->execute([$vote_id]);

                // Group nominations by score and randomly order tied groups
                $results = $tieStatus['results'];
                $randomized_results = [];
                $current_rank = 1;

                // Process results in groups of tied scores
                $i = 0;
                while ($i < count($results)) {
                    $current_score = $results[$i]['score'];
                    $tied_group = [];

                    // Collect all nominations with the same score
                    while ($i < count($results) && $results[$i]['score'] == $current_score) {
                        $tied_group[] = $results[$i];
                        $i++;
                    }

                    // Randomly shuffle the tied group
                    shuffle($tied_group);

                    // Assign ranks and store random resolution
                    foreach ($tied_group as $nomination) {
                        $stmt = $pdo->prepare('INSERT INTO random_tie_resolution (vote_id, nomination_text, original_rank, random_rank) VALUES (?, ?, ?, ?)');
                        $stmt->execute([$vote_id, $nomination['nomination'], $current_rank, $current_rank]);

                        $nomination['random_rank'] = $current_rank;
                        $randomized_results[] = $nomination;
                        $current_rank++;
                    }
                }

                $stmt = $pdo->prepare('UPDATE votes SET phase = "finished" WHERE id = ?');
                $stmt->execute([$vote_id]);

                EmailUtils::notifyVoteCompleted($vote_id);
                jsonResponse(true, 'Tie resolved randomly - vote completed');

            } elseif ($method === 'automatic') {
                // Use existing automatic tiebreaking and finish vote
                $stmt = $pdo->prepare('UPDATE votes SET phase = "finished" WHERE id = ?');
                $stmt->execute([$vote_id]);

                EmailUtils::notifyVoteCompleted($vote_id);
                jsonResponse(true, 'Tie resolved using automatic method - vote completed');

            } elseif ($method === 'runoff') {
                // Check if runoff is viable (need some non-tied options to eliminate)
                $tieStatus = checkForTies($vote_id);

                // Count total nominations vs tied nominations
                $totalNominations = count($tieStatus['results']);
                $tiedNominations = 0;

                // Count how many nominations are tied at the top score
                if (!empty($tieStatus['results'])) {
                    $topScore = $tieStatus['results'][0]['score'];
                    foreach ($tieStatus['results'] as $result) {
                        if ($result['score'] == $topScore) {
                            $tiedNominations++;
                        } else {
                            break;
                        }
                    }
                }

                if ($tiedNominations >= $totalNominations || $tiedNominations < 2) {
                    // All nominations tied or insufficient tied nominations - runoff not viable
                    jsonResponse(false, null, 'Runoff not viable: ' .
                        ($tiedNominations >= $totalNominations ?
                            'All nominations are tied' :
                            'Need at least 2 tied nominations for runoff'
                        ) . '. Try automatic or random resolution instead.');
                } else {
                    // Runoff is viable - start runoff process
                    $stmt = $pdo->prepare('UPDATE votes SET phase = "runoff" WHERE id = ?');
                    $stmt->execute([$vote_id]);

                    // Send email notification for runoff phase
                    EmailUtils::notifyVotePhaseAdvanced($vote_id, 'runoff');

                    jsonResponse(true, "Runoff initiated with top $tiedNominations tied nominations. Participants will be notified to re-rank.");
                }

            } else {
                jsonResponse(false, null, 'Invalid tiebreaking method');
            }

        } catch (PDOException $e) {
            jsonResponse(false, null, 'Database error: ' . $e->getMessage());
        }
        break;

    case 'get_runoff_nominations':
        $vote_id = $_GET['vote_id'] ?? null;
        if (!$vote_id) {
            jsonResponse(false, null, 'Vote ID is required');
        }

        $pdo = getDb();

        try {
            // Get the tied nominations from the original results
            $tieStatus = checkForTies($vote_id);

            if (!$tieStatus['has_ties']) {
                jsonResponse(true, []); // No ties = no runoff nominations
                break;
            }

            // Get the top tied nominations
            $topScore = $tieStatus['results'][0]['score'];
            $tiedNominations = [];

            foreach ($tieStatus['results'] as $result) {
                if ($result['score'] == $topScore) {
                    // Find the nomination ID for this text
                    $stmt = $pdo->prepare('SELECT id FROM nominations WHERE vote_id = ? AND text = ?');
                    $stmt->execute([$vote_id, $result['nomination']]);
                    $nomId = $stmt->fetchColumn();

                    if ($nomId) {
                        $tiedNominations[] = [
                            'id' => $nomId,
                            'text' => $result['nomination'],
                            'score' => $result['score']
                        ];
                    }
                } else {
                    break; // Stop when we reach non-tied nominations
                }
            }

            jsonResponse(true, $tiedNominations);

        } catch (Exception $e) {
            jsonResponse(false, null, 'Error loading runoff nominations: ' . $e->getMessage());
        }
        break;

    case 'submit_runoff_rankings':
        try {
            if (!validateInput($input, ['user_id', 'vote_id', 'rankings'])) {
                jsonResponse(false, null, 'User ID, vote ID and rankings are required');
            }

            if (!is_array($input['rankings'])) {
                jsonResponse(false, null, 'Rankings must be an array');
            }

            $pdo = getDb();
            $vote_id = $input['vote_id'];

            // Clear existing runoff rankings for this user
            $stmt = $pdo->prepare('DELETE FROM runoff_rankings WHERE vote_id = ? AND user_id = ?');
            $stmt->execute([$vote_id, $input['user_id']]);

            // Insert new runoff rankings
            $stmt = $pdo->prepare('INSERT INTO runoff_rankings (vote_id, user_id, nomination_id, rank) VALUES (?, ?, ?, ?)');
            foreach ($input['rankings'] as $ranking) {
                if (!isset($ranking['nomination_id']) || !isset($ranking['rank'])) {
                    jsonResponse(false, null, 'Invalid ranking data: missing nomination_id or rank');
                }

                $stmt->execute([$vote_id, $input['user_id'], $ranking['nomination_id'], $ranking['rank']]);
            }

            // Update user runoff status
            $stmt = $pdo->prepare('UPDATE user_votes SET has_runoff_ranked = TRUE WHERE user_id = ? AND vote_id = ?');
            $stmt->execute([$input['user_id'], $vote_id]);

            // Check if all users have completed runoff rankings
            $stmt = $pdo->prepare('
                SELECT COUNT(DISTINCT uv.user_id) as total_users,
                       COUNT(DISTINCT CASE WHEN uv.has_runoff_ranked = 1 THEN uv.user_id END) as completed_users
                FROM user_votes uv
                WHERE uv.vote_id = ?
            ');
            $stmt->execute([$vote_id]);
            $runoffStatus = $stmt->fetch(PDO::FETCH_ASSOC);

            $message = 'Runoff rankings submitted successfully';

            // Auto-advance if all users completed runoff
            if ($runoffStatus && $runoffStatus['total_users'] === $runoffStatus['completed_users'] && $runoffStatus['total_users'] > 0) {
                $stmt = $pdo->prepare('UPDATE votes SET phase = "finished" WHERE id = ?');
                $stmt->execute([$vote_id]);
                $message .= ' - All users finished runoff, vote completed!';

                // Send completion email
                EmailUtils::notifyVoteCompleted($vote_id);
            }

            jsonResponse(true, $message);

        } catch (Exception $e) {
            error_log("Runoff rankings submission error: " . $e->getMessage());
            jsonResponse(false, null, 'Database error: ' . $e->getMessage());
        }
        break;

    default:
        jsonResponse(false, null, 'Invalid action');
}
?>