<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowInstance extends Model
{
    protected $guarded = [];
    protected $casts = ['submitted_at' => 'datetime', 'completed_at' => 'datetime'];

    public const STATUSES = [
        'in_progress' => 'Berjalan', 'approved' => 'Disetujui', 'revision' => 'Perlu Revisi',
        'rejected' => 'Ditolak', 'cancelled' => 'Dibatalkan',
    ];

    public function document() { return $this->belongsTo(Document::class); }
    public function version() { return $this->belongsTo(DocumentVersion::class, 'document_version_id'); }
    public function submitter() { return $this->belongsTo(User::class, 'submitted_by'); }
    public function steps() { return $this->hasMany(WorkflowStep::class)->orderBy('sequence'); }
}
