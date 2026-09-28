<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cuisine extends Model {
    protected $table = 'Cuisine';

    public $timestamps = false;

    protected $guarded = ['id'];
}
