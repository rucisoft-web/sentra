<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\DisposalRequest;
use App\Models\Document;
use App\Models\LegalHold;
use App\Models\RetentionPolicy;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\DocumentStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** M10 Retention & records — jadwal retensi, legal hold, pemusnahan dengan persetujuan ganda & berita acara. */
class RetentionController extends Controller
{
    public function index(Request $request)
    {
        $this->requireRole('doc_controller', 'legal', 'bod_gm', 'auditor', 'owner');
        return view('retention.index', [
            'policies' => RetentionPolicy::withCount('documents')->orderBy('code')->get(),
            'holds' => LegalHold::with(['document', 'owner', 'releaser'])->latest('placed_at')->get(),
            'eligible' => Document::with(['retentionPolicy', 'department', 'holds'])->where(fn ($q) => $q->where('status', 'eligible_disposal')
                ->orWhere(fn ($x) => $x->whereNotNull('retention_until')->whereDate('retention_until', '<', today())->whereNotIn('status', ['disposed'])))->get(),
            'disposals' => DisposalRequest::with(['document', 'requester', 'firstApprover', 'secondApprover'])->latest()->get(),
            'inactive' => Document::with(['department', 'retentionPolicy'])->whereIn('status', ['archived', 'superseded'])->orderBy('retention_until')->limit(50)->get(),
        ]);
    }

    public function placeHold(Request $request, Document $document)
    {
        $this->requireRole('legal', 'doc_controller');
        $data = $request->validate(['reason' => 'required|string|max:1000', 'case_reference' => 'nullable|string|max:120', 'release_at' => 'nullable|date|after:today']);
        $hold = $document->holds()->create($data + ['hold_owner_id' => $request->user()->id, 'placed_at' => now()]);
        // batalkan usulan pemusnahan yang berjalan
        $document->disposalRequests()->whereIn('status', ['pending', 'first_approved', 'approved'])
            ->update(['status' => 'rejected', 'rejection_note' => 'Otomatis dibatalkan: legal hold dipasang.', 'rejected_by' => $request->user()->id]);
        AuditLogger::log('hold.placed', $document, 'Legal hold: '.$data['reason']);
        return back()->with('success', 'Legal hold dipasang. Dokumen tidak dapat dimusnahkan sampai hold dilepas.');
    }

    public function releaseHold(Request $request, LegalHold $hold)
    {
        $this->requireRole('legal');
        $request->validate(['release_note' => 'required|string|max:500']);
        $hold->update(['released_at' => now(), 'released_by' => $request->user()->id, 'release_note' => $request->release_note]);
        AuditLogger::log('hold.released', $hold->document, 'Legal hold dilepas: '.$request->release_note);
        return back()->with('success', 'Legal hold dilepas.');
    }

    public function requestDisposal(Request $request, Document $document)
    {
        $this->requireRole('doc_controller');
        $request->validate(['reason' => 'required|string|max:1000', 'method' => 'required|string|max:40']);
        if ($document->hasActiveHold()) {
            AuditLogger::log('disposal.blocked', $document, 'Usulan pemusnahan diblokir: legal hold aktif');
            return back()->with('error', 'Tidak dapat diusulkan: dokumen dalam LEGAL HOLD aktif.');
        }
        if ($document->retentionPolicy?->disposal_action === 'permanent') {
            return back()->with('error', 'Kategori ini wajib disimpan permanen.');
        }
        if (! $document->retention_until || $document->retention_until->isFuture()) {
            return back()->with('error', 'Masa retensi belum berakhir (s.d. '.($document->retention_until?->format('d/m/Y') ?? '—').').');
        }
        $dr = $document->disposalRequests()->create(['requested_by' => $request->user()->id, 'reason' => $request->reason, 'method' => $request->input('method')]);
        $document->update(['status' => 'eligible_disposal']);
        AuditLogger::log('disposal.requested', $document, 'Usulan pemusnahan #'.$dr->id);
        foreach (User::whereIn('role', ['legal', 'bod_gm'])->get() as $u) {
            AppNotification::send($u, 'Usulan pemusnahan arsip', $document->code.' — '.$document->title, route('retention.index'), 'warning');
        }
        return back()->with('success', 'Usulan pemusnahan dibuat; memerlukan dua persetujuan (Legal lalu BOD/GM).');
    }

    public function approveDisposal(Request $request, DisposalRequest $disposal)
    {
        $user = $request->user();
        $doc = $disposal->document;
        if ($doc->hasActiveHold()) {
            AuditLogger::log('disposal.blocked', $doc, 'Persetujuan pemusnahan diblokir: legal hold aktif');
            return back()->with('error', 'Diblokir: legal hold aktif.');
        }
        abort_if($disposal->requested_by === $user->id, 403, 'Pengusul tidak boleh menyetujui usulannya sendiri.');

        if ($disposal->status === 'pending') {
            $this->requireRole('legal');
            $disposal->update(['status' => 'first_approved', 'first_approver_id' => $user->id, 'first_approved_at' => now()]);
        } elseif ($disposal->status === 'first_approved') {
            $this->requireRole('bod_gm');
            abort_if($disposal->first_approver_id === $user->id, 403, 'Persetujuan ganda harus oleh dua orang berbeda.');
            $disposal->update(['status' => 'approved', 'second_approver_id' => $user->id, 'second_approved_at' => now()]);
        } else {
            abort(422, 'Status usulan tidak dapat disetujui.');
        }
        AuditLogger::log('disposal.approved', $doc, 'Persetujuan pemusnahan tahap '.($disposal->status === 'approved' ? '2' : '1'));
        return back()->with('success', 'Persetujuan dicatat.');
    }

    public function rejectDisposal(Request $request, DisposalRequest $disposal)
    {
        $this->requireRole('legal', 'bod_gm');
        $request->validate(['rejection_note' => 'required|string|max:500']);
        $disposal->update(['status' => 'rejected', 'rejected_by' => $request->user()->id, 'rejection_note' => $request->rejection_note]);
        $disposal->document->update(['status' => 'archived']);
        AuditLogger::log('disposal.rejected', $disposal->document, $request->rejection_note);
        return back()->with('success', 'Usulan pemusnahan ditolak.');
    }

    public function executeDisposal(Request $request, DisposalRequest $disposal)
    {
        $this->requireRole('doc_controller');
        $doc = $disposal->document;
        abort_unless($disposal->status === 'approved', 422, 'Pemusnahan belum disetujui ganda.');
        if ($doc->hasActiveHold()) {
            AuditLogger::log('disposal.blocked', $doc, 'Eksekusi pemusnahan diblokir: legal hold aktif');
            return back()->with('error', 'Diblokir: legal hold aktif.');
        }
        DB::transaction(function () use ($disposal, $doc, $request) {
            $manifest = [];
            foreach ($doc->versions as $v) {
                $manifest[] = ['version' => $v->label(), 'file' => $v->original_name, 'size' => $v->size, 'sha256' => $v->checksum];
                DocumentStorage::delete($v->object_key);
                $v->forceFill(['status' => 'destroyed', 'object_key' => null, 'content_text' => null])->saveQuietly();
            }
            $number = 'BA-MUSNAH/'.$doc->entity->code.'/'.now()->format('Y').'/'.str_pad((string) (DisposalRequest::whereNotNull('certificate_number')->count() + 1), 3, '0', STR_PAD_LEFT);
            $disposal->update(['status' => 'executed', 'executed_at' => now(), 'executed_by' => $request->user()->id, 'certificate_number' => $number, 'manifest' => $manifest]);
            $doc->update(['status' => 'disposed', 'disposed_at' => now()]);
            AuditLogger::log('disposal.executed', $doc, 'Dimusnahkan dengan berita acara '.$number, ['manifest' => $manifest]);
        });
        return redirect()->route('disposal.certificate', $disposal)->with('success', 'Pemusnahan dieksekusi. Berita acara diterbitkan.');
    }

    public function certificate(DisposalRequest $disposal)
    {
        $this->requireRole('doc_controller', 'legal', 'bod_gm', 'auditor', 'owner');
        abort_unless($disposal->status === 'executed', 404);
        $disposal->load(['document.department', 'document.retentionPolicy', 'document.entity', 'requester', 'firstApprover', 'secondApprover', 'executor']);
        return view('retention.certificate', compact('disposal'));
    }
}
