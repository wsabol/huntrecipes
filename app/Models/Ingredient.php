<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model {
    protected $table = 'Ingredient';

    public $timestamps = false;

    protected $guarded = ['id'];
}
