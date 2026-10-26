<?php

declare(strict_types=1);

require_once __DIR__ . '/php/bootstrap.php';

if (!moun_is_installed()) {
    header('Location: install.php', true, 302);
    exit;
}

moun_send_security_headers();

try {
    $pdo = moun_pdo();
} catch (Throwable $e) {
    http_response_code(503);
    echo 'Database unavailable';
    exit;
}

moun_session_start();
if (moun_current_user_row($pdo)) {
    moun_redirect('/index.php');
}

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!moun_csrf_validate($_POST['_csrf'] ?? null)) {
        $err = 'Invalid session token. Refresh and try again.';
    } else {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $pass = (string) ($_POST['password'] ?? '');
        $st = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $st->execute([$email]);
        $u = $st->fetch(PDO::FETCH_ASSOC);
        if (!$u || empty($u['password_hash']) || !password_verify($pass, (string) $u['password_hash'])) {
            $err = 'Invalid email or password.';
        } else {
            moun_session_regenerate();
            $_SESSION['user_id'] = (int) $u['id'];
            $geo = moun_geo_lookup(moun_client_ip());
            moun_log_login_event($pdo, (int) $u['id'], moun_client_ip(), moun_user_agent(), $geo);
            $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([(int) $u['id']]);
            moun_redirect('/index.php');
        }
    }
}

echo moun_layout(
    'Sign in',
    '<p class="moun-login-kicker">Confidential build guide</p>'
        . '<p class="moun-login-lead">Use the email and password issued with your invite. This portal hosts engineering and diligence materials under NNN terms.</p>'
        . '<form class="moun-login-form" method="post">'
        . '<input type="hidden" name="_csrf" value="' . moun_h(moun_csrf_token()) . '">'
        . '<label>Email <input type="email" name="email" required autocomplete="username" placeholder="you@company.com"></label>'
        . '<label>Password <input type="password" name="password" required autocomplete="current-password" placeholder="••••••••"></label>'
        . ($err ? '<p class="err">' . moun_h($err) . '</p>' : '')
        . '<p class="moun-login-actions"><button type="submit">Sign in</button></p>'
        . '</form>'
        . '<p class="moun-login-footnote">No password yet? Open your <strong>invite link</strong> from the administrator first, then return here to sign in.</p>',
    null,
    'login'
);
