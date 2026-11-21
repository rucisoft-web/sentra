@extends('layouts.app')
@section('title', 'Master Data')
@section('header')
    <div class="section-title">M13 · Master data & taksonomi</div>
    <h1 class="text-2xl font-bold text-navy-700 mt-1">Master Data</h1>
    <p class="text-sm text-slate-500">Entitas hukum, departemen, kategori, jenis dokumen, jadwal retensi dan mandat auditor.</p>
@endsection
@section('content')
<div class="grid lg:grid-cols-2 gap-5 mb-6">
    <div class="card overflow-hidden">
        <div class="px-4 py-3 border-b-2 border-navy-700 section-title">Entitas & departemen</div>
        <div class="p-4 space-y-3">
            @foreach($entities as $e)<div class="text-sm"><span class="doc-code font-bold text-navy-700">{{ $e->code }}</span> — {{ $e->name }} <span class="text-xs text-slate-500">({{ $e->departments_count }} departemen)</span></div>@endforeach
            <div class="overflow-x-auto"><table class="tbl"><thead><tr><th>Kode</th><th>Departemen</th><th>Kepala</th><th>User</th></tr></thead><tbody>
            @foreach($departments as $d)<tr><td class="doc-code text-xs">{{ $d->entity->code }}/{{ $d->code }}</td><td>{{ $d->name }}</td><td class="text-sm">{{ $d->head?->name ?? '—' }}</td><td>{{ $d->users_count }}</td></tr>@endforeach
            </tbody></table></div>
            <form method="POST" action="{{ route('admin.master.store', 'department') }}" class="grid grid-cols-2 gap-2 pt-2">@csrf
                <select name="entity_id" class="input">@foreach($entities as $e)<option value="{{ $e->id }}">{{ $e->code }}</option>@endforeach</select>
                <input name="code" class="input" placeholder="Kode" required>
                <input name="name" class="input col-span-2" placeholder="Nama departemen" required>
                <button class="btn btn-outline btn-sm col-span-2">+ Departemen</button>
            </form>
        </div>
    </div>
    <div class="card overflow-hidden">
        <div class="px-4 py-3 border-b-2 border-gold-500 section-title">Kategori & jenis dokumen</div>
        <div class="p-4">
            <div class="overflow-x-auto max-h-[420px] overflow-y-auto"><table class="tbl"><thead><tr><th>Jenis</th><th>Kategori</th><th>Atribut</th></tr></thead><tbody>
            @foreach($types as $t)<tr><td><span class="doc-code text-xs text-slate-500">{{ $t->code }}</span> {{ $t->name }}</td><td class="text-xs">{{ $t->category->name }}</td>
                <td class="text-[11px]">@if($t->requires_expiry)<span class="badge bg-amber-100 text-amber-800">Masa berlaku</span>@endif @if($t->is_material)<span class="badge bg-rose-100 text-rose-800">Material</span>@endif @if($t->requires_acknowledgement)<span class="badge bg-sky-100 text-sky-800">Wajib baca</span>@endif @if($t->review_cycle_months)<span class="badge bg-slate-100">Review {{ $t->review_cycle_months }} bln</span>@endif</td></tr>@endforeach
            </tbody></table></div>
            <form method="POST" action="{{ route('admin.master.store', 'type') }}" class="grid grid-cols-2 gap-2 pt-3">@csrf
                <select name="category_id" class="input col-span-2">@foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>
                <input name="code" class="input" placeholder="Kode (mis. SOP)" required><input name="name" class="input" placeholder="Nama jenis" required>
                <input name="review_cycle_months" type="number" class="input" placeholder="Siklus review (bulan)">
                <div class="flex flex-wrap gap-2 text-xs items-center"><label><input type="checkbox" name="requires_expiry" value="1"> Masa berlaku</label><label><input type="checkbox" name="is_material" value="1"> Material</label><label><input type="checkbox" name="requires_acknowledgement" value="1"> Wajib baca</label><label><input type="checkbox" name="requires_review" value="1"> Review</label></div>
                <button class="btn btn-outline btn-sm col-span-2">+ Jenis dokumen</button>
            </form>
        </div>
    </div>
</div>
<div class="grid lg:grid-cols-2 gap-5">
    <div class="card overflow-hidden">
        <div class="px-4 py-3 border-b-2 border-teal-600 section-title">Jadwal retensi</div>
        <div class="p-4">
            <div class="overflow-x-auto"><table class="tbl"><thead><tr><th>Kode</th><th>Nama</th><th>Periode</th><th>Dok.</th></tr></thead><tbody>
            @foreach($policies as $p)<tr><td class="doc-code text-xs">{{ $p->code }}</td><td class="text-sm">{{ $p->name }}</td><td>{{ $p->periodLabel() }}</td><td>{{ $p->documents_count }}</td></tr>@endforeach
            </tbody></table></div>
            <form method="POST" action="{{ route('admin.master.store', 'retention') }}" class="grid grid-cols-2 gap-2 pt-3">@csrf
                <input name="code" class="input" placeholder="Kode" required><input name="name" class="input" placeholder="Nama" required>
                <input name="category" class="input" placeholder="Kategori" required><input name="period_years" type="number" class="input" placeholder="Tahun (kosong=permanen)">
                <select name="trigger" class="input">@foreach(\App\Models\RetentionPolicy::TRIGGERS as $k=>$l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
                <select name="disposal_action" class="input">@foreach(\App\Models\RetentionPolicy::ACTIONS as $k=>$l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
                <input name="legal_basis" class="input col-span-2" placeholder="Dasar hukum">
                <button class="btn btn-outline btn-sm col-span-2">+ Jadwal retensi</button>
            </form>
        </div>
    </div>
    <div class="card overflow-hidden">
        <div class="px-4 py-3 border-b-2 border-rose-500 section-title">Mandat auditor (akses read-only berbatas waktu)</div>
        <div class="p-4 space-y-3">
            @foreach($mandates as $m)
                <div class="text-sm border-l-4 {{ $m->isActive() ? 'border-emerald-500' : 'border-slate-300' }} pl-3">
                    <div class="font-semibold">{{ $m->auditor->name }} <span class="doc-code text-xs text-slate-500">{{ $m->mandate_number }}</span></div>
                    <div class="text-xs text-slate-500">{{ $m->purpose }} · {{ $m->starts_at->format('d/m/Y') }} – {{ $m->ends_at->format('d/m/Y') }} · {{ $m->isActive() ? 'aktif' : 'tidak aktif' }}</div>
                    <div class="text-xs">Cakupan kategori: {{ collect($m->category_ids)->map(fn($id) => $categories->firstWhere('id', $id)?->name)->filter()->join(', ') ?: 'semua' }}</div>
                </div>
            @endforeach
            <form method="POST" action="{{ route('admin.master.store', 'mandate') }}" class="grid grid-cols-2 gap-2 pt-2">@csrf
                <select name="auditor_id" class="input col-span-2">@foreach($auditors as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach</select>
                <input name="mandate_number" class="input" placeholder="Nomor mandat" required><input name="purpose" class="input" placeholder="Tujuan audit" required>
                <select name="category_ids[]" multiple class="input col-span-2" size="4">@foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>
                <input type="date" name="starts_at" class="input" required><input type="date" name="ends_at" class="input" required>
                <button class="btn btn-outline btn-sm col-span-2">+ Mandat</button>
            </form>
        </div>
    </div>
</div>
@endsection
