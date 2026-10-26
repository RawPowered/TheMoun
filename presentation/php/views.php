<?php

declare(strict_types=1);

function moun_h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

/**
 * Official wordmark when `public/brand/moun-logo-fitted.png` (or official) exists; else favicon mark.
 *
 * @param 'aside'|'card' $variant
 */
function moun_ui_brand_logo(string $variant = 'aside'): string
{
    $root = moun_root_path();
    $alt = 'Moun';
    $fitted = $root . DIRECTORY_SEPARATOR . 'brand' . DIRECTORY_SEPARATOR . 'moun-logo-fitted.png';
    $official = $root . DIRECTORY_SEPARATOR . 'brand' . DIRECTORY_SEPARATOR . 'moun-logo-official.png';
    $cls = $variant === 'card' ? 'moun-brand-logo moun-brand-logo--card' : 'moun-brand-logo moun-brand-logo--aside';

    if (is_readable($fitted)) {
        $src = moun_h(moun_url('/brand/moun-logo-fitted.png'));

        return '<div class="' . $cls . '"><img class="moun-brand-logo__img" src="' . $src . '" alt="' . moun_h($alt) . '" width="220" height="72" decoding="async" fetchpriority="high"></div>';
    }
    if (is_readable($official)) {
        $src = moun_h(moun_url('/brand/moun-logo-official.png'));

        return '<div class="' . $cls . '"><img class="moun-brand-logo__img" src="' . $src . '" alt="' . moun_h($alt) . '" width="220" height="72" decoding="async" fetchpriority="high"></div>';
    }
    $src = moun_h(moun_url('/favicon.svg'));

    return '<div class="' . $cls . ' moun-brand-logo--fallback"><img class="moun-brand-logo__img moun-brand-logo__img--fallback" src="' . $src . '" alt="' . moun_h($alt) . '" width="32" height="32" decoding="async"></div>';
}

/**
 * @param 'card'|'admin'|'login' $surface Card = auth/onboarding; login = refined sign-in shell; admin = deal-room console
 * @param null|'home'|'users'|'logins'|'nda'|'interest'|'patches'|'account' $adminNav Active admin nav id
 */
function moun_layout(string $title, string $innerHtml, ?array $user = null, string $surface = 'card', ?string $adminNav = null): string
{
    $cfg = moun_config();
    $brandShort = moun_h((string) ($cfg['BRAND_SHORT_NAME'] ?? 'Moun'));
    $brandLegal = moun_h((string) ($cfg['BRAND_LEGAL_NAME'] ?? 'The Moun'));
    $who = $user ? moun_h((string) ($user['email'] ?? '')) : '';
    $fontHref = 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap';

    $stylesheet = moun_layout_stylesheet_link();

    if ($surface === 'admin' && $user && moun_user_is_admin($user)) {
        $nav = static function (string $id, string $href, string $label, string $hint) use ($adminNav): string {
            $active = $adminNav === $id ? ' aria-current="page"' : '';
            $cls = $adminNav === $id ? 'moun-admin-nav__a moun-admin-nav__a--active' : 'moun-admin-nav__a';
            $fullHref = str_starts_with($href, '/')
                ? moun_h(moun_url($href))
                : moun_h(moun_url('/admin/' . $href));

            return '<li><a class="' . $cls . '" href="' . $fullHref . '"' . $active . '><span class="moun-admin-nav__label">' . moun_h($label) . '</span><span class="moun-admin-nav__hint">' . moun_h($hint) . '</span></a></li>';
        };

        $navHtml = '<ul class="moun-admin-nav">'
            . $nav('home', 'index.php', 'Overview', 'Dashboard')
            . $nav('users', 'users.php', 'Access', 'Users & invites')
            . $nav('logins', 'logins.php', 'Security', 'Login audit')
            . $nav('nda', 'nda_acceptances.php', 'Legal', 'NNN signatures')
            . $nav('interest', 'interest_analytics.php', 'Interest', 'Section & image dwell')
            . $nav('patches', 'update_patches.php', 'Updates', 'Sync SQL')
            . $nav('account', '/profile.php', 'Account', 'Profile & security')
            . '</ul>';

        $appUrl = moun_h(moun_url('/index.php'));
        $profileUrl = moun_h(moun_url('/profile.php'));
        $logoutUrl = moun_h(moun_url('/logout.php'));

        $userBlock = '<div class="moun-admin-user">'
            . '<span class="moun-admin-user__email" title="' . $who . '">' . $who . '</span>'
            . '<span class="moun-admin-user__badge">Administrator</span>'
            . '<div class="moun-admin-user__links">'
            . '<a href="' . $appUrl . '">Build guide</a>'
            . '<a href="' . $profileUrl . '">Account</a>'
            . '<a href="' . $logoutUrl . '">Sign out</a>'
            . '</div></div>';

        $body = '<div class="moun-admin">'
            . '<aside class="moun-admin-aside" aria-label="Admin navigation">'
            . '<div class="moun-admin-brand">'
            . '<a class="moun-admin-brand__link" href="' . moun_h(moun_url('/admin/index.php')) . '">'
            . moun_ui_brand_logo('aside')
            . '<span class="moun-admin-brand__sub">Investor console</span>'
            . '</a>'
            . '<p class="moun-admin-brand__legal">' . $brandLegal . '</p>'
            . '</div>'
            . $navHtml
            . '<div class="moun-admin-aside__foot">'
            . '<span class="moun-admin-status"><span class="moun-admin-status__dot"></span> Confidential</span>'
            . '</div>'
            . '</aside>'
            . '<div class="moun-admin-main">'
            . '<header class="moun-admin-topbar">'
            . '<h1 class="moun-admin-topbar__title">' . moun_h($title) . '</h1>'
            . $userBlock
            . '</header>'
            . '<div class="moun-admin-content"><div class="moun-admin-content-inner">' . $innerHtml . '</div></div>'
            . '</div></div>';
    } else {
        $navGuest = '';
        if ($user) {
            $navGuest = '<nav class="moun-card-nav">'
                . '<a href="' . moun_h(moun_url('/index.php')) . '">Build guide</a>';
            if (moun_user_is_admin($user)) {
                $navGuest .= '<a href="' . moun_h(moun_url('/admin/index.php')) . '">Admin</a>';
            }
            $navGuest .= '<a href="' . moun_h(moun_url('/profile.php')) . '">Account</a>'
                . '<a href="' . moun_h(moun_url('/logout.php')) . '">Sign out</a></nav>';
        }
        $isLogin = $surface === 'login';
        $shellClass = 'moun-card-shell' . ($isLogin ? ' moun-card-shell--login' : '');
        $cardClass = 'moun-card' . ($isLogin ? ' moun-card--login' : '');
        $body = '<div class="' . moun_h($shellClass) . '">'
            . '<div class="' . moun_h($cardClass) . '">'
            . '<div class="moun-card__brand">'
            . moun_ui_brand_logo('card')
            . '<span class="moun-card__sub">' . $brandShort . '</span>'
            . '</div>'
            . '<h1 class="moun-card__title">' . moun_h($title) . '</h1>'
            . ($who ? '<p class="moun-card__who">Signed in as <strong>' . $who . '</strong></p>' : '')
            . $navGuest
            . '<div class="moun-card__body">' . $innerHtml . '</div>'
            . '</div></div>';
    }

    $bodyClassFinal = $surface === 'login' ? 'moun-body moun-body--card moun-body--login' : 'moun-body moun-body--' . moun_h($surface);

    return '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">'
        . '<meta name="theme-color" content="#1a1f26">'
        . '<title>' . moun_h($title) . ' — ' . $brandShort . '</title>'
        . '<link rel="preconnect" href="https://fonts.googleapis.com">'
        . '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
        . '<link href="' . moun_h($fontHref) . '" rel="stylesheet">'
        . $stylesheet
        . '</head><body class="' . moun_h($bodyClassFinal) . '">'
        . $body
        . '</body></html>';
}

/**
 * External stylesheet (same origin) so UI survives hosts that strip inline style blocks.
 */
function moun_layout_stylesheet_link(): string
{
    $path = __DIR__ . DIRECTORY_SEPARATOR . 'moun-ui.css';
    if (!is_readable($path)) {
        return '<style>body{font-family:system-ui,sans-serif;padding:2rem;background:#1a1f26;color:#fafaf9}code{background:#0003;padding:.2rem}</style>';
    }
    $v = rawurlencode((string) filemtime($path));

    return '<link rel="stylesheet" href="' . moun_h(moun_url('/php/moun-ui.css?v=' . $v)) . '">';
}
