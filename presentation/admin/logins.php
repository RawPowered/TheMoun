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

$uid = (int) ($_GET['user'] ?? 0);
$sql = 'SELECT le.*, u.email FROM login_events le JOIN users u ON u.id = le.user_id';
$args = [];
if ($uid > 0) {
    $sql .= ' WHERE le.user_id = ?';
    $args[] = $uid;
}
$sql .= ' ORDER BY le.occurred_at DESC LIMIT 500';
$st = $pdo->prepare($sql);
$st->execute($args);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

$uids = $pdo->query('SELECT id, email FROM users ORDER BY id DESC LIMIT 40')->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>
<section class="moun-panel">
  <div class="moun-panel__head">
    <div>
      <p class="moun-kicker">Security telemetry</p>
      <h2 class="moun-panel__title">Successful sign-ins</h2>
      <p class="moun-panel__sub">Each row is a successful authentication. Geo is derived server-side from IP (VPNs and mobile carriers may skew location).</p>
    </div>
  </div>
  <p class="moun-panel__sub" style="margin-bottom:.75rem"><strong>Filter by account</strong></p>
  <div style="display:flex;flex-wrap:wrap;gap:.4rem .5rem;align-items:center;margin-bottom:1rem;font-size:.78rem;">
    <a class="moun-pill moun-pill--viewer" href="<?= moun_h(moun_url('/admin/logins.php')) ?>" style="text-decoration:none;color:inherit;<?= $uid === 0 ? 'outline:2px solid var(--moun-forest-bright);outline-offset:2px' : '' ?>">All users</a>
    <?php foreach ($uids as $u): ?>
      <a class="moun-pill moun-pill--viewer" href="<?= moun_h(moun_url('/admin/logins.php?user=' . (int) $u['id'])) ?>" style="text-decoration:none;color:inherit;<?= $uid === (int) $u['id'] ? 'outline:2px solid var(--moun-forest-bright);outline-offset:2px' : '' ?>"><?= moun_h((string) $u['email']) ?></a>
    <?php endforeach; ?>
  </div>
  <div class="moun-table-scroll">
    <table class="moun-data-table">
      <thead>
        <tr>
          <th>Time (server)</th>
          <th>User</th>
          <th>IP</th>
          <th>Country</th>
          <th>City</th>
          <th>User-Agent</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= moun_h((string) $r['occurred_at']) ?></td>
          <td><strong><?= moun_h((string) $r['email']) ?></strong></td>
          <td class="moun-mono"><?= moun_h((string) $r['ip']) ?></td>
          <td><?= moun_h((string) ($r['geo_country'] ?? '—')) ?></td>
          <td><?= moun_h((string) ($r['geo_city'] ?? '—')) ?></td>
          <td class="moun-mono" style="max-width:14rem"><?= moun_h(mb_substr((string) $r['user_agent'], 0, 120)) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if (count($rows) === 0): ?>
    <p class="moun-panel__sub" style="margin-top:1rem">No login events yet for this filter.</p>
  <?php endif; ?>
</section>
<?php
echo moun_layout('Login audit', ob_get_clean(), $user, 'admin', 'logins');
