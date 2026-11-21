<?php

namespace App\Http\Controllers;

use App\Models\Acknowledgement;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentAcl;
use App\Models\DocumentCategory;
use App\Models\DocumentLink;
use App\Models\DocumentRelation;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\Entity;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\DocumentFinder;
use App\Services\DocumentNumbering;
use App\Services\DocumentStorage;
use App\Services\WorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DocumentController extends Controller
{
    public function index(Request $request, DocumentFinder $finder)
    {
        $filters = $request->only(DocumentFinder::FILTERS);
        $results = $finder->search($request->user(), $filters);
        $documents = DocumentFinder::paginate($results);

        // pohon folder virtual: modul → kategori → jumlah dokumen (sesuai izin)
        $tree = DocumentCategory::with('types')->orderBy('module')->orderBy('name')->get()->groupBy('module');
        $counts = $finder->search($request->user(), [])->groupBy('category_id')->map->count();

        return view('documents.index', compact('documents', 'filters', 'tree', 'counts') + $this->lookups());
    }

    public function create(Request $request)
    {
        $this->requireRole('doc_controller', 'legal', 'finance', 'hr', 'dept_head', 'staff', 'bod_gm');
        return view('documents.form', ['document' => new Document([
            'entity_id' => $request->user()->entity_id, 'department_id' => $request->user()->department_id,
            'owner_id' => $request->user()->id, 'classification' => 'department',
        ])] + $this->lookups());
    }

    public function store(Request $request)
    {
        $this->requireRole('doc_controller', 'legal', 'finance', 'hr', 'dept_head', 'staff', 'bod_gm');
        $data = $this->validated($request);
        $request->validate(['file' => $this->fileRules(true)]);

        $doc = DB::transaction(function () use ($data, $request) {
            $entity = Entity::findOrFail($data['entity_id']);
            $type = DocumentType::with('category')->findOrFail($data['type_id']);
            $data['category_id'] = $type->category_id;
            $data['retention_policy_id'] ??= $type->category->retention_policy_id;
            $data['code'] = $data['code'] ?: DocumentNumbering::next($entity, Department::findOrFail($data['department_id']), $type);
            $data['created_by'] = $request->user()->id;
            $data['status'] = 'draft';

            $doc = Document::create($data);
            $this->storeVersion($doc, $request, 0, 1, 'Draft awal');
            AuditLogger::log('document.created', $doc, 'Dokumen didaftarkan: '.$doc->code, [], null, null, null, AuditLogger::snapshotHash($doc));
            return $doc;
        });

        return redirect()->route('documents.show', $doc)->with('success', 'Dokumen terdaftar dengan nomor '.$doc->code.'. Status: Draft (belum sah).');
    }

    public function show(Request $request, Document $document)
    {
        $user = $request->user();
        [$canView, $reason] = $this->acl()->decide($user, $document);

        if (! $canView) {
            if (! $this->acl()->canDiscover($user, $document)) {
                AuditLogger::log('document.access_denied', $document, 'Akses ditolak: '.$reason);
                abort(403, $reason);
            }
            AuditLogger::log('document.access_denied', $document, 'Melihat metadata terbatas: '.$reason);
            return view('documents.locked', ['document' => $document, 'reason' => $reason,
                'pendingRequest' => \App\Models\AccessRequest::where('document_id', $document->id)
                    ->where('requester_id', $user->id)->where('decision', 'pending')->first()]);
        }

        if ($document->classification === 'strictly_confidential' && ! \App\Http\Middleware\RequireMfa::isFresh($request)) {
            $request->session()->put('mfa_intended', $request->fullUrl());
            return redirect()->route('mfa.show')->with('warning', 'Dokumen Sangat Rahasia memerlukan verifikasi MFA ulang.');
        }

        $document->load(['versions.uploader', 'versions.approver', 'acl.grantor', 'relations.related', 'inverseRelations.document',
            'links', 'workflows.steps.assignee', 'workflows.steps.delegate', 'workflows.steps.decider', 'workflows.version', 'holds.owner', 'disposalRequests',
            'type', 'category', 'department', 'owner', 'creator', 'retentionPolicy', 'entity', 'supersededBy', 'checkedOutBy']);

        AuditLogger::log('document.viewed', $document, 'Membuka detail dokumen');

        $acked = null;
        if ($document->type->requires_acknowledgement && $document->current_version_id) {
            $acked = Acknowledgement::where('document_version_id', $document->current_version_id)->where('user_id', $user->id)->first();
        }

        $history = \App\Models\AuditEvent::with('actor')->where('resource_type', 'Document')->where('resource_id', $document->id)
            ->orderByDesc('created_at')->orderByDesc('id')->limit(25)->get();

        return view('documents.show', [
            'document' => $document, 'accessReason' => $reason,
            'canDownload' => $this->acl()->can($user, $document, 'download'),
            'canEdit' => $this->acl()->can($user, $document, 'edit'),
            'acked' => $acked, 'history' => $history,
            'reviewers' => User::whereNotIn('role', ['service', 'auditor', 'it_admin'])->where('is_active', true)->orderBy('name')->get(),
            'allDocuments' => Document::where('id', '!=', $document->id)->where('status', '!=', 'disposed')->orderBy('code')->get(['id', 'code', 'title']),
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function edit(Document $document)
    {
        $this->authorizeDocument($document, 'edit');
        return view('documents.form', ['document' => $document] + $this->lookups());
    }

    public function update(Request $request, Document $document)
    {
        $this->authorizeDocument($document, 'edit');
        $data = $this->validated($request, $document);
        $isFinal = $document->status === 'published';

        // Metadata dokumen final hanya boleh diubah Document Controller (terkendali + log)
        if ($isFinal && ! $request->user()->hasRole('doc_controller')) {
            abort(403, 'Metadata dokumen final hanya dapat diubah oleh Document Controller.');
        }
        if ($isFinal) {
            $request->validate(['change_reason' => 'required|string|min:5']);
            unset($data['code'], $data['entity_id']);
        }

        $before = AuditLogger::snapshotHash($document);
        $type = DocumentType::find($data['type_id']);
        $data['category_id'] = $type->category_id;
        $old = $document->only(array_keys($data));
        $document->update($data);
        $document->update(['retention_until' => $document->fresh()->computeRetentionUntil()]);
        $changes = collect($document->getChanges())->except(['updated_at', 'retention_until'])->keys()->all();

        AuditLogger::log('document.updated', $document, 'Metadata diubah'.($isFinal ? ' (dokumen final) — alasan: '.$request->change_reason : '').': '.implode(', ', $changes),
            ['changed' => $changes, 'old' => array_intersect_key($old, array_flip($changes))], null, null, $before, AuditLogger::snapshotHash($document));

        return redirect()->route('documents.show', $document)->with('success', 'Metadata diperbarui.');
    }

    public function uploadVersion(Request $request, Document $document)
    {
        $this->authorizeDocument($document, 'edit');
        $request->validate(['file' => $this->fileRules(true), 'level' => 'required|in:major,minor', 'change_note' => 'required|string|max:500']);
        if ($document->activeWorkflow()) {
            return back()->with('error', 'Masih ada versi dalam proses persetujuan. Tunggu keputusan atau minta revisi.');
        }

        $latest = $document->versions()->first();
        [$major, $minor] = $request->level === 'major' ? [($latest?->major ?? 0) + 1, 0] : [$latest?->major ?? 0, ($latest?->minor ?? 0) + 1];

        // Draft yang belum dikunci boleh diganti (bukan versi final)
        $v = $this->storeVersion($document, $request, $major, $minor, $request->change_note);

        if ($document->checked_out_by === $request->user()->id) {
            $document->update(['checked_out_by' => null, 'checked_out_at' => null]);
            AuditLogger::log('document.checked_in', $document, 'Check-in dengan versi '.$v->label());
        }
        if (in_array($document->status, ['revision_required', 'rejected'])) $document->update(['status' => 'draft']);

        return back()->with('success', 'Versi '.$v->label().' diunggah (SHA-256 '.substr($v->checksum, 0, 12).'…). Ajukan untuk review agar menjadi sah.');
    }

    public function compare(Request $request, Document $document)
    {
        $this->authorizeDocument($document);
        $versions = $document->versions()->with(['uploader', 'approver'])->get();
        $a = $versions->firstWhere('id', $request->integer('a')) ?? $versions->get(1) ?? $versions->first();
        $b = $versions->firstWhere('id', $request->integer('b')) ?? $versions->first();
        return view('documents.compare', compact('document', 'versions', 'a', 'b'));
    }

    public function checkout(Request $request, Document $document)
    {
        $this->authorizeDocument($document, 'edit');
        $document->update(['checked_out_by' => $request->user()->id, 'checked_out_at' => now()]);
        AuditLogger::log('document.checked_out', $document, 'Check-out untuk revisi');
        return back()->with('success', 'Dokumen di-check-out. Pengguna lain tidak dapat mengubah sampai Anda check-in (unggah versi) atau melepas.');
    }

    public function release(Request $request, Document $document)
    {
        abort_unless($document->checked_out_by === $request->user()->id || $request->user()->hasRole('doc_controller'), 403);
        $document->update(['checked_out_by' => null, 'checked_out_at' => null]);
        AuditLogger::log('document.checked_in', $document, 'Check-out dilepas tanpa perubahan');
        return back()->with('success', 'Check-out dilepas.');
    }

    public function submit(Request $request, Document $document, WorkflowService $wf)
    {
        $this->authorizeDocument($document, 'edit');
        $data = $request->validate([
            'reviewers' => 'array', 'reviewers.*' => 'exists:users,id',
            'approver_id' => 'required|exists:users,id', 'mode' => 'required|in:serial,parallel',
            'sla_days' => 'required|integer|min:1|max:30', 'note' => 'nullable|string|max:500',
        ]);
        $version = $document->versions()->whereIn('status', ['draft'])->first();
        if (! $version) return back()->with('error', 'Tidak ada versi draft untuk diajukan. Unggah versi baru terlebih dahulu.');
        if ($document->activeWorkflow()) return back()->with('error', 'Dokumen sudah dalam proses persetujuan.');

        $approver = User::find($data['approver_id']);
        if ($this->acl()->violatesSeparationOfDuties($approver, $document)) {
            return back()->with('error', 'Pemisahan tugas: pembuat/pemilik tidak boleh menyetujui dokumen material miliknya sendiri.');
        }
        if (in_array($request->user()->id, $data['reviewers'] ?? []) || $approver->id === $request->user()->id) {
            return back()->with('error', 'Pengaju tidak boleh menjadi reviewer/approver atas pengajuannya sendiri.');
        }

        $wf->submit($document, $version, $request->user(), $data['reviewers'] ?? [], $data['approver_id'], $data['mode'], (int) $data['sla_days'], $data['note'] ?? null);
        return back()->with('success', 'Diajukan untuk review/persetujuan. Notifikasi dikirim ke reviewer.');
    }

    public function publish(Request $request, Document $document, WorkflowService $wf)
    {
        $this->requireRole('doc_controller');
        $request->validate(['effective_at' => 'nullable|date']);
        $version = $document->versions()->where('status', 'approved')->first();
        if (! $version) return back()->with('error', 'Tidak ada versi yang sudah disahkan untuk dipublikasikan.');
        $wf->publish($document, $version, $request->user(), $request->effective_at);
        return back()->with('success', 'Dokumen dipublikasikan sebagai versi berlaku '.$version->label().'.');
    }

    public function archive(Request $request, Document $document)
    {
        $this->requireRole('doc_controller');
        $request->validate(['superseded_by_id' => 'nullable|exists:documents,id', 'reason' => 'required|string']);
        $document->update([
            'status' => $request->superseded_by_id ? 'superseded' : 'archived',
            'superseded_by_id' => $request->superseded_by_id, 'archived_at' => now(),
        ]);
        $document->update(['retention_until' => $document->fresh()->computeRetentionUntil()]);
        AuditLogger::log($request->superseded_by_id ? 'document.superseded' : 'document.archived', $document, $request->reason);
        return back()->with('success', 'Dokumen dipindahkan ke arsip inaktif.');
    }

    public function addAcl(Request $request, Document $document)
    {
        abort_unless($document->owner_id === $request->user()->id || $request->user()->hasRole('doc_controller')
            || $document->department?->head_user_id === $request->user()->id, 403, 'Hanya pemilik, kepala departemen atau Document Controller.');
        $data = $request->validate([
            'subject_type' => 'required|in:user,department,role', 'subject_id' => 'required|string',
            'permission' => ['required', Rule::in(array_keys(DocumentAcl::PERMISSIONS))],
            'expires_at' => 'nullable|date|after:today', 'conditions' => 'nullable|string|max:200',
        ]);
        $acl = $document->acl()->create($data + ['granted_by' => $request->user()->id]);
        AuditLogger::log('acl.granted', $document, $acl->subjectLabel().' → '.DocumentAcl::PERMISSIONS[$acl->permission].($acl->expires_at ? ' s.d. '.$acl->expires_at->format('d/m/Y') : ''));
        return back()->with('success', 'Hak akses ditambahkan.');
    }

    public function removeAcl(Request $request, Document $document, DocumentAcl $acl)
    {
        abort_unless($acl->document_id === $document->id, 404);
        abort_unless($document->owner_id === $request->user()->id || $request->user()->hasRole('doc_controller'), 403);
        AuditLogger::log('acl.revoked', $document, 'Dicabut: '.$acl->subjectLabel().' ('.$acl->permission.')');
        $acl->delete();
        return back()->with('success', 'Hak akses dicabut.');
    }

    public function addRelation(Request $request, Document $document)
    {
        $this->authorizeDocument($document);
        abort_unless($request->user()->hasRole('doc_controller', 'legal') || $document->owner_id === $request->user()->id, 403);
        $data = $request->validate(['related_document_id' => 'required|exists:documents,id', 'relation_type' => ['required', Rule::in(array_keys(DocumentRelation::TYPES))]]);
        $document->relations()->create($data);
        AuditLogger::log('document.updated', $document, 'Relasi ditambahkan: '.DocumentRelation::TYPES[$data['relation_type']]);
        return back()->with('success', 'Relasi dokumen ditambahkan.');
    }

    public function addLink(Request $request, Document $document)
    {
        $this->authorizeDocument($document);
        abort_unless($request->user()->hasRole('doc_controller', 'legal') || $document->owner_id === $request->user()->id, 403);
        $data = $request->validate([
            'related_system' => ['required', Rule::in(array_keys(DocumentLink::SYSTEMS))],
            'external_record_id' => 'required|string|max:80', 'relation_type' => 'required|string|max:40', 'label' => 'nullable|string|max:120',
        ]);
        $document->links()->create($data);
        AuditLogger::log('document.updated', $document, 'Tautan sistem '.DocumentLink::SYSTEMS[$data['related_system']].' #'.$data['external_record_id']);
        return back()->with('success', 'Tautan integrasi ditambahkan.');
    }

    public function acknowledge(Request $request, Document $document)
    {
        $this->authorizeDocument($document);
        abort_unless($document->status === 'published' && $document->current_version_id, 422);
        Acknowledgement::firstOrCreate(
            ['document_version_id' => $document->current_version_id, 'user_id' => $request->user()->id],
            ['document_id' => $document->id, 'acknowledged_at' => now()]
        );
        AuditLogger::log('acknowledged', $document, 'Menyatakan telah membaca & memahami '.$document->currentVersion->label());
        return back()->with('success', 'Terima kasih, pernyataan telah membaca tercatat.');
    }

    // ---------------- helpers ----------------

    private function lookups(): array
    {
        return [
            'entities' => Entity::orderBy('code')->get(),
            'departments' => Department::with('entity')->orderBy('name')->get(),
            'categories' => DocumentCategory::orderBy('module')->orderBy('name')->get(),
            'types' => DocumentType::with('category')->orderBy('name')->get(),
            'owners' => User::whereNotIn('role', ['service', 'auditor'])->orderBy('name')->get(),
            'retentionPolicies' => \App\Models\RetentionPolicy::orderBy('name')->get(),
        ];
    }

    private function validated(Request $request, ?Document $doc = null): array
    {
        $entityId = $request->input('entity_id', $doc?->entity_id);
        return $request->validate([
            'code' => ['nullable', 'string', 'max:80', Rule::unique('documents', 'code')->where('entity_id', $entityId)->ignore($doc?->id)],
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'entity_id' => 'required|exists:entities,id',
            'department_id' => 'required|exists:departments,id',
            'type_id' => 'required|exists:document_types,id',
            'classification' => ['required', Rule::in(array_keys(Document::CLASSIFICATIONS))],
            'owner_id' => 'required|exists:users,id',
            'effective_at' => 'nullable|date',
            'review_at' => 'nullable|date',
            'expires_at' => 'nullable|date',
            'retention_policy_id' => 'nullable|exists:retention_policies,id',
            'counterparty' => 'nullable|string|max:255',
            'obligations' => 'nullable|string|max:3000',
            'contract_value' => 'nullable|numeric|min:0',
            'tags' => 'nullable|string|max:255',
        ], [
            'code.unique' => 'Nomor dokumen sudah dipakai dalam entitas ini (duplikasi ditolak).',
        ]);
    }

    private function fileRules(bool $required): array
    {
        return [$required ? 'required' : 'nullable', 'file', 'max:'.config('archive.max_upload_kb'), 'mimes:'.implode(',', config('archive.allowed_mimes'))];
    }

    private function storeVersion(Document $doc, Request $request, int $major, int $minor, string $note): DocumentVersion
    {
        $file = $request->file('file');
        $contents = file_get_contents($file->getRealPath());
        $stored = DocumentStorage::put($doc, $contents);
        $mime = $file->getMimeType();

        $text = null;
        if ($mime === 'text/plain') $text = mb_substr($contents, 0, 200000);
        elseif ($mime === 'application/pdf') $text = $this->naivePdfText($contents);

        $v = $doc->versions()->create($stored + [
            'major' => $major, 'minor' => $minor, 'status' => 'draft', 'original_name' => $file->getClientOriginalName(),
            'mime' => $mime, 'change_note' => $note, 'uploaded_by' => $request->user()->id, 'content_text' => $text,
        ]);
        AuditLogger::log('version.uploaded', $doc, 'Versi '.$v->label().' diunggah: '.$v->original_name, ['size' => $v->size], null, null, null, $v->checksum);
        return $v;
    }

    /** Ekstraksi teks PDF sederhana untuk indeks (OCR penuh via queue worker opsional). */
    private function naivePdfText(string $pdf): ?string
    {
        preg_match_all('/\((.*?)\)\s*Tj/s', $pdf, $m);
        $t = trim(implode(' ', $m[1] ?? []));
        return $t !== '' ? mb_convert_encoding($t, 'UTF-8', 'Windows-1252') : null;
    }
}
