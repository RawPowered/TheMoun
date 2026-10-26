<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

try {
    $pdo = moun_pdo();
} catch (Throwable $e) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Service unavailable';
    exit;
}

moun_session_start();
$user = moun_current_user_row($pdo);
if (!$user || empty($user['password_hash'])) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Forbidden';
    exit;
}

$nda = moun_current_nda_version($pdo);
if ($nda && !moun_user_has_accepted_nda($pdo, (int) $user['id'], (int) $nda['id'])) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

$rel = (string) ($_GET['file'] ?? '');
$rel = str_replace('\\', '/', $rel);
$rel = ltrim($rel, '/');
if ($rel === '' || str_contains($rel, '..')) {
    http_response_code(400);
    exit;
}

$root = moun_root_path();
$assetsRoot = realpath($root . '/assets');
if ($assetsRoot === false) {
    http_response_code(404);
    exit;
}
$full = realpath($assetsRoot . '/' . $rel);
if ($full === false || !str_starts_with($full, $assetsRoot . DIRECTORY_SEPARATOR)) {
    http_response_code(404);
    exit;
}

if (!is_file($full) || !is_readable($full)) {
    http_response_code(404);
    exit;
}

$ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
$types = [
    'js' => 'application/javascript; charset=UTF-8',
    'css' => 'text/css; charset=UTF-8',
    'svg' => 'image/svg+xml',
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
    'woff2' => 'font/woff2',
    'woff' => 'font/woff',
    'obj' => 'text/plain; charset=UTF-8',
    'mtl' => 'text/plain; charset=UTF-8',
    'ico' => 'image/x-icon',
    'map' => 'application/json',
];
$mime = $types[$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Cache-Control: private, max-age=3600');
readfile($full);
