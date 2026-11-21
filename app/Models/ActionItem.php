<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActionItem extends Model
{
    protected $guarded = [];
    protected $casts = ['due_at' => 'date', 'completed_at' => 'datetime'];

    public const STATUSES = ['open' => 'Terbuka', 'in_progress' => 'Dikerjakan', 'done' => 'Selesai'];

    public function meeting() { return $this->belongsTo(Meeting::class); }
    public function decision() { return $this->belongsTo(MeetingDecision::class, 'decision_id'); }
    public function pic() { return $this->belongsTo(User::class, 'pic_id'); }
    public function evidence() { return $this->belongsTo(Document::class, 'evidence_document_id'); }

    public function isOverdue(): bool { return $this->status !== 'done' && $this->due_at->lt(today()); }
}
