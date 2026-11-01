<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A cryptographically valid Firebase token whose user has since been
 * disabled, deleted, or had their sessions revoked.
 */
final class InactiveFirebaseIdentity extends RuntimeException {
}
