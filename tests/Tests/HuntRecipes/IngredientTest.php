<?php

namespace Tests\HuntRecipes;

require __DIR__ . '/../../../includes/common.php';

use HuntRecipes\Database\SqlController;
use HuntRecipes\Ingredient;
use PHPUnit\Framework\TestCase;
use HuntRecipes\Test\SQLResultStub;

class IngredientTest extends TestCase {
    private SqlController $conn;
    private Ingredient $ingredient;

    protected function setUp(): void {
        $this->conn = $this->createMock(SqlController::class);
        $this->ingredient = new Ingredient(1, $this->conn);
    }

    public function testCreateFromName(): void {
        $mockData = (object)[
            'id' => 1,
            'name' => 'salt',
            'name_plural' => 'salt'
        ];

        $mockResult = new SQLResultStub([$mockData]);

        $this->conn->expects($this->once())
            ->method('query')
            ->willReturn($mockResult);

        $ingredient = Ingredient::create_from_name('salt', $this->conn);

        $this->assertEquals(1, $ingredient->id);
        $this->assertEquals('salt', $ingredient->name);
    }

    public function testSaveToDb(): void {
        $mockResult = new SQLResultStub([(object)['id' => 1]]);

        $this->conn->expects($this->exactly(2))
            ->method('query')
            ->willReturn($mockResult);

        $this->ingredient->name = 'Test Ingredient';
        $this->ingredient->name_plural = 'Test Ingredients';

        $result = $this->ingredient->save_to_db();
        $this->assertTrue($result);
    }
}
