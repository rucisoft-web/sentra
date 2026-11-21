@extends('layouts.app')
@section('title', 'Rekap acknowledgement')
@section('header')
    <div class="section-title">Rekap acknowledgement · {{ $document->currentVersion?->label() }}</div>
    <h1 class="text-xl font-bold text-navy-700 mt-1">{{ $document->title }}</h1>
    <p class="text-sm text-slate-500">{{ $acks->count() }} dari {{ $users->count() }} karyawan telah menyatakan membaca.</p>
@endsection
@section('actions')<a href="{{ route('documents.show', $document) }}" class="btn btn-outline">← Detail</a>@endsection
@section('content')
<div class="card overflow-x-auto">
<table class="tbl">
    <thead><tr><th>Nama</th><th>Departemen</th><th>Status</th><th>Waktu</th></tr></thead>
    <tbody>
    @foreach($users as $usr)
        <tr><td class="font-medium">{{ $usr->name }}<div class="text-xs text-slate-500">{{ $usr->position }}</div></td><td>{{ $usr->department?->name }}</td>
        <td>@if(isset($acks[$usr->id]))<span class="badge bg-emerald-100 text-emerald-800">Sudah</span>@else<span class="badge bg-amber-100 text-amber-800">Belum</span>@endif</td>
        <td class="text-sm">{{ $acks[$usr->id]?->acknowledged_at?->format('d/m/Y H:i') ?? '—' }}</td></tr>
    @endforeach
    </tbody>
</table>
</div>
@endsection
