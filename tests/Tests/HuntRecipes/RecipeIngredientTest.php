<?php

namespace Tests\HuntRecipes;

require __DIR__ . '/../../../includes/common.php';

use HuntRecipes\Database\SqlController;
use HuntRecipes\Ingredient;
use HuntRecipes\Recipe;
use HuntRecipes\RecipeIngredient;
use HuntRecipes\Test\SQLResultStub;
use PHPUnit\Framework\TestCase;

class RecipeIngredientTest extends TestCase {
    private SqlController $conn;
    private RecipeIngredient $recipeIngredient;

    protected function setUp(): void {
        $this->conn = $this->createMock(SqlController::class);
        $this->recipeIngredient = new RecipeIngredient(1, $this->conn);
    }

    public function testCreate(): void {
        $recipe = $this->createMock(Recipe::class);
        $recipe->id = 1;

        $ingredient = $this->createMock(Ingredient::class);
        $ingredient->id = 2;

        $mockResult = new SQLResultStub([]);

        $this->conn->expects($this->once())
            ->method('query')
            ->willReturn($mockResult);

        $recipeIngredient = RecipeIngredient::create(
            $this->conn,
            $recipe,
            $ingredient,
            'chopped',
            1,
            2.5,
            false
        );

        $this->assertEquals(1, $recipeIngredient->recipe_id);
        $this->assertEquals(2, $recipeIngredient->ingredient_id);
        $this->assertEquals('chopped', $recipeIngredient->ingredient_prep);
        $this->assertEquals(2.5, $recipeIngredient->amount);
    }
}
