<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $guarded = [];

    public function entity() { return $this->belongsTo(Entity::class); }
    public function head() { return $this->belongsTo(User::class, 'head_user_id'); }
    public function users() { return $this->hasMany(User::class); }
}
