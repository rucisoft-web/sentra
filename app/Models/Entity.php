<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entity extends Model
{
    protected $guarded = [];

    public function departments() { return $this->hasMany(Department::class); }
}
