@extends('layouts.app')
@section('title', $document->code)
@php
    $u = auth()->user();
    $cv = $document->currentVersion;
    $latest = $document->versions->first();
    $active = $document->workflows->firstWhere('status', 'in_progress');
    $hold = $document->holds->whereNull('released_at')->first();
    $draftVersion = $document->versions->firstWhere('status', 'draft');
    $approvedVersion = $document->versions->firstWhere('status', 'approved');
@endphp
@section('header')
    <div class="flex flex-wrap items-center gap-1.5 mb-2">
        <span class="doc-code text-xs text-navy-700 bg-navy-50 border-2 border-navy-100 rounded px-2 py-0.5">{{ $document->code }}</span>
        <x-cls :doc="$document"/><x-status :doc="$document"/><x-expiry :doc="$document"/>
        @if($hold)<span class="badge bg-rose-700 text-white">⚖ LEGAL HOLD</span>@endif
        @if($document->checked_out_by)<span class="badge bg-amber-100 text-amber-800">🔒 Check-out: {{ $document->checkedOutBy->name }}</span>@endif
        @if($document->isReviewOverdue())<span class="badge bg-orange-100 text-orange-800">Review overdue</span>@endif
    </div>
    <h1 class="text-xl sm:text-2xl font-bold text-navy-700 leading-tight">{{ $document->title }}</h1>
    <div class="text-sm text-slate-500 mt-1">{{ $document->category->name }} › {{ $document->type->name }} · {{ $document->entity->code }} · {{ $document->department->name }}</div>
@endsection
@section('actions')
    @if($cv && $cv->object_key)
        <a href="{{ route('documents.preview', [$document, $cv]) }}" class="btn btn-primary">Buka versi berlaku</a>
    @endif
    @if($canEdit && $document->status !== 'disposed')
        <a href="{{ route('documents.edit', $document) }}" class="btn btn-outline">Ubah metadata</a>
    @endif
@endsection
@section('content')
<div class="card card-accent border-l-emerald-600 px-4 py-2 mb-5 text-xs text-slate-600 flex flex-wrap gap-x-4 gap-y-1">
    <span><b>Dasar akses Anda:</b> {{ $accessReason }}</span>
    <span><b>Unduh:</b> {{ $canDownload ? 'diizinkan (tercatat)' : 'tidak — preview saja' }}</span>
</div>

@if($document->status === 'disposed')
<div class="card card-accent border-l-zinc-800 p-5 mb-5 bg-zinc-50">
    <div class="font-bold">Dokumen ini telah dimusnahkan pada {{ $document->disposed_at?->format('d M Y H:i') }}.</div>
    <div class="text-sm text-slate-600">Hanya metadata (tombstone) dan berita acara yang tersisa.
    @if($dr = $document->disposalRequests->firstWhere('status','executed'))<a class="text-navy-600 font-semibold" href="{{ route('disposal.certificate', $dr) }}">Lihat berita acara {{ $dr->certificate_number }} →</a>@endif</div>
</div>
@endif

<div class="grid xl:grid-cols-3 gap-5">
<div class="xl:col-span-2 space-y-5">

    {{-- Metadata --}}
    <div class="card overflow-hidden">
        <div class="px-4 py-3 border-b-2 border-navy-700 section-title">Metadata</div>
        <div class="p-4">
            <p class="text-sm text-slate-700 mb-4 whitespace-pre-line">{{ $document->description }}</p>
            <dl class="grid grid-cols-2 md:grid-cols-3 gap-x-4 gap-y-3 text-sm">
                @foreach([
                    'Pemilik / PIC' => $document->owner->name, 'Dibuat oleh' => $document->creator->name,
                    'Tanggal efektif' => $document->effective_at?->format('d M Y') ?? '—', 'Tanggal review' => $document->review_at?->format('d M Y') ?? '—',
                    'Berakhir' => $document->expires_at?->format('d M Y') ?? '—', 'Versi berlaku' => $cv?->label() ?? 'Belum ada (draft)',
                    'Jadwal retensi' => $document->retentionPolicy ? $document->retentionPolicy->code.' · '.$document->retentionPolicy->periodLabel() : '—',
                    'Retensi s.d.' => $document->retention_until?->format('d M Y') ?? ($document->retentionPolicy?->period_years ? 'Belum terpicu' : 'Permanen'),
                    'Dipublikasikan' => $document->published_at?->format('d M Y H:i') ?? '—',
                ] as $k => $v)
                    <div><dt class="text-[11px] uppercase tracking-wide text-slate-500 font-semibold">{{ $k }}</dt><dd class="font-medium text-slate-800">{{ $v }}</dd></div>
                @endforeach
                @if($document->counterparty)<div class="col-span-2"><dt class="text-[11px] uppercase tracking-wide text-slate-500 font-semibold">Pihak terkait</dt><dd class="font-medium">{{ $document->counterparty }}</dd></div>@endif
                @if($document->contract_value)<div><dt class="text-[11px] uppercase tracking-wide text-slate-500 font-semibold">Nilai kontrak</dt><dd class="font-medium">Rp {{ number_format($document->contract_value, 0, ',', '.') }}</dd></div>@endif
            </dl>
            @if($document->obligations)<div class="mt-4 p-3 bg-gold-50 border-l-4 border-gold-500 rounded text-sm"><b>Kewajiban:</b> {{ $document->obligations }}</div>@endif
            @if($document->tagList())<div class="mt-3 flex flex-wrap gap-1">@foreach($document->tagList() as $t)<a href="{{ route('search', ['q' => $t]) }}" class="badge bg-slate-100 text-slate-600 border border-slate-200">#{{ $t }}</a>@endforeach</div>@endif
            @if($document->supersededBy)<div class="mt-3 text-sm">Digantikan oleh: <a class="text-navy-600 font-semibold" href="{{ route('documents.show', $document->supersededBy) }}">{{ $document->supersededBy->code }} — {{ $document->supersededBy->title }}</a></div>@endif
        </div>
    </div>

    {{-- Versi --}}
    <div class="card overflow-hidden">
        <div class="px-4 py-3 border-b-2 border-indigo-600 flex justify-between items-center">
            <div class="section-title">Riwayat versi (M08)</div>
            @if($document->versions->count() > 1)<a href="{{ route('documents.compare', $document) }}" class="text-xs font-semibold text-navy-600">Bandingkan metadata →</a>@endif
        </div>
        <div class="divide-y divide-slate-100">
        @foreach($document->versions as $v)
            <div class="px-4 py-3 flex flex-col md:flex-row md:items-center gap-2 {{ $v->id === $document->current_version_id ? 'bg-emerald-50/60' : '' }}">
                <div class="flex items-center gap-2 md:w-40 shrink-0">
                    <span class="doc-code font-bold text-navy-700">{{ $v->label() }}</span>
                    <span class="badge {{ ['final'=>'bg-emerald-600 text-white','superseded'=>'bg-zinc-200 text-zinc-700','approved'=>'bg-teal-100 text-teal-800','in_review'=>'bg-indigo-100 text-indigo-800','draft'=>'bg-slate-100 text-slate-700','rejected'=>'bg-rose-100 text-rose-800','destroyed'=>'bg-zinc-800 text-white'][$v->status] ?? '' }}">{{ \App\Models\DocumentVersion::STATUSES[$v->status] }}</span>
                </div>
                <div class="flex-1 min-w-0 text-sm">
                    <div class="truncate">{{ $v->original_name ?? '—' }} <span class="text-slate-400">· {{ $v->sizeLabel() }}</span> @if($v->locked)<span title="Terkunci, tidak dapat ditimpa">🔒</span>@endif</div>
                    <div class="text-xs text-slate-500">{{ $v->change_note }} — {{ $v->uploader->name }}, {{ $v->created_at->format('d/m/Y') }}
                        @if($v->approver) · disahkan {{ $v->approver->name }} {{ $v->approved_at?->format('d/m/Y') }}@endif</div>
                    @if($v->checksum)<div class="doc-code text-[10.5px] text-slate-400 truncate" title="SHA-256">SHA-256 {{ $v->checksum }}</div>@endif
                </div>
                @if($v->object_key)
                <div class="flex gap-2 shrink-0">
                    <a href="{{ route('documents.preview', [$document, $v]) }}" class="btn btn-outline btn-sm">Preview</a>
                    @if($canDownload)<a href="{{ route('documents.download', [$document, $v]) }}" class="btn btn-outline btn-sm">Unduh</a>@endif
                </div>
                @endif
            </div>
        @endforeach
        </div>
    </div>

    {{-- Workflow --}}
    <div class="card overflow-hidden">
        <div class="px-4 py-3 border-b-2 border-gold-500 section-title">Alur review & pengesahan (M07)</div>
        <div class="p-4 space-y-4">
        @forelse($document->workflows as $wf)
            <div class="border-2 {{ $wf->status === 'in_progress' ? 'border-indigo-300' : 'border-slate-200' }} rounded-lg">
                <div class="px-3 py-2 bg-slate-50 border-b border-slate-200 flex flex-wrap gap-2 justify-between text-xs">
                    <span><b class="doc-code">{{ $wf->version->label() }}</b> · diajukan {{ $wf->submitted_at->format('d/m/Y H:i') }} · alur {{ $wf->mode === 'parallel' ? 'paralel' : 'serial' }}</span>
                    <span class="badge {{ ['in_progress'=>'bg-indigo-100 text-indigo-800','approved'=>'bg-emerald-100 text-emerald-800','revision'=>'bg-orange-100 text-orange-800','rejected'=>'bg-rose-100 text-rose-800'][$wf->status] ?? 'bg-slate-100' }}">{{ \App\Models\WorkflowInstance::STATUSES[$wf->status] }}</span>
                </div>
                <ol class="p-3 space-y-3">
                @foreach($wf->steps as $s)
                    @php $dot = ['approved'=>'bg-emerald-600','pending'=>'bg-indigo-600 ring-4 ring-indigo-100','revision'=>'bg-orange-500','rejected'=>'bg-rose-600','waiting'=>'bg-slate-300','skipped'=>'bg-slate-200'][$s->action]; @endphp
                    <li class="flex gap-3">
                        <span class="mt-1 w-3 h-3 rounded-full shrink-0 {{ $dot }}"></span>
                        <div class="flex-1 text-sm">
                            <div><b>{{ $s->stageLabel() }}</b> — {{ $s->assignee->name }} @if($s->delegate)<span class="text-xs text-slate-500">(didelegasikan ke {{ $s->delegate->name }})</span>@endif
                                <span class="text-xs text-slate-500">· {{ \App\Models\WorkflowStep::ACTIONS[$s->action] }}</span>
                                @if($s->mfa_verified)<span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200">MFA ✓</span>@endif
                                @if($s->isOverdue())<span class="badge bg-rose-100 text-rose-800">Lewat SLA {{ $s->due_at->format('d/m') }}</span>@elseif($s->action==='pending' && $s->due_at)<span class="text-xs text-slate-500">· tenggat {{ $s->due_at->format('d/m H:i') }}</span>@endif
                                @if($s->escalated_at)<span class="badge bg-rose-50 text-rose-700">Dieskalasi</span>@endif
                            </div>
                            @if($s->decided_at)<div class="text-xs text-slate-500">{{ $s->decided_at->format('d/m/Y H:i') }} oleh {{ $s->decider?->name }}</div>@endif
                            @if($s->comment)<div class="mt-1 text-sm bg-slate-50 border-l-4 border-slate-300 px-3 py-1.5 rounded whitespace-pre-line">{{ $s->comment }}</div>@endif
                        </div>
                    </li>
                @endforeach
                </ol>
            </div>
        @empty
            <x-empty text="Belum pernah diajukan untuk review."/>
        @endforelse
        </div>
    </div>

    {{-- Relasi & integrasi --}}
    <div class="grid md:grid-cols-2 gap-5">
        <div class="card overflow-hidden">
            <div class="px-4 py-3 border-b-2 border-teal-600 section-title">Relasi antardokumen</div>
            <div class="p-4 space-y-2 text-sm">
                @forelse($document->relations as $r)
                    <div><span class="text-xs text-slate-500">{{ \App\Models\DocumentRelation::TYPES[$r->relation_type] ?? $r->relation_type }}</span><br><a class="text-navy-600 font-medium" href="{{ route('documents.show', $r->related) }}">{{ $r->related->code }} — {{ $r->related->title }}</a></div>
                @empty @endforelse
                @foreach($document->inverseRelations as $r)
                    <div><span class="text-xs text-slate-500">Dirujuk oleh ({{ \App\Models\DocumentRelation::TYPES[$r->relation_type] ?? $r->relation_type }})</span><br><a class="text-navy-600 font-medium" href="{{ route('documents.show', $r->document) }}">{{ $r->document->code }} — {{ $r->document->title }}</a></div>
                @endforeach
                @if($document->relations->isEmpty() && $document->inverseRelations->isEmpty())<div class="text-slate-500 text-sm">Belum ada relasi.</div>@endif
                @if($u->hasRole('doc_controller','legal') || $document->owner_id === $u->id)
                <form method="POST" action="{{ route('documents.relations.store', $document) }}" class="pt-3 border-t border-slate-100 space-y-2">@csrf
                    <select name="relation_type" class="input">@foreach(\App\Models\DocumentRelation::TYPES as $k=>$l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
                    <select name="related_document_id" class="input">@foreach($allDocuments as $d)<option value="{{ $d->id }}">{{ $d->code }} — {{ \Illuminate\Support\Str::limit($d->title, 50) }}</option>@endforeach</select>
                    <button class="btn btn-outline btn-sm">+ Tambah relasi</button>
                </form>
                @endif
            </div>
        </div>
        <div class="card overflow-hidden">
            <div class="px-4 py-3 border-b-2 border-sky-600 section-title">Tautan sistem sumber</div>
            <div class="p-4 space-y-2 text-sm">
                @forelse($document->links as $l)
                    <div class="flex items-start gap-2"><span class="badge bg-sky-100 text-sky-800 shrink-0">{{ \App\Models\DocumentLink::SYSTEMS[$l->related_system] }}</span>
                    <div><span class="doc-code">{{ $l->external_record_id }}</span> <span class="text-xs text-slate-500">· {{ $l->relation_type }}</span>@if($l->label)<div class="text-xs text-slate-500">{{ $l->label }}</div>@endif</div></div>
                @empty <div class="text-slate-500">Tidak ada tautan.</div>
                @endforelse
                @if($u->hasRole('doc_controller','legal') || $document->owner_id === $u->id)
                <form method="POST" action="{{ route('documents.links.store', $document) }}" class="pt-3 border-t border-slate-100 grid grid-cols-2 gap-2">@csrf
                    <select name="related_system" class="input col-span-2">@foreach(\App\Models\DocumentLink::SYSTEMS as $k=>$l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
                    <input name="external_record_id" placeholder="ID record" class="input" required>
                    <input name="relation_type" placeholder="Relasi (mis. garansi)" class="input" required>
                    <button class="btn btn-outline btn-sm col-span-2">+ Tambah tautan</button>
                </form>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Panel aksi --}}
<div class="space-y-5">
    @if($document->type->requires_acknowledgement && $document->status === 'published')
    <div class="card card-accent border-l-sky-600 p-4">
        <div class="section-title mb-2">Acknowledgement karyawan</div>
        @if($acked)
            <div class="text-sm text-emerald-700 font-semibold">✓ Anda telah membaca {{ $cv->label() }} pada {{ $acked->acknowledged_at->format('d/m/Y H:i') }}</div>
        @else
            <p class="text-sm text-slate-600 mb-3">Dokumen ini wajib dibaca. Konfirmasi bahwa Anda telah membaca dan memahami versi {{ $cv?->label() }}.</p>
            <form method="POST" action="{{ route('documents.acknowledge', $document) }}">@csrf<button class="btn btn-success w-full">Saya telah membaca & memahami</button></form>
        @endif
        @if($u->hasRole('doc_controller','hr','bod_gm') || $document->owner_id === $u->id)<a href="{{ route('modules.acknowledgements', $document) }}" class="block text-xs font-semibold text-navy-600 mt-2">Lihat rekap acknowledgement →</a>@endif
    </div>
    @endif

    @if($canEdit && $document->status !== 'disposed')
    <div class="card card-accent border-l-indigo-600 p-4 space-y-3">
        <div class="section-title">Versi & check-out</div>
        @if(!$document->checked_out_by)
            <form method="POST" action="{{ route('documents.checkout', $document) }}">@csrf<button class="btn btn-outline btn-sm w-full">Check-out untuk revisi</button></form>
        @elseif($document->checked_out_by === $u->id || $u->hasRole('doc_controller'))
            <form method="POST" action="{{ route('documents.release', $document) }}">@csrf<button class="btn btn-outline btn-sm w-full">Lepas check-out</button></form>
        @endif
        @unless($active)
        <form method="POST" action="{{ route('documents.versions.store', $document) }}" enctype="multipart/form-data" class="space-y-2">@csrf
            <input type="file" name="file" class="input" required>
            <div class="flex gap-3 text-sm"><label><input type="radio" name="level" value="minor" checked> Minor ({{ $latest?->major ?? 0 }}.{{ ($latest?->minor ?? 0) + 1 }})</label><label><input type="radio" name="level" value="major"> Major ({{ ($latest?->major ?? 0) + 1 }}.0)</label></div>
            <input name="change_note" placeholder="Catatan perubahan" class="input" required>
            <button class="btn btn-primary btn-sm w-full">Unggah versi baru (check-in)</button>
        </form>
        @else
            <div class="text-xs text-slate-500">Versi {{ $active->version->label() }} sedang dalam proses persetujuan.</div>
        @endunless
    </div>

    @if($draftVersion && !$active)
    <div class="card card-accent border-l-gold-500 p-4">
        <div class="section-title mb-2">Ajukan review & pengesahan</div>
        <p class="text-xs text-slate-500 mb-3">Versi {{ $draftVersion->label() }}. Pengaju tidak boleh menjadi approver; dokumen material tidak boleh disahkan pembuatnya.</p>
        <form method="POST" action="{{ route('documents.submit', $document) }}" class="space-y-2">@csrf
            <label class="label">Reviewer (boleh lebih dari satu)</label>
            <select name="reviewers[]" multiple size="5" class="input">@foreach($reviewers as $r)@if($r->id !== $u->id)<option value="{{ $r->id }}">{{ $r->name }} — {{ $r->position }}</option>@endif @endforeach</select>
            <label class="label">Approver *</label>
            <select name="approver_id" class="input" required>@foreach($reviewers->whereIn('role',['bod_gm','dept_head','legal','finance','hr','doc_controller','owner']) as $r)@if($r->id !== $u->id)<option value="{{ $r->id }}">{{ $r->name }} — {{ $r->position }}</option>@endif @endforeach</select>
            <div class="grid grid-cols-2 gap-2">
                <div><label class="label">Alur</label><select name="mode" class="input"><option value="serial">Serial</option><option value="parallel">Paralel</option></select></div>
                <div><label class="label">SLA (hari)</label><input type="number" name="sla_days" value="3" min="1" max="30" class="input"></div>
            </div>
            <input name="note" placeholder="Catatan untuk reviewer" class="input">
            <button class="btn btn-gold w-full">Ajukan</button>
        </form>
    </div>
    @endif
    @endif

    @if($u->hasRole('doc_controller') && $approvedVersion)
    <div class="card card-accent border-l-emerald-600 p-4">
        <div class="section-title mb-2">Publikasi</div>
        <p class="text-xs text-slate-500 mb-2">Versi {{ $approvedVersion->label() }} telah disahkan oleh {{ $approvedVersion->approver?->name }}. Publikasi akan menggantikan versi berlaku sebelumnya.</p>
        <form method="POST" action="{{ route('documents.publish', $document) }}" class="space-y-2">@csrf
            <label class="label">Tanggal efektif</label><input type="date" name="effective_at" value="{{ today()->toDateString() }}" class="input">
            <button class="btn btn-success w-full">Publikasikan (MFA)</button>
        </form>
    </div>
    @endif

    @if($u->hasRole('doc_controller') && in_array($document->status, ['published']))
    <div class="card p-4">
        <div class="section-title mb-2">Arsipkan / supersede</div>
        <form method="POST" action="{{ route('documents.archive', $document) }}" class="space-y-2" data-confirm="Pindahkan dokumen ini ke arsip inaktif?">@csrf
            <select name="superseded_by_id" class="input"><option value="">Arsip inaktif (tanpa pengganti)</option>@foreach($allDocuments as $d)<option value="{{ $d->id }}">Digantikan oleh {{ $d->code }}</option>@endforeach</select>
            <input name="reason" class="input" placeholder="Alasan" required>
            <button class="btn btn-outline btn-sm w-full">Arsipkan</button>
        </form>
    </div>
    @endif

    {{-- ACL --}}
    <div class="card overflow-hidden">
        <div class="px-4 py-3 border-b-2 border-rose-500 section-title">Hak akses dokumen (ACL)</div>
        <div class="p-4 space-y-2 text-sm">
            <div class="text-xs text-slate-500">Default mengikuti klasifikasi <b>{{ $document->classificationLabel() }}</b>. Deny mengalahkan allow.</div>
            @forelse($document->acl as $a)
                <div class="flex items-start gap-2 py-1.5 border-b border-slate-100 {{ $a->isActive() ? '' : 'opacity-50 line-through' }}">
                    <div class="flex-1 min-w-0"><div class="font-medium">{{ $a->subjectLabel() }}</div>
                    <div class="text-xs text-slate-500">{{ \App\Models\DocumentAcl::PERMISSIONS[$a->permission] }} @if($a->expires_at)· s.d. {{ $a->expires_at->format('d/m/Y') }}@endif @if($a->conditions)· {{ $a->conditions }}@endif</div></div>
                    @if($document->owner_id === $u->id || $u->hasRole('doc_controller'))
                    <form method="POST" action="{{ route('documents.acl.destroy', [$document, $a]) }}">@csrf @method('DELETE')<button class="text-rose-600 text-xs font-semibold">Cabut</button></form>
                    @endif
                </div>
            @empty <div class="text-slate-500">Tidak ada ACL eksplisit.</div>
            @endforelse
            @if($document->owner_id === $u->id || $u->hasRole('doc_controller') || $document->department->head_user_id === $u->id)
            <form method="POST" action="{{ route('documents.acl.store', $document) }}" class="pt-2 space-y-2" id="acl-form">@csrf
                <div class="grid grid-cols-2 gap-2">
                    <select name="subject_type" class="input" onchange="document.querySelectorAll('[data-subj]').forEach(e=>{e.hidden=e.dataset.subj!==this.value;e.disabled=e.hidden})"><option value="user">Pengguna</option><option value="department">Departemen</option><option value="role">Peran</option></select>
                    <select name="permission" class="input">@foreach(\App\Models\DocumentAcl::PERMISSIONS as $k=>$l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
                </div>
                <select name="subject_id" data-subj="user" class="input">@foreach($reviewers as $r)<option value="{{ $r->id }}">{{ $r->name }}</option>@endforeach</select>
                <select name="subject_id" data-subj="department" class="input" hidden disabled>@foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select>
                <select name="subject_id" data-subj="role" class="input" hidden disabled>@foreach(\App\Models\User::ROLES as $k=>$l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
                <div><label class="label">Berlaku s.d. (opsional)</label><input type="date" name="expires_at" class="input"></div>
                <button class="btn btn-outline btn-sm w-full">+ Berikan akses</button>
            </form>
            @endif
        </div>
    </div>

    {{-- Retensi --}}
    @if($u->hasRole('legal','doc_controller') && $document->status !== 'disposed')
    <div class="card overflow-hidden">
        <div class="px-4 py-3 border-b-2 border-zinc-700 section-title">Retensi & legal hold (M10)</div>
        <div class="p-4 space-y-3 text-sm">
            @if($hold)
                <div class="bg-rose-50 border-l-4 border-rose-700 p-3 rounded"><b>Legal hold aktif</b> sejak {{ $hold->placed_at->format('d/m/Y') }} oleh {{ $hold->owner->name }}<br>{{ $hold->reason }} @if($hold->case_reference)<span class="doc-code text-xs">({{ $hold->case_reference }})</span>@endif</div>
                @if($u->hasRole('legal'))
                <form method="POST" action="{{ route('holds.release', $hold) }}" class="space-y-2">@csrf<input name="release_note" class="input" placeholder="Dasar pelepasan" required><button class="btn btn-outline btn-sm w-full">Lepas legal hold</button></form>
                @endif
            @else
                <form method="POST" action="{{ route('holds.store', $document) }}" class="space-y-2">@csrf
                    <input name="reason" class="input" placeholder="Alasan legal hold (sengketa, pemeriksaan…)" required>
                    <input name="case_reference" class="input" placeholder="Nomor perkara / referensi">
                    <button class="btn btn-danger btn-sm w-full">Pasang legal hold</button>
                </form>
                @if($u->hasRole('doc_controller') && in_array($document->status, ['archived','superseded','eligible_disposal']) && !$document->disposalRequests->whereIn('status',['pending','first_approved','approved'])->count())
                <form method="POST" action="{{ route('disposal.store', $document) }}" class="space-y-2 pt-3 border-t border-slate-100">@csrf
                    <div class="text-xs text-slate-500">Retensi s.d. {{ $document->retention_until?->format('d/m/Y') ?? '—' }}</div>
                    <input name="reason" class="input" placeholder="Dasar usulan pemusnahan" required>
                    <select name="method" class="input"><option>Penghapusan aman digital</option><option>Pencacahan fisik + penghapusan digital</option></select>
                    <button class="btn btn-outline btn-sm w-full">Usulkan pemusnahan</button>
                </form>
                @endif
            @endif
        </div>
    </div>
    @endif

    <div class="card overflow-hidden">
        <div class="px-4 py-3 border-b-2 border-slate-600 section-title">Jejak audit dokumen</div>
        <ul class="p-4 space-y-2.5 text-xs max-h-96 overflow-y-auto">
            @foreach($history as $e)
                <li class="border-l-2 border-slate-200 pl-3"><div class="text-slate-400">{{ $e->created_at->format('d/m/Y H:i') }} · {{ $e->actor?->name ?? 'Sistem' }}</div><div><b>{{ $e->eventLabel() }}</b> {{ $e->description }}</div></li>
            @endforeach
        </ul>
    </div>
</div>
</div>
@endsection
