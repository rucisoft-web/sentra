<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $guarded = [];

    protected $casts = [
        'effective_at' => 'date', 'review_at' => 'date', 'expires_at' => 'date', 'retention_until' => 'date',
        'checked_out_at' => 'datetime', 'published_at' => 'datetime', 'archived_at' => 'datetime',
        'disposed_at' => 'datetime', 'contract_value' => 'decimal:2',
    ];

    public const CLASSIFICATIONS = [
        'public_internal' => 'Publik Internal',
        'department' => 'Terbatas Departemen',
        'confidential' => 'Rahasia',
        'strictly_confidential' => 'Sangat Rahasia',
    ];

    public const CLASSIFICATION_COLORS = [
        'public_internal' => 'bg-emerald-100 text-emerald-800',
        'department' => 'bg-sky-100 text-sky-800',
        'confidential' => 'bg-amber-100 text-amber-800',
        'strictly_confidential' => 'bg-rose-100 text-rose-800',
    ];

    // SRS §6: Draft → Submitted → Under Review → Revision Required / Approved → Published → Superseded → Archived → Eligible for Disposal → Disposed
    public const STATUSES = [
        'draft' => 'Draft', 'submitted' => 'Diajukan', 'under_review' => 'Dalam Review',
        'revision_required' => 'Perlu Revisi', 'approved' => 'Disetujui', 'published' => 'Berlaku (Published)',
        'superseded' => 'Digantikan', 'archived' => 'Arsip Inaktif', 'eligible_disposal' => 'Layak Musnah',
        'disposed' => 'Dimusnahkan', 'rejected' => 'Ditolak',
    ];

    public const STATUS_COLORS = [
        'draft' => 'bg-slate-100 text-slate-700', 'submitted' => 'bg-indigo-100 text-indigo-800',
        'under_review' => 'bg-indigo-100 text-indigo-800', 'revision_required' => 'bg-orange-100 text-orange-800',
        'approved' => 'bg-teal-100 text-teal-800', 'published' => 'bg-emerald-100 text-emerald-800',
        'superseded' => 'bg-zinc-200 text-zinc-700', 'archived' => 'bg-stone-200 text-stone-700',
        'eligible_disposal' => 'bg-yellow-100 text-yellow-800', 'disposed' => 'bg-zinc-800 text-white',
        'rejected' => 'bg-rose-100 text-rose-800',
    ];

    public const READABLE_STATUSES = ['published', 'superseded', 'archived', 'eligible_disposal'];
    public const EDITABLE_STATUSES = ['draft', 'revision_required', 'rejected'];

    public function entity() { return $this->belongsTo(Entity::class); }
    public function department() { return $this->belongsTo(Department::class); }
    public function category() { return $this->belongsTo(DocumentCategory::class, 'category_id'); }
    public function type() { return $this->belongsTo(DocumentType::class, 'type_id'); }
    public function owner() { return $this->belongsTo(User::class, 'owner_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function checkedOutBy() { return $this->belongsTo(User::class, 'checked_out_by'); }
    public function retentionPolicy() { return $this->belongsTo(RetentionPolicy::class); }
    public function supersededBy() { return $this->belongsTo(Document::class, 'superseded_by_id'); }
    public function currentVersion() { return $this->belongsTo(DocumentVersion::class, 'current_version_id'); }
    public function versions() { return $this->hasMany(DocumentVersion::class)->orderByDesc('major')->orderByDesc('minor'); }
    public function acl() { return $this->hasMany(DocumentAcl::class); }
    public function relations() { return $this->hasMany(DocumentRelation::class); }
    public function inverseRelations() { return $this->hasMany(DocumentRelation::class, 'related_document_id'); }
    public function links() { return $this->hasMany(DocumentLink::class); }
    public function workflows() { return $this->hasMany(WorkflowInstance::class)->latest('id'); }
    public function holds() { return $this->hasMany(LegalHold::class); }
    public function acknowledgements() { return $this->hasMany(Acknowledgement::class); }
    public function disposalRequests() { return $this->hasMany(DisposalRequest::class); }

    public function latestVersion(): ?DocumentVersion { return $this->versions()->first(); }
    public function activeWorkflow(): ?WorkflowInstance { return $this->workflows()->where('status', 'in_progress')->first(); }
    public function hasActiveHold(): bool { return $this->holds()->whereNull('released_at')->exists(); }

    public function statusLabel(): string { return self::STATUSES[$this->status] ?? $this->status; }
    public function statusColor(): string { return self::STATUS_COLORS[$this->status] ?? 'bg-slate-100'; }
    public function classificationLabel(): string { return self::CLASSIFICATIONS[$this->classification] ?? $this->classification; }
    public function classificationColor(): string { return self::CLASSIFICATION_COLORS[$this->classification] ?? ''; }

    public function tagList(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $this->tags))));
    }

    /** Sisa hari sebelum berakhir (negatif = sudah lewat). */
    public function daysToExpiry(): ?int
    {
        return $this->expires_at ? (int) today()->diffInDays($this->expires_at, false) : null;
    }

    public function expiryBadge(): ?array
    {
        $d = $this->daysToExpiry();
        if ($d === null) return null;
        if ($d < 0) return ['Lewat '.abs($d).' hari', 'bg-rose-600 text-white'];
        if ($d <= 30) return [$d.' hari lagi', 'bg-rose-100 text-rose-800'];
        if ($d <= 60) return [$d.' hari lagi', 'bg-orange-100 text-orange-800'];
        if ($d <= 90) return [$d.' hari lagi', 'bg-yellow-100 text-yellow-800'];
        return [$d.' hari lagi', 'bg-emerald-50 text-emerald-700'];
    }

    public function isReviewOverdue(): bool
    {
        return $this->status === 'published' && $this->review_at && $this->review_at->lt(today());
    }

    /** Field metadata wajib yang belum lengkap (untuk laporan "arsip tidak lengkap"). */
    public function missingMetadata(): array
    {
        $m = [];
        if (! $this->current_version_id) $m[] = 'File versi berlaku';
        if (! $this->effective_at && in_array($this->status, ['published', 'superseded', 'archived'])) $m[] = 'Tanggal efektif';
        if ($this->type?->requires_expiry && ! $this->expires_at) $m[] = 'Tanggal berakhir';
        if ($this->type?->requires_review && ! $this->review_at) $m[] = 'Tanggal review';
        if (! $this->retention_policy_id) $m[] = 'Jadwal retensi';
        if (! $this->description) $m[] = 'Deskripsi';
        return $m;
    }

    /** Hitung tanggal akhir retensi berdasarkan kebijakan. */
    public function computeRetentionUntil(): ?\Carbon\Carbon
    {
        $p = $this->retentionPolicy;
        if (! $p || ! $p->period_years) return null;
        $base = match ($p->trigger) {
            'expiry' => $this->expires_at,
            'superseded' => in_array($this->status, ['superseded', 'archived']) ? ($this->archived_at ?? $this->updated_at) : null,
            'created' => $this->created_at,
            default => $this->effective_at,
        };
        return $base ? \Carbon\Carbon::parse($base)->addYears($p->period_years) : null;
    }
}
