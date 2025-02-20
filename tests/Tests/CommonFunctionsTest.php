<?php

namespace Tests;

require __DIR__ . '/../../includes/common.php';

use PHPUnit\Framework\TestCase;

class CommonFunctionsTest extends TestCase {
    public function testSecurityTokenLength(): void {
        $lengths = [10, 20, 30, 40];
        foreach ($lengths as $length) {
            $token = security_token($length);
            $this->assertEquals($length, strlen($token), "Token should be exactly $length characters long");
            $this->assertMatchesRegularExpression('/^[a-f0-9]+$/', $token, "Token should be hexadecimal");
        }
    }

    public function testSecurityTokenUniqueness(): void {
        $tokens = array_map(fn() => security_token(), range(1, 100));
        $uniqueTokens = array_unique($tokens);
        $this->assertEquals(count($tokens), count($uniqueTokens), "All generated tokens should be unique");
    }
}
