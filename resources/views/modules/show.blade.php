@extends('layouts.app')
@section('title', $title)
@section('header')
    <div class="section-title">Arsip modul</div>
    <h1 class="text-2xl font-bold text-navy-700 mt-1">{{ $title }}</h1>
    @if($note)<p class="text-sm text-slate-500 max-w-3xl">{{ $note }}</p>@endif
@endsection
@section('content')
<form class="card p-3 mb-4 flex flex-col sm:flex-row gap-2">
    <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari dalam modul ini" class="input">
    <select name="category_id" class="input sm:w-64"><option value="">Semua kategori</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected(($filters['category_id'] ?? '')==$c->id)>{{ $c->name }}</option>@endforeach</select>
    <select name="status" class="input sm:w-52"><option value="">Semua status aktif</option>@foreach(\App\Models\Document::STATUSES as $k=>$l)<option value="{{ $k }}" @selected(($filters['status'] ?? '')==$k)>{{ $l }}</option>@endforeach</select>
    <button class="btn btn-primary">Filter</button>
</form>
<div class="text-sm text-slate-500 mb-3">{{ $documents->total() }} dokumen</div>
<div class="space-y-2.5">
    @forelse($documents as $doc)<x-doc-row :doc="$doc" :locked="!$doc->can_open"/>@empty<x-empty/>@endforelse
</div>
<div class="mt-4">{{ $documents->links() }}</div>
@endsection
