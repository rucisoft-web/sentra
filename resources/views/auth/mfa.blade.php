@extends('layouts.app')
@section('title', 'Verifikasi MFA')
@section('content')
<div class="max-w-md mx-auto">
    <div class="card card-accent border-l-gold-500 p-6">
        <div class="section-title mb-2">Verifikasi MFA (step-up)</div>
        <h1 class="text-xl font-bold text-marun-800 mb-1">Masukkan kode autentikator</h1>
        <p class="text-sm text-slate-600 mb-4">Pengesahan dokumen, akses dokumen Sangat Rahasia, ekspor, pemusnahan dan administrasi memerlukan verifikasi ulang. Berlaku {{ config('archive.mfa_ttl_minutes') }} menit.</p>
        <form method="POST" action="{{ route('mfa.verify') }}" class="space-y-4">
            @csrf
            <input name="code" inputmode="numeric" maxlength="6" autocomplete="one-time-code" class="input text-center doc-code text-2xl tracking-[0.5em]" placeholder="000000" required autofocus>
            @error('code')<div class="text-sm text-rose-700">{{ $message }}</div>@enderror
            <button class="btn btn-primary w-full">Verifikasi</button>
        </form>
        @if($demoCode)
        <div class="mt-5 border-2 border-dashed border-gold-400 bg-gold-50 rounded-lg p-4 text-sm">
            <div class="font-semibold text-gold-600 mb-1">Mode demo</div>
            Kode TOTP Anda saat ini: <span class="doc-code text-lg font-bold text-navy-700">{{ $demoCode }}</span>
            <div class="text-xs text-slate-500 mt-1">Di produksi, kode diambil dari aplikasi autentikator (Google/Microsoft Authenticator). Secret: <span class="doc-code">{{ $user->mfa_secret }}</span></div>
        </div>
        @endif
    </div>
</div>
@endsection
