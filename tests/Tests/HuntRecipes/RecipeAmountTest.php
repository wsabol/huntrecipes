<?php

namespace Tests\HuntRecipes;

use HuntRecipes\Measure\Measure;
use HuntRecipes\RecipeAmount;
use PHPUnit\Framework\TestCase;

class RecipeAmountTest extends TestCase {

    /**
     * @dataProvider provideAmountFormattedCases
     */
    public function testAmountFormatted(float $amount, int $measureId, string $expectedFormat): void {
        $recipeAmount = new RecipeAmount($amount, $measureId);
        $this->assertEquals($expectedFormat, $recipeAmount->amount_formatted());
    }

    public static function provideAmountFormattedCases(): array {
        return [
            'Zero amount' => [
                'amount' => 0,
                'measureId' => Measure::TEASPOON,
                'expectedFormat' => ''
            ],
            'Simple teaspoon' => [
                'amount' => 1,
                'measureId' => Measure::TEASPOON,
                'expectedFormat' => '1 tsp'
            ],
            'Half teaspoon' => [
                'amount' => 0.5,
                'measureId' => Measure::TEASPOON,
                'expectedFormat' => '1/2 tsp'
            ],
            'One and half teaspoons' => [
                'amount' => 1.5,
                'measureId' => Measure::TEASPOON,
                'expectedFormat' => '1/2 tbsp'
            ],
            'Three tablespoons' => [
                'amount' => 3,
                'measureId' => Measure::TABLESPOON,
                'expectedFormat' => '3 tbsp'
            ],
            'Quarter cup' => [
                'amount' => 0.25,
                'measureId' => Measure::CUP,
                'expectedFormat' => '1/4 c'
            ],
            'One pound' => [
                'amount' => 1,
                'measureId' => Measure::POUND,
                'expectedFormat' => '16 oz'
            ],
            'Half pound' => [
                'amount' => 0.5,
                'measureId' => Measure::POUND,
                'expectedFormat' => '8 oz'
            ],
            'One ounce' => [
                'amount' => 1,
                'measureId' => Measure::OUNCE,
                'expectedFormat' => '1 oz'
            ],
            // Complex conversions
            'Two and half cups' => [
                'amount' => 2.5,
                'measureId' => Measure::CUP,
                'expectedFormat' => '2-1/2 c'
            ],
            'Small volume' => [
                'amount' => 0.0625,
                'measureId' => Measure::TEASPOON,
                'expectedFormat' => '1 pinch'
            ],
            'Count measure' => [
                'amount' => 2,
                'measureId' => Measure::NONE,
                'expectedFormat' => '2'
            ],
            // Metric measures
            'One liter' => [
                'amount' => 1,
                'measureId' => Measure::LITER,
                'expectedFormat' => '1 L'
            ],
            'Fifty milliliters' => [
                'amount' => 50,
                'measureId' => Measure::MILLILITER,
                'expectedFormat' => '50 mL'
            ]
        ];
    }

    public function testComplexConversions(): void {
        $cases = [
            // Converting between units
            [16, Measure::TABLESPOON, '1 c'],  // 16 tbsp = 1 cup
            [8, Measure::TABLESPOON, '1/2 c'],     // 1/2 cup = 8 tbsp
            [2.5, Measure::POUND, '2-1/2 lb'], // 2.5 pounds = 2 pounds 8 ounces
            [1.25, Measure::CUP, '1-1/4 c'],   // 1.25 cups stays as mixed number
            [0.125, Measure::TEASPOON, '1 dash'], // Small fractions
            [0.0625, Measure::TEASPOON, '1 pinch'] // Very small amounts convert to pinch
        ];

        foreach ($cases as [$amount, $measureId, $expected]) {
            $recipeAmount = new RecipeAmount($amount, $measureId);
            $this->assertEquals($expected, $recipeAmount->amount_formatted(),
                "Converting $amount of measure $measureId");
        }
    }
}
