<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Measure extends Model {
    protected $table = 'Measure';
    public $timestamps = false;
    protected $guarded = ['id'];

    public function fractionsAllowed(): array {
        return config('measures.fractions')[$this->id ?? 0] ?? [];
    }

    public function formatAmount(float $amount): string {
        if ($amount == 0) {
            return '';
        }

        $whole = (int) floor($amount);
        foreach ($this->fractionsAllowed() as $fraction) {
            [$numerator, $denominator] = array_map('intval', explode('/', $fraction));
            if (abs($amount - $whole - $numerator / $denominator) < 0.001) {
                return ($whole ? $whole . '-' : '') . $fraction;
            }
        }

        return rtrim(rtrim(number_format($amount, 3, '.', ''), '0'), '.');
    }
}
