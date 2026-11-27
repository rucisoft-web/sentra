@extends('layouts.app')
@section('title', 'Preview '.$document->code)
@section('header')
    <div class="flex flex-wrap gap-1.5 mb-1"><span class="doc-code text-xs bg-navy-50 border border-navy-100 rounded px-2 py-0.5">{{ $document->code }}</span><span class="doc-code text-xs font-bold">{{ $version->label() }}</span><x-cls :doc="$document"/></div>
    <h1 class="text-xl font-bold text-marun-800">{{ $document->title }}</h1>
    <div class="doc-code text-[11px] text-slate-500 break-all">SHA-256 {{ $version->checksum }} — integritas diverifikasi saat dibuka</div>
@endsection
@section('actions')
    <a href="{{ route('documents.show', $document) }}" class="btn btn-outline">← Detail</a>
    @if($canDownload)<a href="{{ route('documents.download', [$document, $version]) }}" class="btn btn-primary">Unduh (tercatat)</a>@endif
@endsection
@section('content')
<div class="relative card overflow-hidden" style="height: calc(100vh - 240px); min-height: 480px;" oncontextmenu="return {{ $canDownload ? 'true' : 'false' }}">
    @if($version->isPreviewable())
        @if(str_starts_with($version->mime, 'image/'))
            <div class="h-full overflow-auto flex justify-center bg-slate-100"><img src="{{ route('documents.raw', [$document, $version]) }}" class="max-w-full" alt="Preview"></div>
        @else
            <iframe src="{{ route('documents.raw', [$document, $version]) }}#toolbar={{ $canDownload ? 1 : 0 }}" class="w-full h-full bg-slate-100" title="Preview dokumen"></iframe>
        @endif
    @else
        <div class="h-full flex items-center justify-center text-center p-6"><div><div class="text-4xl mb-2">📄</div><div class="font-semibold">Format {{ $version->mime }} tidak dapat dipreview di browser.</div><div class="text-sm text-slate-500">{{ $canDownload ? 'Silakan unduh file.' : 'Anda tidak memiliki izin unduh.' }}</div></div></div>
    @endif
    {{-- watermark dinamis --}}
    <div class="pointer-events-none absolute inset-0 overflow-hidden select-none" aria-hidden="true">
        <div class="absolute -inset-[50%] flex flex-wrap content-start gap-x-24 gap-y-20 rotate-[-28deg] opacity-[0.13]">
            @for($i = 0; $i < 80; $i++)<span class="text-sm font-bold text-navy-900 whitespace-nowrap">SENTRA · {{ $watermark }} · {{ strtoupper($document->classificationLabel()) }}</span>@endfor
        </div>
    </div>
</div>
<p class="text-xs text-slate-500 mt-2">Watermark berisi identitas pembuka, waktu dan IP. Setiap preview dan unduhan dicatat dalam audit trail.</p>
@endsection
