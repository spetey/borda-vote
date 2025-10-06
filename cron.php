<?php
// Cron job for automatic deadline advancement
// NFS only sends email if there's output - we suppress output unless there's an action

require_once 'config.php';
require_once 'email_utils.php';

try {
    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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
        // Check if we have any nominations
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM nominations WHERE vote_id = ?');
        $stmt->execute([$vote['id']]);
        $nom_count = $stmt->fetchColumn();

        if ($nom_count > 0) {
            // Advance to ranking
            $stmt = $pdo->prepare('UPDATE votes SET phase = "ranking" WHERE id = ?');
            $stmt->execute([$vote['id']]);

            EmailUtils::notifyVotePhaseAdvanced($vote['id'], 'ranking');
            $advanced_votes[] = $vote['title'] . ' (nominating → ranking)';
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
        // Advance to finished
        $stmt = $pdo->prepare('UPDATE votes SET phase = "finished" WHERE id = ?');
        $stmt->execute([$vote['id']]);

        EmailUtils::notifyVoteCompleted($vote['id']);
        $advanced_votes[] = $vote['title'] . ' (ranking → finished)';
    }

    // Only output if something happened
    if (count($advanced_votes) > 0) {
        echo "Cron job advanced " . count($advanced_votes) . " vote(s) at " . date('Y-m-d H:i:s') . ":\n";
        foreach ($advanced_votes as $msg) {
            echo "  - $msg\n";
        }
    }
    // Silent success if nothing to advance - no email sent

} catch (Exception $e) {
    // Always output errors so we get notified
    echo "ERROR in cron job at " . date('Y-m-d H:i:s') . ":\n";
    echo $e->getMessage() . "\n";
}
?>