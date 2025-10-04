<?php
// Cron job for automatic deadline advancement
// Add to crontab: 0 */2 * * * php /home/public/borda/cron.php

require_once 'config.php';
require_once 'admin_api.php';

// Simulate a GET request to check deadline advance
$_GET['action'] = 'check_deadline_advance';
$_SERVER['REQUEST_METHOD'] = 'GET';

// The admin_api.php will handle the deadline checking
// Output will be logged if needed

echo "Cron job completed at " . date('Y-m-d H:i:s') . "\n";
?>