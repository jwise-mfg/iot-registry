<?php
/**
 * IOT Registry Configuration File
 *
 * Copy this file to config.php and customize for your environment.
 * This file should not be committed to version control if it contains
 * sensitive information.
 */

// ============================================================================
// ALLOWED HOSTNAMES
// ============================================================================
// List of hostnames that are allowed for this installation.
// Used to prevent Host header injection attacks.

$config = [
    'allowed_hosts' => [
        'localhost',
        'localhost:8000',
        // Add your production domains here:
        // 'iot.yourdomain.com',
        // 'iot-registry.example.com',
    ],

    // ========================================================================
    // RATE LIMITING
    // ========================================================================
    'rate_limit' => [
        'max_requests' => 60,      // Maximum requests per time window
        'time_window' => 60,       // Time window in seconds
    ],

    // ========================================================================
    // CLOUDFLARE CONFIGURATION
    // ========================================================================
    // If you're using CloudFlare, the system will only trust the
    // CF-Connecting-IP header if the request comes from a CloudFlare IP.
    //
    // Update this list periodically from: https://www.cloudflare.com/ips/
    // Last updated: 2024

    'cloudflare_ips' => [
        // CloudFlare IPv4 ranges
        '173.245.48.0/20',
        '103.21.244.0/22',
        '103.22.200.0/22',
        '103.31.4.0/22',
        '141.101.64.0/18',
        '108.162.192.0/18',
        '190.93.240.0/20',
        '188.114.96.0/20',
        '197.234.240.0/22',
        '198.41.128.0/17',
        '162.158.0.0/15',
        '104.16.0.0/13',
        '104.24.0.0/14',
        '172.64.0.0/13',
        '131.0.72.0/22',
    ],

    // ========================================================================
    // TRUSTED PROXY CONFIGURATION
    // ========================================================================
    // If you're behind a load balancer or reverse proxy (other than CloudFlare),
    // add the proxy IP addresses here. The system will only trust
    // X-Forwarded-For headers from these IPs.
    //
    // Example: If you have an nginx reverse proxy at 10.0.0.1

    'trusted_proxies' => [
        // Add your trusted proxy IPs here:
        // '10.0.0.1',
        // '192.168.1.1',
    ],

    // ========================================================================
    // INPUT VALIDATION LIMITS
    // ========================================================================
    'input_limits' => [
        'iotid_min' => 3,
        'iotid_max' => 64,
        'version_max' => 32,
        'hostname_max' => 255,
        'username_max' => 64,
        'arch_max' => 32,
        'post_body_max' => 100000,
    ],
];

// Make config available globally
return $config;
?>
