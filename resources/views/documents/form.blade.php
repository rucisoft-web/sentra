@extends('layouts.app')
@section('title', $document->exists ? 'Ubah Metadata' : 'Daftarkan Dokumen')
@section('header')
    <div class="section-title">{{ $document->exists ? 'Ubah metadata' : 'Pendaftaran dokumen baru' }}</div>
    <h1 class="text-2xl font-bold text-navy-700 mt-1">{{ $document->exists ? $document->title : 'Daftarkan Dokumen' }}</h1>
    <p class="text-sm text-slate-500">File yang diunggah berstatus <b>Draft</b> dan tidak otomatis menjadi dokumen sah sebelum melalui review & pengesahan.</p>
@endsection
@section('content')
<form method="POST" enctype="multipart/form-data" action="{{ $document->exists ? route('documents.update', $document) : route('documents.store') }}" class="grid lg:grid-cols-3 gap-5">
    @csrf @if($document->exists) @method('PUT') @endif
    <div class="lg:col-span-2 space-y-5">
        <div class="card card-accent border-l-navy-700 p-5 grid sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2 section-title">Identitas dokumen (metadata wajib)</div>
            <div class="sm:col-span-2"><label class="label">Judul *</label><input name="title" value="{{ old('title', $document->title) }}" class="input" required></div>
            <div class="sm:col-span-2"><label class="label">Deskripsi / ringkasan *</label><textarea name="description" rows="3" class="input" required>{{ old('description', $document->description) }}</textarea></div>
            <div><label class="label">Entitas hukum *</label><select name="entity_id" class="input" @disabled($document->status === 'published')>@foreach($entities as $e)<option value="{{ $e->id }}" @selected(old('entity_id', $document->entity_id) == $e->id)>{{ $e->code }} — {{ $e->name }}</option>@endforeach</select>
                @if($document->status === 'published')<input type="hidden" name="entity_id" value="{{ $document->entity_id }}">@endif</div>
            <div><label class="label">Departemen pemilik *</label><select name="department_id" class="input">@foreach($departments as $d)<option value="{{ $d->id }}" @selected(old('department_id', $document->department_id) == $d->id)>{{ $d->entity->code }} · {{ $d->name }}</option>@endforeach</select></div>
            <div><label class="label">Jenis dokumen *</label><select name="type_id" class="input" required><option value="">— pilih —</option>
                @foreach($types->groupBy(fn($t)=>$t->category->name) as $cat => $ts)<optgroup label="{{ $cat }}">@foreach($ts as $t)<option value="{{ $t->id }}" @selected(old('type_id', $document->type_id) == $t->id)>{{ $t->name }}{{ $t->requires_expiry ? ' (bermasa berlaku)' : '' }}</option>@endforeach</optgroup>@endforeach</select></div>
            <div><label class="label">Nomor dokumen</label><input name="code" value="{{ old('code', $document->code) }}" class="input doc-code" placeholder="Otomatis: GPCH/DEPT/JENIS/{{ date('Y') }}/NNN" @readonly($document->status === 'published')><div class="text-xs text-slate-500 mt-1">Kosongkan untuk penomoran otomatis. Duplikasi dalam entitas ditolak.</div></div>
            <div><label class="label">Klasifikasi *</label><select name="classification" class="input">@foreach(\App\Models\Document::CLASSIFICATIONS as $k => $l)<option value="{{ $k }}" @selected(old('classification', $document->classification) == $k)>{{ $l }}</option>@endforeach</select></div>
            <div><label class="label">Pemilik / PIC *</label><select name="owner_id" class="input">@foreach($owners as $o)<option value="{{ $o->id }}" @selected(old('owner_id', $document->owner_id) == $o->id)>{{ $o->name }} — {{ $o->position }}</option>@endforeach</select></div>
            <div class="sm:col-span-2"><label class="label">Tag (pisahkan koma)</label><input name="tags" value="{{ old('tags', $document->tags) }}" class="input" placeholder="mis. k3, lift, sertifikat"></div>
        </div>
        <div class="card card-accent border-l-gold-500 p-5 grid sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2 section-title">Legal, kontrak & kewajiban</div>
            <div><label class="label">Pihak terkait / counterparty</label><input name="counterparty" value="{{ old('counterparty', $document->counterparty) }}" class="input"></div>
            <div><label class="label">Nilai kontrak (Rp)</label><input name="contract_value" type="number" step="0.01" value="{{ old('contract_value', $document->contract_value) }}" class="input"></div>
            <div class="sm:col-span-2"><label class="label">Kewajiban / obligasi</label><textarea name="obligations" rows="2" class="input">{{ old('obligations', $document->obligations) }}</textarea></div>
        </div>
    </div>
    <div class="space-y-5">
        <div class="card card-accent border-l-teal-600 p-5 space-y-4">
            <div class="section-title">Tanggal & retensi</div>
            <div><label class="label">Tanggal efektif</label><input type="date" name="effective_at" value="{{ old('effective_at', $document->effective_at?->toDateString()) }}" class="input"></div>
            <div><label class="label">Tanggal berakhir (izin/kontrak)</label><input type="date" name="expires_at" value="{{ old('expires_at', $document->expires_at?->toDateString()) }}" class="input"></div>
            <div><label class="label">Tanggal review berikutnya</label><input type="date" name="review_at" value="{{ old('review_at', $document->review_at?->toDateString()) }}" class="input"></div>
            <div><label class="label">Jadwal retensi</label><select name="retention_policy_id" class="input"><option value="">Ikuti kategori</option>@foreach($retentionPolicies as $p)<option value="{{ $p->id }}" @selected(old('retention_policy_id', $document->retention_policy_id) == $p->id)>{{ $p->code }} — {{ $p->name }} ({{ $p->periodLabel() }})</option>@endforeach</select></div>
        </div>
        @unless($document->exists)
        <div class="card card-accent border-l-indigo-600 p-5">
            <div class="section-title mb-3">File (versi 0.1 draft)</div>
            <input type="file" name="file" class="input" required accept=".{{ implode(',.', config('archive.allowed_mimes')) }}">
            <div class="text-xs text-slate-500 mt-2">PDF/DOCX/XLSX/PNG/JPG, maks {{ round(config('archive.max_upload_kb')/1024) }} MB. Divalidasi MIME, dienkripsi & disimpan di storage privat dengan checksum SHA-256.</div>
        </div>
        @endunless
        @if($document->status === 'published')
        <div class="card card-accent border-l-rose-600 p-5">
            <label class="label">Alasan perubahan metadata final *</label>
            <textarea name="change_reason" class="input" rows="2" required></textarea>
            <div class="text-xs text-slate-500 mt-1">Perubahan metadata dokumen final terkendali dan tercatat (hash sebelum/sesudah).</div>
        </div>
        @endif
        <button class="btn btn-primary w-full !py-3">{{ $document->exists ? 'Simpan perubahan' : 'Daftarkan sebagai Draft' }}</button>
    </div>
</form>
@endsection
