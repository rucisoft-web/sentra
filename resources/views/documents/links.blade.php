@extends('layouts.app')
@section('title', 'Tautan dokumen')
@section('header')
    <div class="section-title">Tautan dari {{ \App\Models\DocumentLink::SYSTEMS[$system] }}</div>
    <h1 class="text-xl font-bold text-navy-700 mt-1">Record <span class="doc-code">{{ $record }}</span></h1>
    <p class="text-sm text-slate-500">Dokumen dibuka langsung dari SENTRA sesuai izin Anda — tanpa membuat salinan tidak terkendali.</p>
@endsection
@section('content')
<div class="space-y-2.5">
@forelse($items as $i)
    <x-doc-row :doc="$i['link']->document" :locked="!$i['can']"/>
@empty <x-empty text="Tidak ada dokumen tertaut yang dapat Anda lihat."/>
@endforelse
</div>
@endsection
