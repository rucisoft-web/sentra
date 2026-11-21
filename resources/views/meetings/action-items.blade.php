@extends('layouts.app')
@section('title', 'Action Item')
@section('header')
    <div class="section-title">M04 · Tindak lanjut keputusan</div>
    <h1 class="text-2xl font-bold text-navy-700 mt-1">Action Item</h1>
    <p class="text-sm text-slate-500">Penyelesaian wajib disertai bukti tindak lanjut (dokumen arsip atau catatan).</p>
@endsection
@section('content')
<div class="flex flex-wrap gap-2 mb-4 text-sm">
    @foreach(['' => 'Semua', 'overdue' => 'Terlambat', 'open' => 'Terbuka', 'in_progress' => 'Dikerjakan', 'done' => 'Selesai'] as $k => $l)
        <a href="{{ route('action-items.index', $k ? ['status' => $k] : []) }}" class="px-3 py-1.5 rounded-md border-2 font-semibold {{ request('status', '') === $k ? 'border-navy-700 bg-navy-700 text-white' : 'border-slate-200 bg-white text-slate-600' }}">{{ $l }}</a>
    @endforeach
</div>
<div class="space-y-3">
@forelse($items as $a)
    <div class="card card-accent {{ $a->status === 'done' ? 'border-l-emerald-600' : ($a->isOverdue() ? 'border-l-rose-600' : 'border-l-teal-600') }} p-4">
        <div class="flex flex-col md:flex-row gap-3">
            <div class="flex-1 min-w-0">
                <div class="font-semibold text-slate-800">{{ $a->title }}</div>
                <div class="text-xs text-slate-500 mt-0.5">@if($a->meeting)<a class="text-navy-600" href="{{ route('meetings.show', $a->meeting) }}">{{ $a->meeting->title }}</a>@endif @if($a->decision)· Keputusan {{ $a->decision->number }}@endif</div>
                <div class="text-sm mt-2 flex flex-wrap gap-x-4 gap-y-1">
                    <span>PIC <b>{{ $a->pic->name }}</b> <span class="text-slate-500">({{ $a->pic->department?->name }})</span></span>
                    <span class="{{ $a->isOverdue() ? 'text-rose-700 font-semibold' : '' }}">Tenggat {{ $a->due_at->format('d M Y') }}{{ $a->isOverdue() ? ' · terlambat '.(int)$a->due_at->diffInDays(today()).' hari' : '' }}</span>
                    <span class="badge {{ $a->status === 'done' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">{{ \App\Models\ActionItem::STATUSES[$a->status] }}</span>
                </div>
                @if($a->evidence || $a->evidence_note)<div class="mt-2 text-xs bg-emerald-50 border-l-4 border-emerald-500 px-3 py-1.5 rounded">Bukti: @if($a->evidence)<a class="font-semibold text-navy-600" href="{{ route('documents.show', $a->evidence) }}">{{ $a->evidence->code }}</a>@endif {{ $a->evidence_note }}</div>@endif
            </div>
            @if($a->pic_id === auth()->id() || auth()->user()->hasRole('bod_gm','doc_controller'))
            <form method="POST" action="{{ route('action-items.update', $a) }}" class="md:w-72 space-y-2 shrink-0">@csrf @method('PUT')
                <select name="status" class="input">@foreach(\App\Models\ActionItem::STATUSES as $k=>$l)<option value="{{ $k }}" @selected($a->status===$k)>{{ $l }}</option>@endforeach</select>
                <select name="evidence_document_id" class="input"><option value="">— Dokumen bukti (opsional) —</option>@foreach($evidenceDocs as $d)<option value="{{ $d->id }}" @selected($a->evidence_document_id===$d->id)>{{ $d->code }}</option>@endforeach</select>
                <input name="evidence_note" value="{{ $a->evidence_note }}" class="input" placeholder="Catatan bukti">
                <button class="btn btn-outline btn-sm w-full">Perbarui</button>
            </form>
            @endif
        </div>
    </div>
@empty <x-empty/>
@endforelse
</div>
@endsection
