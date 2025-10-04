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
        case 'create_user':
            // Require admin authentication
            require_once 'auth_api.php';
            requireAdmin();

            if (!isset($input['username']) || !isset($input['email']) || !isset($input['display_name']) || !isset($input['temp_password'])) {
                jsonResponse(false, null, 'All fields are required');
            }

            $username = trim($input['username']);
            $email = trim($input['email']);
            $displayName = trim($input['display_name']);
            $role = $input['role'] ?? 'user';
            $tempPassword = $input['temp_password'];

            // Validation
            if (strlen($tempPassword) < 6) {
                jsonResponse(false, null, 'Password must be at least 6 characters');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                jsonResponse(false, null, 'Invalid email address');
            }

            if (!in_array($role, ['user', 'admin'])) {
                jsonResponse(false, null, 'Invalid role');
            }

            $pdo = getDb();

            try {
                // Check if username or email already exists
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM global_users WHERE username = ? OR email = ?');
                $stmt->execute([$username, $email]);
                if ($stmt->fetchColumn() > 0) {
                    jsonResponse(false, null, 'Username or email already exists');
                }

                // Add must_change_password column if it doesn't exist
                try {
                    $pdo->exec('ALTER TABLE global_users ADD COLUMN must_change_password INTEGER DEFAULT 0');
                } catch (PDOException $e) {
                    // Column might already exist, which is fine
                }

                // Create user with temporary password
                $passwordHash = password_hash($tempPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('
                    INSERT INTO global_users (username, email, display_name, password_hash, role, must_change_password)
                    VALUES (?, ?, ?, ?, ?, 1)
                ');
                $stmt->execute([$username, $email, $displayName, $passwordHash, $role]);

                // Send welcome email with temporary password
                EmailUtils::notifyNewUser($email, $displayName, $username, $tempPassword);

                jsonResponse(true, [
                    'user_id' => $pdo->lastInsertId(),
                    'message' => 'User created successfully'
                ]);

            } catch (PDOException $e) {
                jsonResponse(false, null, 'Database error: ' . $e->getMessage());
            }
            break;

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

                // Send email notification to participants
                EmailUtils::notifyVoteCreated($voteId);

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

                    // Send email notification for manual phase advancement
                    if ($nextPhase === 'finished') {
                        EmailUtils::notifyVoteCompleted($input['vote_id']);
                    } else {
                        EmailUtils::notifyVotePhaseAdvanced($input['vote_id'], $nextPhase);
                    }

                    jsonResponse(true, ['message' => "Phase advanced from $currentPhase to $nextPhase"]);
                } else {
                    jsonResponse(false, null, 'Cannot advance phase further');
                }
            } catch (PDOException $e) {
                jsonResponse(false, null, 'Database error: ' . $e->getMessage());
            }
            break;

        case 'archive_vote':
            if (!isset($input['vote_id'])) {
                jsonResponse(false, null, 'Vote ID is required');
            }

            $pdo = getDb();

            try {
                // Add archived column if it doesn't exist
                $pdo->exec('ALTER TABLE votes ADD COLUMN archived INTEGER DEFAULT 0');
            } catch (PDOException $e) {
                // Column might already exist, which is fine
            }

            try {
                // Archive the vote instead of deleting
                $stmt = $pdo->prepare('UPDATE votes SET archived = 1 WHERE id = ?');
                $stmt->execute([$input['vote_id']]);

                jsonResponse(true, ['message' => 'Vote archived successfully (can be restored later)']);
            } catch (PDOException $e) {
                jsonResponse(false, null, 'Database error: ' . $e->getMessage());
            }
            break;

        case 'restore_vote':
            if (!isset($input['vote_id'])) {
                jsonResponse(false, null, 'Vote ID is required');
            }

            $pdo = getDb();

            try {
                // Restore archived vote
                $stmt = $pdo->prepare('UPDATE votes SET archived = 0 WHERE id = ?');
                $stmt->execute([$input['vote_id']]);

                jsonResponse(true, ['message' => 'Vote restored successfully']);
            } catch (PDOException $e) {
                jsonResponse(false, null, 'Database error: ' . $e->getMessage());
            }
            break;

        case 'test_email':
            if (!isset($input['vote_id']) || !isset($input['email_type'])) {
                jsonResponse(false, null, 'Vote ID and email type are required');
            }

            $emailType = $input['email_type'];
            $voteId = $input['vote_id'];

            try {
                $success = false;

                if ($emailType === 'phase_advance') {
                    $phase = $input['phase'] ?? 'ranking';
                    $success = EmailUtils::notifyVotePhaseAdvanced($voteId, $phase);
                } elseif ($emailType === 'results') {
                    $success = EmailUtils::notifyVoteCompleted($voteId);
                }

                if ($success || DEBUG) {
                    jsonResponse(true, ['message' => 'Test email sent successfully (check logs in debug mode)']);
                } else {
                    jsonResponse(false, null, 'Failed to send test email');
                }
            } catch (Exception $e) {
                jsonResponse(false, null, 'Email test error: ' . $e->getMessage());
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
            // Add archived column if it doesn't exist
            try {
                $pdo->exec('ALTER TABLE votes ADD COLUMN archived INTEGER DEFAULT 0');
            } catch (PDOException $e) {
                // Column might already exist, which is fine
            }

            $include_archived = $_GET['include_archived'] ?? false;
            $whereClause = $include_archived ? '' : 'WHERE (v.archived IS NULL OR v.archived = 0)';

            $stmt = $pdo->query("
                SELECT v.*,
                       COUNT(DISTINCT uv.user_id) as total_users,
                       COUNT(DISTINCT n.id) as total_nominations,
                       COUNT(DISTINCT CASE WHEN uv.has_nominated = 1 THEN uv.user_id END) as users_nominated,
                       COUNT(DISTINCT CASE WHEN uv.has_ranked = 1 THEN uv.user_id END) as users_ranked
                FROM votes v
                LEFT JOIN user_votes uv ON v.id = uv.vote_id
                LEFT JOIN nominations n ON v.id = n.vote_id
                $whereClause
                GROUP BY v.id
                ORDER BY v.created_at DESC
            ");
            $votes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            jsonResponse(true, $votes);
        } catch (PDOException $e) {
            jsonResponse(false, null, 'Database error: ' . $e->getMessage());
        }
    } else if ($action === 'check_deadline_advance') {
        $pdo = getDb();

        try {
            $advanced_votes = [];

            // Check for votes in nomination phase past deadline
            $stmt = $pdo->prepare('
                SELECT v.id, v.title, v.nomination_deadline
                FROM votes v
                WHERE v.phase = "nominating"
                AND v.nomination_deadline IS NOT NULL
                AND datetime(v.nomination_deadline) <= datetime("now")
            ');
            $stmt->execute();
            $nomination_expired = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($nomination_expired as $vote) {
                // Check if we have any nominations before advancing
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM nominations WHERE vote_id = ?');
                $stmt->execute([$vote['id']]);
                $nomination_count = $stmt->fetchColumn();

                if ($nomination_count > 0) {
                    $stmt = $pdo->prepare('UPDATE votes SET phase = "ranking" WHERE id = ?');
                    $stmt->execute([$vote['id']]);

                    // Send notification
                    EmailUtils::notifyVotePhaseAdvanced($vote['id'], 'ranking');

                    $advanced_votes[] = [
                        'vote_id' => $vote['id'],
                        'title' => $vote['title'],
                        'from' => 'nominating',
                        'to' => 'ranking',
                        'reason' => 'Nomination deadline reached'
                    ];
                }
            }

            // Check for votes in ranking phase past deadline
            $stmt = $pdo->prepare('
                SELECT v.id, v.title, v.ranking_deadline
                FROM votes v
                WHERE v.phase = "ranking"
                AND v.ranking_deadline IS NOT NULL
                AND datetime(v.ranking_deadline) <= datetime("now")
            ');
            $stmt->execute();
            $ranking_expired = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($ranking_expired as $vote) {
                // Check if we have any rankings before advancing
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM rankings WHERE vote_id = ?');
                $stmt->execute([$vote['id']]);
                $ranking_count = $stmt->fetchColumn();

                if ($ranking_count > 0) {
                    // Check for ties first
                    require_once 'api.php'; // Need checkForTies function
                    $tieStatus = checkForTies($vote['id']);

                    if ($tieStatus['has_ties']) {
                        // Advance to tie resolution phase
                        $stmt = $pdo->prepare('UPDATE votes SET phase = "tie_resolution" WHERE id = ?');
                        $stmt->execute([$vote['id']]);

                        $advanced_votes[] = [
                            'vote_id' => $vote['id'],
                            'title' => $vote['title'],
                            'from' => 'ranking',
                            'to' => 'tie_resolution',
                            'reason' => 'Ranking deadline reached with ties detected'
                        ];
                    } else {
                        // No ties, finish the vote
                        $stmt = $pdo->prepare('UPDATE votes SET phase = "finished" WHERE id = ?');
                        $stmt->execute([$vote['id']]);

                        // Send completion notification
                        EmailUtils::notifyVoteCompleted($vote['id']);

                        $advanced_votes[] = [
                            'vote_id' => $vote['id'],
                            'title' => $vote['title'],
                            'from' => 'ranking',
                            'to' => 'finished',
                            'reason' => 'Ranking deadline reached'
                        ];
                    }
                }
            }

            jsonResponse(true, [
                'advanced_count' => count($advanced_votes),
                'advanced_votes' => $advanced_votes,
                'message' => count($advanced_votes) > 0 ?
                    'Advanced ' . count($advanced_votes) . ' votes based on deadlines' :
                    'No votes needed deadline advancement'
            ]);

        } catch (Exception $e) {
            jsonResponse(false, null, 'Error: ' . $e->getMessage());
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

                // Send email notification for phase advancement
                EmailUtils::notifyVotePhaseAdvanced($vote['id'], 'ranking');
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

                // Send email notification for vote completion
                EmailUtils::notifyVoteCompleted($vote['id']);
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
    } else if ($action === 'get_vote_participants') {
        $vote_id = $_GET['vote_id'] ?? null;
        if (!$vote_id) {
            jsonResponse(false, null, 'Vote ID is required');
        }

        $pdo = getDb();

        try {
            // Get vote participants from user_votes table (new system)
            $stmt = $pdo->prepare('
                SELECT gu.id, gu.username, gu.email, gu.display_name, gu.role,
                       uv.has_nominated, uv.has_ranked, uv.has_runoff_ranked, v.title
                FROM user_votes uv
                JOIN global_users gu ON uv.user_id = gu.id
                JOIN votes v ON uv.vote_id = v.id
                WHERE uv.vote_id = ?
                ORDER BY gu.display_name
            ');
            $stmt->execute([$vote_id]);
            $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($participants)) {
                jsonResponse(false, null, 'No participants found for this vote');
            }

            $result = [
                'vote_title' => $participants[0]['title'],
                'participants' => array_map(function($user) {
                    return [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'display_name' => $user['display_name'],
                        'role' => $user['role'],
                        'has_nominated' => (bool)$user['has_nominated'],
                        'has_ranked' => (bool)$user['has_ranked'],
                        'has_runoff_ranked' => (bool)$user['has_runoff_ranked']
                    ];
                }, $participants)
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