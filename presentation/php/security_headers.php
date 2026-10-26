<?php

declare(strict_types=1);

function moun_send_security_headers(): void
{
    if (headers_sent()) {
        return;
    }
    $cfg = moun_config();
    $origin = (string) ($cfg['PUBLIC_ORIGIN'] ?? '');
    $isHttps = str_starts_with($origin, 'https://');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: accelerometer=(), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=()');
    // Allow Vite-built scripts; adjust if you add inline scripts (use nonces then).
    header(
        "Content-Security-Policy: default-src 'self'; " .
        "script-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
        "font-src 'self' https://fonts.gstatic.com; " .
        "img-src 'self' data: blob: https://upload.wikimedia.org; " .
        "connect-src 'self'; " .
        "frame-ancestors 'none'; " .
        "base-uri 'self'; form-action 'self'"
    );
    if ($isHttps) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}
