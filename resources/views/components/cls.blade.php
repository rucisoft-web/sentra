@props(['doc', 'value' => null])
@php $v = $value ?? $doc->classification; @endphp
<span class="badge border {{ \App\Models\Document::CLASSIFICATION_COLORS[$v] ?? '' }} border-current/20">
    @if(in_array($v, ['confidential','strictly_confidential']))<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"/></svg>@endif
    {{ \App\Models\Document::CLASSIFICATIONS[$v] ?? $v }}
</span>
