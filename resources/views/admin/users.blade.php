@extends('layouts.app')
@section('title', 'Pengguna & Peran')
@section('header')
    <div class="section-title">M13 · Administration</div>
    <h1 class="text-2xl font-bold text-navy-700 mt-1">Pengguna, Peran & Masa Berlaku Akses</h1>
    <p class="text-sm text-slate-500">Administrator teknis mengelola akun tetapi <b>tidak otomatis</b> dapat membaca dokumen rahasia (AC-07). Akun auditor/tamu wajib bertanggal kedaluwarsa. Menonaktifkan akun langsung mencabut sesi.</p>
@endsection
@section('content')
@php $isIt = auth()->user()->hasRole('it_admin'); @endphp
<div class="space-y-2.5 mb-6">
@foreach($users as $usr)
    <div class="card p-3 sm:p-4 flex flex-col lg:flex-row lg:items-center gap-3 border-l-4 {{ $usr->is_active ? 'border-l-navy-500' : 'border-l-slate-300 opacity-70' }}">
        <div class="flex items-center gap-3 flex-1 min-w-0">
            <div class="w-10 h-10 shrink-0 rounded-md bg-navy-50 border-2 border-navy-200 text-navy-700 font-bold text-xs flex items-center justify-center">{{ $usr->initials() }}</div>
            <div class="min-w-0">
                <div class="font-semibold">{{ $usr->name }} @unless($usr->is_active)<span class="badge bg-slate-200 text-slate-600">Nonaktif</span>@endunless @if($usr->isAccessExpired())<span class="badge bg-rose-100 text-rose-800">Akses berakhir</span>@endif</div>
                <div class="text-xs text-slate-500 truncate">{{ $usr->email }} · {{ $usr->position }} · {{ $usr->department?->name }}</div>
                <div class="text-xs text-slate-400">Login terakhir: {{ $usr->last_login_at?->diffForHumans() ?? 'belum pernah' }}</div>
            </div>
        </div>
        @if($isIt)
        <form method="POST" action="{{ route('admin.users.update', $usr) }}" class="grid grid-cols-2 sm:flex sm:flex-wrap items-center gap-2 text-sm">@csrf @method('PUT')
            <select name="role" class="input !py-1.5 sm:w-48">@foreach(\App\Models\User::ROLES as $k=>$l)<option value="{{ $k }}" @selected($usr->role===$k)>{{ $l }}</option>@endforeach</select>
            <select name="department_id" class="input !py-1.5 sm:w-44">@foreach($departments as $d)<option value="{{ $d->id }}" @selected($usr->department_id===$d->id)>{{ $d->name }}</option>@endforeach</select>
            <input type="date" name="access_expires_at" value="{{ $usr->access_expires_at?->toDateString() }}" class="input !py-1.5 sm:w-40" title="Akses berlaku s.d.">
            <label class="flex items-center gap-1 text-xs"><input type="checkbox" name="is_active" value="1" @checked($usr->is_active)> Aktif</label>
            <label class="flex items-center gap-1 text-xs"><input type="checkbox" name="can_bulk_export" value="1" @checked($usr->can_bulk_export)> Ekspor massal</label>
            <button class="btn btn-outline btn-sm">Simpan</button>
        </form>
        @if($usr->role === 'service')<form method="POST" action="{{ route('admin.users.token', $usr) }}">@csrf<button class="btn btn-gold btn-sm">Terbitkan token API</button></form>@endif
        @else
            <span class="badge bg-navy-50 text-navy-700">{{ $usr->roleLabel() }}</span>
        @endif
    </div>
@endforeach
</div>
@if($isIt)
<form method="POST" action="{{ route('admin.users.store') }}" class="card card-accent border-l-gold-500 p-5 grid sm:grid-cols-2 lg:grid-cols-4 gap-3">@csrf
    <div class="sm:col-span-2 lg:col-span-4 section-title">Tambah pengguna</div>
    <input name="name" class="input" placeholder="Nama lengkap" required>
    <input name="email" type="email" class="input" placeholder="Email" required>
    <input name="position" class="input" placeholder="Jabatan">
    <select name="role" class="input">@foreach(\App\Models\User::ROLES as $k=>$l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
    <select name="entity_id" class="input">@foreach($entities as $e)<option value="{{ $e->id }}">{{ $e->code }}</option>@endforeach</select>
    <select name="department_id" class="input">@foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select>
    <input type="date" name="access_expires_at" class="input" title="Kedaluwarsa akses (wajib auditor/tamu)">
    <button class="btn btn-primary">Tambah</button>
</form>
@endif
@endsection
