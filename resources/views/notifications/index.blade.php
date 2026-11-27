@extends('layouts.app')
@section('title', 'Notifikasi')
@section('header')<h1 class="text-2xl font-bold text-marun-800">Notifikasi</h1>@endsection
@section('actions')<form method="POST" action="{{ route('notifications.read') }}">@csrf<button class="btn btn-outline btn-sm">Tandai semua dibaca</button></form>@endsection
@section('content')
@php $colors = ['info'=>'border-l-navy-500','success'=>'border-l-emerald-600','warning'=>'border-l-amber-500','danger'=>'border-l-rose-600']; @endphp
<div class="space-y-2">
@forelse($items as $n)
    <a href="{{ $n->url ?? '#' }}" class="card card-accent {{ $colors[$n->level] ?? '' }} p-4 block hover:shadow {{ $n->read_at ? 'opacity-70' : '' }}">
        <div class="flex justify-between gap-3"><div class="font-semibold">{{ $n->title }}</div><div class="text-xs text-slate-500 shrink-0">{{ $n->created_at->diffForHumans() }}</div></div>
        @if($n->body)<div class="text-sm text-slate-600 mt-0.5">{{ $n->body }}</div>@endif
    </a>
@empty <x-empty text="Belum ada notifikasi."/>
@endforelse
</div>
<div class="mt-4">{{ $items->links() }}</div>
@endsection
