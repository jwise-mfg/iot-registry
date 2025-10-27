<?php
/**
 * IOT Registry Configuration File - Example Template
 *
 * Copy this file to config.php and customize for your environment.
 */

$config = [
    'allowed_hosts' => [
        'localhost',
        'localhost:8000',
        'iot.yourdomain.com',
    ],

    'rate_limit' => [
        'max_requests' => 60,
        'time_window' => 60,
    ],

    'cloudflare_ips' => [
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

    'trusted_proxies' => [
        // '10.0.0.1',
    ],

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

return $config;
?>
