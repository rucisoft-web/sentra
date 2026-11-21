@extends('layouts.app')
@section('title', 'Repository')
@section('header')
    <div class="section-title">M01 · Repository & Klasifikasi</div>
    <h1 class="text-2xl font-bold text-navy-700 mt-1">Repository Dokumen</h1>
    <p class="text-sm text-slate-500">Taksonomi perusahaan → departemen → kategori → jenis → tahun → nomor. Hanya menampilkan dokumen sesuai izin Anda.</p>
@endsection
@section('actions')
    @if(!auth()->user()->hasRole('auditor','it_admin','owner'))<a href="{{ route('documents.create') }}" class="btn btn-gold">+ Daftarkan Dokumen</a>@endif
@endsection
@section('content')
<div class="grid lg:grid-cols-4 gap-5">
    <aside class="lg:col-span-1">
        <button data-toggle="folder-tree" class="lg:hidden btn btn-outline w-full mb-3">Folder virtual & filter</button>
        <div id="folder-tree" class="hidden lg:block space-y-4">
            <div class="card p-3">
                <div class="section-title mb-2 px-1">Folder virtual</div>
                <a href="{{ route('documents.index') }}" class="block px-2 py-1.5 rounded text-sm {{ empty($filters['category_id']) && empty($filters['module']) ? 'bg-navy-50 font-semibold text-navy-700' : 'hover:bg-slate-50' }}">📁 Semua dokumen</a>
                @foreach($tree as $module => $cats)
                    <div class="mt-2 px-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ \App\Models\DocumentCategory::MODULES[$module] ?? $module }}</div>
                    @foreach($cats as $c)
                        <a href="{{ route('documents.index', ['category_id' => $c->id]) }}" class="flex justify-between px-2 py-1.5 rounded text-sm {{ ($filters['category_id'] ?? null) == $c->id ? 'bg-navy-50 font-semibold text-navy-700 border-l-4 border-gold-500' : 'hover:bg-slate-50' }}">
                            <span class="truncate">📂 {{ $c->name }}</span><span class="text-xs text-slate-400">{{ $counts[$c->id] ?? 0 }}</span>
                        </a>
                    @endforeach
                @endforeach
            </div>
            <form class="card p-3 space-y-3">
                <div class="section-title px-1">Filter</div>
                @if(!empty($filters['category_id']))<input type="hidden" name="category_id" value="{{ $filters['category_id'] }}">@endif
                <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Kata kunci" class="input">
                <select name="department_id" class="input"><option value="">Semua departemen</option>@foreach($departments as $d)<option value="{{ $d->id }}" @selected(($filters['department_id'] ?? '') == $d->id)>{{ $d->name }}</option>@endforeach</select>
                <select name="status" class="input"><option value="">Semua status aktif</option>@foreach(\App\Models\Document::STATUSES as $k => $l)<option value="{{ $k }}" @selected(($filters['status'] ?? '') == $k)>{{ $l }}</option>@endforeach</select>
                <select name="classification" class="input"><option value="">Semua klasifikasi</option>@foreach(\App\Models\Document::CLASSIFICATIONS as $k => $l)<option value="{{ $k }}" @selected(($filters['classification'] ?? '') == $k)>{{ $l }}</option>@endforeach</select>
                <button class="btn btn-primary w-full">Terapkan</button>
            </form>
        </div>
    </aside>
    <div class="lg:col-span-3">
        <div class="text-sm text-slate-500 mb-3">{{ $documents->total() }} dokumen ditemukan</div>
        <div class="space-y-2.5">
            @forelse($documents as $doc)
                <x-doc-row :doc="$doc" :locked="!$doc->can_open"/>
            @empty <x-empty text="Belum ada dokumen pada folder ini."/>
            @endforelse
        </div>
        <div class="mt-4">{{ $documents->links() }}</div>
    </div>
</div>
@endsection
