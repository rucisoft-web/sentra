@props(['doc'])
@if($b = $doc->expiryBadge())<span class="badge {{ $b[1] }}">{{ $b[0] }}</span>@endif
