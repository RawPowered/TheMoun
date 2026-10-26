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

$token = (string) ($_GET['t'] ?? $_POST['invite_token'] ?? '');
$token = trim($token);
$hash = $token !== '' ? hash('sha256', $token) : '';

$err = '';

if ($hash === '') {
    echo moun_layout('Invite', '<p>Missing invite token. Use the link provided by your administrator.</p>', null);
    exit;
}

$st = $pdo->prepare(
    'SELECT * FROM users WHERE invite_token_hash = ? AND (invite_expires_at IS NULL OR invite_expires_at > NOW()) LIMIT 1'
);
$st->execute([$hash]);
$user = $st->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    echo moun_layout('Invite', '<p class="err">Invalid or expired invite.</p>', null);
    exit;
}

$nda = moun_current_nda_version($pdo);
if (!$nda) {
    echo moun_layout('Invite', '<p class="err">NNN terms are not configured.</p>', null);
    exit;
}

if (!empty($user['password_hash']) && moun_user_has_accepted_nda($pdo, (int) $user['id'], (int) $nda['id'])) {
    echo moun_layout('Invite', '<p>This invite is already completed. <a href="' . moun_h(moun_url('/login.php')) . '">Sign in</a>.</p>', null);
    exit;
}

if (empty($user['password_hash']) && moun_user_has_accepted_nda($pdo, (int) $user['id'], (int) $nda['id'])) {
    moun_session_regenerate();
    $_SESSION['onboarding_user_id'] = (int) $user['id'];
    moun_redirect('/set_password.php');
}

$cfg = moun_config();
$rendered = moun_render_nda_html($nda, $user, $cfg);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!moun_csrf_validate($_POST['_csrf'] ?? null)) {
        $err = 'Invalid session token.';
    } elseif (trim((string) ($_POST['invite_token'] ?? '')) !== $token) {
        $err = 'Token mismatch.';
    } else {
        $sig = trim((string) ($_POST['signature_text'] ?? ''));
        $expected = moun_normalize_legal((string) $user['legal_first_name'], (string) $user['legal_last_name']);
        $got = moun_normalize_legal($sig, '');
        if ($got !== $expected) {
            $err = 'Signature must match your full legal name exactly (as shown).';
        } elseif (empty($_POST['ack_legal_binding'])) {
            $err = 'You must acknowledge that this is a legally binding digital signature.';
        } else {
            $decl = trim((string) ($_POST['signer_declared_date'] ?? ''));
            $declDate = $decl !== '' ? $decl : null;
            $ip = moun_client_ip();
            $ua = moun_user_agent();
            $geo = moun_geo_lookup($ip);
            $snap = moun_nda_snapshot_hash(
                $rendered,
                (string) $nda['version_label'],
                (string) $user['legal_first_name'],
                (string) $user['legal_last_name']
            );
            $pdo->beginTransaction();
            try {
                $ins = $pdo->prepare(
                    'INSERT INTO nda_acceptances (user_id, nda_version_id, signature_text, signed_at, signer_declared_date, ip, user_agent, geo_country, geo_region, geo_city, geo_lat, geo_lon, content_snapshot_hash)
                     VALUES (?,?,?,NOW(),?,?,?,?,?,?,?,?,?)'
                );
                $ins->execute([
                    (int) $user['id'],
                    (int) $nda['id'],
                    $sig,
                    $declDate,
                    $ip,
                    mb_substr($ua, 0, 512),
                    $geo['country'],
                    $geo['region'],
                    $geo['city'],
                    $geo['lat'],
                    $geo['lon'],
                    $snap,
                ]);
                $pdo->prepare(
                    'UPDATE users SET legal_identity_locked_at = COALESCE(legal_identity_locked_at, NOW()), invite_token_hash = NULL, invite_expires_at = NULL WHERE id = ?'
                )->execute([(int) $user['id']]);
                $pdo->commit();
            } catch (Throwable $e) {
                $pdo->rollBack();
                $err = 'Could not save: ' . $e->getMessage();
            }
            if ($err === '') {
                moun_session_regenerate();
                $_SESSION['onboarding_user_id'] = (int) $user['id'];
                moun_redirect('/set_password.php');
            }
        }
    }
}

$today = (new DateTimeImmutable('now'))->format('Y-m-d');
$body = '<div class="moun-nda-lead">'
    . '<p class="moun-nda-lead__p"><strong>' . moun_h((string) $cfg['BRAND_SHORT_NAME']) . '</strong> requires NNN acceptance before account setup.</p>'
    . '<p class="moun-nda-lead__sub">Review the full terms, then sign exactly as your legal name is shown.</p>'
    . '</div>'
    . '<div class="moun-nda-document-wrap" tabindex="0" role="region" aria-label="NNN agreement text"><div class="moun-nda-document">' . $rendered . '</div></div>'
    . '<p class="moun-nda-binding-callout">This NNN acceptance is digitally signed and legally binding.</p>'
    . '<form method="post">'
    . '<input type="hidden" name="_csrf" value="' . moun_h(moun_csrf_token()) . '">'
    . '<input type="hidden" name="invite_token" value="' . moun_h($token) . '">'
    . '<p><small>Legal name (read-only): <strong>' . moun_h(trim($user['legal_first_name'] . ' ' . $user['legal_last_name'])) . '</strong></small></p>'
    . '<label>Type your full legal name as signature <input name="signature_text" required autocomplete="name"></label>'
    . '<label>Today\'s date <input type="date" name="signer_declared_date" value="' . moun_h($today) . '"></label>'
    . '<label class="moun-nda-checkbox"><input type="checkbox" name="ack_legal_binding" value="1" required> <span>I understand this is a legally binding digital signature.</span></label>'
    . ($err ? '<p class="err">' . moun_h($err) . '</p>' : '')
    . '<p><button type="submit">Sign and continue</button></p>'
    . '</form>';

echo moun_layout('NNN Agreement', $body, null);
