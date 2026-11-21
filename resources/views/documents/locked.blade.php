@extends('layouts.app')
@section('title', 'Akses terbatas')
@section('content')
<div class="max-w-2xl mx-auto">
    <div class="card card-accent border-l-amber-500 p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-12 h-12 rounded-lg bg-amber-100 border-2 border-amber-400 flex items-center justify-center text-2xl">🔒</div>
            <div><div class="section-title">Akses terbatas</div><div class="text-sm text-slate-500">Dokumen ditemukan, tetapi Anda belum memiliki izin membuka isinya.</div></div>
        </div>
        <div class="flex flex-wrap gap-1.5 mb-2"><span class="doc-code text-xs bg-navy-50 border border-navy-100 rounded px-2 py-0.5">{{ $document->code }}</span><x-cls :doc="$document"/><x-status :doc="$document"/></div>
        <h1 class="text-xl font-bold text-navy-700">{{ $document->title }}</h1>
        <div class="text-sm text-slate-500 mb-4">{{ $document->type->name }} · {{ $document->department->name }} · Pemilik: {{ $document->owner->name }}</div>
        <div class="bg-amber-50 border-l-4 border-amber-500 rounded p-3 text-sm mb-5">{{ $reason }}</div>

        @if($pendingRequest)
            <div class="text-sm text-indigo-800 bg-indigo-50 border-l-4 border-indigo-500 p-3 rounded">Permintaan akses Anda (diajukan {{ $pendingRequest->created_at->format('d/m/Y H:i') }}) sedang menunggu keputusan pemilik dokumen.</div>
        @else
        <form method="POST" action="{{ route('access-requests.store', $document) }}" class="space-y-3">@csrf
            <div class="section-title">Ajukan permintaan akses (need-to-know)</div>
            <div><label class="label">Justifikasi / tujuan</label><textarea name="justification" class="input" rows="3" required minlength="10" placeholder="Jelaskan kebutuhan dan tujuan akses"></textarea></div>
            <div class="grid sm:grid-cols-2 gap-3">
                <div><label class="label">Jenis akses</label><select name="permission" class="input"><option value="view">Lihat saja</option><option value="download">Lihat & unduh</option></select></div>
                <div><label class="label">Berlaku sampai</label><input type="date" name="requested_until" value="{{ today()->addDays(14)->toDateString() }}" class="input" required></div>
            </div>
            <button class="btn btn-primary">Kirim permintaan</button>
        </form>
        @endif
        <p class="text-xs text-slate-500 mt-4">Upaya akses ini tercatat dalam audit trail.</p>
    </div>
</div>
@endsection
