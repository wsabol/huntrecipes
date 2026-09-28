<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChefApplication extends Model {
    public const STATUS_PENDING = 1;

    public const STATUS_APPROVED = 2;

    public const STATUS_DENIED = 3;

    protected $table = 'ChefApplication';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array {
        return ['date_created' => 'immutable_datetime', 'already_exists' => 'boolean', 'is_deleted' => 'boolean'];
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function relationshipName(string $relationship): string {
        return match ($relationship) {
            'birth' => 'Hunt by Birth',
            'marriage' => 'Hunt by Marriage',
            'family' => 'Extended Family',
            'other' => 'Other Connection',
            default => 'Unknown'
        };
    }

    public static function statusName(int $status): string {
        return match ($status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_DENIED => 'Denied',
            default => 'Unknown'
        };
    }
}
