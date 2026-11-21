@extends('layouts.app')
@section('title', 'Pencarian')
@section('header')
    <div class="section-title">M09 · Search & discovery</div>
    <h1 class="text-2xl font-bold text-navy-700 mt-1">Pencarian Arsip</h1>
    <p class="text-sm text-slate-500">Full-text pada nomor, judul, deskripsi, tag, pihak terkait dan isi dokumen (indeks teks/OCR). Hasil hanya sesuai izin Anda.</p>
@endsection
@section('content')
<form class="card card-accent border-l-navy-700 p-4 mb-5">
    <div class="flex flex-col sm:flex-row gap-2 mb-3">
        <input name="q" value="{{ $filters['q'] ?? '' }}" class="input !text-base !py-2.5" placeholder="Contoh: sertifikat laik fungsi, notulen direksi, kontrak laundry…" autofocus>
        <button class="btn btn-primary !py-2.5 sm:w-36">Cari</button>
    </div>
    <details {{ count(array_diff_key($filters, ['q'=>1])) ? 'open' : '' }}>
        <summary class="cursor-pointer text-sm font-semibold text-navy-600">Filter lanjutan</summary>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-3">
            <select name="module" class="input"><option value="">Semua modul</option>@foreach(\App\Models\DocumentCategory::MODULES as $k=>$l)<option value="{{ $k }}" @selected(($filters['module'] ?? '')==$k)>{{ $l }}</option>@endforeach</select>
            <select name="type_id" class="input"><option value="">Semua jenis</option>@foreach($types as $t)<option value="{{ $t->id }}" @selected(($filters['type_id'] ?? '')==$t->id)>{{ $t->name }}</option>@endforeach</select>
            <select name="department_id" class="input"><option value="">Semua departemen</option>@foreach($departments as $d)<option value="{{ $d->id }}" @selected(($filters['department_id'] ?? '')==$d->id)>{{ $d->name }}</option>@endforeach</select>
            <select name="owner_id" class="input"><option value="">Semua PIC</option>@foreach($owners as $o)<option value="{{ $o->id }}" @selected(($filters['owner_id'] ?? '')==$o->id)>{{ $o->name }}</option>@endforeach</select>
            <select name="status" class="input"><option value="">Semua status</option>@foreach(\App\Models\Document::STATUSES as $k=>$l)<option value="{{ $k }}" @selected(($filters['status'] ?? '')==$k)>{{ $l }}</option>@endforeach</select>
            <select name="classification" class="input"><option value="">Semua klasifikasi</option>@foreach(\App\Models\Document::CLASSIFICATIONS as $k=>$l)<option value="{{ $k }}" @selected(($filters['classification'] ?? '')==$k)>{{ $l }}</option>@endforeach</select>
            <div><input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="input" title="Efektif dari"></div>
            <div><input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="input" title="Efektif sampai"></div>
            <select name="expiring" class="input"><option value="">Masa berlaku: semua</option>@foreach([30,60,90,180] as $d)<option value="{{ $d }}" @selected(($filters['expiring'] ?? '')==$d)>Berakhir ≤ {{ $d }} hari</option>@endforeach</select>
        </div>
    </details>
</form>

<div class="grid lg:grid-cols-4 gap-5">
    <div class="lg:col-span-3">
        @if($results)
            <div class="flex flex-wrap justify-between items-center gap-2 mb-3">
                <div class="text-sm text-slate-600"><b>{{ $results->total() }}</b> hasil · {{ $elapsed }} ms</div>
                <form method="POST" action="{{ route('search.save') }}" class="flex gap-2">@csrf
                    @foreach($filters as $k=>$v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
                    <input name="name" placeholder="Nama pencarian" class="input !py-1.5 !text-sm w-44" required><button class="btn btn-outline btn-sm">Simpan</button>
                </form>
            </div>
            <div class="space-y-2.5">
                @forelse($results as $doc)<x-doc-row :doc="$doc" :locked="!$doc->can_open"/>
                @empty <x-empty text="Tidak ada dokumen yang cocok dan dapat Anda akses."/>
                @endforelse
            </div>
            <div class="mt-4">{{ $results->links() }}</div>
            <p class="text-xs text-slate-500 mt-3">Dokumen bergembok terlihat metadata-nya saja; ajukan permintaan akses untuk membuka. Dokumen Sangat Rahasia tidak muncul kecuali Anda berada di whitelist.</p>
        @else
            <div class="card p-8 text-center text-slate-500"><div class="text-4xl mb-2">🔎</div>Masukkan kata kunci atau pilih filter. Coba: <a class="text-navy-600 font-semibold" href="?q=sertifikat">sertifikat</a>, <a class="text-navy-600 font-semibold" href="?q=keputusan+direksi">keputusan direksi</a>, <a class="text-navy-600 font-semibold" href="?expiring=90">berakhir ≤90 hari</a>.</div>
        @endif
    </div>
    <aside>
        <div class="card overflow-hidden">
            <div class="px-4 py-3 border-b-2 border-gold-500 section-title">Pencarian tersimpan</div>
            <div class="divide-y divide-slate-100">
            @forelse($saved as $s)
                <div class="px-4 py-2.5 flex items-center gap-2">
                    <a href="{{ route('search', $s->query) }}" class="flex-1 text-sm font-medium text-navy-600 hover:underline">{{ $s->name }}</a>
                    <form method="POST" action="{{ route('search.destroy', $s) }}">@csrf @method('DELETE')<button class="text-xs text-slate-400 hover:text-rose-600">✕</button></form>
                </div>
            @empty <div class="p-4 text-sm text-slate-500">Belum ada.</div>
            @endforelse
            </div>
        </div>
    </aside>
</div>
@endsection
