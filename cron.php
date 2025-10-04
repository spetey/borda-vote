<?php
// Cron job for automatic deadline advancement
// Add to crontab: 0 */2 * * * php /home/public/borda/cron.php

// Set up environment to simulate web request BEFORE requiring admin_api.php
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['action'] = 'check_deadline_advance';

require_once 'config.php';
require_once 'admin_api.php';

echo "Cron job completed at " . date('Y-m-d H:i:s') . "\n";
?>