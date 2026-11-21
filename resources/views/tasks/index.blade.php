@extends('layouts.app')
@section('title', 'Tugas Saya')
@section('header')
    <div class="section-title">M07 · Workflow & e-approval</div>
    <h1 class="text-2xl font-bold text-navy-700 mt-1">Tugas Review & Persetujuan</h1>
    <p class="text-sm text-slate-500">Pengesahan memerlukan MFA. Revisi/penolakan wajib disertai alasan; riwayat tidak hilang.</p>
@endsection
@section('content')
<div class="space-y-4 mb-8">
@forelse($pending as $s)
    @php $doc = $s->instance->document; @endphp
    <div class="card card-accent {{ $s->stage === 'approval' ? 'border-l-gold-500' : 'border-l-indigo-600' }} overflow-hidden">
        <div class="p-4 flex flex-col md:flex-row gap-4">
            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap gap-1.5 mb-1">
                    <span class="badge {{ $s->stage === 'approval' ? 'bg-gold-500 text-white' : 'bg-indigo-600 text-white' }}">{{ $s->stageLabel() }}</span>
                    <span class="doc-code text-xs bg-navy-50 border border-navy-100 rounded px-1.5 py-0.5">{{ $doc->code }}</span>
                    <span class="doc-code text-xs font-bold">{{ $s->instance->version->label() }}</span>
                    <x-cls :doc="$doc"/>
                    @if($s->isOverdue())<span class="badge bg-rose-600 text-white">Lewat SLA</span>@endif
                    @if($s->delegated_to_id)<span class="badge bg-sky-100 text-sky-800">Delegasi dari {{ $s->assignee->name }}</span>@endif
                </div>
                <a href="{{ route('documents.show', $doc) }}" class="font-semibold text-lg text-slate-800 hover:text-navy-600">{{ $doc->title }}</a>
                <div class="text-sm text-slate-500">{{ $doc->type->name }} · {{ $doc->department->name }} · diajukan {{ $s->instance->submitter->name }}, {{ $s->instance->submitted_at->format('d/m/Y H:i') }} · tenggat {{ $s->due_at?->format('d/m/Y H:i') }}</div>
                @if($s->instance->note)<div class="mt-2 text-sm bg-slate-50 border-l-4 border-slate-300 px-3 py-1.5 rounded">“{{ $s->instance->note }}”</div>@endif
                <div class="mt-3 flex flex-wrap gap-2 text-xs">
                    @foreach($s->instance->steps as $st)
                        <span class="px-2 py-1 rounded border-2 {{ $st->id === $s->id ? 'border-gold-500 bg-gold-50 font-semibold' : ($st->action === 'approved' ? 'border-emerald-300 bg-emerald-50' : 'border-slate-200') }}">{{ $st->sequence }}. {{ $st->stageLabel() }} · {{ $st->assignee->name }}</span>
                    @endforeach
                </div>
                <div class="mt-3 flex gap-2"><a href="{{ route('documents.preview', [$doc, $s->instance->version]) }}" class="btn btn-outline btn-sm">Buka versi yang direview</a></div>
            </div>
            <div class="md:w-80 shrink-0">
                <form method="POST" action="{{ route('tasks.decide', $s) }}" class="space-y-2">@csrf
                    <textarea name="comment" rows="3" class="input" placeholder="Catatan / alasan (wajib untuk revisi & tolak)"></textarea>
                    <div class="grid grid-cols-3 gap-2">
                        <button name="decision" value="approved" class="btn btn-success btn-sm">{{ $s->stage === 'approval' ? 'Sahkan' : 'Setujui' }}</button>
                        <button name="decision" value="revision" class="btn btn-outline btn-sm">Revisi</button>
                        <button name="decision" value="rejected" class="btn btn-danger btn-sm">Tolak</button>
                    </div>
                </form>
                <details class="mt-2 text-sm">
                    <summary class="cursor-pointer text-navy-600 font-semibold text-xs">Delegasikan sementara</summary>
                    <form method="POST" action="{{ route('tasks.delegate', $s) }}" class="mt-2 space-y-2">@csrf
                        <select name="to_id" class="input">@foreach($users as $usr)<option value="{{ $usr->id }}">{{ $usr->name }} — {{ $usr->position }}</option>@endforeach</select>
                        <input name="reason" class="input" placeholder="Alasan (cuti, dinas…)" required>
                        <button class="btn btn-outline btn-sm w-full">Delegasikan</button>
                    </form>
                </details>
            </div>
        </div>
    </div>
@empty
    <x-empty text="Tidak ada tugas yang menunggu keputusan Anda."/>
@endforelse
</div>

<div class="grid lg:grid-cols-2 gap-5">
    <div class="card overflow-hidden">
        <div class="px-4 py-3 border-b-2 border-navy-700 section-title">Pengajuan saya</div>
        <div class="divide-y divide-slate-100">
        @forelse($submitted as $wf)
            <a href="{{ route('documents.show', $wf->document) }}" class="px-4 py-3 flex items-center gap-3 hover:bg-slate-50">
                <div class="flex-1 min-w-0"><div class="text-sm font-medium truncate">{{ $wf->document->title }}</div>
                <div class="text-xs text-slate-500">{{ $wf->version->label() }} · {{ $wf->submitted_at->format('d/m/Y') }} · {{ $wf->steps->where('action','approved')->count() }}/{{ $wf->steps->count() }} langkah</div></div>
                <span class="badge {{ ['in_progress'=>'bg-indigo-100 text-indigo-800','approved'=>'bg-emerald-100 text-emerald-800','revision'=>'bg-orange-100 text-orange-800','rejected'=>'bg-rose-100 text-rose-800'][$wf->status] ?? '' }}">{{ \App\Models\WorkflowInstance::STATUSES[$wf->status] }}</span>
            </a>
        @empty <div class="p-4"><x-empty/></div>
        @endforelse
        </div>
    </div>
    <div class="card overflow-hidden">
        <div class="px-4 py-3 border-b-2 border-slate-500 section-title">Keputusan yang pernah saya ambil</div>
        <div class="divide-y divide-slate-100">
        @forelse($history as $s)
            <a href="{{ route('documents.show', $s->instance->document) }}" class="px-4 py-3 flex items-center gap-3 hover:bg-slate-50">
                <div class="flex-1 min-w-0"><div class="text-sm font-medium truncate">{{ $s->instance->document->title }}</div><div class="text-xs text-slate-500">{{ $s->stageLabel() }} · {{ $s->decided_at->format('d/m/Y H:i') }}</div></div>
                <span class="badge bg-slate-100 text-slate-700">{{ \App\Models\WorkflowStep::ACTIONS[$s->action] }}</span>
            </a>
        @empty <div class="p-4"><x-empty/></div>
        @endforelse
        </div>
    </div>
</div>
@endsection
