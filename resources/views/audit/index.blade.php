@extends('layouts.app')
@section('title', 'Audit Trail')
@section('header')
    <div class="section-title">M11 · Audit & Security</div>
    <h1 class="text-2xl font-bold text-navy-700 mt-1">Audit Trail</h1>
    <p class="text-sm text-slate-500">Log lihat/unduh/ubah/approve/ekspor bersifat append-only dan berantai hash SHA-256 (tamper-evident). Tidak dapat diubah atau dihapus melalui aplikasi.</p>
@endsection
@section('actions')
    @if(auth()->user()->hasRole('doc_controller','it_admin','auditor','legal'))
    <form method="POST" action="{{ route('audit.verify') }}">@csrf<button class="btn btn-gold">Verifikasi integritas rantai</button></form>
    @endif
@endsection
@section('content')
@if($verify)
    <div class="card card-accent {{ $verify['ok'] ? 'border-l-emerald-600 bg-emerald-50' : 'border-l-rose-600 bg-rose-50' }} p-4 mb-5 text-sm">
        @if($verify['ok']) ✓ Rantai hash utuh — {{ number_format($verify['checked']) }} event terverifikasi, tidak ada perubahan terdeteksi.
        @else ✗ Rantai rusak pada event #{{ $verify['broken_at'] }} — indikasi manipulasi log! @endif
    </div>
@endif
@if($anomalies->isNotEmpty())
<div class="card card-accent border-l-rose-600 p-4 mb-5">
    <div class="section-title mb-2">Monitoring anomali (7 hari)</div>
    <div class="flex flex-wrap gap-2">
        @foreach($anomalies as $a)<span class="badge bg-rose-50 text-rose-800 border border-rose-200 !text-xs !py-1">{{ $a->actor?->name ?? 'Tidak dikenal' }} · {{ \App\Models\AuditEvent::EVENTS[$a->event] ?? $a->event }} × {{ $a->total }}</span>@endforeach
    </div>
</div>
@endif
<form class="card p-3 mb-4 grid grid-cols-2 md:grid-cols-6 gap-2">
    <input name="q" value="{{ request('q') }}" class="input col-span-2" placeholder="Cari deskripsi">
    <select name="event" class="input"><option value="">Semua event</option>@foreach(\App\Models\AuditEvent::EVENTS as $k=>$l)<option value="{{ $k }}" @selected(request('event')===$k)>{{ $l }}</option>@endforeach</select>
    <select name="actor_id" class="input"><option value="">Semua pengguna</option>@foreach($users as $usr)<option value="{{ $usr->id }}" @selected(request('actor_id')==$usr->id)>{{ $usr->name }}</option>@endforeach</select>
    <input type="date" name="from" value="{{ request('from') }}" class="input">
    <button class="btn btn-primary">Filter</button>
</form>
<div class="card overflow-hidden">
    <div class="hidden md:block overflow-x-auto">
    <table class="tbl">
        <thead><tr><th>#</th><th>Waktu</th><th>Pengguna</th><th>Event</th><th>Deskripsi</th><th>IP / perangkat</th><th>Hash</th></tr></thead>
        <tbody>
        @foreach($events as $e)
            <tr>
                <td class="text-xs text-slate-400">{{ $e->id }}</td>
                <td class="whitespace-nowrap text-xs">{{ $e->created_at->format('d/m/Y H:i:s') }}</td>
                <td class="text-sm">{{ $e->actor?->name ?? 'Sistem' }}</td>
                <td><span class="badge {{ str_contains($e->event,'denied') || str_contains($e->event,'failed') || str_contains($e->event,'blocked') ? 'bg-rose-100 text-rose-800' : (str_contains($e->event,'download') || $e->event==='export' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700') }}">{{ $e->eventLabel() }}</span></td>
                <td class="text-sm max-w-md">{{ $e->description }} @if($e->resource_type)<span class="text-xs text-slate-400">[{{ $e->resource_type }} #{{ $e->resource_id }}]</span>@endif</td>
                <td class="text-xs text-slate-500">{{ $e->ip }}<div class="truncate max-w-[140px]" title="{{ $e->device }}">{{ $e->device }}</div></td>
                <td class="doc-code text-[10px] text-slate-400" title="prev: {{ $e->prev_hash }}">{{ substr($e->hash, 0, 12) }}…</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
    <div class="md:hidden divide-y divide-slate-100">
        @foreach($events as $e)
            <div class="p-3 text-sm"><div class="flex justify-between gap-2"><b>{{ $e->eventLabel() }}</b><span class="text-xs text-slate-400">{{ $e->created_at->format('d/m H:i') }}</span></div>
            <div class="text-xs text-slate-500">{{ $e->actor?->name ?? 'Sistem' }} · {{ $e->ip }}</div><div class="text-sm">{{ $e->description }}</div></div>
        @endforeach
    </div>
</div>
<div class="mt-4">{{ $events->links() }}</div>
@endsection
