<?php

namespace Tests\HuntRecipes;

require __DIR__ . '/../../../includes/common.php';

use HuntRecipes\Database\SqlController;
use HuntRecipes\RecipeType;
use PHPUnit\Framework\TestCase;

class RecipeTypeTest extends TestCase {
    private SqlController $conn;
    private RecipeType $recipeType;

    protected function setUp(): void {
        $this->conn = $this->createMock(SqlController::class);
        $this->recipeType = new RecipeType(1, $this->conn);
    }

    public function testList(): void {
        $mockResult = $this->createMock(\mysqli_result::class);
        $mockResult->method('fetch_object')
            ->willReturnOnConsecutiveCalls(
                (object)['id' => 1, 'name' => 'Breakfast', 'icon' => 'breakfast.png'],
                (object)['id' => 2, 'name' => 'Dinner', 'icon' => 'dinner.png'],
                null
            );

        $this->conn->expects($this->once())
            ->method('query')
            ->willReturn($mockResult);

        $types = RecipeType::list($this->conn, []);

        $this->assertCount(2, $types);
        $this->assertEquals('Breakfast', $types[0]->name);
    }
}
