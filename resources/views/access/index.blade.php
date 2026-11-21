@extends('layouts.app')
@section('title', 'Permintaan Akses')
@section('header')
    <div class="section-title">Need-to-know access</div>
    <h1 class="text-2xl font-bold text-navy-700 mt-1">Permintaan Akses Dokumen</h1>
    <p class="text-sm text-slate-500">Akses yang disetujui diberikan sebagai ACL berbatas waktu dan otomatis berakhir.</p>
@endsection
@section('content')
<div class="card overflow-hidden mb-6">
    <div class="px-4 py-3 border-b-2 border-gold-500 section-title">Menunggu keputusan saya</div>
    <div class="divide-y divide-slate-100">
    @forelse($toDecide as $r)
        <div class="p-4 flex flex-col lg:flex-row gap-3">
            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap gap-1.5 mb-1"><span class="badge {{ ['pending'=>'bg-amber-100 text-amber-800','approved'=>'bg-emerald-100 text-emerald-800','rejected'=>'bg-rose-100 text-rose-800'][$r->decision] }}">{{ \App\Models\AccessRequest::DECISIONS[$r->decision] }}</span><x-cls :doc="$r->document"/></div>
                <div class="text-sm"><b>{{ $r->requester->name }}</b> <span class="text-slate-500">({{ $r->requester->roleLabel() }}, {{ $r->requester->department?->name }})</span> meminta akses <b>{{ $r->permission === 'download' ? 'lihat & unduh' : 'lihat' }}</b> s.d. {{ $r->requested_until->format('d/m/Y') }}</div>
                <a href="{{ route('documents.show', $r->document) }}" class="text-navy-600 font-semibold text-sm">{{ $r->document->code }} — {{ $r->document->title }}</a>
                <div class="text-sm mt-1 bg-slate-50 border-l-4 border-slate-300 px-3 py-1.5 rounded">“{{ $r->justification }}”</div>
                @if($r->decision_note)<div class="text-xs text-slate-500 mt-1">Catatan: {{ $r->decision_note }}</div>@endif
            </div>
            @if($r->decision === 'pending')
            <form method="POST" action="{{ route('access-requests.decide', $r) }}" class="lg:w-72 space-y-2 shrink-0">@csrf
                <input name="decision_note" class="input" placeholder="Catatan keputusan">
                <div class="grid grid-cols-2 gap-2"><button name="decision" value="approved" class="btn btn-success btn-sm">Setujui</button><button name="decision" value="rejected" class="btn btn-danger btn-sm">Tolak</button></div>
            </form>
            @endif
        </div>
    @empty <div class="p-4"><x-empty text="Tidak ada permintaan untuk Anda putuskan."/></div>
    @endforelse
    </div>
</div>
<div class="card overflow-hidden">
    <div class="px-4 py-3 border-b-2 border-navy-700 section-title">Permintaan saya</div>
    <div class="divide-y divide-slate-100">
    @forelse($mine as $r)
        <a href="{{ route('documents.show', $r->document) }}" class="p-4 flex flex-col sm:flex-row sm:items-center gap-2 hover:bg-slate-50">
            <div class="flex-1 min-w-0"><div class="text-sm font-medium">{{ $r->document->title }}</div><div class="text-xs text-slate-500">Diajukan {{ $r->created_at->format('d/m/Y') }} · s.d. {{ $r->requested_until->format('d/m/Y') }} @if($r->approver)· diputuskan {{ $r->approver->name }}@endif</div></div>
            <span class="badge {{ ['pending'=>'bg-amber-100 text-amber-800','approved'=>'bg-emerald-100 text-emerald-800','rejected'=>'bg-rose-100 text-rose-800'][$r->decision] }}">{{ \App\Models\AccessRequest::DECISIONS[$r->decision] }}</span>
        </a>
    @empty <div class="p-4"><x-empty/></div>
    @endforelse
    </div>
</div>
@endsection
