<?php

namespace App\Http\Controllers;

use App\Models\AccessRequest;
use App\Models\AppNotification;
use App\Models\Document;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class AccessRequestController extends Controller
{
    private function approverFor(Document $d): ?User
    {
        return $d->owner ?? $d->department?->head;
    }

    private function canDecide(User $u, AccessRequest $r): bool
    {
        $d = $r->document;
        return $u->id !== $r->requester_id && ($d->owner_id === $u->id || $d->department?->head_user_id === $u->id
            || ($u->hasRole('doc_controller') && $d->classification !== 'strictly_confidential'));
    }

    public function index(Request $request)
    {
        $u = $request->user();
        $mine = AccessRequest::with(['document', 'approver'])->where('requester_id', $u->id)->latest()->get();
        $toDecide = AccessRequest::with(['document.department', 'requester.department'])->latest()->get()
            ->filter(fn ($r) => $this->canDecide($u, $r));
        return view('access.index', compact('mine', 'toDecide'));
    }

    public function store(Request $request, Document $document)
    {
        $data = $request->validate([
            'justification' => 'required|string|min:10|max:1000',
            'permission' => 'required|in:view,download',
            'requested_until' => 'required|date|after:today|before:'.today()->addDays(181)->toDateString(),
        ]);
        abort_unless($this->acl()->canDiscover($request->user(), $document), 403);
        $r = AccessRequest::create($data + ['requester_id' => $request->user()->id, 'document_id' => $document->id]);
        AuditLogger::log('access.requested', $document, 'Permintaan akses '.$data['permission'].': '.$data['justification']);
        if ($a = $this->approverFor($document)) {
            AppNotification::send($a, 'Permintaan akses dokumen', $request->user()->name.' meminta akses ke '.$document->code, route('access-requests.index'), 'warning');
        }
        return back()->with('success', 'Permintaan akses dikirim ke pemilik dokumen.');
    }

    public function decide(Request $request, AccessRequest $accessRequest)
    {
        abort_unless($accessRequest->decision === 'pending' && $this->canDecide($request->user(), $accessRequest), 403);
        $data = $request->validate(['decision' => 'required|in:approved,rejected', 'decision_note' => 'nullable|string|max:500']);
        $accessRequest->update($data + ['approver_id' => $request->user()->id, 'decided_at' => now()]);
        if ($data['decision'] === 'approved') {
            $accessRequest->document->acl()->create([
                'subject_type' => 'user', 'subject_id' => (string) $accessRequest->requester_id,
                'permission' => $accessRequest->permission, 'expires_at' => $accessRequest->requested_until->endOfDay(),
                'conditions' => 'Permintaan akses #'.$accessRequest->id, 'granted_by' => $request->user()->id,
            ]);
            AuditLogger::log('acl.granted', $accessRequest->document, 'Akses sementara untuk '.$accessRequest->requester->name.' s.d. '.$accessRequest->requested_until->format('d/m/Y'));
        }
        AuditLogger::log('access.decided', $accessRequest->document, 'Permintaan #'.$accessRequest->id.' '.$data['decision']);
        AppNotification::send($accessRequest->requester_id, 'Permintaan akses '.($data['decision'] === 'approved' ? 'disetujui' : 'ditolak'),
            $accessRequest->document->code, route('documents.show', $accessRequest->document), $data['decision'] === 'approved' ? 'success' : 'danger');
        return back()->with('success', 'Keputusan dicatat.');
    }
}
