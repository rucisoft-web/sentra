<!DOCTYPE html>
<html lang="id"><head>@include('partials.head')<title>Tidak ditemukan · SENTRA</title></head>
<body class="bg-paper font-sans min-h-screen flex flex-col"><div class="stripe h-1.5"></div>
<div class="flex-1 flex items-center justify-center p-4"><div class="card card-accent border-l-navy-700 max-w-lg w-full p-8">
<div class="doc-code text-5xl font-bold text-gold-500">404</div>
<h1 class="text-2xl font-bold text-marun-800 mt-2">Tidak ditemukan</h1>
<p class="text-slate-600 mt-2">Halaman atau dokumen yang Anda cari tidak ada.</p>
<div class="mt-6 flex gap-2"><a href="{{ url()->previous() }}" class="btn btn-outline">← Kembali</a><a href="{{ auth()->check() ? route('dashboard') : route('landing') }}" class="btn btn-primary">Beranda</a></div>
</div></div></body></html>
