<?php
// Load configuration if not already loaded
if (!isset($config)) {
    $config = require_once(__DIR__ . "/config.php");
}

function getUserIP()
{
    global $config;
    $remote = $_SERVER['REMOTE_ADDR'];

    // Get trusted proxy IP ranges from config
    $cloudflare_ips = $config['cloudflare_ips'];

    // Check if request is from CloudFlare
    $is_cloudflare = false;
    foreach ($cloudflare_ips as $range) {
        if (ip_in_range($remote, $range)) {
            $is_cloudflare = true;
            break;
        }
    }

    // Only trust CloudFlare header if request is actually from CloudFlare
    if ($is_cloudflare && isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
        $cf_ip = $_SERVER["HTTP_CF_CONNECTING_IP"];
        if (filter_var($cf_ip, FILTER_VALIDATE_IP)) {
            return $cf_ip;
        }
    }

    // Get trusted proxies from config
    $trusted_proxies = $config['trusted_proxies'];

    $use_forwarded = false;
    foreach ($trusted_proxies as $proxy_ip) {
        if ($remote === $proxy_ip) {
            $use_forwarded = true;
            break;
        }
    }

    if ($use_forwarded && isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // X-Forwarded-For can contain multiple IPs, take the first one
        $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'];
        $ips = array_map('trim', explode(',', $forwarded));
        $client_ip = $ips[0];
        if (filter_var($client_ip, FILTER_VALIDATE_IP)) {
            return $client_ip;
        }
    }

    // Default to REMOTE_ADDR (most secure)
    return $remote;
}

// Helper function to check if IP is in CIDR range
function ip_in_range($ip, $range) {
    if (strpos($range, '/') === false) {
        return $ip === $range;
    }

    list($subnet, $bits) = explode('/', $range);

    // Convert IPs to long integers
    $ip_long = ip2long($ip);
    $subnet_long = ip2long($subnet);

    if ($ip_long === false || $subnet_long === false) {
        return false;
    }

    // Create mask
    $mask = -1 << (32 - $bits);
    $subnet_long &= $mask;

    return ($ip_long & $mask) === $subnet_long;
}
?>