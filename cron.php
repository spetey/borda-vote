<?php
// Cron job for automatic deadline advancement
// NFS only sends email if there's output - we suppress output unless there's an error

// Set up environment to simulate web request BEFORE requiring admin_api.php
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['action'] = 'check_deadline_advance';

// Capture output instead of echoing
ob_start();

require_once 'config.php';
require_once 'admin_api.php';

// Get the output
$output = ob_get_clean();

// Parse the JSON response to check for errors
$result = json_decode($output, true);

// Only output (which triggers email) if there was an error or votes were advanced
if (!$result['success'] || ($result['data']['advanced_count'] ?? 0) > 0) {
    echo $output;
    echo "\nCron job completed at " . date('Y-m-d H:i:s') . "\n";
}

// If successful with no advances, silent success - no email sent
?>