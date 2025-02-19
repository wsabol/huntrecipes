<?php

namespace Tests\HuntRecipes;

require __DIR__ . '/../../../includes/common.php';

use HuntRecipes\Autoloader;
use PHPUnit\Framework\TestCase;

class AutoloaderTest extends TestCase {
    public function testLoadExistingClass() {
        // Test case 1: Test loading an existing class
        $this->assertFalse(Autoloader::load('HuntRecipes\Autoloader'));
    }

    public function testLoadClassInPrefix() {
        // Test case 2: Test loading a class within the defined prefix
        $this->assertTrue(Autoloader::load('HuntRecipes\Recipe'));
        // Assuming 'SomeClass.php' exists within the IVRNode package
    }

    public function testLoadClassNotInPrefix() {
        // Test case 3: Test loading a class not within the defined prefix
        $this->assertFalse(Autoloader::load('PHPUnit\Framework\TestCase'));
    }

    public function testLoadNonExistingClass() {
        // Test case 4: Test loading a non-existing class
        $this->assertFalse(Autoloader::load('IVRNode\NonExistingClass'));
    }
}
