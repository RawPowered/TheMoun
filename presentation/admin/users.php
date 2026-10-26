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

$err = '';
$ok = '';
$flashInvite = '';

function moun_user_has_any_nda(PDO $pdo, int $userId): bool
{
    $st = $pdo->prepare('SELECT 1 FROM nda_acceptances WHERE user_id = ? LIMIT 1');
    $st->execute([$userId]);
    return (bool) $st->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!moun_csrf_validate($_POST['_csrf'] ?? null)) {
        $err = 'Invalid CSRF token.';
    } elseif (isset($_POST['create'])) {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $fn = trim((string) ($_POST['legal_first'] ?? ''));
        $ln = trim((string) ($_POST['legal_last'] ?? ''));
        $role = ($_POST['role'] ?? 'viewer') === 'admin' ? 'admin' : 'viewer';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $fn === '' || $ln === '') {
            $err = 'Valid email and legal names required.';
        } else {
            $plain = bin2hex(random_bytes(24));
            $th = hash('sha256', $plain);
            $exp = (new DateTimeImmutable('+14 days'))->format('Y-m-d H:i:s');
            try {
                $st = $pdo->prepare(
                    'INSERT INTO users (email, password_hash, legal_first_name, legal_last_name, role, invite_token_hash, invite_expires_at, display_name)
                     VALUES (?,?,?,?,?,?,?,?)'
                );
                $st->execute([$email, null, $fn, $ln, $role, $th, $exp, $fn . ' ' . $ln]);
                $flashInvite = moun_url('/invite.php?t=' . $plain);
                $ok = 'User created. Copy the invite link below — it is shown only once.';
            } catch (PDOException $e) {
                if ((int) $e->errorInfo[1] === 1062) {
                    $err = 'Email already exists.';
                } else {
                    $err = $e->getMessage();
                }
            }
        }
    } elseif (isset($_POST['rotate_invite'])) {
        $id = (int) ($_POST['user_id'] ?? 0);
        $plain = bin2hex(random_bytes(24));
        $th = hash('sha256', $plain);
        $exp = (new DateTimeImmutable('+14 days'))->format('Y-m-d H:i:s');
        $pdo->prepare('UPDATE users SET invite_token_hash = ?, invite_expires_at = ? WHERE id = ?')->execute([$th, $exp, $id]);
        $flashInvite = moun_url('/invite.php?t=' . $plain);
        $ok = 'New invite link (shown once):';
    } elseif (isset($_POST['save_user'])) {
        $id = (int) ($_POST['user_id'] ?? 0);
        $fn = trim((string) ($_POST['legal_first'] ?? ''));
        $ln = trim((string) ($_POST['legal_last'] ?? ''));
        $role = ($_POST['role'] ?? 'viewer') === 'admin' ? 'admin' : 'viewer';
        if ($id <= 0 || $fn === '' || $ln === '') {
            $err = 'Invalid user or names.';
        } elseif (moun_user_has_any_nda($pdo, $id)) {
            $err = 'Cannot change legal name after an NNN acceptance exists.';
        } else {
            $pdo->prepare('UPDATE users SET legal_first_name = ?, legal_last_name = ?, role = ?, display_name = ? WHERE id = ?')->execute([
                $fn,
                $ln,
                $role,
                $fn . ' ' . $ln,
                $id,
            ]);
            $ok = 'User updated.';
        }
    }
}

$rows = $pdo->query(
    'SELECT u.*, (SELECT COUNT(*) FROM nda_acceptances na WHERE na.user_id = u.id) AS nda_cnt FROM users u ORDER BY u.id DESC'
)->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>
<?php if ($err): ?><p class="err"><?= moun_h($err) ?></p><?php endif; ?>
<?php if ($ok): ?><p class="ok"><?= moun_h($ok) ?></p><?php endif; ?>

<section class="moun-panel">
  <div class="moun-panel__head">
    <div>
      <p class="moun-kicker">Provisioning</p>
      <h2 class="moun-panel__title">Invite a partner</h2>
      <p class="moun-panel__sub">Legal first and last name are prefilled on the NNN agreement for the invitee. They sign, then choose a password — no public registration.</p>
    </div>
  </div>
  <form method="post">
    <input type="hidden" name="_csrf" value="<?= moun_h(moun_csrf_token()) ?>">
    <input type="hidden" name="create" value="1">
    <label>Work email <input name="email" type="email" required autocomplete="off" placeholder="name@organization.com"></label>
    <div class="moun-fieldrow">
      <label>Legal first <input name="legal_first" required placeholder="Given name"></label>
      <label>Legal last <input name="legal_last" required placeholder="Family name"></label>
    </div>
    <label>Access level
      <select name="role">
        <option value="viewer">Viewer — investor / partner review</option>
        <option value="admin">Administrator</option>
      </select>
    </label>
    <p style="margin-top:1.1rem"><button type="submit">Create account &amp; invite link</button></p>
  </form>
  <?php if ($flashInvite): ?>
    <p class="moun-kicker" style="margin-top:1.25rem">One-time invite URL</p>
    <div class="moun-invite-box"><a href="<?= moun_h($flashInvite) ?>"><?= moun_h($flashInvite) ?></a></div>
  <?php endif; ?>
</section>

<section class="moun-panel">
  <div class="moun-panel__head">
    <div>
      <p class="moun-kicker">Directory</p>
      <h2 class="moun-panel__title">Accounts</h2>
      <p class="moun-panel__sub">Edit legal names only before the first NNN acceptance. After signature, identity is locked for audit integrity.</p>
    </div>
  </div>
  <div class="moun-table-scroll">
    <table class="moun-data-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Email</th>
          <th>Legal identity</th>
          <th>Role</th>
          <th>NNN</th>
          <th>Access</th>
          <th>Invite</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <?php
        $role = (string) $r['role'];
        $pillRole = $role === 'admin' ? 'moun-pill moun-pill--admin' : 'moun-pill moun-pill--viewer';
        $ndaOk = (int) $r['nda_cnt'] > 0;
        $pillNda = $ndaOk ? 'moun-pill moun-pill--ok' : 'moun-pill moun-pill--wait';
        $hasPw = !empty($r['password_hash']);
        $pillPw = $hasPw ? 'moun-pill moun-pill--ok' : 'moun-pill moun-pill--warn';
        ?>
        <tr>
          <td><?= (int) $r['id'] ?></td>
          <td><strong><?= moun_h((string) $r['email']) ?></strong></td>
          <td><?= moun_h(trim($r['legal_first_name'] . ' ' . $r['legal_last_name'])) ?></td>
          <td><span class="<?= $pillRole ?>"><?= moun_h($role) ?></span></td>
          <td><span class="<?= $pillNda ?>"><?= $ndaOk ? 'Signed' : 'Pending' ?></span></td>
          <td><span class="<?= $pillPw ?>"><?= $hasPw ? 'Active' : 'Pre-password' ?></span></td>
          <td>
            <form method="post" style="margin:0">
              <input type="hidden" name="_csrf" value="<?= moun_h(moun_csrf_token()) ?>">
              <input type="hidden" name="user_id" value="<?= (int) $r['id'] ?>">
              <button type="submit" name="rotate_invite" value="1" class="moun-btn--sm moun-btn--ghost">Rotate link</button>
            </form>
          </td>
        </tr>
        <?php if ((int) $r['nda_cnt'] === 0): ?>
        <tr>
          <td colspan="7" style="background:linear-gradient(180deg,#fafaf9,#f4f3f0);padding:0">
            <form method="post" style="padding:1rem 1.25rem;display:flex;flex-wrap:wrap;gap:.75rem 1rem;align-items:flex-end">
              <input type="hidden" name="_csrf" value="<?= moun_h(moun_csrf_token()) ?>">
              <input type="hidden" name="user_id" value="<?= (int) $r['id'] ?>">
              <input type="hidden" name="save_user" value="1">
              <label style="margin:0;flex:1;min-width:6rem">Legal first <input name="legal_first" value="<?= moun_h((string) $r['legal_first_name']) ?>"></label>
              <label style="margin:0;flex:1;min-width:6rem">Legal last <input name="legal_last" value="<?= moun_h((string) $r['legal_last_name']) ?>"></label>
              <label style="margin:0;flex:0 0 10rem">Role
                <select name="role">
                  <option value="viewer" <?= $r['role'] === 'viewer' ? 'selected' : '' ?>>Viewer</option>
                  <option value="admin" <?= $r['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
              </label>
              <button type="submit" class="moun-btn--sm">Save</button>
            </form>
          </td>
        </tr>
        <?php endif; ?>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php
$inner = ob_get_clean();
echo moun_layout('Users & invites', $inner, $user, 'admin', 'users');
