<?php
// Load configuration
$config = require_once("../../config.php");

// Validate hostname against whitelist or format
function validateAndGetHost($config) {
    $host = $_SERVER['HTTP_HOST'] ?? '';

    // Get allowed hosts from config
    $allowed_hosts = $config['allowed_hosts'];

    // Check whitelist first
    if (in_array($host, $allowed_hosts)) {
        return $host;
    }

    // If not in whitelist, validate format (hostname or hostname:port)
    // This regex validates proper hostname format with optional port
    if (preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*(:[0-9]{1,5})?$/', $host)) {
        return $host;
    }

    // If validation fails, use a default or return error
    die("Invalid host header");
}

$script = file_get_contents('./checkin', true);
$validatedHost = validateAndGetHost($config);
$script = str_replace("<SERVERPATH>", ("https://" . $validatedHost), $script);
header('Content-type: text/plain; charset=utf-8');
header('Content-Disposition: filename="checkin"');
echo $script;
?>