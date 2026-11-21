<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentReminder extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['sent_at' => 'datetime', 'expires_at' => 'date'];
}
