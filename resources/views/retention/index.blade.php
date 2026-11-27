@extends('layouts.app')
@section('title', 'Retensi & Legal Hold')
@section('header')
    <div class="section-title">M10 · Retention & Records</div>
    <h1 class="text-2xl font-bold text-marun-800 mt-1">Retensi, Legal Hold & Pemusnahan</h1>
    <p class="text-sm text-slate-500">Legal hold mengesampingkan pemusnahan. Pemusnahan memerlukan persetujuan ganda (Legal → BOD/GM), verifikasi retensi, MFA, dan berita acara.</p>
@endsection
@section('content')
@php $u = auth()->user(); @endphp
<div class="card overflow-hidden mb-6">
    <div class="px-4 py-3 border-b-2 border-gold-500 section-title">Usulan pemusnahan</div>
    <div class="divide-y divide-slate-100">
    @forelse($disposals as $dr)
        <div class="p-4 flex flex-col lg:flex-row gap-3">
            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap gap-1.5 mb-1"><span class="badge {{ ['pending'=>'bg-amber-100 text-amber-800','first_approved'=>'bg-indigo-100 text-indigo-800','approved'=>'bg-teal-100 text-teal-800','rejected'=>'bg-rose-100 text-rose-800','executed'=>'bg-zinc-800 text-white'][$dr->status] }}">{{ \App\Models\DisposalRequest::STATUSES[$dr->status] }}</span>
                @if($dr->document->hasActiveHold())<span class="badge bg-rose-700 text-white">⚖ Legal hold aktif — diblokir</span>@endif</div>
                <a href="{{ route('documents.show', $dr->document) }}" class="font-semibold text-navy-700">{{ $dr->document->code }} — {{ $dr->document->title }}</a>
                <div class="text-xs text-slate-500 mt-1">Diusulkan {{ $dr->requester->name }} · {{ $dr->created_at->format('d/m/Y') }} · metode: {{ $dr->method }}</div>
                <div class="text-sm mt-1">{{ $dr->reason }}</div>
                <div class="text-xs mt-2 flex flex-wrap gap-3">
                    <span>① Legal: {!! $dr->firstApprover ? '<b class="text-emerald-700">'.e($dr->firstApprover->name).'</b> '.$dr->first_approved_at->format('d/m/Y') : '<span class="text-slate-400">menunggu</span>' !!}</span>
                    <span>② BOD/GM: {!! $dr->secondApprover ? '<b class="text-emerald-700">'.e($dr->secondApprover->name).'</b> '.$dr->second_approved_at->format('d/m/Y') : '<span class="text-slate-400">menunggu</span>' !!}</span>
                    @if($dr->rejection_note)<span class="text-rose-700">Ditolak: {{ $dr->rejection_note }}</span>@endif
                </div>
            </div>
            <div class="lg:w-72 shrink-0 space-y-2">
                @if(($dr->status === 'pending' && $u->hasRole('legal')) || ($dr->status === 'first_approved' && $u->hasRole('bod_gm')))
                    <form method="POST" action="{{ route('disposal.approve', $dr) }}">@csrf<button class="btn btn-success btn-sm w-full">Setujui (MFA)</button></form>
                    <form method="POST" action="{{ route('disposal.reject', $dr) }}" class="flex gap-2">@csrf<input name="rejection_note" class="input !py-1 text-sm" placeholder="Alasan tolak" required><button class="btn btn-danger btn-sm">Tolak</button></form>
                @endif
                @if($dr->status === 'approved' && $u->hasRole('doc_controller'))
                    <form method="POST" action="{{ route('disposal.execute', $dr) }}" data-confirm="Eksekusi pemusnahan permanen? File akan dihapus dan tidak dapat dipulihkan.">@csrf<button class="btn btn-danger w-full">Eksekusi pemusnahan (MFA)</button></form>
                @endif
                @if($dr->status === 'executed')<a href="{{ route('disposal.certificate', $dr) }}" class="btn btn-outline btn-sm w-full">Berita acara {{ $dr->certificate_number }}</a>@endif
            </div>
        </div>
    @empty <div class="p-4"><x-empty/></div>
    @endforelse
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-5 mb-6">
    <div class="card overflow-hidden">
        <div class="px-4 py-3 border-b-2 border-rose-600 section-title">Legal hold</div>
        <div class="divide-y divide-slate-100">
        @forelse($holds as $h)
            <div class="p-4 {{ $h->released_at ? 'opacity-60' : '' }}">
                <div class="flex justify-between gap-2"><a href="{{ route('documents.show', $h->document) }}" class="font-semibold text-navy-700 text-sm">{{ $h->document->title }}</a>
                <span class="badge {{ $h->released_at ? 'bg-slate-100 text-slate-600' : 'bg-rose-700 text-white' }} shrink-0">{{ $h->released_at ? 'Dilepas' : 'Aktif' }}</span></div>
                <div class="text-xs text-slate-500 mt-0.5">{{ $h->case_reference }} · oleh {{ $h->owner->name }} sejak {{ $h->placed_at->format('d/m/Y') }}</div>
                <div class="text-sm mt-1">{{ $h->reason }}</div>
                @if($h->released_at)<div class="text-xs mt-1">Dilepas {{ $h->released_at->format('d/m/Y') }} oleh {{ $h->releaser?->name }}: {{ $h->release_note }}</div>@endif
            </div>
        @empty <div class="p-4"><x-empty/></div>
        @endforelse
        </div>
    </div>
    <div class="card overflow-hidden">
        <div class="px-4 py-3 border-b-2 border-amber-500 section-title">Masa retensi berakhir / layak musnah</div>
        <div class="divide-y divide-slate-100">
        @forelse($eligible as $d)
            <a href="{{ route('documents.show', $d) }}" class="p-4 flex items-center gap-3 hover:bg-slate-50">
                <div class="flex-1 min-w-0"><div class="font-medium text-sm">{{ $d->title }}</div><div class="text-xs text-slate-500">{{ $d->retentionPolicy?->code }} · retensi s.d. {{ $d->retention_until?->format('d/m/Y') }}</div></div>
                @if($d->holds->whereNull('released_at')->count())<span class="badge bg-rose-700 text-white">Hold</span>@else<x-status :doc="$d"/>@endif
            </a>
        @empty <div class="p-4"><x-empty text="Tidak ada arsip yang melewati masa retensi."/></div>
        @endforelse
        </div>
    </div>
</div>

<div class="card overflow-hidden mb-6">
    <div class="px-4 py-3 border-b-2 border-navy-700 section-title">Jadwal retensi per kategori</div>
    <div class="overflow-x-auto">
    <table class="tbl">
        <thead><tr><th>Kode</th><th>Jadwal</th><th>Pemicu</th><th>Periode</th><th>Tindakan akhir</th><th>Dasar</th><th>Dok.</th></tr></thead>
        <tbody>
        @foreach($policies as $p)
            <tr><td class="doc-code text-xs">{{ $p->code }}</td><td class="font-medium">{{ $p->name }}</td><td class="text-sm">{{ \App\Models\RetentionPolicy::TRIGGERS[$p->trigger] }}</td>
            <td><span class="badge {{ $p->period_years ? 'bg-sky-100 text-sky-800' : 'bg-gold-100 text-gold-600' }}">{{ $p->periodLabel() }}</span></td>
            <td class="text-sm">{{ \App\Models\RetentionPolicy::ACTIONS[$p->disposal_action] }}</td><td class="text-xs text-slate-500 max-w-xs">{{ $p->legal_basis }}</td><td>{{ $p->documents_count }}</td></tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="px-4 py-3 border-b-2 border-stone-500 section-title">Arsip inaktif & digantikan</div>
    <div class="p-3 space-y-2">@forelse($inactive as $d)<x-doc-row :doc="$d"/>@empty<x-empty/>@endforelse</div>
</div>
@endsection
