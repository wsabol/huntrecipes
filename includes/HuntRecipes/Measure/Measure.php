<?php

namespace HuntRecipes\Measure;

use HuntRecipes\Exception\HuntRecipesException;

class Measure {
    public const NONE = 0;
    public const TEASPOON = 1;
    public const TABLESPOON = 2;
    public const FLUID_OUNCE = 3;
    public const CUP = 4;
    public const PINT = 5;
    public const QUART = 6;
    public const GALLON = 7;
    public const OUNCE = 8;
    public const POUND = 9;
    public const PINCH = 10;
    public const DASH = 11;
    public const SMIDGEN = 12;
    public const SPRIG = 14;
    public const DROP = 15;
    public const LITER = 16;
    public const MILLILITER = 17;
    public const TAD = 18;

    public int $id;
    public string $name;
    public string $name_plural;
    public string $abbr;
    public string $abbr_alt;
    public int $measure_type;
    public float $base_unit_conversion;
    public bool $is_metric;
    public array $fractions;

    private const JSON_PATH = __DIR__ . "/../../../assets/measure.json";

    public function __construct(int $id) {
        $measures = json_decode(file_get_contents(self::JSON_PATH));

        $data = null;
        foreach ($measures as $m) {
            if ($m->id == $id) {
                $data = $m;
                break;
            }
        }

        if (empty($data)) {
            throw new HuntRecipesException("measure does not exist: $id");
        }

        $this->id = $data->id;
        $this->name = $data->name;
        $this->name_plural = $data->name_plural;
        $this->abbr = $data->abbr;
        $this->abbr_alt = @$data->abbr_alt ?? "";
        $this->measure_type = $data->measure_type;
        $this->base_unit_conversion = $data->base_unit_conversion;
        $this->is_metric = $data->is_metric;
        $this->fractions = @$data->fractions ?? [];
    }

    public static function get_all_of_type(int $measure_type): array {
        $measures = json_decode(file_get_contents(self::JSON_PATH));

        $return = [];
        foreach ($measures as $m) {
            if ((int)$m->measure_type === $measure_type) {
                $return[] = $m;
            }
        }

        return $return;
    }

    public function __set(string $name, $value): void {
        trigger_error("do not do this", E_USER_ERROR);
    }
}
