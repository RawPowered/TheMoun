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
$user = moun_current_user_row($pdo);
if (!$user) {
    moun_redirect('/login.php');
}

if (empty($user['password_hash'])) {
    moun_redirect('/invite.php');
}

$nda = moun_current_nda_version($pdo);
if ($nda && !moun_user_has_accepted_nda($pdo, (int) $user['id'], (int) $nda['id'])) {
    moun_redirect('/nda.php');
}

$htmlPath = moun_root_path() . '/index.html';
if (!is_readable($htmlPath)) {
    http_response_code(503);
    echo 'Application bundle missing — run npm run build and deploy dist output.';
    exit;
}

$html = file_get_contents($htmlPath);
if ($html === false) {
    http_response_code(503);
    exit;
}

$csrfJson = json_encode(moun_csrf_token(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$mounBridge =
    '<script>window.__MOUN=window.__MOUN||{};window.__MOUN.csrf=' . $csrfJson . ';</script>';
if (stripos($html, '</head>') !== false) {
    $html = str_ireplace('</head>', $mounBridge . '</head>', $html);
} else {
    $html = $mounBridge . $html;
}

$logoutUrl = moun_h(moun_url('/logout.php'));
$inject = '<div id="gs-php-auth-bar" style="position:fixed;bottom:0;right:0;left:0;z-index:99999;padding:0.35rem 0.75rem;background:rgba(26,31,38,0.92);border-top:1px solid #3f4a54;font-size:12px;font-family:system-ui,sans-serif;display:flex;justify-content:flex-end;gap:0.75rem;align-items:center">'
    . '<span style="color:#a8a29e">Session protected</span>'
    . '<a href="' . $logoutUrl . '" style="color:#86efac">Sign out</a></div>';
if (stripos($html, '</body>') !== false) {
    $html = str_ireplace('</body>', $inject . '</body>', $html);
} else {
    $html .= $inject;
}

header('Content-Type: text/html; charset=UTF-8');
echo $html;
