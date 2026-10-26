<?php

declare(strict_types=1);

function moun_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        moun_session_touch_guard();
        return;
    }
    $cfg = moun_config();
    $name = (string) ($cfg['SESSION_NAME'] ?? 'MOUNSESS');
    session_name($name);
    $secure = str_starts_with((string) $cfg['PUBLIC_ORIGIN'], 'https://');
    $prefix = moun_path_prefix();
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $prefix === '' ? '/' : $prefix . '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
    moun_session_touch_guard();
}

function moun_session_touch_guard(): void
{
    $cfg = moun_config();
    $idle = (int) ($cfg['SESSION_IDLE_SECONDS'] ?? 1800);
    $maxLife = (int) ($cfg['SESSION_MAX_LIFETIME'] ?? 86400);
    $now = time();
    if (!empty($_SESSION['_moun_created'])) {
        if ($now - (int) $_SESSION['_moun_created'] > $maxLife) {
            $_SESSION = [];
            session_destroy();
            session_start();
            $_SESSION['_moun_created'] = $now;
            $_SESSION['_moun_last'] = $now;
            return;
        }
    } else {
        $_SESSION['_moun_created'] = $now;
    }
    $last = (int) ($_SESSION['_moun_last'] ?? $now);
    if ($now - $last > $idle) {
        $_SESSION = [];
        session_destroy();
        session_start();
        $_SESSION['_moun_created'] = $now;
    }
    $_SESSION['_moun_last'] = $now;
}

function moun_session_regenerate(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

function moun_logout(): void
{
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}
