@extends('layouts.app')
@section('title', 'Integrasi & API')
@section('header')
    <div class="section-title">M13 · Integration</div>
    <h1 class="text-2xl font-bold text-navy-700 mt-1">Integrasi Sistem & API</h1>
    <p class="text-sm text-slate-500">SENTRA melengkapi — bukan menggantikan — GPCH Asset Care, Ruci BSC, CRM GPCH dan VHP. Integrasi via tautan terkontrol & service account terbatas.</p>
@endsection
@section('content')
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
    @foreach(\App\Models\DocumentLink::SYSTEMS as $k => $l)<x-stat :label="$l" :value="($linkStats[$k] ?? 0).' tautan'" color="sky-700"/>@endforeach
</div>
<div class="grid lg:grid-cols-2 gap-5">
    <div class="card p-5 text-sm space-y-3">
        <div class="section-title">Endpoint API (Bearer token service account)</div>
        @foreach([['GET','/api/v1/documents?q=','Daftar metadata dokumen berlaku non-rahasia'],['GET','/api/v1/documents/{id|code}','Detail metadata + deep link'],['GET','/api/v1/links/{system}/{record}','Dokumen yang tertaut ke record sistem lain'],['POST','/api/v1/links','Daftarkan tautan record → dokumen (idempoten)']] as [$m,$p,$d])
            <div class="border-2 border-slate-200 rounded-md p-3"><span class="badge {{ $m==='GET' ? 'bg-emerald-100 text-emerald-800' : 'bg-gold-100 text-gold-600' }}">{{ $m }}</span> <code class="doc-code text-xs">{{ $p }}</code><div class="text-xs text-slate-500 mt-1">{{ $d }}</div></div>
        @endforeach
        <pre class="bg-navy-900 text-navy-100 text-xs rounded-md p-3 overflow-x-auto">curl -H "Authorization: Bearer &lt;TOKEN&gt;" \
  {{ url('/api/v1/links/asset_care/AST-ENG-CHL-01') }}</pre>
        <p class="text-xs text-slate-500">API tidak pernah mengirim isi file. Pengguna membuka dokumen melalui deep link <code class="doc-code">/link/{system}/{record}</code> dan tetap melalui pemeriksaan izin pribadinya (AC-10).</p>
    </div>
    <div class="space-y-5">
        <div class="card p-5">
            <div class="section-title mb-3">Service account</div>
            @foreach($services as $s)<div class="text-sm py-1.5 border-b border-slate-100"><b>{{ $s->name }}</b> <span class="text-xs text-slate-500">{{ $s->email }} · token {{ $s->api_token ? 'aktif' : 'belum diterbitkan' }}</span></div>@endforeach
            <a href="{{ route('admin.users.index') }}" class="text-xs font-semibold text-navy-600 mt-2 inline-block">Kelola token di Pengguna →</a>
        </div>
        <div class="card overflow-hidden">
            <div class="px-4 py-3 border-b-2 border-sky-600 section-title">Contoh tautan aktif</div>
            <div class="divide-y divide-slate-100">
            @foreach($samples as $l)
                <a href="{{ route('link.resolve', [$l->related_system, $l->external_record_id]) }}" class="px-4 py-2.5 flex items-center gap-2 text-sm hover:bg-slate-50">
                    <span class="badge bg-sky-100 text-sky-800 shrink-0">{{ \App\Models\DocumentLink::SYSTEMS[$l->related_system] }}</span>
                    <span class="doc-code text-xs">{{ $l->external_record_id }}</span><span class="text-slate-500 truncate">→ {{ $l->document->code }}</span>
                </a>
            @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
