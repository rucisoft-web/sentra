@extends('layouts.app')
@section('title', 'Berita Acara Pemusnahan')
@section('actions')<button onclick="window.print()" class="btn btn-outline">Cetak</button>@endsection
@section('header')<div class="section-title">Berita acara</div>@endsection
@section('content')
<div class="card max-w-3xl mx-auto p-6 sm:p-10 border-t-8 border-t-navy-700" id="certificate">
    <div class="text-center border-b-2 border-gold-500 pb-4 mb-6">
        <div class="text-xs tracking-[0.3em] text-slate-500 uppercase">{{ $disposal->document->entity->name }}</div>
        <h1 class="text-2xl font-bold text-navy-700 mt-2">BERITA ACARA PEMUSNAHAN ARSIP</h1>
        <div class="doc-code text-sm mt-1">{{ $disposal->certificate_number }}</div>
    </div>
    <p class="text-sm leading-relaxed mb-4">Pada {{ $disposal->executed_at->translatedFormat('l, d F Y \p\u\k\u\l H:i') }} WIB telah dilaksanakan pemusnahan arsip dengan metode <b>{{ $disposal->method }}</b> berdasarkan jadwal retensi yang berlaku, setelah dipastikan tidak terdapat legal hold aktif dan memperoleh persetujuan ganda.</p>
    <table class="tbl mb-6">
        <tbody>
            <tr><td class="font-semibold w-48">Nomor dokumen</td><td class="doc-code">{{ $disposal->document->code }}</td></tr>
            <tr><td class="font-semibold">Judul</td><td>{{ $disposal->document->title }}</td></tr>
            <tr><td class="font-semibold">Departemen</td><td>{{ $disposal->document->department->name }}</td></tr>
            <tr><td class="font-semibold">Jadwal retensi</td><td>{{ $disposal->document->retentionPolicy?->code }} — {{ $disposal->document->retentionPolicy?->name }} (s.d. {{ $disposal->document->retention_until?->format('d/m/Y') }})</td></tr>
            <tr><td class="font-semibold">Dasar usulan</td><td>{{ $disposal->reason }}</td></tr>
        </tbody>
    </table>
    <div class="section-title mb-2">Manifest file yang dimusnahkan</div>
    <div class="overflow-x-auto mb-6"><table class="tbl"><thead><tr><th>Versi</th><th>File</th><th>Ukuran</th><th>SHA-256</th></tr></thead><tbody>
        @foreach($disposal->manifest ?? [] as $m)<tr><td class="doc-code">{{ $m['version'] }}</td><td>{{ $m['file'] }}</td><td>{{ number_format($m['size']) }} B</td><td class="doc-code text-[10px] break-all">{{ $m['sha256'] }}</td></tr>@endforeach
    </tbody></table></div>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-center text-xs mt-10">
        @foreach([['Diusulkan', $disposal->requester, $disposal->created_at], ['Persetujuan 1 (Legal)', $disposal->firstApprover, $disposal->first_approved_at], ['Persetujuan 2 (BOD/GM)', $disposal->secondApprover, $disposal->second_approved_at], ['Pelaksana', $disposal->executor, $disposal->executed_at]] as [$role, $person, $at])
            <div><div class="text-slate-500 mb-10">{{ $role }}</div><div class="border-t-2 border-slate-300 pt-1 font-semibold">{{ $person?->name }}</div><div class="text-slate-500">{{ $at?->format('d/m/Y H:i') }}</div></div>
        @endforeach
    </div>
    <p class="text-[11px] text-slate-400 mt-8">Dokumen elektronik ini dihasilkan SENTRA dan tercatat dalam audit trail berantai hash. Metadata arsip disimpan permanen sebagai tombstone.</p>
</div>
<style>@media print{aside,header,footer,nav,.stripe,[onclick]{display:none!important}.lg\:pl-72{padding-left:0!important}}</style>
@endsection
