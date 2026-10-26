<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$migrationFile = __DIR__ . '/php/install_schema.sql';
if (!is_readable($migrationFile)) {
    $migrationFile = $root . '/deploy/migrations/20260412_moun_investor_auth.sql';
}
$marker = __DIR__ . '/php/.installed';
$configTarget = __DIR__ . '/php/config.local.php';

require_once __DIR__ . '/php/sql_split.php';

function moun_install_run_sql(PDO $pdo, string $path): void
{
    $sql = file_get_contents($path);
    if ($sql === false) {
        throw new RuntimeException('Cannot read migration file');
    }
    foreach (moun_sql_split_statements($sql) as $stmt) {
        $pdo->exec($stmt);
    }
}

if (is_file($marker) && is_readable($configTarget)) {
    http_response_code(403);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Installed</title></head><body style="font-family:sans-serif;padding:2rem;background:#1a1f26;color:#fafaf9">'
        . '<p>Install already completed. Remove <code>public/php/.installed</code> only if you intend to re-run setup (risky on production).</p>'
        . '</body></html>';
    exit;
}

$err = '';
$ok = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost = trim((string) ($_POST['db_host'] ?? ''));
    $dbPort = (int) ($_POST['db_port'] ?? 3306);
    $dbName = trim((string) ($_POST['db_name'] ?? ''));
    $dbUser = trim((string) ($_POST['db_user'] ?? ''));
    $dbPass = (string) ($_POST['db_pass'] ?? '');
    $publicOrigin = rtrim(trim((string) ($_POST['public_origin'] ?? '')), '/');
    $pathPrefix = trim((string) ($_POST['path_prefix'] ?? ''));
    $pathPrefix = '/' . trim($pathPrefix, '/');
    if ($pathPrefix === '/') {
        $pathPrefix = '';
    }
    $brandLegal = trim((string) ($_POST['brand_legal'] ?? 'The Moun'));
    $brandShort = trim((string) ($_POST['brand_short'] ?? 'Moun'));
    $adminEmail = strtolower(trim((string) ($_POST['admin_email'] ?? '')));
    $adminPass = (string) ($_POST['admin_password'] ?? '');
    $adminFirst = trim((string) ($_POST['admin_legal_first'] ?? ''));
    $adminLast = trim((string) ($_POST['admin_legal_last'] ?? ''));
    $trustForwarded = !empty($_POST['trust_forwarded']);

    if ($dbHost === '' || $dbName === '' || $dbUser === '' || $publicOrigin === '' || $adminEmail === '' || $adminPass === '' || $adminFirst === '' || $adminLast === '') {
        $err = 'Fill all required fields.';
    } elseif (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        $err = 'Invalid admin email.';
    } elseif (strlen($adminPass) < 12) {
        $err = 'Admin password must be at least 12 characters.';
    } elseif (!is_readable($migrationFile)) {
        $err = 'Migration file missing at deploy/migrations/20260412_moun_investor_auth.sql';
    } else {
        try {
            $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            moun_install_run_sql($pdo, $migrationFile);

            $nda = $pdo->query('SELECT id FROM nda_versions WHERE is_current = 1 ORDER BY id DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
            if (!$nda) {
                throw new RuntimeException('No current NNN version after migration');
            }
            $ndaId = (int) $nda['id'];

            $hash = password_hash($adminPass, PASSWORD_DEFAULT);
            $find = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $find->execute([$adminEmail]);
            $existing = $find->fetch(PDO::FETCH_ASSOC);
            if ($existing) {
                $adminId = (int) $existing['id'];
                $pdo->prepare(
                    'UPDATE users SET password_hash = ?, display_name = ?, legal_first_name = ?, legal_last_name = ?, role = ?, must_change_password = 0,
                     legal_identity_locked_at = COALESCE(legal_identity_locked_at, NOW()) WHERE id = ?'
                )->execute([
                    $hash,
                    $adminFirst . ' ' . $adminLast,
                    $adminFirst,
                    $adminLast,
                    'admin',
                    $adminId,
                ]);
            } else {
                $st = $pdo->prepare(
                    'INSERT INTO users (email, password_hash, display_name, legal_first_name, legal_last_name, role, legal_identity_locked_at, must_change_password)
                     VALUES (?,?,?,?,?,?,NOW(),0)'
                );
                $st->execute([
                    $adminEmail,
                    $hash,
                    $adminFirst . ' ' . $adminLast,
                    $adminFirst,
                    $adminLast,
                    'admin',
                ]);
                $adminId = (int) $pdo->lastInsertId();
            }

            $ndaRow = $pdo->query('SELECT * FROM nda_versions WHERE id = ' . (int) $ndaId)->fetch(PDO::FETCH_ASSOC);
            if (!$ndaRow) {
                throw new RuntimeException('NNN row missing');
            }
            $html = strtr((string) $ndaRow['body_html'], [
                '[[BRAND_LEGAL_NAME]]' => htmlspecialchars($brandLegal, ENT_QUOTES, 'UTF-8'),
                '[[BRAND_SHORT_NAME]]' => htmlspecialchars($brandShort, ENT_QUOTES, 'UTF-8'),
                '[[PARTY_FULL_NAME]]' => htmlspecialchars(trim($adminFirst . ' ' . $adminLast), ENT_QUOTES, 'UTF-8'),
                '[[VERSION_LABEL]]' => htmlspecialchars((string) $ndaRow['version_label'], ENT_QUOTES, 'UTF-8'),
                '[[EFFECTIVE_AT]]' => htmlspecialchars((string) $ndaRow['effective_at'], ENT_QUOTES, 'UTF-8'),
            ]);
            $snap = hash('sha256', $ndaRow['version_label'] . "\n" . $adminFirst . "\n" . $adminLast . "\n" . $html);
            $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
            $ua = mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 512);
            $hasAccept = $pdo->prepare(
                'SELECT 1 FROM nda_acceptances WHERE user_id = ? AND nda_version_id = ? LIMIT 1'
            );
            $hasAccept->execute([$adminId, $ndaId]);
            if (!$hasAccept->fetchColumn()) {
                $ins = $pdo->prepare(
                    'INSERT INTO nda_acceptances (user_id, nda_version_id, signature_text, signed_at, signer_declared_date, ip, user_agent, geo_country, geo_region, geo_city, geo_lat, geo_lon, content_snapshot_hash)
                     VALUES (?,?,?,NOW(),CURDATE(),?,?,?,?,?,?,?,?)'
                );
                $ins->execute([
                    $adminId,
                    $ndaId,
                    'Bootstrap install — ' . $adminFirst . ' ' . $adminLast,
                    $ip,
                    $ua,
                    null,
                    null,
                    null,
                    null,
                    null,
                    $snap,
                ]);
            }

            $export = "<?php\nreturn " . var_export([
                'db' => [
                    'host' => $dbHost,
                    'port' => $dbPort,
                    'name' => $dbName,
                    'user' => $dbUser,
                    'pass' => $dbPass,
                    'charset' => 'utf8mb4',
                ],
                'PUBLIC_ORIGIN' => $publicOrigin,
                'PATH_PREFIX' => $pathPrefix,
                'BRAND_LEGAL_NAME' => $brandLegal,
                'BRAND_SHORT_NAME' => $brandShort,
                'SESSION_NAME' => 'MOUNSESS',
                'SESSION_IDLE_SECONDS' => 1800,
                'SESSION_MAX_LIFETIME' => 86400,
                'GEOIP_ENABLED' => true,
                'TRUST_FORWARDED_FOR' => $trustForwarded,
            ], true) . ";\n";

            $configDir = dirname($configTarget);
            if (!is_dir($configDir) || !is_writable($configDir)) {
                throw new RuntimeException('Cannot write into php/ — check that the directory exists and is writable by the web server.');
            }
            $tmp = $configTarget . '.tmp.' . bin2hex(random_bytes(4));
            if (file_put_contents($tmp, $export) === false) {
                throw new RuntimeException('Failed writing temporary config file.');
            }
            if (is_file($configTarget)) {
                @unlink($configTarget);
            }
            if (!@rename($tmp, $configTarget)) {
                @unlink($tmp);
                if (file_put_contents($configTarget, $export, LOCK_EX) === false) {
                    throw new RuntimeException('Failed writing config.local.php (rename and direct write both failed). Check permissions.');
                }
            }
            @chmod($configTarget, 0640);
            $probe = @include $configTarget;
            if (!is_array($probe) || empty($probe['db']['host'])) {
                throw new RuntimeException('config.local.php was written but does not load as a valid PHP config array.');
            }
            if (file_put_contents($marker, date('c')) === false) {
                throw new RuntimeException('Could not write php/.installed marker.');
            }

            $ok = 'Install complete. Delete or protect install.php on production. Open login.php.';
        } catch (Throwable $e) {
            $err = 'Install failed: ' . $e->getMessage();
        }
    }
}

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Moun — Install</title>
  <style>
    body { font-family: system-ui, sans-serif; background: #1a1f26; color: #fafaf9; margin: 0; padding: 1.5rem; }
    .card { max-width: 36rem; margin: 0 auto; background: #272e36; border: 1px solid #3f4a54; border-radius: 12px; padding: 1.5rem; }
    label { display: block; margin-top: 0.75rem; font-size: 0.85rem; color: #a8a29e; }
    input { width: 100%; box-sizing: border-box; padding: 0.5rem; border-radius: 8px; border: 1px solid #57534e; background: #1c1917; color: #fafaf9; }
    button { margin-top: 1rem; padding: 0.6rem 1rem; border-radius: 8px; border: 0; background: #166534; color: #fff; font-weight: 600; cursor: pointer; }
    .err { color: #fecaca; margin-top: 0.5rem; }
    .ok { color: #bbf7d0; margin-top: 0.5rem; }
    code { background: #1c1917; padding: 0.1rem 0.3rem; border-radius: 4px; }
  </style>
</head>
<body>
<div class="card">
  <h1>Moun investor gate — install</h1>
  <p><small>Creates <code>php/config.local.php</code>, runs MySQL migration, seeds first admin (with bootstrap NNN acceptance). If a previous attempt created the admin user but failed before saving config, submit again with the <strong>same admin email</strong> — it will resume without duplicate errors.</small></p>
  <?php if ($err): ?><p class="err"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
  <?php if ($ok): ?><p class="ok"><?= htmlspecialchars($ok, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
  <form method="post">
    <h2>Database</h2>
    <label>Host <input name="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? '127.0.0.1', ENT_QUOTES, 'UTF-8') ?>" required></label>
    <label>Port <input name="db_port" type="number" value="<?= htmlspecialchars((string) ($_POST['db_port'] ?? '3306'), ENT_QUOTES, 'UTF-8') ?>" required></label>
    <label>Database name <input name="db_name" value="<?= htmlspecialchars($_POST['db_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required></label>
    <label>MySQL user <input name="db_user" value="<?= htmlspecialchars($_POST['db_user'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required></label>
    <label>MySQL password <input name="db_pass" type="password" value=""></label>
    <h2>Site</h2>
    <label>Public origin (no path) <input name="public_origin" placeholder="https://investors.example.com" value="<?= htmlspecialchars($_POST['public_origin'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required></label>
    <label>Path prefix (optional, e.g. <code>/moun</code>) <input name="path_prefix" value="<?= htmlspecialchars($_POST['path_prefix'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></label>
    <p><small>Must match Vite build: <code>set VITE_BASE_URL=/moun/&amp;&amp; npm run build</code> when using a prefix.</small></p>
    <label>Brand legal name <input name="brand_legal" value="<?= htmlspecialchars($_POST['brand_legal'] ?? 'The Moun', ENT_QUOTES, 'UTF-8') ?>" required></label>
    <label>Brand short name <input name="brand_short" value="<?= htmlspecialchars($_POST['brand_short'] ?? 'Moun', ENT_QUOTES, 'UTF-8') ?>" required></label>
    <label><input type="checkbox" name="trust_forwarded" value="1" <?= !empty($_POST['trust_forwarded']) ? 'checked' : '' ?>> Trust X-Forwarded-For (behind trusted reverse proxy)</label>
    <h2>First admin</h2>
    <label>Admin email <input name="admin_email" type="email" value="<?= htmlspecialchars($_POST['admin_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required></label>
    <label>Admin password (min 12) <input name="admin_password" type="password" required></label>
    <label>Admin legal first name <input name="admin_legal_first" value="<?= htmlspecialchars($_POST['admin_legal_first'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required></label>
    <label>Admin legal last name <input name="admin_legal_last" value="<?= htmlspecialchars($_POST['admin_legal_last'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required></label>
    <button type="submit">Install</button>
  </form>
</div>
</body>
</html>
