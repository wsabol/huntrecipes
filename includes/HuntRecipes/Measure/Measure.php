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
    public array $fractions_allowed;

    private const JSON_PATH = __DIR__ . "/../../../assets/measure.json";

    public static function create(int $measure_id): self {
        $measures = json_decode(file_get_contents(self::JSON_PATH));

        $data = null;
        foreach ($measures as $m) {
            if ($m->id == $measure_id) {
                $data = $m;
                break;
            }
        }

        if (empty($data)) {
            throw new HuntRecipesException("measure does not exist: $measure_id");
        }

        return new Measure(
            $data->id,
            $data->name,
            $data->name_plural,
            $data->abbr,
            $data->abbr_alt,
            $data->measure_type,
            $data->base_unit_conversion,
            $data->is_metric,
            $data->fractions_allowed,
        );
    }

    public static function format_name(int $measure_id, float $serving_count): string {
        $measure = new Measure(
            0,
            'serving',
            'servings',
            '',
            '',
            MeasureType::COUNT,
            1,
            false,
            ['1/4', '1/2', '3/4'],
        );

        if ($measure_id > 0) {
            try {
                $measure = self::create($measure_id);
            } catch (HuntRecipesException) {}
        }

        if ($serving_count > 1.0 || $serving_count === 0.0) {
            return $measure->name_plural;
        }
        return $measure->name;
    }

    public function __construct(
        int $id,
        string $name,
        string $name_plural,
        string $abbr,
        string $abbr_alt,
        int $measure_type,
        float $base_unit_conversion,
        bool $is_metric,
        array $fractions_allowed
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->name_plural = $name_plural;
        $this->abbr = $abbr;
        $this->abbr_alt = $abbr_alt;
        $this->measure_type = $measure_type;
        $this->base_unit_conversion = $base_unit_conversion;
        $this->is_metric = $is_metric;
        $this->fractions_allowed = $fractions_allowed;
    }

    /**
     * @param int $measure_type
     * @param bool $is_metric
     * @return Measure[]
     */
    public static function get_all_of_type(int $measure_type, bool $is_metric): array {
        $measures = json_decode(file_get_contents(self::JSON_PATH));

        $return = [];
        foreach ($measures as $m) {
            if ((int)$m->measure_type === $measure_type && (bool)$m->is_metric === $is_metric) {
                $return[] = new Measure(
                    $m->id,
                    $m->name,
                    $m->name_plural,
                    $m->abbr,
                    $m->abbr_alt,
                    $m->measure_type,
                    $m->base_unit_conversion,
                    $m->is_metric,
                    $m->fractions_allowed,
                );
            }
        }

        return $return;
    }

    /**
     * @return object[]
     */
    public static function list(): array {
        return json_decode(file_get_contents(self::JSON_PATH));
    }

    public function __set(string $name, $value): void {
        trigger_error("do not do this", E_USER_ERROR);
    }
}
