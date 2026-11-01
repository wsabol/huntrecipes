<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecipeType extends Model {
    protected $table = 'RecipeType';
    public $timestamps = false;
    protected $guarded = ['id'];
}
