<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegalHold extends Model
{
    protected $guarded = [];
    protected $casts = ['placed_at' => 'datetime', 'released_at' => 'datetime', 'release_at' => 'date'];

    public function document() { return $this->belongsTo(Document::class); }
    public function owner() { return $this->belongsTo(User::class, 'hold_owner_id'); }
    public function releaser() { return $this->belongsTo(User::class, 'released_by'); }

    public function scopeActive($q) { return $q->whereNull('released_at'); }
}
