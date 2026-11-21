<?php

namespace App\Http\Controllers;

use App\Models\AuditEvent;
use App\Models\Document;
use App\Models\WorkflowStep;
use App\Services\AuditLogger;
use App\Services\DocumentFinder;
use Illuminate\Http\Request;

/** M12 Reports & KPI keberhasilan (SRS §14) */
class ReportController extends Controller
{
    public function index(Request $request)
    {
        $this->requireRole('owner', 'bod_gm', 'doc_controller', 'legal', 'auditor');
        $docs = Document::with(['type', 'department', 'owner', 'retentionPolicy', 'currentVersion'])->where('status', '!=', 'disposed')->get();
        $published = $docs->where('status', 'published');

        $steps = WorkflowStep::whereNotNull('decided_at')->whereNotNull('due_at')->whereIn('action', ['approved', 'revision', 'rejected'])->get();
        $slaMet = $steps->filter(fn ($s) => $s->decided_at->lte($s->due_at))->count();

        $searches = AuditEvent::where('event', 'search')->where('created_at', '>=', now()->subDays(30))->get()
            ->map(fn ($e) => $e->meta['ms'] ?? null)->filter(fn ($v) => $v !== null)->sort()->values();
        $p95 = $searches->count() ? $searches[(int) floor(0.95 * ($searches->count() - 1))] : null;

        $activity = AuditEvent::with('actor')->selectRaw('actor_id, count(*) as total, max(created_at) as last_at')
            ->where('created_at', '>=', now()->subDays(30))->whereNotNull('actor_id')->groupBy('actor_id')->orderByDesc('total')->limit(15)->get();
        $eventsByType = AuditEvent::selectRaw('event, count(*) as total')->where('created_at', '>=', now()->subDays(30))->groupBy('event')->orderByDesc('total')->pluck('total', 'event');

        $kpi = [
            ['Dokumen resmi dengan owner & klasifikasi', $published->count() ? round($published->filter(fn ($d) => $d->owner_id && $d->classification)->count() / $published->count() * 100) : 0, '100%', 100],
            ['Persetujuan sesuai SLA', $steps->count() ? round($slaMet / $steps->count() * 100) : 0, '≥90%', 90],
            ['Dokumen prioritas terdaftar & tervalidasi (metadata lengkap)', $published->count() ? round($published->reject(fn ($d) => $d->missingMetadata())->count() / $published->count() * 100) : 0, '≥95%', 95],
        ];
        $criticalExpired = $published->filter(fn ($d) => $d->expires_at && $d->daysToExpiry() < 0 && $d->type->requires_expiry);

        return view('reports.index', [
            'kpi' => $kpi, 'criticalExpired' => $criticalExpired,
            'incomplete' => $published->filter(fn ($d) => $d->missingMetadata())->values(),
            'reviewOverdue' => $published->filter->isReviewOverdue()->values(),
            'pending' => WorkflowStep::with(['instance.document', 'assignee'])->where('action', 'pending')->orderBy('due_at')->get(),
            'activity' => $activity, 'eventsByType' => $eventsByType,
            'searchCount' => $searches->count(), 'searchAvg' => $searches->count() ? round($searches->avg()) : null, 'searchP95' => $p95,
        ]);
    }

    /** Ekspor terkendali — izin ekspor massal terpisah + MFA + tercatat. */
    public function export(Request $request, DocumentFinder $finder)
    {
        abort_unless($request->user()->can_bulk_export, 403, 'Anda tidak memiliki izin ekspor massal (izin terpisah).');
        $rows = $finder->search($request->user(), [])->where('can_open', true);
        AuditLogger::log('export', null, 'Ekspor register dokumen ('.$rows->count().' baris, hanya metadata)', ['count' => $rows->count()]);
        $out = fopen('php://temp', 'w+');
        fputcsv($out, ['Nomor', 'Judul', 'Entitas', 'Departemen', 'Kategori', 'Jenis', 'Klasifikasi', 'Status', 'Pemilik', 'Efektif', 'Review', 'Berakhir', 'Retensi s.d.', 'Versi', 'SHA-256']);
        foreach ($rows as $d) {
            fputcsv($out, [$d->code, $d->title, $d->entity->code, $d->department->name, $d->category->name, $d->type->name,
                $d->classificationLabel(), $d->statusLabel(), $d->owner->name, $d->effective_at?->toDateString(), $d->review_at?->toDateString(),
                $d->expires_at?->toDateString(), $d->retention_until?->toDateString(), $d->currentVersion?->label(), $d->currentVersion?->checksum]);
        }
        rewind($out);
        return response(stream_get_contents($out), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="register-dokumen-'.now()->format('Ymd-His').'.csv"',
        ]);
    }
}
