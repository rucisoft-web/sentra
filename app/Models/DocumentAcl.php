<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentAcl extends Model
{
    protected $table = 'document_acl';
    protected $guarded = [];
    protected $casts = ['expires_at' => 'datetime'];

    public const PERMISSIONS = ['view' => 'Lihat', 'download' => 'Lihat & Unduh', 'edit' => 'Edit', 'deny' => 'TOLAK (deny)'];

    public function document() { return $this->belongsTo(Document::class); }
    public function grantor() { return $this->belongsTo(User::class, 'granted_by'); }

    public function isActive(): bool { return ! $this->expires_at || $this->expires_at->isFuture(); }

    public function subjectLabel(): string
    {
        return match ($this->subject_type) {
            'user' => 'Pengguna: '.(User::find($this->subject_id)?->name ?? $this->subject_id),
            'department' => 'Departemen: '.(Department::find($this->subject_id)?->name ?? $this->subject_id),
            'role' => 'Peran: '.(User::ROLES[$this->subject_id] ?? $this->subject_id),
            default => $this->subject_id,
        };
    }
}
