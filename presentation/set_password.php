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

$uid = (int) ($_SESSION['onboarding_user_id'] ?? 0);
if ($uid <= 0) {
    echo moun_layout('Set password', '<p class="err">Session expired. Open your invite link again.</p>', null);
    exit;
}

$st = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
$st->execute([$uid]);
$user = $st->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    unset($_SESSION['onboarding_user_id']);
    echo moun_layout('Set password', '<p class="err">User not found.</p>', null);
    exit;
}

$nda = moun_current_nda_version($pdo);
if (!$nda || !moun_user_has_accepted_nda($pdo, $uid, (int) $nda['id'])) {
    unset($_SESSION['onboarding_user_id']);
    echo moun_layout('Set password', '<p class="err">NNN agreement must be completed first.</p>', null);
    exit;
}

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!moun_csrf_validate($_POST['_csrf'] ?? null)) {
        $err = 'Invalid session token.';
    } else {
        $p1 = (string) ($_POST['password'] ?? '');
        $p2 = (string) ($_POST['password2'] ?? '');
        if (strlen($p1) < 12) {
            $err = 'Password must be at least 12 characters.';
        } elseif ($p1 !== $p2) {
            $err = 'Passwords do not match.';
        } else {
            $hash = password_hash($p1, PASSWORD_DEFAULT);
            $pdo->prepare('UPDATE users SET password_hash = ?, invite_token_hash = NULL, invite_expires_at = NULL WHERE id = ?')->execute([$hash, $uid]);
            unset($_SESSION['onboarding_user_id']);
            moun_session_regenerate();
            $_SESSION['user_id'] = $uid;
            $geo = moun_geo_lookup(moun_client_ip());
            moun_log_login_event($pdo, $uid, moun_client_ip(), moun_user_agent(), $geo);
            moun_redirect('/index.php');
        }
    }
}

echo moun_layout('Create password', '<form method="post">'
    . '<input type="hidden" name="_csrf" value="' . moun_h(moun_csrf_token()) . '">'
    . '<p>Choose a strong password for <strong>' . moun_h((string) $user['email']) . '</strong>.</p>'
    . '<label>Password (min 12) <input type="password" name="password" required minlength="12" autocomplete="new-password"></label>'
    . '<label>Confirm password <input type="password" name="password2" required minlength="12" autocomplete="new-password"></label>'
    . ($err ? '<p class="err">' . moun_h($err) . '</p>' : '')
    . '<p><button type="submit">Save and enter</button></p>'
    . '</form>', null);
