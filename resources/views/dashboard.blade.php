@extends('layouts.app')
@section('title', 'Dashboard')
@section('header')
    <div class="text-sm text-slate-500">{{ now()->translatedFormat('l, d F Y') }}</div>
    <h1 class="text-2xl font-bold text-marun-800">Selamat datang, {{ auth()->user()->name }}</h1>
    <div class="text-sm text-slate-500">{{ auth()->user()->position }} · {{ auth()->user()->department?->name }}</div>
@endsection
@section('actions')
    @if(!auth()->user()->hasRole('auditor','it_admin','owner'))<a href="{{ route('documents.create') }}" class="btn btn-gold">+ Daftarkan Dokumen</a>@endif
    <a href="{{ route('search') }}" class="btn btn-outline">Cari Arsip</a>
@endsection
@section('content')

@if($executive)
<div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-7 gap-3 mb-6">
    <x-stat label="Total arsip" :value="$exec['total']" :hint="$exec['published'].' berlaku'" href="{{ route('documents.index') }}"/>
    <x-stat label="Dalam workflow" :value="$exec['in_workflow']" color="indigo-600" href="{{ route('reports.index') }}#pending"/>
    <x-stat label="Kedaluwarsa" :value="$exec['expired']" color="rose-600" href="{{ route('modules.legal') }}" hint="tanpa tindak lanjut"/>
    <x-stat label="Berakhir ≤90 hr" :value="$exec['expiring90']" color="amber-500" href="{{ route('modules.legal') }}"/>
    <x-stat label="Review overdue" :value="$exec['review_overdue']" color="orange-600" href="{{ route('reports.index') }}#review"/>
    <x-stat label="Arsip tak lengkap" :value="$exec['incomplete']" color="slate-600" href="{{ route('reports.index') }}#incomplete"/>
    <x-stat label="Action item terbuka" :value="$exec['open_actions']" color="teal-700" :hint="$exec['overdue_actions']->count().' lewat tenggat'" href="{{ route('action-items.index') }}"/>
</div>

<div class="grid lg:grid-cols-3 gap-5 mb-6">
    <div class="card lg:col-span-2 overflow-hidden">
        <div class="px-4 py-3 border-b-2 border-rose-500 flex justify-between items-center"><div class="section-title">Action item lewat tenggat — penanggung jawab</div><a class="text-xs text-navy-600 font-semibold" href="{{ route('action-items.index', ['status'=>'overdue']) }}">Semua →</a></div>
        <div class="divide-y divide-slate-100">
        @forelse($exec['overdue_actions']->take(6) as $a)
            <div class="px-4 py-3 flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3">
                <div class="flex-1 min-w-0"><div class="font-medium text-sm">{{ $a->title }}</div><div class="text-xs text-slate-500">{{ $a->meeting?->title }}</div></div>
                <div class="text-xs"><span class="font-semibold">{{ $a->pic->name }}</span> <span class="text-slate-500">· {{ $a->pic->department?->name }}</span></div>
                <span class="badge bg-rose-100 text-rose-800">{{ (int) $a->due_at->diffInDays(today()) }} hari terlambat</span>
            </div>
        @empty <div class="p-4"><x-empty text="Tidak ada action item terlambat."/></div>
        @endforelse
        </div>
    </div>
    <div class="card p-4">
        <div class="section-title mb-3">Komposisi klasifikasi</div>
        @php $tot = max(1, array_sum($exec['by_class']->all())); $cc = ['public_internal'=>'bg-emerald-500','department'=>'bg-sky-500','confidential'=>'bg-amber-500','strictly_confidential'=>'bg-rose-500']; @endphp
        <div class="flex h-4 rounded overflow-hidden border-2 border-slate-200 mb-3">
            @foreach(\App\Models\Document::CLASSIFICATIONS as $k => $l)<div class="{{ $cc[$k] }}" style="width: {{ ($exec['by_class'][$k] ?? 0) / $tot * 100 }}%"></div>@endforeach
        </div>
        @foreach(\App\Models\Document::CLASSIFICATIONS as $k => $l)
            <div class="flex items-center justify-between text-sm py-1"><span class="flex items-center gap-2"><span class="w-3 h-3 rounded-sm {{ $cc[$k] }}"></span>{{ $l }}</span><span class="font-semibold">{{ $exec['by_class'][$k] ?? 0 }}</span></div>
        @endforeach
        <div class="mt-4 pt-3 border-t-2 border-slate-100 text-sm flex justify-between"><span class="text-slate-600">Dokumen resmi ber-owner & berklasifikasi</span><span class="font-bold text-emerald-700">{{ $exec['owner_coverage'] }}%</span></div>
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-5 mb-6">
    <div class="card overflow-hidden">
        <div class="px-4 py-3 border-b-2 border-indigo-500 section-title">Persetujuan tertunda (seluruh perusahaan)</div>
        <div class="divide-y divide-slate-100">
        @forelse($exec['pending_steps']->take(7) as $s)
            <a href="{{ route('documents.show', $s->instance->document) }}" class="px-4 py-2.5 flex items-center gap-3 hover:bg-slate-50">
                <div class="flex-1 min-w-0"><div class="text-sm font-medium truncate">{{ $s->instance->document->title }}</div>
                <div class="text-xs text-slate-500">{{ $s->stageLabel() }} · {{ $s->assignee->name }}</div></div>
                @if($s->isOverdue())<span class="badge bg-rose-100 text-rose-800">Lewat SLA</span>@else<span class="text-xs text-slate-500 shrink-0">s.d. {{ $s->due_at?->format('d/m') }}</span>@endif
            </a>
        @empty <div class="p-4"><x-empty text="Tidak ada persetujuan tertunda."/></div>
        @endforelse
        </div>
    </div>
    <div class="card overflow-hidden">
        <div class="px-4 py-3 border-b-2 border-orange-500 section-title">Dokumen berakhir ≤30 hari & review overdue</div>
        <div class="divide-y divide-slate-100">
        @foreach($exec['expired_list'] as $d)
            <a href="{{ route('documents.show', $d) }}" class="px-4 py-2.5 flex items-center gap-3 hover:bg-slate-50">
                <div class="flex-1 min-w-0"><div class="text-sm font-medium truncate">{{ $d->title }}</div><div class="text-xs text-slate-500">PIC {{ $d->owner->name }}</div></div>
                <x-expiry :doc="$d"/>
            </a>
        @endforeach
        @foreach($exec['review_list'] as $d)
            <a href="{{ route('documents.show', $d) }}" class="px-4 py-2.5 flex items-center gap-3 hover:bg-slate-50">
                <div class="flex-1 min-w-0"><div class="text-sm font-medium truncate">{{ $d->title }}</div><div class="text-xs text-slate-500">PIC {{ $d->owner->name }}</div></div>
                <span class="badge bg-orange-100 text-orange-800">Review lewat {{ (int) $d->review_at->diffInDays(today()) }} hari</span>
            </a>
        @endforeach
        @if($exec['expired_list']->isEmpty() && $exec['review_list']->isEmpty())<div class="p-4"><x-empty/></div>@endif
        </div>
    </div>
</div>
@endif

<div class="grid lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 space-y-5">
        <div class="card overflow-hidden">
            <div class="px-4 py-3 border-b-2 border-gold-500 flex justify-between items-center"><div class="section-title">Tugas review & persetujuan saya</div><a href="{{ route('tasks.index') }}" class="text-xs font-semibold text-navy-600">Buka →</a></div>
            <div class="divide-y divide-slate-100">
            @forelse($myTasks as $s)
                <a href="{{ route('tasks.index') }}" class="px-4 py-3 flex items-center gap-3 hover:bg-slate-50">
                    <span class="badge {{ $s->stage === 'approval' ? 'bg-gold-100 text-gold-600' : 'bg-indigo-100 text-indigo-700' }}">{{ $s->stageLabel() }}</span>
                    <div class="flex-1 min-w-0"><div class="text-sm font-medium truncate">{{ $s->instance->document->title }}</div><div class="text-xs text-slate-500 doc-code">{{ $s->instance->document->code }} · {{ $s->instance->version->label() }}</div></div>
                    @if($s->isOverdue())<span class="badge bg-rose-100 text-rose-800">Lewat SLA</span>@else<span class="text-xs text-slate-500">s.d. {{ $s->due_at?->format('d/m H:i') }}</span>@endif
                </a>
            @empty <div class="p-4"><x-empty text="Tidak ada tugas menunggu Anda."/></div>
            @endforelse
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="px-4 py-3 border-b-2 border-amber-500 section-title">Masa berlaku ≤90 hari (dokumen yang dapat Anda akses)</div>
            <div class="divide-y divide-slate-100">
            @forelse($expiring->take(8) as $d)
                <a href="{{ route('documents.show', $d) }}" class="px-4 py-3 flex items-center gap-3 hover:bg-slate-50">
                    <div class="flex-1 min-w-0"><div class="text-sm font-medium truncate">{{ $d->title }}</div><div class="text-xs text-slate-500">{{ $d->type->name }} · berakhir {{ $d->expires_at->format('d M Y') }}</div></div>
                    <x-expiry :doc="$d"/>
                </a>
            @empty <div class="p-4"><x-empty text="Tidak ada dokumen yang segera berakhir."/></div>
            @endforelse
            </div>
        </div>
    </div>

    <div class="space-y-5">
        <div class="card overflow-hidden">
            <div class="px-4 py-3 border-b-2 border-teal-600 section-title">Action item saya</div>
            <div class="divide-y divide-slate-100">
            @forelse($myActions as $a)
                <a href="{{ route('action-items.index') }}" class="px-4 py-3 block hover:bg-slate-50">
                    <div class="text-sm font-medium">{{ $a->title }}</div>
                    <div class="text-xs mt-0.5 {{ $a->isOverdue() ? 'text-rose-700 font-semibold' : 'text-slate-500' }}">Tenggat {{ $a->due_at->format('d M Y') }} {{ $a->isOverdue() ? '· TERLAMBAT' : '' }}</div>
                </a>
            @empty <div class="p-4"><x-empty text="Tidak ada action item."/></div>
            @endforelse
            </div>
        </div>
        <div class="card overflow-hidden">
            <div class="px-4 py-3 border-b-2 border-sky-600 section-title">SOP wajib dibaca</div>
            <div class="divide-y divide-slate-100">
            @forelse($ackPending->take(6) as $d)
                <a href="{{ route('documents.show', $d) }}" class="px-4 py-3 block hover:bg-slate-50 text-sm font-medium">{{ $d->title }}<div class="text-xs text-slate-500 font-normal">{{ $d->currentVersion?->label() }} · efektif {{ $d->effective_at?->format('d M Y') }}</div></a>
            @empty <div class="p-4"><x-empty text="Semua SOP wajib sudah Anda baca."/></div>
            @endforelse
            </div>
        </div>
        <div class="card p-4">
            <div class="section-title mb-3">Aktivitas terakhir saya</div>
            <ul class="space-y-2 text-xs">
                @foreach($recent as $e)<li class="flex gap-2"><span class="text-slate-400 shrink-0 w-24">{{ $e->created_at->format('d/m H:i') }}</span><span><b>{{ $e->eventLabel() }}</b> — {{ $e->description }}</span></li>@endforeach
            </ul>
        </div>
    </div>
</div>
@endsection
