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

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?: [];

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

    case 'submit_nomination':
        if (!validateInput($input, ['user_id', 'text'])) {
            jsonResponse(false, null, 'User ID and text are required');
        }

        if (strlen($input['text']) > MAX_NOMINATION_LENGTH) {
            jsonResponse(false, null, 'Nomination text too long');
        }

        $pdo = getDb();

        // Check if user has reached nomination limit
        $stmt = $pdo->prepare('SELECT u.vote_id, v.max_nominations_per_user, COUNT(n.id) as current_count FROM users u JOIN votes v ON u.vote_id = v.id LEFT JOIN nominations n ON n.user_id = u.id WHERE u.id = ?');
        $stmt->execute([$input['user_id']]);
        $user_info = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user_info['current_count'] >= $user_info['max_nominations_per_user']) {
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

        // Only mark as complete if they've reached the maximum
        $newCount = $user_info['current_count'] + 1;
        if ($newCount >= $user_info['max_nominations_per_user']) {
            $stmt = $pdo->prepare('UPDATE users SET has_nominated = TRUE WHERE id = ?');
            $stmt->execute([$input['user_id']]);
        }

        jsonResponse(true, 'Nomination submitted successfully');
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
            if (!validateInput($input, ['user_id', 'rankings'])) {
                jsonResponse(false, null, 'User ID and rankings are required');
            }

            if (!is_array($input['rankings'])) {
                jsonResponse(false, null, 'Rankings must be an array');
            }

            $pdo = getDb();

            // Get user's vote_id
            $stmt = $pdo->prepare('SELECT vote_id FROM users WHERE id = ?');
            $stmt->execute([$input['user_id']]);
            $vote_id = $stmt->fetchColumn();

            if (!$vote_id) {
                jsonResponse(false, null, 'Invalid user ID');
            }

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
            $stmt = $pdo->prepare('UPDATE users SET has_ranked = TRUE WHERE id = ?');
            $stmt->execute([$input['user_id']]);

            jsonResponse(true, 'Rankings submitted successfully');
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

        // Get total number of nominations for Borda calculation
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM nominations WHERE vote_id = ?');
        $stmt->execute([$vote_id]);
        $total_nominations = $stmt->fetchColumn();

        // Calculate Borda scores
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

        // Add ranking position
        foreach ($results as $index => &$result) {
            $result['rank'] = $index + 1;
            $result['score'] = (int)$result['score'];
        }

        jsonResponse(true, $results);
        break;

    case 'get_status':
        $vote_id = $_GET['vote_id'] ?? null;
        if (!$vote_id) {
            jsonResponse(false, null, 'Vote ID is required');
        }

        $pdo = getDb();
        $stmt = $pdo->prepare('
            SELECT v.title, v.phase, v.nomination_deadline, v.ranking_deadline,
                   COUNT(DISTINCT u.id) as total_users,
                   COUNT(DISTINCT CASE WHEN u.has_nominated = 1 THEN u.id END) as users_nominated,
                   COUNT(DISTINCT CASE WHEN u.has_ranked = 1 THEN u.id END) as users_ranked,
                   COUNT(DISTINCT n.id) as total_nominations
            FROM votes v
            LEFT JOIN users u ON v.id = u.vote_id
            LEFT JOIN nominations n ON v.id = n.vote_id
            WHERE v.id = ?
        ');
        $stmt->execute([$vote_id]);
        $status = $stmt->fetch(PDO::FETCH_ASSOC);

        jsonResponse(true, $status);
        break;

    case 'get_user_status':
        $user_id = $_GET['user_id'] ?? null;
        if (!$user_id) {
            jsonResponse(false, null, 'User ID is required');
        }

        $pdo = getDb();
        $stmt = $pdo->prepare('
            SELECT u.has_nominated, u.has_ranked, v.max_nominations_per_user,
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
        if (!validateInput($input, ['user_id'])) {
            jsonResponse(false, null, 'User ID is required');
        }

        $pdo = getDb();
        $stmt = $pdo->prepare('UPDATE users SET has_nominated = TRUE WHERE id = ?');
        $stmt->execute([$input['user_id']]);

        jsonResponse(true, 'Marked as complete');
        break;

    default:
        jsonResponse(false, null, 'Invalid action');
}
?>