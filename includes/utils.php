<?php

function generateSecurityToken(int $length = 40): string {
    return substr(bin2hex(random_bytes($length)), $length);
}

function ColumnAlpha($index) {
    return ($index >= 26 ? ColumnAlpha(floor($index / 26) - 1) : '') . chr(($index % 26) + 65);
}

function str_starts_with(string $haystack, string $needle): bool {
    return strpos($haystack, $needle) === 0;
}

function str_ends_with(string $haystack, string $needle): bool {
    return substr($haystack, -strlen($needle)) === $needle;
}

function str_contains(string $haystack, string $needle): bool {
    return strpos($haystack, $needle) !== false;
}
