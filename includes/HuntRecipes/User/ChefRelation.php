<?php

namespace HuntRecipes\User;

class ChefRelation {
    public const BIRTH = 'birth';
    public const MARRIAGE = 'marriage';
    public const FAMILY = 'family';
    public const OTHER = 'other';

    public static function get_name(string $relation): string {
        return match($relation) {
            self::BIRTH => 'Hunt by Birth',
            self::MARRIAGE => 'Hunt by Marriage',
            self::FAMILY => 'Extended Family',
            self::OTHER => 'Other Connection',
            default => 'Unknown',
        };
    }

    public static function list(): array {
        return [
            (object)[
                'value' => self::BIRTH,
                'name' => self::get_name(self::BIRTH),
            ],
            (object)[
                'value' => self::MARRIAGE,
                'name' => self::get_name(self::MARRIAGE),
            ],
            (object)[
                'value' => self::FAMILY,
                'name' => self::get_name(self::FAMILY),
            ],
            (object)[
                'value' => self::OTHER,
                'name' => self::get_name(self::OTHER),
            ],
        ];
    }
}
