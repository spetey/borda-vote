<?php
// Authored or modified by Claude - 2025-09-25

require_once 'config.php';
require_once 'auth_api.php'; // For authentication functions

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_dashboard':
        $user = requireAuth();

        try {
            $pdo = getDb();

            // Get user's votes (exclude archived)
            $stmt = $pdo->prepare('
                SELECT v.*, v.id as vote_id, uv.role, uv.has_nominated, uv.has_ranked
                FROM votes v
                JOIN user_votes uv ON v.id = uv.vote_id
                WHERE uv.user_id = ? AND (v.archived IS NULL OR v.archived = 0)
                ORDER BY v.created_at DESC
            ');
            $stmt->execute([$user['id']]);
            $userVotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // No longer showing available votes - invite-only system

            // Calculate stats
            $totalVotes = count($userVotes);
            $pendingActions = 0;
            $completedVotes = 0;

            foreach ($userVotes as $vote) {
                if ($vote['phase'] === 'finished') {
                    $completedVotes++;
                } else {
                    // Check if user has pending actions
                    if ($vote['phase'] === 'nominating' && !$vote['has_nominated']) {
                        $pendingActions++;
                    } elseif ($vote['phase'] === 'ranking' && !$vote['has_ranked']) {
                        $pendingActions++;
                    }
                }
            }

            $stats = [
                'total_votes' => $totalVotes,
                'pending_actions' => $pendingActions,
                'completed_votes' => $completedVotes
            ];

            jsonResponse(true, [
                'user' => $user,
                'user_votes' => $userVotes,
                'stats' => $stats
            ]);

        } catch (PDOException $e) {
            jsonResponse(false, null, 'Database error: ' . $e->getMessage());
        }
        break;

    case 'join_vote':
        $user = requireAuth();
        $voteId = $_GET['vote_id'] ?? null;

        if (!$voteId) {
            jsonResponse(false, null, 'Vote ID is required');
        }

        try {
            $pdo = getDb();

            // Check if vote exists and user isn't already in it
            $stmt = $pdo->prepare('SELECT id FROM votes WHERE id = ?');
            $stmt->execute([$voteId]);
            if (!$stmt->fetch()) {
                jsonResponse(false, null, 'Vote not found');
            }

            $stmt = $pdo->prepare('SELECT id FROM user_votes WHERE user_id = ? AND vote_id = ?');
            $stmt->execute([$user['id'], $voteId]);
            if ($stmt->fetch()) {
                jsonResponse(false, null, 'You are already participating in this vote');
            }

            // Add user to vote
            $stmt = $pdo->prepare('INSERT INTO user_votes (user_id, vote_id, role) VALUES (?, ?, ?)');
            $stmt->execute([$user['id'], $voteId, 'participant']);

            jsonResponse(true, ['message' => 'Successfully joined the vote']);

        } catch (PDOException $e) {
            jsonResponse(false, null, 'Database error: ' . $e->getMessage());
        }
        break;

    case 'get_user_votes':
        $user = requireAuth();

        try {
            $pdo = getDb();

            $stmt = $pdo->prepare('
                SELECT v.*, uv.role, uv.has_nominated, uv.has_ranked,
                       COUNT(DISTINCT uv2.user_id) as total_participants
                FROM votes v
                JOIN user_votes uv ON v.id = uv.vote_id
                LEFT JOIN user_votes uv2 ON v.id = uv2.vote_id
                WHERE uv.user_id = ? AND (v.archived IS NULL OR v.archived = 0)
                GROUP BY v.id
                ORDER BY v.created_at DESC
            ');
            $stmt->execute([$user['id']]);
            $votes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            jsonResponse(true, $votes);

        } catch (PDOException $e) {
            jsonResponse(false, null, 'Database error: ' . $e->getMessage());
        }
        break;

    default:
        jsonResponse(false, null, 'Invalid action');
}
?>