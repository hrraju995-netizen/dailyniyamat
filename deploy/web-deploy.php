<?php
/**
 * Daily Niyamat - Web-based Deployment Runner
 * Access this via: https://yourdomain.com/deploy.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');
set_time_limit(300);

echo "<pre style='background:#111; color:#0f0; padding:20px; font-family:monospace; line-height:1.5;'>";
echo "=== Starting Daily Niyamat Web Deployment ===\n\n";

$home = dirname(dirname(__DIR__)); // /home/dailyniy
$repo = $home . '/repositories/dailyniyamat';
$script = $repo . '/deploy/cpanel-deploy.sh';

if (function_exists('shell_exec')) {
    echo "Running cpanel-deploy.sh...\n";
    $output = shell_exec("bash " . escapeshellarg($script) . " 2>&1");
    echo htmlspecialchars($output ?: "No output");
} else {
    echo "Notice: shell_exec is disabled. Executing via PHP...\n";
}

echo "\n\n=== Deployment Finished ===";
echo "</pre>";
