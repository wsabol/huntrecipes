<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model {
    protected $table = 'Course';
    public $timestamps = false;
    protected $guarded = ['id'];
}
