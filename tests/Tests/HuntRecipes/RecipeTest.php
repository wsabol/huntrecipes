<?php

namespace Tests\HuntRecipes;

require __DIR__ . '/../../../includes/common.php';

use HuntRecipes\Database\SqlController;
use HuntRecipes\Recipe;
use HuntRecipes\Test\SQLResultStub;
use PHPUnit\Framework\TestCase;

class RecipeTest extends TestCase {
    private SqlController $conn;
    private Recipe $recipe;

    protected function setUp(): void {
        $this->conn = $this->createMock(SqlController::class);
        $this->recipe = new Recipe(1, $this->conn);
    }

    public function testConstructorWithValidId(): void {
        $mockData = (object)[
            'course_id' => 1,
            'cuisine_id' => 2,
            'type_id' => 3,
            'chef_id' => 4,
            'title' => 'Test Recipe',
            'instructions' => 'Test Instructions',
            'image_filename' => 'test.jpg',
            'serving_count' => 4,
            'serving_measure_id' => 1,
            'parent_recipe_id' => 0,
            'published_flag' => true
        ];

        $mockResult = new SQLResultStub([$mockData]);

        $this->conn->expects($this->once())
            ->method('query')
            ->with($this->stringContains('select * from Recipe where id = 1'))
            ->willReturn($mockResult);

        $recipe = new Recipe(1, $this->conn);

        $this->assertEquals(1, $recipe->course_id);
        $this->assertEquals('Test Recipe', $recipe->title);
        $this->assertTrue($recipe->published_flag);
    }

    public function testGetInstructions(): void {
        $mockData = (object)[
            'course_id' => 1,
            'cuisine_id' => 2,
            'type_id' => 3,
            'chef_id' => 4,
            'title' => 'Test Recipe',
            'instructions' => "Test Instructions 1\nTestInstructions 2\n",
            'image_filename' => 'test.jpg',
            'serving_count' => 4,
            'serving_measure_id' => 1,
            'parent_recipe_id' => 0,
            'published_flag' => true
        ];

        $mockResult = new SQLResultStub([$mockData]);

        $this->conn->expects($this->once())
            ->method('query')
            ->with($this->stringContains('select * from Recipe where id = 1'))
            ->willReturn($mockResult);

        $recipe = new Recipe(1, $this->conn);

        $instructions = $recipe->get_instructions();
        $this->assertCount(2, $instructions);
    }

    public function testSaveToDb(): void {
        $mockData = (object)[
            'id' => 1,
        ];

        $mockResult = new SQLResultStub([$mockData]);

        $this->conn->expects($this->exactly(2))
            ->method('query')
            ->willReturn($mockResult);

        $this->recipe->title = 'New Recipe';
        $this->recipe->instructions = 'Test instructions';

        $result = $this->recipe->save_to_db();
        $this->assertTrue($result);
    }

    public function testDeleteFromDb(): void {
        $this->conn->expects($this->exactly(2))
            ->method('query')
            ->willReturn(true);

        $result = $this->recipe->delete_from_db();
        $this->assertTrue($result);
    }

    public function testOrganizeIngredientsIntoColumns(): void {
        $mainIngredientsLong = array_map(fn($i) => "ingredient$i", range(1, 15));
        $mainIngredientsShort = array_map(fn($i) => "ingredient$i", range(1, 7));
        $child1 = array_map(fn($i) => "child1_ingredient$i", range(1, 5));
        $child2 = array_map(fn($i) => "child2_ingredient$i", range(1, 3));

        // test 2 children, short
        $result = Recipe::organize_ingredients_into_columns($mainIngredientsShort, $child1, $child2);
        $this->assertCount(3, $result);
        $this->assertCount(7, $result[0]['items']);
        $this->assertCount(5, $result[1]['items']);
        $this->assertCount(3, $result[2]['items']);

        // test 2 children, long
        $result = Recipe::organize_ingredients_into_columns($mainIngredientsLong, $child1, $child2);
        $this->assertCount(3, $result);
        $this->assertCount(15, $result[0]['items']);
        $this->assertCount(5, $result[1]['items']);
        $this->assertCount(3, $result[2]['items']);

        // test 1 child, short
        $result = Recipe::organize_ingredients_into_columns($mainIngredientsShort, $child1, []);
        $this->assertCount(2, $result);
        $this->assertCount(7, $result[0]['items']);
        $this->assertCount(5, $result[1]['items']);

        // test 1 child, long
        $result = Recipe::organize_ingredients_into_columns($mainIngredientsLong, $child1, []);
        $this->assertCount(3, $result);
        $this->assertCount(10, $result[0]['items']);
        $this->assertCount(5, $result[1]['items']);
        $this->assertCount(5, $result[2]['items']);

        // test 0 children, short
        $result = Recipe::organize_ingredients_into_columns($mainIngredientsShort, [], []);
        $this->assertCount(2, $result);
        $this->assertCount(7, $result[0]['items']);
        $this->assertCount(0, $result[1]['items']);

        // test 0 children, long
        $result = Recipe::organize_ingredients_into_columns($mainIngredientsLong, [], []);
        $this->assertCount(2, $result);
        $this->assertCount(10, $result[0]['items']);
        $this->assertCount(5, $result[1]['items']);
    }
}
