@extends('layouts.app')
@section('title', 'Rapat & Keputusan')
@section('header')
    <div class="section-title">M04 · Board & Management Records</div>
    <h1 class="text-2xl font-bold text-marun-800 mt-1">Rapat, Risalah & Keputusan</h1>
    <p class="text-sm text-slate-500">Agenda, notulen, keputusan, action item, PIC, tenggat dan bukti tindak lanjut — dengan akses khusus.</p>
@endsection
@section('actions')<a href="{{ route('action-items.index') }}" class="btn btn-outline">Lihat semua action item</a>@endsection
@section('content')
<div class="space-y-3">
@forelse($meetings as $m)
    @php $open = $m->actionItems->where('status','!=','done'); $late = $open->filter->isOverdue(); @endphp
    <a href="{{ route('meetings.show', $m) }}" class="card card-accent {{ $m->type === 'rups' ? 'border-l-gold-500' : ($m->type === 'bod' ? 'border-l-navy-700' : 'border-l-teal-600') }} p-4 flex flex-col md:flex-row md:items-center gap-3 hover:shadow">
        <div class="md:w-20 shrink-0 flex md:flex-col items-center md:items-center gap-2 md:gap-0 text-navy-700">
            <div class="text-2xl font-bold leading-none">{{ $m->meeting_at->format('d') }}</div>
            <div class="text-xs uppercase font-semibold">{{ $m->meeting_at->translatedFormat('M Y') }}</div>
        </div>
        <div class="flex-1 min-w-0">
            <div class="flex flex-wrap gap-1.5 mb-1"><span class="badge bg-navy-50 text-navy-700 border border-navy-100">{{ \App\Models\Meeting::TYPES[$m->type] }}</span><x-cls :doc="null" :value="$m->classification"/><span class="doc-code text-[11px] text-slate-500">{{ $m->number }}</span></div>
            <div class="font-semibold text-slate-800">{{ $m->title }}</div>
            <div class="text-xs text-slate-500">Pimpinan: {{ $m->chair->name }} · {{ $m->location }}</div>
        </div>
        <div class="flex gap-4 text-center text-xs shrink-0">
            <div><div class="text-lg font-bold text-navy-700">{{ $m->decisions->count() }}</div>Keputusan</div>
            <div><div class="text-lg font-bold text-teal-700">{{ $open->count() }}</div>Terbuka</div>
            <div><div class="text-lg font-bold {{ $late->count() ? 'text-rose-600' : 'text-slate-400' }}">{{ $late->count() }}</div>Terlambat</div>
        </div>
    </a>
@empty <x-empty/>
@endforelse
</div>
@endsection
