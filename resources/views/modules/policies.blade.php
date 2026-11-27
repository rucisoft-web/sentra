@extends('layouts.app')
@section('title', 'Kebijakan & SOP')
@section('header')
    <div class="section-title">M03 · Policy, SOP & Organisasi</div>
    <h1 class="text-2xl font-bold text-marun-800 mt-1">Pusat Kebijakan, SOP & Struktur Organisasi</h1>
    <p class="text-sm text-slate-500">Hanya versi berlaku yang ditampilkan. Dokumen wajib baca memerlukan acknowledgement karyawan; review periodik dipantau.</p>
@endsection
@section('content')
<div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
@forelse($docs as $d)
    @php $acked = in_array($d->current_version_id, $ackIds); $cnt = $ackCounts[$d->current_version_id] ?? 0; $pct = $totalUsers ? round($cnt / $totalUsers * 100) : 0; @endphp
    <div class="card card-accent {{ $d->isReviewOverdue() ? 'border-l-orange-500' : 'border-l-navy-500' }} p-4 flex flex-col">
        <div class="flex flex-wrap gap-1.5 mb-2"><span class="badge bg-navy-50 text-navy-700 border border-navy-100">{{ $d->type->name }}</span><span class="doc-code text-xs font-bold">{{ $d->currentVersion?->label() }}</span>
            @if($d->isReviewOverdue())<span class="badge bg-orange-100 text-orange-800">Review overdue</span>@endif</div>
        <a href="{{ route('documents.show', $d) }}" class="font-semibold text-slate-800 hover:text-navy-600 leading-snug">{{ $d->title }}</a>
        <div class="text-xs text-slate-500 mt-1 doc-code">{{ $d->code }}</div>
        <div class="text-xs text-slate-500 mt-1">{{ $d->department->name }} · efektif {{ $d->effective_at?->format('d M Y') }} · review {{ $d->review_at?->format('d M Y') ?? '—' }}</div>
        <div class="mt-auto pt-3">
        @if($d->type->requires_acknowledgement)
            <div class="flex justify-between text-xs mb-1"><span class="text-slate-500">Acknowledgement</span><span class="font-semibold">{{ $cnt }}/{{ $totalUsers }} ({{ $pct }}%)</span></div>
            <div class="h-2 bg-slate-100 rounded border border-slate-200 overflow-hidden"><div class="h-full bg-emerald-500" style="width: {{ $pct }}%"></div></div>
            <div class="mt-2 text-xs font-semibold {{ $acked ? 'text-emerald-700' : 'text-amber-700' }}">{{ $acked ? '✓ Anda sudah membaca' : '● Anda belum membaca' }}</div>
        @endif
        </div>
    </div>
@empty <x-empty/>
@endforelse
</div>
@endsection
