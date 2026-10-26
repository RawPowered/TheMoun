<?php



declare(strict_types=1);



require_once __DIR__ . '/php/bootstrap.php';



if (!moun_is_installed()) {

    header('Location: install.php', true, 302);

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

$user = moun_require_user($pdo);



$err = '';

$ok = '';

$locked = !empty($user['legal_identity_locked_at']);



if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!moun_csrf_validate($_POST['_csrf'] ?? null)) {

        $err = 'Invalid session token.';

    } else {

        $display = trim((string) ($_POST['display_name'] ?? ''));

        $cur = (string) ($_POST['current_password'] ?? '');

        $p1 = (string) ($_POST['new_password'] ?? '');

        $p2 = (string) ($_POST['new_password2'] ?? '');

        if ($p1 !== '' || $p2 !== '' || $cur !== '') {

            if ($cur === '' || !password_verify($cur, (string) $user['password_hash'])) {

                $err = 'Current password incorrect.';

            } elseif (strlen($p1) < 12) {

                $err = 'New password must be at least 12 characters.';

            } elseif ($p1 !== $p2) {

                $err = 'New passwords do not match.';

            } else {

                $hash = password_hash($p1, PASSWORD_DEFAULT);

                $pdo->prepare('UPDATE users SET display_name = ?, password_hash = ? WHERE id = ?')->execute([

                    $display !== '' ? $display : null,

                    $hash,

                    (int) $user['id'],

                ]);

                $ok = 'Your account has been updated.';

            }

        } else {

            $pdo->prepare('UPDATE users SET display_name = ? WHERE id = ?')->execute([

                $display !== '' ? $display : null,

                (int) $user['id'],

            ]);

            $ok = 'Your account has been updated.';

        }

        $st = $pdo->prepare('SELECT * FROM users WHERE id = ?');

        $st->execute([(int) $user['id']]);

        $user = $st->fetch(PDO::FETCH_ASSOC) ?: $user;

    }

}



$legalName = trim((string) ($user['legal_first_name'] ?? '') . ' ' . (string) ($user['legal_last_name'] ?? ''));

$legalNameDisp = $legalName !== '' ? moun_h($legalName) : '<span class="moun-profile-legal-empty">Not on file</span>';



$alerts = '';

if ($err !== '') {

    $alerts .= '<p class="err" role="alert">' . moun_h($err) . '</p>';

}

if ($ok !== '') {

    $alerts .= '<p class="ok" role="status">' . moun_h($ok) . '</p>';

}



$lockBadge = $locked

    ? '<span class="moun-profile-lock-badge" title="Legal identity is fixed after NNN acceptance">Locked</span>'

    : '';



$legalNote = $locked

    ? 'This name is recorded with your NNN acceptance and cannot be changed here. Contact an administrator if a correction is required.'

    : 'This is the legal name associated with your account.';



$form = '<div class="moun-profile">'

    . $alerts

    . '<form method="post" class="moun-profile__form">'

    . '<input type="hidden" name="_csrf" value="' . moun_h(moun_csrf_token()) . '">'



    . '<section class="moun-panel moun-profile-panel" aria-labelledby="moun-profile-legal-h">'

    . '<p class="moun-kicker">Legal identity</p>'

    . '<div class="moun-profile-panel__head">'

    . '<h2 id="moun-profile-legal-h" class="moun-panel__title">Registered name</h2>'

    . $lockBadge

    . '</div>'

    . '<p class="moun-profile-legal-name">' . $legalNameDisp . '</p>'

    . '<p class="moun-panel__sub">' . moun_h($legalNote) . '</p>'

    . '</section>'



    . '<section class="moun-panel" aria-labelledby="moun-profile-display-h">'

    . '<p class="moun-kicker">Workspace</p>'

    . '<h2 id="moun-profile-display-h" class="moun-panel__title">Display name</h2>'

    . '<p class="moun-panel__sub">Shown in the build guide and other signed-in surfaces. Leave blank to use your email.</p>'

    . '<label for="moun-display-name">Display name</label>'

    . '<input id="moun-display-name" name="display_name" value="' . moun_h((string) ($user['display_name'] ?? '')) . '" autocomplete="nickname" maxlength="120">'

    . '</section>'



    . '<section class="moun-panel" aria-labelledby="moun-profile-pass-h">'

    . '<p class="moun-kicker">Security</p>'

    . '<h2 id="moun-profile-pass-h" class="moun-panel__title">Password</h2>'

    . '<p class="moun-panel__sub">Leave the password fields empty if you only want to update your display name. New passwords must be at least 12 characters.</p>'

    . '<label for="moun-cur-pass">Current password</label>'

    . '<input id="moun-cur-pass" type="password" name="current_password" autocomplete="current-password">'

    . '<label for="moun-new-pass">New password</label>'

    . '<input id="moun-new-pass" type="password" name="new_password" minlength="12" autocomplete="new-password">'

    . '<label for="moun-new-pass2">Confirm new password</label>'

    . '<input id="moun-new-pass2" type="password" name="new_password2" minlength="12" autocomplete="new-password">'

    . '<p class="moun-profile-actions"><button type="submit" class="btn">Save changes</button></p>'

    . '</section>'



    . '</form></div>';



$isAdmin = moun_user_is_admin($user);

echo moun_layout('Account', $form, $user, $isAdmin ? 'admin' : 'card', $isAdmin ? 'account' : null);

