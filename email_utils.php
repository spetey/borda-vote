<?php
// Email utility functions for Borda Vote System
// Generated with Claude Code - 2025-09-29

require_once 'config.php';

class EmailUtils {

    public static function sendEmail($to, $subject, $htmlBody, $textBody = null) {
        // For development/testing - log emails instead of sending
        if (DEBUG) {
            error_log("EMAIL: To: $to, Subject: $subject");
            error_log("EMAIL BODY: " . strip_tags($htmlBody));
            return true; // Simulate success in debug mode
        }

        // Production email using built-in mail() function
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: noreply@stevepetersen.net\r\n";
        $headers .= "Reply-To: noreply@stevepetersen.net\r\n";

        return mail($to, $subject, $htmlBody, $headers);
    }

    public static function notifyVotePhaseAdvanced($voteId, $newPhase) {
        error_log("DEBUG: notifyVotePhaseAdvanced called - voteId: $voteId, newPhase: $newPhase");
        try {
            $pdo = new PDO('sqlite:' . DB_PATH);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Get vote details and participants
            $stmt = $pdo->prepare('
                SELECT v.title, gu.email, gu.display_name
                FROM votes v
                JOIN user_votes uv ON v.id = uv.vote_id
                JOIN global_users gu ON uv.user_id = gu.id
                WHERE v.id = ? AND gu.active = 1
            ');
            $stmt->execute([$voteId]);
            $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($participants)) {
                return false;
            }

            $voteTitle = $participants[0]['title'];
            $phaseText = self::getPhaseDisplayText($newPhase);

            $subject = "Vote Update: $voteTitle - Now in $phaseText";

            $htmlBody = self::generatePhaseAdvancedEmail($voteTitle, $newPhase, $voteId);

            $success = true;
            foreach ($participants as $participant) {
                $personalizedHtml = str_replace('{{PARTICIPANT_NAME}}', $participant['display_name'], $htmlBody);
                $result = self::sendEmail($participant['email'], $subject, $personalizedHtml);
                if (!$result) {
                    $success = false;
                }
            }

            return $success;

        } catch (Exception $e) {
            error_log("Email notification error: " . $e->getMessage());
            return false;
        }
    }

    public static function notifyVoteCompleted($voteId) {
        error_log("DEBUG: notifyVoteCompleted called - voteId: $voteId");
        try {
            $pdo = new PDO('sqlite:' . DB_PATH);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Get vote details, participants, and results
            $stmt = $pdo->prepare('
                SELECT v.title, gu.email, gu.display_name
                FROM votes v
                JOIN user_votes uv ON v.id = uv.vote_id
                JOIN global_users gu ON uv.user_id = gu.id
                WHERE v.id = ? AND gu.active = 1
            ');
            $stmt->execute([$voteId]);
            $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($participants)) {
                return false;
            }

            $voteTitle = $participants[0]['title'];

            // Get results for email
            $results = self::getVoteResults($voteId);

            $subject = "Vote Complete: $voteTitle - Results Available";
            $htmlBody = self::generateResultsEmail($voteTitle, $voteId, $results);

            $success = true;
            foreach ($participants as $participant) {
                $personalizedHtml = str_replace('{{PARTICIPANT_NAME}}', $participant['display_name'], $htmlBody);
                $result = self::sendEmail($participant['email'], $subject, $personalizedHtml);
                if (!$result) {
                    $success = false;
                }
            }

            return $success;

        } catch (Exception $e) {
            error_log("Email notification error: " . $e->getMessage());
            return false;
        }
    }

    private static function getVoteResults($voteId) {
        try {
            $pdo = new PDO('sqlite:' . DB_PATH);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Get total nominations for Borda calculation
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM nominations WHERE vote_id = ?');
            $stmt->execute([$voteId]);
            $total_nominations = $stmt->fetchColumn();

            // Get results (simplified version for email)
            $stmt = $pdo->prepare('
                SELECT n.text as nomination,
                       SUM(? - r.rank + 1) as score
                FROM nominations n
                LEFT JOIN rankings r ON n.id = r.nomination_id
                WHERE n.vote_id = ?
                GROUP BY n.id, n.text
                ORDER BY score DESC
                LIMIT 5
            ');
            $stmt->execute([$total_nominations, $voteId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            return [];
        }
    }

    private static function getPhaseDisplayText($phase) {
        switch($phase) {
            case 'nominating': return 'Nomination Phase';
            case 'ranking': return 'Ranking Phase';
            case 'runoff': return 'Runoff Vote';
            case 'finished': return 'Results Available';
            default: return ucfirst($phase);
        }
    }

    private static function generatePhaseAdvancedEmail($voteTitle, $newPhase, $voteId) {
        $phaseText = self::getPhaseDisplayText($newPhase);
        $actionText = '';
        $voteUrl = 'https://stevepetersen.net/borda/vote.php?id=' . $voteId;

        switch($newPhase) {
            case 'ranking':
                $actionText = 'You can now rank the submitted nominations.';
                break;
            case 'runoff':
                $actionText = 'Due to tied results, a runoff vote is required. Please re-rank the top tied nominations to determine the final winner.';
                break;
            case 'finished':
                $actionText = 'The results are now available to view.';
                break;
        }

        return "
        <html>
        <body style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background: #3498db; color: white; padding: 20px; text-align: center;'>
                <h1>🗳️ Borda Vote Update</h1>
            </div>
            <div style='padding: 20px;'>
                <p>Hi {{PARTICIPANT_NAME}},</p>

                <p>The vote \"<strong>$voteTitle</strong>\" has advanced to the next phase:</p>

                <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <h3 style='margin: 0; color: #2c3e50;'>📋 $phaseText</h3>
                    <p style='margin: 10px 0 0 0;'>$actionText</p>
                </div>

                <p style='text-align: center; margin: 30px 0;'>
                    <a href='$voteUrl' style='background: #3498db; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;'>
                        View Vote
                    </a>
                </p>

                <p style='color: #6c757d; font-size: 14px;'>
                    This is an automated notification from the Borda Vote System.
                </p>
            </div>
        </body>
        </html>";
    }

    private static function generateResultsEmail($voteTitle, $voteId, $results) {
        $voteUrl = 'https://stevepetersen.net/borda/vote.php?id=' . $voteId;

        $resultsHtml = '';
        foreach ($results as $index => $result) {
            $medal = $index === 0 ? '🥇' : ($index === 1 ? '🥈' : ($index === 2 ? '🥉' : ($index + 1) . '.'));
            $resultsHtml .= "<div style='margin: 10px 0; padding: 10px; background: " .
                           ($index === 0 ? '#fffbf0' : '#f8f9fa') . "; border-radius: 5px;'>" .
                           "<strong>$medal {$result['nomination']}</strong> - {$result['score']} points</div>";
        }

        return "
        <html>
        <body style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background: #27ae60; color: white; padding: 20px; text-align: center;'>
                <h1>🏆 Vote Results</h1>
            </div>
            <div style='padding: 20px;'>
                <p>Hi {{PARTICIPANT_NAME}},</p>

                <p>The vote \"<strong>$voteTitle</strong>\" is now complete! Here are the final results:</p>

                <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <h3 style='margin: 0 0 15px 0; color: #2c3e50;'>📊 Final Rankings</h3>
                    $resultsHtml
                </div>

                <p style='text-align: center; margin: 30px 0;'>
                    <a href='$voteUrl' style='background: #27ae60; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;'>
                        View Full Results
                    </a>
                </p>

                <p style='color: #6c757d; font-size: 14px;'>
                    Results calculated using the Borda Count method. This is an automated notification from the Borda Vote System.
                </p>
            </div>
        </body>
        </html>";
    }

    public static function notifyVoteCreated($voteId) {
        error_log("DEBUG: notifyVoteCreated called - voteId: $voteId");
        try {
            $pdo = new PDO('sqlite:' . DB_PATH);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Get vote details and participants
            $stmt = $pdo->prepare('
                SELECT v.title, v.nomination_deadline, v.ranking_deadline, gu.email, gu.display_name
                FROM votes v
                JOIN user_votes uv ON v.id = uv.vote_id
                JOIN global_users gu ON uv.user_id = gu.id
                WHERE v.id = ? AND gu.active = 1
            ');
            $stmt->execute([$voteId]);
            $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($participants)) {
                error_log("No participants found for vote $voteId");
                return false;
            }

            $voteTitle = $participants[0]['title'];
            $nominationDeadline = $participants[0]['nomination_deadline'];
            $rankingDeadline = $participants[0]['ranking_deadline'];

            foreach ($participants as $participant) {
                $subject = "New Vote Created: $voteTitle";

                $htmlBody = "
                    <h2>🗳️ You've been invited to participate in a new vote!</h2>
                    <div style='background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;'>
                        <h3>$voteTitle</h3>
                        <p><strong>Your participation is requested!</strong></p>

                        <h4>📅 Timeline:</h4>
                        <ul>
                        " . ($nominationDeadline ? "<li><strong>Nomination Phase:</strong> Submit your nominations by " . date('M j, Y g:i A', strtotime($nominationDeadline)) . "</li>" : "<li><strong>Nomination Phase:</strong> Open now</li>") . "
                        " . ($rankingDeadline ? "<li><strong>Ranking Phase:</strong> Rank all nominations by " . date('M j, Y g:i A', strtotime($rankingDeadline)) . "</li>" : "<li><strong>Ranking Phase:</strong> After nominations close</li>") . "
                        </ul>

                        <h4>🔗 How to Participate:</h4>
                        <ol>
                            <li>Go to your dashboard at: <a href='https://stevepetersen.net/borda/dashboard.php'>Borda Vote Dashboard</a></li>
                            <li>Look for '$voteTitle' in your available votes</li>
                            <li>Click to participate and submit your nominations</li>
                        </ol>
                    </div>

                    <p>Questions? Contact your vote administrator.</p>
                    <hr>
                    <p style='font-size: 12px; color: #666;'>This is an automated message from the Borda Vote System.</p>
                ";

                $textBody = "You've been invited to participate in: $voteTitle\n\nGo to your dashboard to participate: dashboard.php\n\nLook for '$voteTitle' in your available votes.";

                self::sendEmail($participant['email'], $subject, $htmlBody, $textBody);
            }

            return true;
        } catch (Exception $e) {
            error_log("Error sending vote creation notification: " . $e->getMessage());
            return false;
        }
    }

    public static function notifyNewUser($email, $displayName, $username, $temporaryPassword) {
        error_log("DEBUG: notifyNewUser called - email: $email, username: $username");

        $subject = "Welcome to Borda Vote - Your Account Details";

        $htmlBody = "
        <html>
        <body style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background: #3498db; color: white; padding: 20px; text-align: center;'>
                <h1>🗳️ Welcome to Borda Vote</h1>
            </div>
            <div style='padding: 20px;'>
                <p>Hi $displayName,</p>

                <p>An account has been created for you on the Borda Vote system. Here are your login details:</p>

                <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <h3 style='margin: 0 0 15px 0; color: #2c3e50;'>🔑 Your Login Credentials</h3>
                    <p><strong>Username:</strong> $username</p>
                    <p><strong>Email:</strong> $email</p>
                    <p><strong>Temporary Password:</strong> <code style='background: #e9ecef; padding: 4px 8px; border-radius: 3px; font-family: monospace;'>$temporaryPassword</code></p>
                </div>

                <div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #ffc107;'>
                    <h4 style='margin: 0 0 10px 0; color: #856404;'>⚠️ Important Security Notice</h4>
                    <p style='margin: 0; color: #856404;'>You will be required to change this temporary password when you first log in. Please choose a secure password that you'll remember.</p>
                </div>

                <p style='text-align: center; margin: 30px 0;'>
                    <a href='https://stevepetersen.net/borda/auth.php' style='background: #3498db; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;'>
                        Login Now
                    </a>
                </p>

                <h3 style='color: #2c3e50;'>📝 What is Borda Vote?</h3>
                <p>Borda Vote is a democratic group decision-making system that uses the Borda Count method to help groups make fair collective decisions. You'll be able to:</p>
                <ul>
                    <li>Participate in group votes and nominations</li>
                    <li>Rank options in order of preference</li>
                    <li>View transparent, fair results</li>
                </ul>

                <p style='color: #6c757d; font-size: 14px; margin-top: 30px;'>
                    Questions? Contact your system administrator.<br>
                    This is an automated notification from the Borda Vote System.
                </p>
            </div>
        </body>
        </html>";

        $textBody = "Welcome to Borda Vote!\n\nYour login details:\nUsername: $username\nEmail: $email\nTemporary Password: $temporaryPassword\n\nYou must change this password on first login.\n\nLogin at: https://stevepetersen.net/borda/auth.php";

        return self::sendEmail($email, $subject, $htmlBody, $textBody);
    }
}
?>