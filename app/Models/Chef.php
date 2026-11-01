<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Chef extends Model {
    protected $table = 'Chef';
    public $timestamps = false;
    protected $guarded = ['id'];

    protected function casts(): array {
        return ['is_male' => 'boolean'];
    }

    public function recipes(): HasMany {
        return $this->hasMany(Recipe::class, 'chef_id');
    }

    public function user(): HasOne {
        return $this->hasOne(User::class, 'chef_id');
    }
}
