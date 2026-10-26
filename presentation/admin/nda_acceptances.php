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

$st = $pdo->query(
    'SELECT na.*, u.email, nv.version_label
     FROM nda_acceptances na
     JOIN users u ON u.id = na.user_id
     JOIN nda_versions nv ON nv.id = na.nda_version_id
     ORDER BY na.signed_at DESC LIMIT 500'
);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>
<section class="moun-panel">
  <div class="moun-panel__head">
    <div>
      <p class="moun-kicker">Legal evidence</p>
      <h2 class="moun-panel__title">NNN acceptance log</h2>
      <p class="moun-panel__sub">Immutable records: typed signature, declared date, server time, network context, and a hash of the agreement body shown at acceptance.</p>
    </div>
  </div>
  <div class="moun-table-scroll">
    <table class="moun-data-table">
      <thead>
        <tr>
          <th>Signed (UTC)</th>
          <th>Party</th>
          <th>Version</th>
          <th>Signature</th>
          <th>Declared</th>
          <th>IP</th>
          <th>Location</th>
          <th>Snapshot hash</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= moun_h((string) $r['signed_at']) ?></td>
          <td><strong><?= moun_h((string) $r['email']) ?></strong></td>
          <td><span class="moun-pill moun-pill--admin"><?= moun_h((string) $r['version_label']) ?></span></td>
          <td><?= moun_h((string) $r['signature_text']) ?></td>
          <td><?= moun_h((string) ($r['signer_declared_date'] ?? '—')) ?></td>
          <td class="moun-mono"><?= moun_h((string) $r['ip']) ?></td>
          <td><?php
            $gp = array_filter([(string) ($r['geo_country'] ?? ''), (string) ($r['geo_city'] ?? '')]);
            echo moun_h(count($gp) ? implode(' · ', $gp) : '—');
          ?></td>
          <td class="moun-mono" style="max-width:7rem;word-break:break-all"><?= moun_h((string) $r['content_snapshot_hash']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if (count($rows) === 0): ?>
    <p class="moun-panel__sub" style="margin-top:1rem">No acceptance rows yet.</p>
  <?php endif; ?>
</section>
<?php
echo moun_layout('NNN signatures', ob_get_clean(), $user, 'admin', 'nda');
