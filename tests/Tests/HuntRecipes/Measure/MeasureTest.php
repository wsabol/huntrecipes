<?php

namespace Tests\HuntRecipes\Measure;

use HuntRecipes\Exception\HuntRecipesException;
use HuntRecipes\Measure\Measure;
use HuntRecipes\Measure\MeasureType;
use PHPUnit\Framework\TestCase;

class MeasureTest extends TestCase {
    public function testCreateMeasureAll(): void {
        foreach (range(0, 18) as $id) {
            if ($id === 13) continue; // Skip ID 13 as it's not in the JSON

            try {
                $measure = Measure::create($id);
                $this->assertInstanceOf(Measure::class, $measure);
                $this->assertEquals($id, $measure->id);
            } catch (HuntRecipesException $e) {
                $this->fail("Failed to create measure with ID $id: " . $e->getMessage());
            }
        }
    }

    public function testGetAllOfType(): void {
        // Test Volume measures
        $volumeMeasures = Measure::get_all_of_type(MeasureType::VOLUME, false);
        $this->assertNotEmpty($volumeMeasures);
        foreach ($volumeMeasures as $measure) {
            $this->assertEquals(MeasureType::VOLUME, $measure->measure_type);
            $this->assertFalse($measure->is_metric);
        }

        // Test Weight measures
        $weightMeasures = Measure::get_all_of_type(MeasureType::WEIGHT, false);
        $this->assertNotEmpty($weightMeasures);
        foreach ($weightMeasures as $measure) {
            $this->assertEquals(MeasureType::WEIGHT, $measure->measure_type);
            $this->assertFalse($measure->is_metric);
        }

        // Test Metric measures
        $metricMeasures = Measure::get_all_of_type(MeasureType::VOLUME, true);
        $this->assertNotEmpty($metricMeasures);
        foreach ($metricMeasures as $measure) {
            $this->assertTrue($measure->is_metric);
        }
    }

    public function testMeasureProperties(): void {
        $teaspoon = Measure::create(Measure::TEASPOON);
        $this->assertEquals('teaspoon', $teaspoon->name);
        $this->assertEquals('teaspoons', $teaspoon->name_plural);
        $this->assertEquals('tsp', $teaspoon->abbr);
        $this->assertEquals('t', $teaspoon->abbr_alt);
        $this->assertEquals(MeasureType::VOLUME, $teaspoon->measure_type);
        $this->assertEquals(1.0, $teaspoon->base_unit_conversion);
        $this->assertFalse($teaspoon->is_metric);
        $this->assertEquals(['1/4', '1/2', '3/4'], $teaspoon->fractions_allowed);
    }
}
