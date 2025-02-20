<?php

namespace Tests\HuntRecipes\Base;

require __DIR__ . '/../../../../includes/common.php';

use HuntRecipes\Base\Common_Object;
use HuntRecipes\Database\SqlController;
use PHPUnit\Framework\TestCase;

class CommonObjectTest extends TestCase {
    public function testToObject(): void {
        $obj = new TestObject();
        $result = $obj->toObject();

        // Public properties should be included
        $this->assertTrue(property_exists($result, 'id'));
        $this->assertTrue(property_exists($result, 'name'));

        // Private properties should be excluded
        $this->assertFalse(property_exists($result, 'private_field'));
        $this->assertFalse(property_exists($result, 'conn'));

        // Values should match
        $this->assertEquals(1, $result->id);
        $this->assertEquals("Test", $result->name);
    }
}

class TestObject extends Common_Object {
    private SqlController $conn;
    public int $id;
    public string $name;
    private string $private_field;

    public function __construct() {
        $this->id = 1;
        $this->name = "Test";
        $this->private_field = "private";
    }

    protected function exists_in_db(): bool {
        return true;
    }

    protected function update_from_db(): void {}

    public function save_to_db(): bool {
        return true;
    }

    public function delete_from_db(): bool {
        return true;
    }

    public static function list(SqlController $conn, array $props): array {
        return [];
    }
}
