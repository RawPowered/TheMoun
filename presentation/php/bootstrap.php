<?php

declare(strict_types=1);

/**
 * Single entry for path resolution (public web root = dirname of /php).
 */
function moun_root_path(): string
{
    return dirname(__DIR__);
}

function moun_php_path(): string
{
    return __DIR__;
}

function moun_config_path(): string
{
    return moun_php_path() . '/config.local.php';
}

/** @return array<string,mixed> */
function moun_config(): array
{
    static $cfg;
    if ($cfg !== null) {
        return $cfg;
    }
    $path = moun_config_path();
    if (!is_readable($path)) {
        throw new RuntimeException('Missing php/config.local.php — run install.php');
    }
    $loaded = require $path;
    if (!is_array($loaded)) {
        throw new RuntimeException('Invalid config.local.php');
    }
    $cfg = $loaded;
    return $cfg;
}

function moun_installed_marker_path(): string
{
    return moun_php_path() . '/.installed';
}

function moun_is_installed(): bool
{
    return is_file(moun_installed_marker_path()) && is_readable(moun_config_path());
}

/** Base path for URLs (leading slash, no trailing slash, or empty). */
function moun_path_prefix(): string
{
    $p = (string) (moun_config()['PATH_PREFIX'] ?? '');
    $p = '/' . trim($p, '/');
    return $p === '/' ? '' : $p;
}

/** Full public URL to a path, e.g. /login.php */
function moun_url(string $path = '/'): string
{
    $origin = rtrim((string) moun_config()['PUBLIC_ORIGIN'], '/');
    $prefix = moun_path_prefix();
    $path = '/' . ltrim($path, '/');
    if ($prefix !== '' && str_starts_with($path, $prefix . '/')) {
        // already prefixed
    } elseif ($prefix !== '') {
        $path = $prefix . $path;
    }
    return $origin . $path;
}

function moun_redirect(string $path, int $code = 302): never
{
    header('Location: ' . moun_url($path), true, $code);
    exit;
}

function moun_client_ip(): string
{
    $cfg = moun_config();
    $trust = (bool) ($cfg['TRUST_FORWARDED_FOR'] ?? false);
    if ($trust && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($parts[0]);
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
    }
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

function moun_user_agent(): string
{
    $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
    return mb_substr($ua, 0, 512);
}

/** Parent of web root (project root containing deploy/). */
function moun_repo_root_path(): string
{
    return dirname(moun_root_path());
}

/**
 * Path to Sync SQL (deploy/sync.sql). Tries repo root, then public/deploy/sync.sql.
 */
function moun_sync_sql_path(): string
{
    $candidates = [
        moun_repo_root_path() . DIRECTORY_SEPARATOR . 'deploy' . DIRECTORY_SEPARATOR . 'sync.sql',
        moun_root_path() . DIRECTORY_SEPARATOR . 'deploy' . DIRECTORY_SEPARATOR . 'sync.sql',
    ];
    foreach ($candidates as $p) {
        if (is_readable($p)) {
            return $p;
        }
    }
    return $candidates[0];
}

require_once __DIR__ . '/sql_split.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/geoip.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/security_headers.php';
require_once __DIR__ . '/views.php';
