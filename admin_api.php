<?php
// Authored or modified by Claude - 2025-09-25

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
        jsonResponse(false, null, 'Database connection failed: ' . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        jsonResponse(false, null, 'Invalid JSON input');
    }

    $action = $input['action'] ?? '';

    switch ($action) {
        case 'create_vote':
            // Require admin authentication
            require_once 'auth_api.php';
            requireAdmin();

            if (!isset($input['title']) || !isset($input['participant_user_ids']) || !is_array($input['participant_user_ids'])) {
                jsonResponse(false, null, 'Title and participant user IDs are required');
            }

            if (count($input['participant_user_ids']) === 0) {
                jsonResponse(false, null, 'At least one participant must be selected');
            }

            $pdo = getDb();

            try {
                // Insert vote
                $stmt = $pdo->prepare('INSERT INTO votes (title, max_nominations_per_user, nomination_deadline, ranking_deadline) VALUES (?, ?, ?, ?)');
                $stmt->execute([
                    $input['title'],
                    $input['max_nominations'] ?? 2,
                    $input['nomination_deadline'] ?? null,
                    $input['ranking_deadline'] ?? null
                ]);

                $voteId = $pdo->lastInsertId();

                // Add selected users to this vote
                $stmt = $pdo->prepare('INSERT INTO user_votes (user_id, vote_id, role) VALUES (?, ?, ?)');
                $participantCount = 0;

                foreach ($input['participant_user_ids'] as $userId) {
                    // Verify user exists
                    $checkStmt = $pdo->prepare('SELECT id FROM global_users WHERE id = ? AND active = 1');
                    $checkStmt->execute([$userId]);
                    if ($checkStmt->fetch()) {
                        $stmt->execute([$userId, $voteId, 'participant']);
                        $participantCount++;
                    }
                }

                if ($participantCount === 0) {
                    // Clean up the vote if no valid participants
                    $pdo->prepare('DELETE FROM votes WHERE id = ?')->execute([$voteId]);
                    jsonResponse(false, null, 'No valid participants found');
                }

                jsonResponse(true, [
                    'vote_id' => $voteId,
                    'participant_count' => $participantCount,
                    'message' => 'Vote created successfully'
                ]);

            } catch (PDOException $e) {
                jsonResponse(false, null, 'Database error: ' . $e->getMessage());
            }
            break;

        case 'advance_phase':
            if (!isset($input['vote_id'])) {
                jsonResponse(false, null, 'Vote ID is required');
            }

            $pdo = getDb();

            try {
                $stmt = $pdo->prepare('SELECT phase FROM votes WHERE id = ?');
                $stmt->execute([$input['vote_id']]);
                $currentPhase = $stmt->fetchColumn();

                if (!$currentPhase) {
                    jsonResponse(false, null, 'Vote not found');
                }

                $nextPhase = match($currentPhase) {
                    'nominating' => 'ranking',
                    'ranking' => 'finished',
                    default => null
                };

                if ($nextPhase) {
                    $stmt = $pdo->prepare('UPDATE votes SET phase = ? WHERE id = ?');
                    $stmt->execute([$nextPhase, $input['vote_id']]);
                    jsonResponse(true, ['message' => "Phase advanced from $currentPhase to $nextPhase"]);
                } else {
                    jsonResponse(false, null, 'Cannot advance phase further');
                }
            } catch (PDOException $e) {
                jsonResponse(false, null, 'Database error: ' . $e->getMessage());
            }
            break;

        case 'delete_vote':
            if (!isset($input['vote_id'])) {
                jsonResponse(false, null, 'Vote ID is required');
            }

            $pdo = getDb();

            try {
                // Delete in correct order due to foreign keys
                $stmt = $pdo->prepare('DELETE FROM rankings WHERE vote_id = ?');
                $stmt->execute([$input['vote_id']]);

                $stmt = $pdo->prepare('DELETE FROM nominations WHERE vote_id = ?');
                $stmt->execute([$input['vote_id']]);

                $stmt = $pdo->prepare('DELETE FROM users WHERE vote_id = ?');
                $stmt->execute([$input['vote_id']]);

                $stmt = $pdo->prepare('DELETE FROM votes WHERE id = ?');
                $stmt->execute([$input['vote_id']]);

                jsonResponse(true, ['message' => 'Vote deleted successfully']);
            } catch (PDOException $e) {
                jsonResponse(false, null, 'Database error: ' . $e->getMessage());
            }
            break;

        default:
            jsonResponse(false, null, 'Invalid action');
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    if ($action === 'list_votes') {
        $pdo = getDb();

        try {
            $stmt = $pdo->query('
                SELECT v.*,
                       COUNT(DISTINCT uv.user_id) as total_users,
                       COUNT(DISTINCT n.id) as total_nominations,
                       COUNT(DISTINCT CASE WHEN uv.has_nominated = 1 THEN uv.user_id END) as users_nominated,
                       COUNT(DISTINCT CASE WHEN uv.has_ranked = 1 THEN uv.user_id END) as users_ranked
                FROM votes v
                LEFT JOIN user_votes uv ON v.id = uv.vote_id
                LEFT JOIN nominations n ON v.id = n.vote_id
                GROUP BY v.id
                ORDER BY v.created_at DESC
            ');
            $votes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            jsonResponse(true, $votes);
        } catch (PDOException $e) {
            jsonResponse(false, null, 'Database error: ' . $e->getMessage());
        }
    } else if ($action === 'check_auto_advance') {
        $pdo = getDb();

        try {
            // Find votes in nomination phase where all users have completed nominations
            $stmt = $pdo->query('
                SELECT v.id, v.title,
                       COUNT(DISTINCT uv.user_id) as total_users,
                       COUNT(DISTINCT CASE WHEN uv.has_nominated = 1 THEN uv.user_id END) as completed_users
                FROM votes v
                JOIN user_votes uv ON v.id = uv.vote_id
                WHERE v.phase = "nominating"
                GROUP BY v.id
                HAVING total_users = completed_users AND total_users > 0
            ');

            $votesToAdvance = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($votesToAdvance as $vote) {
                $stmt = $pdo->prepare('UPDATE votes SET phase = "ranking" WHERE id = ?');
                $stmt->execute([$vote['id']]);
            }

            // Similar check for ranking phase
            $stmt = $pdo->query('
                SELECT v.id, v.title,
                       COUNT(DISTINCT uv.user_id) as total_users,
                       COUNT(DISTINCT CASE WHEN uv.has_ranked = 1 THEN uv.user_id END) as completed_users
                FROM votes v
                JOIN user_votes uv ON v.id = uv.vote_id
                WHERE v.phase = "ranking"
                GROUP BY v.id
                HAVING total_users = completed_users AND total_users > 0
            ');

            $rankingVotesToAdvance = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rankingVotesToAdvance as $vote) {
                $stmt = $pdo->prepare('UPDATE votes SET phase = "finished" WHERE id = ?');
                $stmt->execute([$vote['id']]);
            }

            $totalAdvanced = count($votesToAdvance) + count($rankingVotesToAdvance);
            jsonResponse(true, ['advanced_count' => $totalAdvanced, 'votes' => array_merge($votesToAdvance, $rankingVotesToAdvance)]);
        } catch (PDOException $e) {
            jsonResponse(false, null, 'Database error: ' . $e->getMessage());
        }
    } else if ($action === 'get_users') {
        try {
            // Require admin authentication
            require_once 'auth_api.php';
            requireAdmin();

            $pdo = getDb();

            $stmt = $pdo->query('
                SELECT id, username, email, display_name, role, created_at, last_login, active
                FROM global_users
                WHERE active = 1
                ORDER BY display_name
            ');
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            jsonResponse(true, $users);
        } catch (Exception $e) {
            jsonResponse(false, null, 'Error: ' . $e->getMessage());
        }
    } else if ($action === 'get_passwords') {
        $vote_id = $_GET['vote_id'] ?? null;
        if (!$vote_id) {
            jsonResponse(false, null, 'Vote ID is required');
        }

        $pdo = getDb();

        try {
            // Note: This is not secure in production - passwords should not be retrievable
            // For development/testing purposes only
            $stmt = $pdo->prepare('
                SELECT u.email, u.password_hash, v.title
                FROM users u
                JOIN votes v ON u.vote_id = v.id
                WHERE u.vote_id = ?
            ');
            $stmt->execute([$vote_id]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // In a real app, you wouldn't be able to retrieve original passwords
            // This is a development workaround
            $result = [
                'vote_title' => $users[0]['title'] ?? 'Unknown Vote',
                'message' => 'Original passwords cannot be retrieved (they are hashed). You would need to reset passwords or check your original notes.',
                'users' => array_map(function($user) {
                    return ['email' => $user['email'], 'password_hash' => substr($user['password_hash'], 0, 20) . '...'];
                }, $users)
            ];

            jsonResponse(true, $result);
        } catch (PDOException $e) {
            jsonResponse(false, null, 'Database error: ' . $e->getMessage());
        }
    } else {
        jsonResponse(false, null, 'Invalid action');
    }
} else {
    jsonResponse(false, null, 'Method not allowed');
}
?>