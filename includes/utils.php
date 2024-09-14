<?php

/**
 * generates up to 40 character cryptographically secure pseudo-random alphanumeric string
 *
 * If source of randomness cannot be found, if falls back to a less secure method
 *
 * @see https://stackoverflow.com/questions/48628985/is-it-cryptographically-secure-to-use-bin2hexrandom-bytesstr
 * @param int $length
 * @return string
 */
function generateSecurityToken(int $length = 40): string {
    /* 40 char max security token */
    try {
        return substr(bin2hex(random_bytes(20)), 0, $length);
    } catch (Throwable) {
        return substr(sha1(md5(mt_rand())), 0, $length);
    }
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
