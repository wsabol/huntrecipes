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
function security_token(int $length = 40): string {
    /* 40 char max security token */
    try {
        return substr(bin2hex(random_bytes(20)), 0, $length);
    } catch (Throwable) {
        return substr(sha1(md5(mt_rand())), 0, $length);
    }
}
