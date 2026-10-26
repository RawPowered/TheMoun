<?php

declare(strict_types=1);

/**
 * @return array{country:?string,region:?string,city:?string,lat:?float,lon:?float}
 */
function moun_geo_lookup(string $ip): array
{
    $empty = ['country' => null, 'region' => null, 'city' => null, 'lat' => null, 'lon' => null];
    $cfg = moun_config();
    if (empty($cfg['GEOIP_ENABLED'])) {
        return $empty;
    }
    if ($ip === '127.0.0.1' || $ip === '::1' || str_starts_with($ip, '10.') || str_starts_with($ip, '192.168.')) {
        return $empty;
    }
    $url = 'http://ip-api.com/json/' . rawurlencode($ip) . '?fields=status,countryCode,regionName,city,lat,lon';
    $ctx = stream_context_create(['http' => ['timeout' => 2]]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) {
        return $empty;
    }
    $j = json_decode($raw, true);
    if (!is_array($j) || ($j['status'] ?? '') !== 'success') {
        return $empty;
    }
    return [
        'country' => isset($j['countryCode']) ? (string) $j['countryCode'] : null,
        'region' => isset($j['regionName']) ? (string) $j['regionName'] : null,
        'city' => isset($j['city']) ? (string) $j['city'] : null,
        'lat' => isset($j['lat']) ? (float) $j['lat'] : null,
        'lon' => isset($j['lon']) ? (float) $j['lon'] : null,
    ];
}
