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
$user = moun_require_user($pdo);

$nda = moun_current_nda_version($pdo);
if (!$nda || moun_user_has_accepted_nda($pdo, (int) $user['id'], (int) $nda['id'])) {
    moun_redirect('/index.php');
}

$cfg = moun_config();
$rendered = moun_render_nda_html($nda, $user, $cfg);
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!moun_csrf_validate($_POST['_csrf'] ?? null)) {
        $err = 'Invalid session token.';
    } else {
        $sig = trim((string) ($_POST['signature_text'] ?? ''));
        $expected = moun_normalize_legal((string) $user['legal_first_name'], (string) $user['legal_last_name']);
        $got = moun_normalize_legal($sig, '');
        if ($got !== $expected) {
            $err = 'Signature must match your full legal name exactly.';
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
            moun_session_regenerate();
            moun_redirect('/index.php');
        }
    }
}

$today = (new DateTimeImmutable('now'))->format('Y-m-d');
$body = '<div class="moun-nda-lead">'
    . '<p class="moun-nda-lead__p">You need to sign the updated NNN terms before you can open the build guide or other protected materials.</p>'
    . '<p class="moun-nda-lead__sub">Read the full text in the box below, then sign with your <strong>exact</strong> legal name on file.</p>'
    . '</div>'
    . '<div class="moun-nda-document-wrap" tabindex="0" role="region" aria-label="NNN agreement text">'
    . '<div class="moun-nda-document">' . $rendered . '</div></div>'
    . '<p class="moun-nda-binding-callout">By typing your name and submitting, you are placing a legally binding digital signature on this agreement.</p>'
    . '<form method="post">'
    . '<input type="hidden" name="_csrf" value="' . moun_h(moun_csrf_token()) . '">'
    . '<p><small>Legal name: <strong>' . moun_h(trim($user['legal_first_name'] . ' ' . $user['legal_last_name'])) . '</strong></small></p>'
    . '<label>Type your full legal name as signature <input name="signature_text" required autocomplete="name"></label>'
    . '<label>Date <input type="date" name="signer_declared_date" value="' . moun_h($today) . '"></label>'
    . '<label class="moun-nda-checkbox"><input type="checkbox" name="ack_legal_binding" value="1" required> <span>I understand this is a legally binding digital signature.</span></label>'
    . ($err ? '<p class="err">' . moun_h($err) . '</p>' : '')
    . '<p><button type="submit">Sign</button></p>'
    . '</form>';

echo moun_layout('Updated NNN Terms', $body, $user);
