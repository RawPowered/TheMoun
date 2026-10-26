<?php

declare(strict_types=1);

require_once __DIR__ . '/php/bootstrap.php';

if (moun_is_installed()) {
    moun_session_start();
    moun_logout();
}

header('Location: login.php', true, 302);
exit;
