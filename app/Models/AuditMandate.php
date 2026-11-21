<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditMandate extends Model
{
    protected $guarded = [];
    protected $casts = ['starts_at' => 'date', 'ends_at' => 'date', 'category_ids' => 'array', 'department_ids' => 'array'];

    public function auditor() { return $this->belongsTo(User::class, 'auditor_id'); }
    public function grantor() { return $this->belongsTo(User::class, 'granted_by'); }

    public function isActive(): bool
    {
        return today()->between($this->starts_at, $this->ends_at);
    }

    public function covers(Document $d): bool
    {
        if (! $this->isActive()) return false;
        $cats = $this->category_ids ?: [];
        $deps = $this->department_ids ?: [];
        return ($cats === [] || in_array($d->category_id, $cats)) && ($deps === [] || in_array($d->department_id, $deps));
    }
}
