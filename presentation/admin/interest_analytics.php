<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/php/bootstrap.php';
require_once dirname(__DIR__) . '/php/analytics_allowlist.php';

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

$tablesOk = false;
try {
    $pdo->query('SELECT 1 FROM analytics_dwell_events LIMIT 1');
    $tablesOk = true;
} catch (Throwable $e) {
    $tablesOk = false;
}

$dateTo = trim((string) ($_GET['date_to'] ?? ''));
$dateFrom = trim((string) ($_GET['date_from'] ?? ''));
if ($dateFrom === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
    $dateFrom = (new DateTimeImmutable('-30 days'))->format('Y-m-d');
}
if ($dateTo === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
    $dateTo = (new DateTimeImmutable('now'))->format('Y-m-d');
}

$minDwell = max(0, (int) ($_GET['min_ms'] ?? 3000));
$filterUid = (int) ($_GET['user'] ?? 0);

$viewers = $pdo->query("SELECT id, email FROM users WHERE role = 'viewer' ORDER BY email ASC")->fetchAll(PDO::FETCH_ASSOC);

$sectionCatalog = moun_analytics_section_ids();
$imageCatalog = moun_analytics_interest_image_defs();

$hotSections = [];
$hotImages = [];
$perUserSections = [];
$perUserImages = [];
$notSeenSections = [];
$notSeenImages = [];
$coveragePct = null;

if ($tablesOk) {
    $startDt = $dateFrom . ' 00:00:00';
    $endDt = (new DateTimeImmutable($dateTo))->modify('+1 day')->format('Y-m-d H:i:s');

    $stHotS = $pdo->prepare(
        'SELECT e.target_key, SUM(e.duration_ms) AS total_ms, COUNT(DISTINCT e.user_id) AS uniq_users
         FROM analytics_dwell_events e
         INNER JOIN users u ON u.id = e.user_id AND u.role = ?
         WHERE e.kind = ? AND e.started_at >= ? AND e.started_at < ? AND e.duration_ms >= ?
         GROUP BY e.target_key
         ORDER BY total_ms DESC'
    );
    $stHotS->execute(['viewer', 'section', $startDt, $endDt, $minDwell]);
    $hotSections = $stHotS->fetchAll(PDO::FETCH_ASSOC);

    $stHotI = $pdo->prepare(
        'SELECT e.target_key, SUM(e.duration_ms) AS total_ms, COUNT(DISTINCT e.user_id) AS uniq_users
         FROM analytics_dwell_events e
         INNER JOIN users u ON u.id = e.user_id AND u.role = ?
         WHERE e.kind = ? AND e.started_at >= ? AND e.started_at < ? AND e.duration_ms >= ?
         GROUP BY e.target_key
         ORDER BY total_ms DESC'
    );
    $stHotI->execute(['viewer', 'image', $startDt, $endDt, $minDwell]);
    $hotImages = $stHotI->fetchAll(PDO::FETCH_ASSOC);

    if ($filterUid > 0) {
        $stU = $pdo->prepare('SELECT id, email, role FROM users WHERE id = ? LIMIT 1');
        $stU->execute([$filterUid]);
        $fu = $stU->fetch(PDO::FETCH_ASSOC);
        if ($fu && strtolower((string) $fu['role']) === 'viewer') {
            $stPs = $pdo->prepare(
                'SELECT target_key, SUM(duration_ms) AS total_ms, MAX(ended_at) AS last_seen
                 FROM analytics_dwell_events
                 WHERE user_id = ? AND kind = ? AND started_at >= ? AND started_at < ? AND duration_ms >= ?
                 GROUP BY target_key
                 ORDER BY total_ms DESC'
            );
            $stPs->execute([$filterUid, 'section', $startDt, $endDt, $minDwell]);
            $perUserSections = $stPs->fetchAll(PDO::FETCH_ASSOC);

            $stPi = $pdo->prepare(
                'SELECT target_key, SUM(duration_ms) AS total_ms
                 FROM analytics_dwell_events
                 WHERE user_id = ? AND kind = ? AND started_at >= ? AND started_at < ? AND duration_ms >= ?
                 GROUP BY target_key
                 ORDER BY total_ms DESC
                 LIMIT 24'
            );
            $stPi->execute([$filterUid, 'image', $startDt, $endDt, $minDwell]);
            $perUserImages = $stPi->fetchAll(PDO::FETCH_ASSOC);

            $qualifying = 0;
            foreach ($sectionCatalog as $sid) {
                $stOne = $pdo->prepare(
                    'SELECT 1 FROM analytics_dwell_events
                     WHERE user_id = ? AND kind = ? AND target_key = ? AND started_at >= ? AND started_at < ? AND duration_ms >= ?
                     LIMIT 1'
                );
                $stOne->execute([$filterUid, 'section', $sid, $startDt, $endDt, $minDwell]);
                if ($stOne->fetchColumn()) {
                    $qualifying++;
                } else {
                    $notSeenSections[] = $sid;
                }
            }
            $coveragePct = count($sectionCatalog) > 0
                ? round(100 * $qualifying / count($sectionCatalog), 1)
                : null;

            $seenImg = [];
            foreach ($perUserImages as $row) {
                $seenImg[(string) $row['target_key']] = true;
            }
            foreach ($imageCatalog as $def) {
                $ik = $def['id'];
                if (!empty($seenImg[$ik])) {
                    continue;
                }
                $stOne = $pdo->prepare(
                    'SELECT 1 FROM analytics_dwell_events
                     WHERE user_id = ? AND kind = ? AND target_key = ? AND started_at >= ? AND started_at < ? AND duration_ms >= ?
                     LIMIT 1'
                );
                $stOne->execute([$filterUid, 'image', $ik, $startDt, $endDt, $minDwell]);
                if (!$stOne->fetchColumn()) {
                    $notSeenImages[] = $ik;
                }
            }
        }
    }
}

$qBase = moun_url('/admin/interest_analytics.php');

ob_start();
?>
<section class="moun-panel">
  <div class="moun-panel__head">
    <div>
      <p class="moun-kicker">Internal diligence</p>
      <h2 class="moun-panel__title">Interest analytics</h2>
      <p class="moun-panel__sub">Section and curated-image dwell from investor sessions (<code>viewer</code> accounts). Administrator browsing is excluded at ingest.</p>
    </div>
  </div>

  <?php if (!$tablesOk): ?>
    <p class="moun-pill moun-pill--warn" style="display:inline-block;margin:.5rem 0 1rem">Analytics tables not found. Run <strong>Admin → Updates → Apply Sync SQL</strong> (or import <code>deploy/sync.sql</code>).</p>
  <?php endif; ?>

  <form method="get" action="<?= moun_h($qBase) ?>" style="display:flex;flex-wrap:wrap;gap:.75rem 1rem;align-items:flex-end;margin-bottom:1.25rem;font-size:.85rem">
    <div>
      <label for="df">From</label><br>
      <input id="df" name="date_from" type="date" value="<?= moun_h($dateFrom) ?>" style="margin-top:.25rem;padding:.35rem .5rem;border-radius:6px;border:1px solid #3f4a54;background:#0f1419;color:#fafaf9">
    </div>
    <div>
      <label for="dt">To</label><br>
      <input id="dt" name="date_to" type="date" value="<?= moun_h($dateTo) ?>" style="margin-top:.25rem;padding:.35rem .5rem;border-radius:6px;border:1px solid #3f4a54;background:#0f1419;color:#fafaf9">
    </div>
    <div>
      <label for="minm">Min. dwell (ms)</label><br>
      <input id="minm" name="min_ms" type="number" min="0" step="100" value="<?= (int) $minDwell ?>" style="margin-top:.25rem;padding:.35rem .5rem;border-radius:6px;border:1px solid #3f4a54;background:#0f1419;color:#fafaf9;max-width:8rem">
    </div>
    <div>
      <label for="vu">Viewer account</label><br>
      <select id="vu" name="user" style="margin-top:.25rem;padding:.35rem .5rem;border-radius:6px;border:1px solid #3f4a54;background:#0f1419;color:#fafaf9;min-width:14rem">
        <option value="0">— Aggregate only —</option>
        <?php foreach ($viewers as $vu): ?>
          <option value="<?= (int) $vu['id'] ?>" <?= $filterUid === (int) $vu['id'] ? ' selected' : '' ?>><?= moun_h((string) $vu['email']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div><button type="submit" class="moun-btn--sm">Apply</button></div>
  </form>

  <?php if ($tablesOk): ?>
  <h3 class="moun-panel__title" style="font-size:1rem;margin:1.5rem 0 .5rem">Peaking interest (all viewers)</h3>
  <p class="moun-panel__sub" style="margin-bottom:.75rem">Ranked by total dwell and distinct viewers with at least the minimum dwell per event.</p>
  <div style="display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));margin-bottom:1.5rem">
    <div>
      <p style="font-weight:600;margin:0 0 .35rem;font-size:.85rem">Sections</p>
      <div class="moun-table-scroll">
        <table class="moun-data-table">
          <thead><tr><th>Section</th><th>Total (s)</th><th>Users</th></tr></thead>
          <tbody>
          <?php foreach ($hotSections as $r): ?>
            <tr>
              <td class="moun-mono"><?= moun_h((string) $r['target_key']) ?></td>
              <td><?= moun_h(number_format(((int) $r['total_ms']) / 1000, 1)) ?></td>
              <td><?= (int) $r['uniq_users'] ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (count($hotSections) === 0): ?><tr><td colspan="3">No qualifying events.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div>
      <p style="font-weight:600;margin:0 0 .35rem;font-size:.85rem">Images</p>
      <div class="moun-table-scroll">
        <table class="moun-data-table">
          <thead><tr><th>Target</th><th>Total (s)</th><th>Users</th></tr></thead>
          <tbody>
          <?php foreach ($hotImages as $r): ?>
            <tr>
              <td class="moun-mono" style="max-width:12rem;word-break:break-all"><?= moun_h((string) $r['target_key']) ?></td>
              <td><?= moun_h(number_format(((int) $r['total_ms']) / 1000, 1)) ?></td>
              <td><?= (int) $r['uniq_users'] ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (count($hotImages) === 0): ?><tr><td colspan="3">No qualifying events.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <?php if ($filterUid > 0 && $coveragePct !== null): ?>
    <h3 class="moun-panel__title" style="font-size:1rem;margin:1.5rem 0 .5rem">Selected viewer</h3>
    <p class="moun-panel__sub">Catalog section coverage (≥ min dwell): <strong><?= moun_h((string) $coveragePct) ?>%</strong></p>
    <div style="display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));margin:1rem 0">
      <div>
        <p style="font-weight:600;margin:0 0 .35rem;font-size:.85rem">Sections</p>
        <div class="moun-table-scroll">
          <table class="moun-data-table">
            <thead><tr><th>Section</th><th>Total (s)</th><th>Last seen</th></tr></thead>
            <tbody>
            <?php foreach ($perUserSections as $r): ?>
              <tr>
                <td class="moun-mono"><?= moun_h((string) $r['target_key']) ?></td>
                <td><?= moun_h(number_format(((int) $r['total_ms']) / 1000, 1)) ?></td>
                <td class="moun-mono"><?= moun_h((string) $r['last_seen']) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (count($perUserSections) === 0): ?><tr><td colspan="3">No qualifying section dwell.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      <div>
        <p style="font-weight:600;margin:0 0 .35rem;font-size:.85rem">Top images</p>
        <div class="moun-table-scroll">
          <table class="moun-data-table">
            <thead><tr><th>Target</th><th>Total (s)</th></tr></thead>
            <tbody>
            <?php foreach ($perUserImages as $r): ?>
              <tr>
                <td class="moun-mono" style="max-width:12rem;word-break:break-all"><?= moun_h((string) $r['target_key']) ?></td>
                <td><?= moun_h(number_format(((int) $r['total_ms']) / 1000, 1)) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (count($perUserImages) === 0): ?><tr><td colspan="2">No qualifying image dwell.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php if (count($notSeenSections) > 0 || count($notSeenImages) > 0): ?>
      <div style="display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));margin-top:1rem">
        <?php if (count($notSeenSections) > 0): ?>
          <div class="moun-panel" style="padding:.75rem 1rem">
            <p style="font-weight:600;margin:0 0 .35rem;font-size:.82rem">Sections not seen (in range)</p>
            <ul style="margin:0;padding-left:1.1rem;font-size:.78rem;font-family:ui-monospace,monospace">
              <?php foreach ($notSeenSections as $ns): ?>
                <li><?= moun_h($ns) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>
        <?php if (count($notSeenImages) > 0): ?>
          <div class="moun-panel" style="padding:.75rem 1rem">
            <p style="font-weight:600;margin:0 0 .35rem;font-size:.82rem">Catalog images not seen</p>
            <ul style="margin:0;padding-left:1.1rem;font-size:.72rem;font-family:ui-monospace,monospace;word-break:break-all">
              <?php foreach ($notSeenImages as $ni): ?>
                <li><?= moun_h($ni) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
  <?php endif; ?>
</section>
<?php
echo moun_layout('Interest analytics', ob_get_clean(), $user, 'admin', 'interest');
