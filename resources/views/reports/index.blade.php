@extends('layouts.app')
@section('title', 'Laporan & KPI')
@section('header')
    <div class="section-title">M12 · Dashboard & Reports</div>
    <h1 class="text-2xl font-bold text-marun-800 mt-1">Laporan Tata Kelola & KPI</h1>
    <p class="text-sm text-slate-500">KPI keberhasilan (SRS §14), dokumen kedaluwarsa, review overdue, persetujuan tertunda, arsip tidak lengkap, aktivitas pengguna dan SLA pencarian.</p>
@endsection
@section('actions')
    @if(auth()->user()->can_bulk_export)<a href="{{ route('reports.export') }}" class="btn btn-outline">Ekspor register (CSV, MFA)</a>@endif
@endsection
@section('content')
<div class="grid md:grid-cols-3 gap-4 mb-6">
    @foreach($kpi as [$label, $val, $target, $t])
    <div class="card card-accent {{ $val >= $t ? 'border-l-emerald-600' : 'border-l-amber-500' }} p-4">
        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $label }}</div>
        <div class="flex items-end gap-2 mt-1"><span class="text-3xl font-bold {{ $val >= $t ? 'text-emerald-700' : 'text-amber-600' }}">{{ $val }}%</span><span class="text-sm text-slate-500 mb-1">target {{ $target }}</span></div>
        <div class="h-2 bg-slate-100 border border-slate-200 rounded mt-2 overflow-hidden"><div class="h-full {{ $val >= $t ? 'bg-emerald-500' : 'bg-amber-500' }}" style="width: {{ min(100,$val) }}%"></div></div>
    </div>
    @endforeach
    <div class="card card-accent {{ $criticalExpired->isEmpty() ? 'border-l-emerald-600' : 'border-l-rose-600' }} p-4">
        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Dokumen kritis kedaluwarsa tanpa tindak lanjut</div>
        <div class="text-3xl font-bold {{ $criticalExpired->isEmpty() ? 'text-emerald-700' : 'text-rose-600' }} mt-1">{{ $criticalExpired->count() }}</div><div class="text-sm text-slate-500">target 0</div>
    </div>
    <div class="card card-accent border-l-sky-600 p-4">
        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">SLA pencarian (30 hari)</div>
        <div class="text-3xl font-bold text-sky-700 mt-1">{{ $searchP95 !== null ? $searchP95.' ms' : '—' }}</div>
        <div class="text-sm text-slate-500">p95 · rata-rata {{ $searchAvg ?? '—' }} ms · {{ $searchCount }} pencarian · target ≤ 3.000 ms</div>
    </div>
    <div class="card card-accent border-l-navy-700 p-4">
        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Persetujuan tertunda</div>
        <div class="text-3xl font-bold text-navy-700 mt-1">{{ $pending->count() }}</div><div class="text-sm text-slate-500">{{ $pending->filter->isOverdue()->count() }} melewati SLA</div>
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-5 mb-6">
    <div class="card overflow-hidden" id="pending">
        <div class="px-4 py-3 border-b-2 border-indigo-600 section-title">Persetujuan tertunda</div>
        <div class="overflow-x-auto"><table class="tbl"><thead><tr><th>Dokumen</th><th>Tahap</th><th>PIC</th><th>Tenggat</th></tr></thead><tbody>
        @forelse($pending as $s)<tr><td><a class="text-navy-600 font-medium" href="{{ route('documents.show', $s->instance->document) }}">{{ \Illuminate\Support\Str::limit($s->instance->document->title, 45) }}</a></td><td>{{ $s->stageLabel() }}</td><td>{{ $s->assignee->name }}</td><td class="{{ $s->isOverdue() ? 'text-rose-700 font-semibold' : '' }} whitespace-nowrap">{{ $s->due_at?->format('d/m/Y') }}</td></tr>
        @empty <tr><td colspan="4" class="text-center text-slate-500">Tidak ada</td></tr>@endforelse
        </tbody></table></div>
    </div>
    <div class="card overflow-hidden" id="review">
        <div class="px-4 py-3 border-b-2 border-orange-500 section-title">Review periodik overdue</div>
        <div class="overflow-x-auto"><table class="tbl"><thead><tr><th>Dokumen</th><th>PIC</th><th>Jatuh tempo</th></tr></thead><tbody>
        @forelse($reviewOverdue as $d)<tr><td><a class="text-navy-600 font-medium" href="{{ route('documents.show', $d) }}">{{ \Illuminate\Support\Str::limit($d->title, 50) }}</a></td><td>{{ $d->owner->name }}</td><td class="text-orange-700 font-semibold whitespace-nowrap">{{ $d->review_at->format('d/m/Y') }}</td></tr>
        @empty <tr><td colspan="3" class="text-center text-slate-500">Tidak ada</td></tr>@endforelse
        </tbody></table></div>
    </div>
</div>

<div class="card overflow-hidden mb-6" id="incomplete">
    <div class="px-4 py-3 border-b-2 border-slate-600 section-title">Arsip tidak lengkap (metadata wajib kurang)</div>
    <div class="overflow-x-auto"><table class="tbl"><thead><tr><th>Dokumen</th><th>Departemen</th><th>PIC</th><th>Kekurangan</th></tr></thead><tbody>
    @forelse($incomplete as $d)<tr><td><a class="text-navy-600 font-medium" href="{{ route('documents.show', $d) }}">{{ $d->title }}</a><div class="doc-code text-[11px] text-slate-500">{{ $d->code }}</div></td><td>{{ $d->department->name }}</td><td>{{ $d->owner->name }}</td>
        <td>@foreach($d->missingMetadata() as $m)<span class="badge bg-amber-100 text-amber-800 mr-1 mb-1">{{ $m }}</span>@endforeach</td></tr>
    @empty <tr><td colspan="4" class="text-center text-slate-500">Semua lengkap</td></tr>@endforelse
    </tbody></table></div>
</div>

<div class="grid lg:grid-cols-2 gap-5">
    <div class="card overflow-hidden">
        <div class="px-4 py-3 border-b-2 border-teal-600 section-title">Aktivitas pengguna (30 hari)</div>
        <div class="overflow-x-auto"><table class="tbl"><thead><tr><th>Pengguna</th><th>Peran</th><th>Aktivitas</th><th>Terakhir</th></tr></thead><tbody>
        @foreach($activity as $a)<tr><td class="font-medium">{{ $a->actor?->name }}</td><td class="text-xs">{{ $a->actor?->roleLabel() }}</td><td>{{ $a->total }}</td><td class="text-xs whitespace-nowrap">{{ \Carbon\Carbon::parse($a->last_at)->format('d/m H:i') }}</td></tr>@endforeach
        </tbody></table></div>
    </div>
    <div class="card p-4">
        <div class="section-title mb-3">Distribusi event audit (30 hari)</div>
        @php $max = max(1, $eventsByType->max() ?? 1); @endphp
        @foreach($eventsByType->take(12) as $ev => $n)
            <div class="flex items-center gap-2 text-xs mb-1.5"><span class="w-40 shrink-0 truncate">{{ \App\Models\AuditEvent::EVENTS[$ev] ?? $ev }}</span><div class="flex-1 h-3 bg-slate-100 rounded overflow-hidden"><div class="h-full bg-navy-500" style="width: {{ $n / $max * 100 }}%"></div></div><span class="w-8 text-right font-semibold">{{ $n }}</span></div>
        @endforeach
    </div>
</div>
@endsection
