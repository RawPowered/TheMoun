<?php
/**
 * Copy to config.local.php (gitignored) or let install.php generate it.
 * Do not commit secrets.
 */
return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'moun_investor',
        'user' => 'moun_app',
        'pass' => 'change-me',
        'charset' => 'utf8mb4',
    ],
    /** Origin only, no path: https://investors.example.com */
    'PUBLIC_ORIGIN' => 'https://investors.example.com',
    /** URL path prefix where this app is mounted, e.g. /moun or '' */
    'PATH_PREFIX' => '',
    'BRAND_LEGAL_NAME' => 'The Moun',
    'BRAND_SHORT_NAME' => 'Moun',
    'SESSION_NAME' => 'MOUNSESS',
    'SESSION_IDLE_SECONDS' => 1800,
    /** Absolute max session wall time (seconds) */
    'SESSION_MAX_LIFETIME' => 86400,
    /** ip-api.com style lookups; set false to skip external calls */
    'GEOIP_ENABLED' => true,
];
