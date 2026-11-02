<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\Measure;
use App\Models\Recipe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RecipeMeasurementsTest extends TestCase {
    use RefreshDatabase;

    public function testRecipeAndPrintShowFractionsAndExposeBaseAmountsForScaling(): void {
        $cup = Measure::forceCreate(['id' => 4, 'name' => 'cup', 'name_plural' => 'cups', 'abbr' => 'c', 'measure_type_id' => 1, 'general_unit_conversion' => 48]);
        $teaspoon = Measure::forceCreate(['id' => 1, 'name' => 'teaspoon', 'name_plural' => 'teaspoons', 'abbr' => 'tsp', 'measure_type_id' => 1, 'general_unit_conversion' => 1]);
        $recipe = Recipe::create(['title' => 'Fraction recipe', 'instructions' => 'Mix', 'published_flag' => true, 'serving_count' => 4]);
        foreach ([[$cup, 0.25], [$cup, 0.5], [$cup, 1.333], [$teaspoon, 0.5], [$cup, 0]] as $index => [$measure, $amount]) {
            $ingredient = Ingredient::create(['name' => 'ingredient ' . $index]);
            $recipe->ingredients()->create(['ingredient_id' => $ingredient->id, 'measure_id' => $measure->id, 'amount' => $amount]);
        }

        foreach ([route('recipes.show', $recipe->slug), route('recipes.print', $recipe->id)] as $url) {
            $response = $this->get($url)->assertOk();
            foreach (['1/4 c', '1/2 c', '1-1/3 c', '1/2 tsp', 'data-org-amount="12"'] as $expected) {
                $response->assertSee($expected, false);
            }
            preg_match('/<dt class="[^"]*no-amount"[^>]*>(.*?)<\/dt>/s', $response->getContent(), $blank);
            $this->assertSame('', trim($blank[1]));
        }
    }

    public function testMeasuresApiRestoresOriginalFractionRulesAndMetricFlags(): void {
        foreach ([1 => 'teaspoon', 4 => 'cup', 8 => 'ounce', 9 => 'pound', 16 => 'liter'] as $id => $name) {
            Measure::forceCreate(['id' => $id, 'name' => $name, 'name_plural' => $name . 's']);
        }
        $measures = collect($this->getJson('/api/v1/measures')->assertOk()->json('data'))->keyBy('id');
        $this->assertSame(['1/4', '1/3', '1/2', '2/3', '3/4'], $measures[4]['fractions_allowed']);
        $this->assertSame(['1/4', '1/2', '3/4'], $measures[1]['fractions_allowed']);
        $this->assertSame($measures[1]['fractions_allowed'], $measures[9]['fractions_allowed']);
        $this->assertSame([], $measures[8]['fractions_allowed']);
        $this->assertTrue($measures[16]['is_metric']);
        $this->assertFalse($measures[4]['is_metric']);
    }
}
