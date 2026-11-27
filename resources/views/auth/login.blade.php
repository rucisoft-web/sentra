<!DOCTYPE html>
<html lang="id">
<head>@include('partials.head')<title>Masuk · SENTRA</title></head>
<body class="bg-paper font-sans text-slate-800 min-h-screen">
<div class="stripe h-1.5"></div>
<div class="max-w-6xl mx-auto px-4 py-8 sm:py-12 grid lg:grid-cols-5 gap-6 lg:gap-10">
    <div class="lg:col-span-2">
        <a href="{{ route('landing') }}" class="flex items-center gap-3 mb-8">
            <div class="w-11 h-11 rounded-md bg-navy-700 flex items-center justify-center border-b-4 border-gold-500">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16v4H4zM5 8v12h14V8M10 12h4"/></svg>
            </div>
            <div><div class="font-bold text-navy-700 text-xl tracking-wide">SENTRA</div><div class="text-xs text-slate-500">Enterprise Document &amp; Governance System</div></div>
        </a>
        <div class="card card-accent border-l-navy-700 p-6">
            <h1 class="text-xl font-bold text-marun-800">Masuk ke Arsip Korporasi</h1>
            <p class="text-sm text-slate-500 mt-1 mb-5">Grand Pasundan Convention Hotel</p>
            @if($errors->any())<div class="mb-4 text-sm bg-rose-50 border-l-4 border-rose-600 text-rose-800 px-3 py-2 rounded">{{ $errors->first() }}</div>@endif
            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                <div><label class="label">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" class="input" required autofocus autocomplete="username"></div>
                <div><label class="label">Kata sandi</label><input id="password" name="password" type="password" class="input" required autocomplete="current-password"></div>
                <button class="btn btn-primary w-full !py-2.5">Masuk</button>
            </form>
            <p class="text-xs text-slate-500 mt-4">Sesi berakhir otomatis setelah {{ config('session.lifetime') }} menit tidak aktif. Aksi sensitif memerlukan verifikasi MFA.</p>
        </div>
    </div>
    @if($demoUsers->isNotEmpty())
    <div class="lg:col-span-3">
        <div class="card overflow-hidden">
            <div class="px-5 py-3 border-b-2 border-gold-500 bg-gold-50 flex flex-wrap items-center justify-between gap-2">
                <div class="section-title">Akun demo — klik untuk mengisi</div>
                <span class="text-xs text-slate-600">Semua kata sandi: <code class="doc-code bg-white border px-1.5 rounded">password</code></span>
            </div>
            <div class="divide-y divide-slate-100 max-h-[70vh] overflow-y-auto">
                @foreach($demoUsers as $d)
                <button type="button" onclick="document.getElementById('email').value='{{ $d->email }}';document.getElementById('password').value='password';window.scrollTo({top:0,behavior:'smooth'})" class="w-full text-left px-5 py-3 hover:bg-navy-50 flex items-center gap-3">
                    <div class="w-9 h-9 shrink-0 rounded-md bg-navy-50 border-2 border-navy-200 text-navy-700 font-bold text-xs flex items-center justify-center">{{ $d->initials() }}</div>
                    <div class="min-w-0 flex-1">
                        <div class="font-semibold text-sm">{{ $d->name }} <span class="text-slate-400 font-normal">· {{ $d->position }}</span></div>
                        <div class="text-xs text-slate-500 truncate">{{ $d->email }} · {{ $d->roleLabel() }}</div>
                    </div>
                    @if($d->access_expires_at)<span class="badge bg-amber-100 text-amber-800 hidden sm:inline-flex">Akses s.d. {{ $d->access_expires_at->format('d/m/Y') }}</span>@endif
                </button>
                @endforeach
            </div>
        </div>
    </div>
    @endif
</div>
</body>
</html>
