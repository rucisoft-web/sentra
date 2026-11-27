@extends('layouts.app')
@section('title', 'Bandingkan versi')
@section('header')
    <div class="section-title">M08 · Compare metadata</div>
    <h1 class="text-xl font-bold text-marun-800 mt-1">{{ $document->title }}</h1>
@endsection
@section('actions')<a href="{{ route('documents.show', $document) }}" class="btn btn-outline">← Detail</a>@endsection
@section('content')
<form class="card p-4 mb-5 flex flex-wrap items-end gap-3">
    <div><label class="label">Versi A</label><select name="a" class="input">@foreach($versions as $v)<option value="{{ $v->id }}" @selected($a?->id === $v->id)>{{ $v->label() }}</option>@endforeach</select></div>
    <div><label class="label">Versi B</label><select name="b" class="input">@foreach($versions as $v)<option value="{{ $v->id }}" @selected($b?->id === $v->id)>{{ $v->label() }}</option>@endforeach</select></div>
    <button class="btn btn-primary">Bandingkan</button>
</form>
<div class="card overflow-x-auto">
<table class="tbl">
    <thead><tr><th>Atribut</th><th>Versi A ({{ $a->label() }})</th><th>Versi B ({{ $b->label() }})</th></tr></thead>
    <tbody>
    @foreach([
        'Status' => fn($v) => \App\Models\DocumentVersion::STATUSES[$v->status], 'Nama file' => fn($v) => $v->original_name,
        'Tipe MIME' => fn($v) => $v->mime, 'Ukuran' => fn($v) => $v->sizeLabel(), 'SHA-256' => fn($v) => $v->checksum,
        'Catatan perubahan' => fn($v) => $v->change_note, 'Diunggah' => fn($v) => $v->uploader->name.' · '.$v->created_at->format('d/m/Y H:i'),
        'Disahkan' => fn($v) => $v->approver ? $v->approver->name.' · '.$v->approved_at?->format('d/m/Y H:i') : '—', 'Terkunci' => fn($v) => $v->locked ? 'Ya' : 'Tidak',
    ] as $label => $fn)
        @php $va = $fn($a); $vb = $fn($b); @endphp
        <tr class="{{ $va !== $vb ? 'bg-amber-50' : '' }}"><td class="font-semibold whitespace-nowrap">{{ $label }}</td><td class="{{ $label === 'SHA-256' ? 'doc-code text-xs break-all' : '' }}">{{ $va }}</td><td class="{{ $label === 'SHA-256' ? 'doc-code text-xs break-all' : '' }}">{{ $vb }}</td></tr>
    @endforeach
    </tbody>
</table>
</div>
<p class="text-xs text-slate-500 mt-2">Baris berwarna menandakan perbedaan.</p>
@endsection
