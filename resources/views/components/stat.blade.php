@props(['label', 'value', 'color' => 'navy-700', 'href' => null, 'hint' => null])
<a @if($href) href="{{ $href }}" @endif class="card card-accent border-l-{{ $color }} p-4 block {{ $href ? 'hover:shadow-md transition' : '' }}">
    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500">{{ $label }}</div>
    <div class="text-2xl sm:text-3xl font-bold text-{{ $color }} mt-1">{{ $value }}</div>
    @if($hint)<div class="text-xs text-slate-500 mt-1">{{ $hint }}</div>@endif
</a>
