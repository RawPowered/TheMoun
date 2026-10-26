<?php

declare(strict_types=1);

function moun_csrf_token(): string
{
    moun_session_start();
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return (string) $_SESSION['_csrf'];
}

function moun_csrf_validate(?string $token): bool
{
    moun_session_start();
    if ($token === null || $token === '') {
        return false;
    }
    return hash_equals((string) ($_SESSION['_csrf'] ?? ''), $token);
}
