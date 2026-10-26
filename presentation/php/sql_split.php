<?php

declare(strict_types=1);

/**
 * Split SQL file contents into executable statements (skips -- comment-only lines).
 *
 * @return list<string>
 */
function moun_sql_split_statements(string $sql): array
{
    $out = [];
    $buf = '';
    foreach (preg_split('/\R/', $sql) as $line) {
        $t = trim($line);
        if ($t === '' || str_starts_with($t, '--')) {
            continue;
        }
        $buf .= $line . "\n";
        if (str_ends_with(rtrim($t), ';')) {
            $q = trim($buf);
            if ($q !== '') {
                $out[] = $q;
            }
            $buf = '';
        }
    }
    $q = trim($buf);
    if ($q !== '') {
        $out[] = $q;
    }
    return $out;
}
