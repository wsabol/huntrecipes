<?php

namespace HuntRecipes;

use HuntRecipes\Measure\Fraction;
use HuntRecipes\Measure\Measure;
use HuntRecipes\Measure\MeasureType;

class RecipeAmount {
    private float $amount;
    private float $amount_in_base_measurement;
    private Measure $measure;
    private const EPSILON = 0.0001;

    public function __construct(float $amount, int $measure) {
        $this->amount = $amount;
        $this->measure = Measure::create($measure);
        $this->amount_in_base_measurement = $amount * $this->measure->base_unit_conversion;
    }

    public function amount_formatted(): string {
        if ($this->amount <= 0) {
            return "";
        }

        if ($this->measure->measure_type === MeasureType::COUNT) {
            $f0 = new Fraction($this->amount);
            return $f0->toString();
        }

        // get all measurement units of this type
        $Measures = Measure::get_all_of_type($this->measure->measure_type, $this->measure->is_metric);
        // print_r($Measures);

        // sort largest to smallest
        usort($Measures, fn($a, $b) => $b->base_unit_conversion <=> $a->base_unit_conversion);

        $formatted_values = [];
        $remaining_base_amount = $this->amount_in_base_measurement;

        for ($i = 0; $i < count($Measures) && $remaining_base_amount > self::EPSILON; $i++) {
            $measure = $Measures[$i];

            $current_amount = $remaining_base_amount / $measure->base_unit_conversion;

            if (abs(floor($current_amount) - $current_amount) < self::EPSILON || count($measure->fractions_allowed) > 0) {

                // special case, is less than 1.25 pounds, continue to ounces
                if ($measure->id === 9 && $current_amount < 1.25) {
                    continue;
                }

                $f1 = new Fraction($current_amount);
                if (!$f1->is_partial_allowed($measure->fractions_allowed)) {
                    $current_amount = floor($current_amount);
                    $f1 = new Fraction($current_amount);
                }

                $remaining_base_amount -= ($f1->decimal * $measure->base_unit_conversion);

                if ($f1->decimal > self::EPSILON) {
                    $formatted_values[] = $f1->toString() . " " . $measure->abbr;
                }

                continue;
            }

            if ($i === count($Measures) - 1 && $current_amount > self::EPSILON) {
                if ($this->measure->measure_type === MeasureType::WEIGHT) {
                    $formatted_values[] = round($current_amount, 2) . " " . $measure->abbr;
                } else {
                    $f1 = new Fraction(round($current_amount));
                    if ($f1->decimal > self::EPSILON) {
                        $formatted_values[] = $f1->toString() . " " . $measure->abbr;
                    }
                }
            }

        }

        return implode(" + ", $formatted_values);
    }
}
