<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/php/bootstrap.php';

if (!moun_is_installed()) {
    header('Location: ../install.php', true, 302);
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
$user = moun_require_admin($pdo);

$userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$loginCount = (int) $pdo->query('SELECT COUNT(*) FROM login_events')->fetchColumn();
$ndaCount = (int) $pdo->query('SELECT COUNT(*) FROM nda_acceptances')->fetchColumn();

$uUsers = moun_h(moun_url('/admin/users.php'));
$uLogins = moun_h(moun_url('/admin/logins.php'));
$uNda = moun_h(moun_url('/admin/nda_acceptances.php'));

$body = '<p class="moun-panel__sub" style="margin:0 0 1.25rem;max-width:40rem">Provision partners and investors, review access trails, and retain NNN evidence — all under the same confidentiality standard as the engineering build guide.</p>'
    . '<div class="moun-dash-grid">'
    . '<a class="moun-dash-card" href="' . $uUsers . '">'
    . '<p class="moun-dash-card__k">Directory</p>'
    . '<h2 class="moun-dash-card__t">Users &amp; invites</h2>'
    . '<p class="moun-dash-card__d">Create invitees, set legal names before first signature, and rotate secure invite links.</p>'
    . '<div class="moun-dash-meta"><span class="moun-pill moun-pill--viewer">' . $userCount . ' accounts</span></div>'
    . '</a>'
    . '<a class="moun-dash-card" href="' . $uLogins . '">'
    . '<p class="moun-dash-card__k">Telemetry</p>'
    . '<h2 class="moun-dash-card__t">Login audit</h2>'
    . '<p class="moun-dash-card__d">Timestamped sessions with IP, geo hints, and device strings for security review.</p>'
    . '<div class="moun-dash-meta"><span class="moun-pill moun-pill--ok">' . $loginCount . ' events</span></div>'
    . '</a>'
    . '<a class="moun-dash-card" href="' . $uNda . '">'
    . '<p class="moun-dash-card__k">Compliance</p>'
    . '<h2 class="moun-dash-card__t">NNN signatures</h2>'
    . '<p class="moun-dash-card__d">Immutable acceptance log: signature text, declared date, IP, location snapshot, content hash.</p>'
    . '<div class="moun-dash-meta"><span class="moun-pill moun-pill--admin">' . $ndaCount . ' records</span></div>'
    . '</a>'
    . '</div>';

echo moun_layout('Overview', $body, $user, 'admin', 'home');
