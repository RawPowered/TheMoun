<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/analytics_allowlist.php';

moun_send_security_headers();

header('Content-Type: application/json; charset=UTF-8');

if (!moun_is_installed()) {
    http_response_code(503);
    echo json_encode(['ok' => false, 'error' => 'not_installed']);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method']);
    exit;
}

try {
    $pdo = moun_pdo();
} catch (Throwable $e) {
    http_response_code(503);
    echo json_encode(['ok' => false, 'error' => 'db']);
    exit;
}

moun_session_start();
$user = moun_current_user_row($pdo);
if (!$user) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'auth']);
    exit;
}

if (moun_user_is_admin($user)) {
    http_response_code(204);
    exit;
}

$raw = file_get_contents('php://input');
if ($raw === false || $raw === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'body']);
    exit;
}

/** @var mixed $payload */
$payload = json_decode($raw, true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'json']);
    exit;
}

$csrf = isset($payload['csrf']) ? (string) $payload['csrf'] : '';
if (!moun_csrf_validate($csrf)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'csrf']);
    exit;
}

$sessionId = isset($payload['sessionId']) ? trim((string) $payload['sessionId']) : '';
if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $sessionId)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'session_id']);
    exit;
}

$events = $payload['events'] ?? null;
if (!is_array($events)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'events']);
    exit;
}

$userId = (int) $user['id'];
$maxPerMinute = 180;
$since = (new DateTimeImmutable('-1 minute'))->format('Y-m-d H:i:s');
$stCount = $pdo->prepare(
    'SELECT COUNT(*) FROM analytics_dwell_events WHERE user_id = ? AND created_at >= ?'
);
$stCount->execute([$userId, $since]);
$recent = (int) $stCount->fetchColumn();
if ($recent + count($events) > $maxPerMinute) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'error' => 'rate_limit']);
    exit;
}

$stSess = $pdo->prepare('SELECT user_id FROM analytics_sessions WHERE id = ? LIMIT 1');
$stSess->execute([$sessionId]);
$row = $stSess->fetch(PDO::FETCH_ASSOC);
if ($row) {
    if ((int) $row['user_id'] !== $userId) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'session_conflict']);
        exit;
    }
} else {
    $ins = $pdo->prepare(
        'INSERT INTO analytics_sessions (id, user_id, started_at, user_agent) VALUES (?,?,?,?)'
    );
    $ins->execute([$sessionId, $userId, (new DateTimeImmutable())->format('Y-m-d H:i:s'), moun_user_agent()]);
}

$maxDur = 3_600_000;
$insEv = $pdo->prepare(
    'INSERT INTO analytics_dwell_events (user_id, session_id, kind, target_key, section_context, duration_ms, started_at, ended_at)
     VALUES (?,?,?,?,?,?,?,?)'
);

$accepted = 0;
foreach ($events as $ev) {
    if (!is_array($ev)) {
        continue;
    }
    $kind = isset($ev['kind']) ? (string) $ev['kind'] : '';
    $targetKey = isset($ev['targetKey']) ? trim((string) $ev['targetKey']) : '';
    $sectionContext = isset($ev['sectionContext']) ? trim((string) $ev['sectionContext']) : null;
    if ($sectionContext === '') {
        $sectionContext = null;
    }
    $durationMs = isset($ev['durationMs']) ? (int) $ev['durationMs'] : -1;
    $startedAt = isset($ev['startedAt']) ? (string) $ev['startedAt'] : '';
    $endedAt = isset($ev['endedAt']) ? (string) $ev['endedAt'] : '';

    if (($kind !== 'section' && $kind !== 'image') || $targetKey === '' || $durationMs < 0) {
        continue;
    }
    if ($durationMs > $maxDur) {
        $durationMs = $maxDur;
    }
    if (!moun_analytics_target_allowed($kind, $targetKey, $sectionContext)) {
        continue;
    }

    $tStart = DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $startedAt)
        ?: date_create_immutable($startedAt);
    $tEnd = DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $endedAt)
        ?: date_create_immutable($endedAt);
    if (!$tStart || !$tEnd) {
        continue;
    }
    $insEv->execute([
        $userId,
        $sessionId,
        $kind,
        mb_substr($targetKey, 0, 191),
        $sectionContext !== null ? mb_substr($sectionContext, 0, 64) : null,
        $durationMs,
        $tStart->format('Y-m-d H:i:s'),
        $tEnd->format('Y-m-d H:i:s'),
    ]);
    $accepted++;
}

echo json_encode(['ok' => true, 'accepted' => $accepted]);
