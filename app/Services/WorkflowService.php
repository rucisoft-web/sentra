<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStep;
use Illuminate\Support\Facades\DB;

/** M07 Workflow & e-approval — alur serial/paralel, delegasi, batas waktu, eskalasi, penolakan beralasan. */
class WorkflowService
{
    public function submit(Document $doc, DocumentVersion $version, User $by, array $reviewerIds, int $approverId, string $mode = 'serial', int $slaDays = 3, ?string $note = null, $at = null): WorkflowInstance
    {
        return DB::transaction(function () use ($doc, $version, $by, $reviewerIds, $approverId, $mode, $slaDays, $note, $at) {
            $at = $at ? \Carbon\Carbon::parse($at) : now();
            $wf = WorkflowInstance::create([
                'document_id' => $doc->id, 'document_version_id' => $version->id, 'mode' => $mode,
                'status' => 'in_progress', 'submitted_by' => $by->id, 'submitted_at' => $at, 'note' => $note,
            ]);
            $seq = 1;
            foreach (array_values(array_unique($reviewerIds)) as $i => $rid) {
                $first = $mode === 'parallel' || $i === 0;
                WorkflowStep::create([
                    'workflow_instance_id' => $wf->id, 'sequence' => $mode === 'parallel' ? 1 : $seq++, 'stage' => 'review',
                    'assignee_id' => $rid, 'action' => $first ? 'pending' : 'waiting',
                    'due_at' => $first ? $at->copy()->addDays($slaDays) : null,
                ]);
            }
            $approvalSeq = $mode === 'parallel' ? 2 : $seq;
            WorkflowStep::create([
                'workflow_instance_id' => $wf->id, 'sequence' => $reviewerIds ? $approvalSeq : 1, 'stage' => 'approval',
                'assignee_id' => $approverId, 'action' => $reviewerIds ? 'waiting' : 'pending',
                'due_at' => $reviewerIds ? null : $at->copy()->addDays($slaDays),
            ]);
            $version->update(['status' => 'in_review']);
            if (! in_array($doc->status, ['published'])) {
                $doc->update(['status' => $reviewerIds ? 'under_review' : 'submitted']);
            }
            AuditLogger::log('workflow.submitted', $doc, "Versi {$version->label()} diajukan untuk review/persetujuan", ['workflow_id' => $wf->id], $by->id, $at);
            $this->notifyPending($wf);
            return $wf;
        });
    }

    public function decide(WorkflowStep $step, User $by, string $decision, ?string $comment = null, bool $mfa = false, $at = null): void
    {
        DB::transaction(function () use ($step, $by, $decision, $comment, $mfa, $at) {
            $at = $at ? \Carbon\Carbon::parse($at) : now();
            $wf = $step->instance;
            $doc = $wf->document;
            $version = $wf->version;

            $step->update(['action' => $decision, 'decided_at' => $at, 'decided_by' => $by->id, 'comment' => $comment, 'mfa_verified' => $mfa]);

            if ($decision === 'revision' || $decision === 'rejected') {
                $wf->steps()->whereIn('action', ['waiting', 'pending'])->update(['action' => 'skipped']);
                $wf->update(['status' => $decision === 'revision' ? 'revision' : 'rejected', 'completed_at' => $at]);
                $version->update(['status' => $decision === 'revision' ? 'draft' : 'rejected']);
                if ($doc->status !== 'published') {
                    $doc->update(['status' => $decision === 'revision' ? 'revision_required' : 'rejected']);
                }
                AuditLogger::log('workflow.'.$decision, $doc, ($decision === 'revision' ? 'Dikembalikan untuk revisi: ' : 'Ditolak: ').$comment, ['step_id' => $step->id], $by->id, $at);
                AppNotification::send($wf->submitted_by, $decision === 'revision' ? 'Dokumen perlu revisi' : 'Dokumen ditolak',
                    $doc->title.' — '.$comment, route('documents.show', $doc), $decision === 'revision' ? 'warning' : 'danger');
                return;
            }

            AuditLogger::log($step->stage === 'approval' ? 'workflow.approved' : 'workflow.reviewed', $doc,
                ($step->stage === 'approval' ? 'Disahkan' : 'Review disetujui').' oleh '.$by->name.($mfa ? ' (MFA terverifikasi)' : ''),
                ['step_id' => $step->id, 'version' => $version->label()], $by->id, $at);

            // langkah berikutnya
            $openSameSeq = $wf->steps()->where('sequence', $step->sequence)->where('action', 'pending')->exists();
            if ($openSameSeq) return; // paralel: tunggu reviewer lain

            $next = $wf->steps()->where('action', 'waiting')->orderBy('sequence')->get();
            if ($next->isNotEmpty()) {
                $seq = $next->first()->sequence;
                foreach ($next->where('sequence', $seq) as $n) {
                    $n->update(['action' => 'pending', 'due_at' => $at->copy()->addDays(3)]);
                }
                $this->notifyPending($wf->fresh());
                return;
            }

            // selesai: disahkan
            $wf->update(['status' => 'approved', 'completed_at' => $at]);
            $version->update(['status' => 'approved', 'approved_by' => $by->id, 'approved_at' => $at, 'locked' => true]);
            if ($doc->status !== 'published') $doc->update(['status' => 'approved']);
            foreach (User::where('role', 'doc_controller')->get() as $dc) {
                AppNotification::send($dc, 'Siap dipublikasikan', $doc->title.' '.$version->label().' telah disahkan.', route('documents.show', $doc), 'success');
            }
            AppNotification::send($wf->submitted_by, 'Dokumen disahkan', $doc->title.' '.$version->label(), route('documents.show', $doc), 'success');
        });
    }

    public function publish(Document $doc, DocumentVersion $version, User $by, ?string $effectiveAt = null, $at = null): void
    {
        DB::transaction(function () use ($doc, $version, $by, $effectiveAt, $at) {
            $at = $at ? \Carbon\Carbon::parse($at) : now();
            // versi sebelumnya yang berlaku → superseded
            $doc->versions()->where('status', 'final')->where('id', '!=', $version->id)->update(['status' => 'superseded']);
            $version->update(['status' => 'final', 'locked' => true]);
            $type = $doc->type;
            $doc->update([
                'status' => 'published',
                'current_version_id' => $version->id,
                'effective_at' => $effectiveAt ?? $doc->effective_at ?? $at->toDateString(),
                'published_at' => $at,
                'review_at' => $doc->review_at ?? ($type->review_cycle_months ? $at->copy()->addMonths($type->review_cycle_months)->toDateString() : null),
            ]);
            $doc->refresh();
            $doc->update(['retention_until' => $doc->computeRetentionUntil()]);
            AuditLogger::log('document.published', $doc, "Dipublikasikan {$version->label()} (SHA-256 {$version->checksum})", [], $by->id, $at, null, $version->checksum);
        });
    }

    public function delegate(WorkflowStep $step, User $by, User $to, ?string $reason = null): void
    {
        $step->update(['delegated_to_id' => $to->id, 'comment' => trim(($step->comment ? $step->comment."\n" : '').'Didelegasikan: '.$reason)]);
        AuditLogger::log('workflow.delegated', $step->instance->document, "Tugas {$step->stageLabel()} didelegasikan dari {$by->name} ke {$to->name}", [], $by->id);
        AppNotification::send($to, 'Tugas didelegasikan kepada Anda', $step->instance->document->title, route('tasks.index'), 'info');
    }

    /** Eskalasi langkah yang melewati batas waktu ke kepala departemen / GM. */
    public function escalateOverdue(): int
    {
        $n = 0;
        $steps = WorkflowStep::with('instance.document.department')->where('action', 'pending')
            ->whereNull('escalated_at')->where('due_at', '<', now())->get();
        foreach ($steps as $s) {
            $doc = $s->instance->document;
            $targets = collect([$doc->department?->head_user_id])->merge(User::where('role', 'bod_gm')->pluck('id'))->filter()->unique();
            foreach ($targets as $t) {
                AppNotification::send($t, 'Eskalasi: persetujuan melewati SLA', $doc->title.' — menunggu '.$s->assignee->name, route('documents.show', $doc), 'danger');
            }
            $s->update(['escalated_at' => now()]);
            AuditLogger::log('workflow.escalated', $doc, 'Langkah '.$s->stageLabel().' oleh '.$s->assignee->name.' melewati batas waktu', [], null);
            $n++;
        }
        return $n;
    }

    private function notifyPending(WorkflowInstance $wf): void
    {
        foreach ($wf->steps()->where('action', 'pending')->get() as $s) {
            AppNotification::send($s->actorId(), 'Tugas '.$s->stageLabel().' baru', $wf->document->title, route('tasks.index'), 'info');
        }
    }
}
