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

$syncPath = moun_sync_sql_path();
$syncReadable = is_readable($syncPath);
$syncContents = $syncReadable ? (string) file_get_contents($syncPath) : '';
$syncSha = $syncReadable ? hash('sha256', $syncContents) : '';
$syncMtime = $syncReadable ? (int) filemtime($syncPath) : 0;

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string) ($_POST['_csrf'] ?? '');
    if (!moun_csrf_validate($token)) {
        http_response_code(403);
        $err = 'Invalid CSRF token.';
    } elseif (!$syncReadable) {
        $err = 'Sync SQL file is not readable at: ' . moun_h($syncPath);
    } else {
        $dry = !empty($_POST['dry_run']);
        try {
            $stmts = moun_sql_split_statements($syncContents);
            $ran = 0;
            $log = [];
            foreach ($stmts as $stmt) {
                if ($dry) {
                    $preview = mb_substr(preg_replace('/\s+/', ' ', trim($stmt)), 0, 200);
                    $log[] = $preview . (mb_strlen($stmt) > 200 ? '…' : '');
                    $ran++;
                } else {
                    $pdo->exec($stmt);
                    $ran++;
                }
            }
            if ($dry) {
                $msg = 'Dry run: ' . $ran . ' statement(s) would execute. First lines below.';
                $err = ''; // show log in body
                $_SESSION['_patch_dry_log'] = $log;
            } else {
                $msg = 'Applied ' . $ran . ' statement(s) successfully.';
            }
        } catch (Throwable $e) {
            $err = 'SQL error: ' . $e->getMessage();
        }
    }
}

$dryLog = $_SESSION['_patch_dry_log'] ?? null;
if (is_array($dryLog)) {
    unset($_SESSION['_patch_dry_log']);
} else {
    $dryLog = [];
}

$csrf = moun_csrf_token();
$pathEsc = moun_h($syncPath);

$body = '<p class="moun-panel__sub" style="margin:0 0 1rem;max-width:44rem">Runs the repository <code>deploy/sync.sql</code> on the server (idempotent DDL only). Existing deployments: use this after pulling updates instead of ad-hoc ALTERs.</p>'
    . '<div class="moun-panel" style="margin-bottom:1rem">'
    . '<p style="margin:0 0 0.5rem"><strong>File</strong></p>'
    . '<p style="margin:0;font-family:ui-monospace,monospace;font-size:12px;word-break:break-all">' . $pathEsc . '</p>'
    . '<p style="margin:0.75rem 0 0;font-size:12px;color:#a8a29e">SHA-256: ' . moun_h($syncSha ?: '—') . ($syncMtime ? ' · mtime ' . gmdate('Y-m-d H:i:s', $syncMtime) . ' UTC' : '') . '</p>'
    . '</div>';

if ($msg !== '') {
    $body .= '<p class="moun-pill moun-pill--ok" style="display:inline-block;margin-bottom:1rem">' . moun_h($msg) . '</p>';
}
if ($err !== '') {
    $body .= '<p class="moun-pill moun-pill--warn" style="display:inline-block;margin-bottom:1rem">' . moun_h($err) . '</p>';
}

if (!$syncReadable) {
    $body .= '<p class="moun-panel__sub" style="color:#fecaca">Cannot read Sync SQL. Deploy <code>deploy/sync.sql</code> next to the app (see path above).</p>';
}

$body .= '<form method="post" style="display:flex;flex-wrap:wrap;gap:0.75rem;align-items:center;margin-bottom:1.25rem">'
    . '<input type="hidden" name="_csrf" value="' . moun_h($csrf) . '">'
    . '<button type="submit" name="dry_run" value="1" class="moun-btn moun-btn--secondary"' . ($syncReadable ? '' : ' disabled') . '>Dry run (preview)</button>'
    . '<button type="submit" class="moun-btn moun-btn--primary"' . ($syncReadable ? '' : ' disabled') . '>Apply Sync SQL</button>'
    . '</form>';

if ($dryLog !== []) {
    $body .= '<div class="moun-panel"><p style="margin:0 0 0.5rem"><strong>Dry run — statement previews</strong></p><ol style="margin:0;padding-left:1.25rem;font-size:12px;font-family:ui-monospace,monospace">';
    foreach ($dryLog as $line) {
        $body .= '<li style="margin:0.35rem 0">' . moun_h((string) $line) . '</li>';
    }
    $body .= '</ol></div>';
}

if ($syncReadable && $syncContents !== '') {
    $body .= '<details class="moun-panel" style="margin-top:1rem"><summary style="cursor:pointer;font-weight:600">Show file contents</summary>'
        . '<pre style="margin:0.75rem 0 0;overflow:auto;max-height:24rem;font-size:11px;line-height:1.4;padding:0.75rem;background:#0f1419;border-radius:6px;border:1px solid #3f4a54">'
        . moun_h($syncContents)
        . '</pre></details>';
}

echo moun_layout('Update patches', $body, $user, 'admin', 'patches');
