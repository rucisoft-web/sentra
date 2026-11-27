<!DOCTYPE html>
<html lang="id"><head>@include('partials.head')<title>Terjadi kesalahan · SENTRA</title></head>
<body class="bg-paper font-sans min-h-screen flex flex-col"><div class="stripe h-1.5"></div>
<div class="flex-1 flex items-center justify-center p-4"><div class="card card-accent border-l-navy-700 max-w-lg w-full p-8">
<div class="doc-code text-5xl font-bold text-gold-500">500</div>
<h1 class="text-2xl font-bold text-marun-800 mt-2">Terjadi kesalahan</h1>
<p class="text-slate-600 mt-2">Silakan coba lagi atau hubungi administrator.</p>
<div class="mt-6 flex gap-2"><a href="{{ url()->previous() }}" class="btn btn-outline">← Kembali</a><a href="{{ auth()->check() ? route('dashboard') : route('landing') }}" class="btn btn-primary">Beranda</a></div>
</div></div></body></html>
