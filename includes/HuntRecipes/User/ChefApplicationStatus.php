<?php

namespace HuntRecipes\User;

class ChefApplicationStatus {
    public const NONE = 0;
    public const PENDING = 1;
    public const APPROVED = 2;
    public const DENIED = 3;

    public static function get_name(int $status): string {
        return match($status) {
            self::NONE => 'None',
            self::PENDING => 'Pending',
            self::APPROVED => 'Approved',
            self::DENIED => 'Denied',
            default => 'Unknown',
        };
    }
}
