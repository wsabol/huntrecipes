<?php

namespace Tests\HuntRecipes\Measure;

use HuntRecipes\Exception\HuntRecipesException;
use HuntRecipes\Measure\{Fraction, Measure, MeasureType};
use PHPUnit\Framework\TestCase;

class FractionTest extends TestCase {

    /**
     * @dataProvider provideFractionConstructorCases
     */
    public function testFractionConstruction($input, $expectedDecimal, $expectedString): void {
        $fraction = new Fraction($input);
        $this->assertEquals($expectedDecimal, $fraction->decimal, "Decimal value should match for input: $input");
        $this->assertEquals($expectedString, $fraction->toString(), "String representation should match for input: $input");
    }

    public static function provideFractionConstructorCases(): array {
        return [
            'Simple whole number' => [
                'input' => '5',
                'expectedDecimal' => 5.0,
                'expectedString' => '5'
            ],
            'Simple fraction' => [
                'input' => '1/2',
                'expectedDecimal' => 0.5,
                'expectedString' => '1/2'
            ],
            'Mixed number' => [
                'input' => '1-1/2',
                'expectedDecimal' => 1.5,
                'expectedString' => '1-1/2'
            ],
            'Complex mixed number' => [
                'input' => '2-3/4',
                'expectedDecimal' => 2.75,
                'expectedString' => '2-3/4'
            ],
            'Zero' => [
                'input' => '0',
                'expectedDecimal' => 0.0,
                'expectedString' => '0'
            ],
            'Negative fraction' => [
                'input' => '-1/2',
                'expectedDecimal' => -0.5,
                'expectedString' => '-1/2'
            ],
            'Negative mixed number' => [
                'input' => '-1-1/2',
                'expectedDecimal' => -1.5,
                'expectedString' => '-1-1/2'
            ]
        ];
    }

    public function testFractionOperations(): void {
        $cases = [
            // Common cooking fractions
            ['1/4', '1/4', '1/2', '0', '1/16', '1'],
            ['1/3', '1/3', '2/3', '0', '1/9', '1'],
            ['1/2', '1/2', '1', '0', '1/4', '1'],
            ['2/3', '1/3', '1', '1/3', '2/9', '2'],
            ['3/4', '1/4', '1', '1/2', '3/16', '3'],
            // Mixed numbers
            ['1-1/2', '1/2', '2', '1', '3/4', '3'],
            ['2-1/4', '1-3/4', '4', '1/2', '3-15/16', '1-2/7']
        ];

        foreach ($cases as [$a, $b, $sum, $diff, $prod, $quot]) {
            $f1 = new Fraction($a);
            $f2 = new Fraction($b);

            $this->assertEquals($sum, $f1->add($f2)->toString(), "Addition of $a + $b");
            $this->assertEquals($diff, $f1->subtract($f2)->toString(), "Subtraction of $a - $b");
            $this->assertEquals($prod, $f1->multiply($f2)->toString(), "Multiplication of $a * $b");
            $this->assertEquals($quot, $f1->divide($f2)->toString(), "Division of $a / $b");
        }
    }

    public function testFractionEquality(): void {
        $equivalentPairs = [
            ['1/2', '2/4'],
            ['1-1/2', '3/2'],
            ['2/3', '4/6'],
            ['1', '2/2'],
            ['0.5', '1/2']
        ];

        foreach ($equivalentPairs as [$a, $b]) {
            $f1 = new Fraction($a);
            $f2 = new Fraction($b);
            $this->assertTrue($f1->equals($f2), "$a should equal $b");
        }
    }

    public function testIsPartialAllowed(): void {
        $fractionAllowedTests = [
            ['1/4', ['1/4', '1/2', '3/4'], true],
            ['1/3', ['1/4', '1/2', '3/4'], false],
            ['1/2', ['1/4', '1/2', '3/4'], true],
            ['2/3', ['1/3', '2/3'], true],
            ['3/4', ['1/4', '1/2', '3/4'], true],
            ['1/8', ['1/4', '1/2', '3/4'], false]
        ];

        foreach ($fractionAllowedTests as [$fraction, $allowed, $expected]) {
            $f = new Fraction($fraction);
            $this->assertEquals(
                $expected,
                $f->is_partial_allowed($allowed),
                "$fraction " . ($expected ? "should" : "should not") . " be allowed in " . implode(', ', $allowed)
            );
        }
    }
}
