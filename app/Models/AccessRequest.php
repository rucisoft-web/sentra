<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessRequest extends Model
{
    protected $guarded = [];
    protected $casts = ['requested_until' => 'date', 'decided_at' => 'datetime'];

    public const DECISIONS = ['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'];

    public function requester() { return $this->belongsTo(User::class, 'requester_id'); }
    public function approver() { return $this->belongsTo(User::class, 'approver_id'); }
    public function document() { return $this->belongsTo(Document::class); }
}
