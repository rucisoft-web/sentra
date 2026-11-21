<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DisposalRequest extends Model
{
    protected $guarded = [];
    protected $casts = [
        'first_approved_at' => 'datetime', 'second_approved_at' => 'datetime',
        'executed_at' => 'datetime', 'manifest' => 'array',
    ];

    public const STATUSES = [
        'pending' => 'Menunggu persetujuan 1', 'first_approved' => 'Menunggu persetujuan 2',
        'approved' => 'Disetujui – siap dieksekusi', 'rejected' => 'Ditolak', 'executed' => 'Dimusnahkan',
    ];

    public function document() { return $this->belongsTo(Document::class); }
    public function requester() { return $this->belongsTo(User::class, 'requested_by'); }
    public function firstApprover() { return $this->belongsTo(User::class, 'first_approver_id'); }
    public function secondApprover() { return $this->belongsTo(User::class, 'second_approver_id'); }
    public function executor() { return $this->belongsTo(User::class, 'executed_by'); }
}
