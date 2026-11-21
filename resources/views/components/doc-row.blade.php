@props(['doc', 'locked' => false])
{{-- Baris dokumen responsif: kartu di HP, baris di desktop --}}
<div class="card p-3 sm:p-4 flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 border-l-4 {{ $locked ? 'border-l-slate-400 bg-slate-50' : 'border-l-navy-500' }}">
    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-1.5 mb-1">
            <span class="doc-code text-[11.5px] text-navy-600 bg-navy-50 border border-navy-100 rounded px-1.5 py-0.5">{{ $doc->code }}</span>
            <x-cls :doc="$doc"/>
            <x-status :doc="$doc"/>
            <x-expiry :doc="$doc"/>
        </div>
        <a href="{{ route('documents.show', $doc) }}" class="font-semibold text-slate-800 hover:text-navy-600 leading-snug block">
            @if($locked)<svg class="inline w-4 h-4 text-slate-500 -mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"/></svg>@endif
            {{ $doc->title }}
        </a>
        <div class="text-xs text-slate-500 mt-0.5">{{ $doc->type->name }} · {{ $doc->department->name }} · PIC {{ $doc->owner->name }}</div>
    </div>
    <div class="flex sm:flex-col items-center sm:items-end gap-2 text-xs text-slate-500 shrink-0">
        @if($doc->currentVersion)<span class="doc-code">{{ $doc->currentVersion->label() }}</span>@endif
        @if($doc->effective_at)<span>Efektif {{ $doc->effective_at->format('d M Y') }}</span>@endif
        @if($locked)<span class="badge bg-slate-200 text-slate-700">Perlu izin</span>@endif
    </div>
</div>
