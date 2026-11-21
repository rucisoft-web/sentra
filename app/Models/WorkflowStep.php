<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowStep extends Model
{
    protected $guarded = [];
    protected $casts = ['due_at' => 'datetime', 'decided_at' => 'datetime', 'escalated_at' => 'datetime', 'mfa_verified' => 'bool'];

    public const ACTIONS = [
        'waiting' => 'Menunggu giliran', 'pending' => 'Menunggu keputusan', 'approved' => 'Disetujui',
        'revision' => 'Minta revisi', 'rejected' => 'Ditolak', 'skipped' => 'Dilewati',
    ];

    public function instance() { return $this->belongsTo(WorkflowInstance::class, 'workflow_instance_id'); }
    public function assignee() { return $this->belongsTo(User::class, 'assignee_id'); }
    public function delegate() { return $this->belongsTo(User::class, 'delegated_to_id'); }
    public function decider() { return $this->belongsTo(User::class, 'decided_by'); }

    public function actorId(): int { return $this->delegated_to_id ?: $this->assignee_id; }
    public function isOverdue(): bool { return $this->action === 'pending' && $this->due_at && $this->due_at->isPast(); }
    public function stageLabel(): string { return $this->stage === 'approval' ? 'Persetujuan' : 'Review'; }
}
