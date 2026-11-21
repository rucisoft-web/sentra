@extends('layouts.app')
@section('title', $meeting->title)
@section('header')
    <div class="flex flex-wrap gap-1.5 mb-1"><span class="badge bg-navy-50 text-navy-700 border border-navy-100">{{ \App\Models\Meeting::TYPES[$meeting->type] }}</span><x-cls :doc="null" :value="$meeting->classification"/><span class="doc-code text-xs">{{ $meeting->number }}</span></div>
    <h1 class="text-2xl font-bold text-navy-700">{{ $meeting->title }}</h1>
    <div class="text-sm text-slate-500">{{ $meeting->meeting_at->translatedFormat('l, d F Y H:i') }} · {{ $meeting->location }}</div>
@endsection
@section('actions')
    @if($meeting->minutes)
        @if($canOpenMinutes)<a href="{{ route('documents.show', $meeting->minutes) }}" class="btn btn-primary">Buka risalah resmi</a>
        @else<a href="{{ route('documents.show', $meeting->minutes) }}" class="btn btn-outline">🔒 Risalah (ajukan akses)</a>@endif
    @endif
@endsection
@section('content')
<div class="grid lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 space-y-5">
        <div class="card overflow-hidden">
            <div class="px-4 py-3 border-b-2 border-gold-500 section-title">Keputusan & tindak lanjut</div>
            <div class="divide-y divide-slate-100">
            @forelse($meeting->decisions as $dec)
                <div class="p-4">
                    <div class="flex gap-3"><span class="doc-code text-sm font-bold text-gold-600 shrink-0">{{ $dec->number }}</span><p class="text-sm text-slate-800">{{ $dec->decision }}</p></div>
                    @foreach($dec->actionItems as $a)
                        <div class="ml-0 sm:ml-12 mt-2 flex flex-wrap items-center gap-2 text-xs border-l-4 {{ $a->status === 'done' ? 'border-emerald-500' : ($a->isOverdue() ? 'border-rose-500' : 'border-teal-500') }} bg-slate-50 px-3 py-2 rounded">
                            <span class="font-semibold text-sm text-slate-700 flex-1 min-w-[200px]">{{ $a->title }}</span>
                            <span>PIC <b>{{ $a->pic->name }}</b></span><span>Tenggat {{ $a->due_at->format('d/m/Y') }}</span>
                            <span class="badge {{ $a->status === 'done' ? 'bg-emerald-100 text-emerald-800' : ($a->isOverdue() ? 'bg-rose-100 text-rose-800' : 'bg-teal-100 text-teal-800') }}">{{ $a->isOverdue() ? 'Terlambat' : \App\Models\ActionItem::STATUSES[$a->status] }}</span>
                            @if($a->evidence)<a href="{{ route('documents.show', $a->evidence) }}" class="text-navy-600 font-semibold">Bukti: {{ $a->evidence->code }}</a>@endif
                        </div>
                    @endforeach
                </div>
            @empty <div class="p-4"><x-empty/></div>
            @endforelse
            </div>
        </div>
    </div>
    <div class="space-y-5">
        <div class="card p-4 text-sm space-y-3">
            <div class="section-title">Informasi rapat</div>
            <div><div class="label">Pimpinan rapat</div>{{ $meeting->chair->name }}</div>
            <div><div class="label">Sekretaris</div>{{ $meeting->secretary?->name ?? '—' }}</div>
            <div><div class="label">Peserta</div>{{ $meeting->attendees }}</div>
            <div><div class="label">Agenda</div><div class="whitespace-pre-line">{{ $meeting->agenda }}</div></div>
        </div>
    </div>
</div>
@endsection
