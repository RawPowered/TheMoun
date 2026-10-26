<?php

declare(strict_types=1);

/** @return array<string,mixed>|null */
function moun_current_user_row(PDO $pdo): ?array
{
    moun_session_start();
    $id = $_SESSION['user_id'] ?? null;
    if (!is_int($id) && !is_numeric($id)) {
        return null;
    }
    $st = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $st->execute([(int) $id]);
    $u = $st->fetch(PDO::FETCH_ASSOC);
    return $u ?: null;
}

/** @param array<string,mixed> $u */
function moun_user_is_admin(array $u): bool
{
    return strtolower(trim((string) ($u['role'] ?? ''))) === 'admin';
}

/** @return array<string,mixed>|null current NDA row */
function moun_current_nda_version(PDO $pdo): ?array
{
    $st = $pdo->query('SELECT * FROM nda_versions WHERE is_current = 1 ORDER BY id DESC LIMIT 1');
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function moun_user_has_accepted_nda(PDO $pdo, int $userId, int $ndaVersionId): bool
{
    $st = $pdo->prepare(
        'SELECT 1 FROM nda_acceptances WHERE user_id = ? AND nda_version_id = ? LIMIT 1'
    );
    $st->execute([$userId, $ndaVersionId]);
    return (bool) $st->fetchColumn();
}

function moun_normalize_legal(string $a, string $b): string
{
    $s = strtolower(trim(preg_replace('/\s+/', ' ', $a . ' ' . $b)));
    return $s;
}

function moun_render_nda_html(array $ndaRow, array $userRow, array $cfg): string
{
    $html = (string) $ndaRow['body_html'];
    $full = trim($userRow['legal_first_name'] . ' ' . $userRow['legal_last_name']);
    $repl = [
        '[[BRAND_LEGAL_NAME]]' => htmlspecialchars((string) $cfg['BRAND_LEGAL_NAME'], ENT_QUOTES, 'UTF-8'),
        '[[BRAND_SHORT_NAME]]' => htmlspecialchars((string) $cfg['BRAND_SHORT_NAME'], ENT_QUOTES, 'UTF-8'),
        '[[PARTY_FULL_NAME]]' => htmlspecialchars($full, ENT_QUOTES, 'UTF-8'),
        '[[VERSION_LABEL]]' => htmlspecialchars((string) $ndaRow['version_label'], ENT_QUOTES, 'UTF-8'),
        '[[EFFECTIVE_AT]]' => htmlspecialchars((string) $ndaRow['effective_at'], ENT_QUOTES, 'UTF-8'),
    ];
    return strtr($html, $repl);
}

function moun_nda_snapshot_hash(string $renderedHtml, string $versionLabel, string $legalFirst, string $legalLast): string
{
    return hash('sha256', $versionLabel . "\n" . $legalFirst . "\n" . $legalLast . "\n" . $renderedHtml);
}

function moun_require_user(PDO $pdo): array
{
    $u = moun_current_user_row($pdo);
    if (!$u) {
        moun_redirect('/login.php');
    }
    return $u;
}

function moun_require_admin(PDO $pdo): array
{
    $u = moun_require_user($pdo);
    if (!moun_user_is_admin($u)) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
    return $u;
}

function moun_log_login_event(PDO $pdo, int $userId, string $ip, string $ua, array $geo): void
{
    $st = $pdo->prepare(
        'INSERT INTO login_events (user_id, ip, user_agent, geo_country, geo_region, geo_city, geo_lat, geo_lon) VALUES (?,?,?,?,?,?,?,?)'
    );
    $st->execute([
        $userId,
        $ip,
        mb_substr($ua, 0, 512),
        $geo['country'],
        $geo['region'],
        $geo['city'],
        $geo['lat'],
        $geo['lon'],
    ]);
}
