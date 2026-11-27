@extends('layouts.app')
@section('title', 'Legal & Perizinan')
@section('header')
    <div class="section-title">M02 · Legal & Compliance</div>
    <h1 class="text-2xl font-bold text-marun-800 mt-1">Register Legalitas, Perizinan & Kontrak</h1>
    <p class="text-sm text-slate-500">Pengingat otomatis H-90 / H-60 / H-30 kepada pemilik, kepala departemen dan Legal. Bukti perpanjangan ditautkan sebagai relasi "perpanjangan dari".</p>
@endsection
@section('content')
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
    <x-stat label="Kedaluwarsa" :value="$stats['expired']" color="rose-600"/>
    <x-stat label="≤ 30 hari" :value="$stats['d30']" color="rose-500"/>
    <x-stat label="31–60 hari" :value="$stats['d60']" color="orange-500"/>
    <x-stat label="61–90 hari" :value="$stats['d90']" color="amber-500"/>
</div>

@foreach([['Perizinan & sertifikat', $permits, 'border-gold-500'], ['Kontrak & perjanjian', $contracts, 'border-navy-700']] as [$title, $list, $bc])
<div class="card overflow-hidden mb-6">
    <div class="px-4 py-3 border-b-2 {{ $bc }} section-title">{{ $title }} ({{ $list->count() }})</div>
    <div class="hidden md:block overflow-x-auto">
    <table class="tbl">
        <thead><tr><th>Dokumen</th><th>Pihak / penerbit</th><th>PIC</th><th>Berakhir</th><th>Status</th><th>Pengingat</th></tr></thead>
        <tbody>
        @foreach($list as $d)
            <tr>
                <td><a href="{{ route('documents.show', $d) }}" class="font-semibold text-navy-700 hover:underline">{{ $d->title }}</a><div class="doc-code text-[11px] text-slate-500">{{ $d->code }}</div></td>
                <td class="text-sm">{{ $d->counterparty ?? '—' }}</td>
                <td class="text-sm">{{ $d->owner->name }}</td>
                <td class="whitespace-nowrap">{{ $d->expires_at->format('d M Y') }}</td>
                <td><x-status :doc="$d"/></td>
                <td><x-expiry :doc="$d"/></td>
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
    <div class="md:hidden divide-y divide-slate-100">
        @foreach($list as $d)
        <a href="{{ route('documents.show', $d) }}" class="block px-4 py-3">
            <div class="flex justify-between gap-2"><div class="font-semibold text-sm text-navy-700">{{ $d->title }}</div><x-expiry :doc="$d"/></div>
            <div class="text-xs text-slate-500 mt-0.5">{{ $d->counterparty }} · PIC {{ $d->owner->name }} · berakhir {{ $d->expires_at->format('d M Y') }}</div>
        </a>
        @endforeach
    </div>
    @if($list->isEmpty())<div class="p-4"><x-empty/></div>@endif
</div>
@endforeach

<div class="card overflow-hidden">
    <div class="px-4 py-3 border-b-2 border-teal-600 section-title">Dokumen legalitas tanpa masa berlaku (akta, NIB)</div>
    <div class="p-3 space-y-2">@forelse($foundation as $d)<x-doc-row :doc="$d"/>@empty<x-empty/>@endforelse</div>
</div>
@endsection
