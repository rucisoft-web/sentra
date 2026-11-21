<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Audit trail append-only. Setiap baris menyimpan hash dari baris sebelumnya
 * (hash chain) sehingga perubahan/penghapusan dapat dideteksi.
 */
class AuditEvent extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['meta' => 'array', 'created_at' => 'datetime'];

    public const EVENTS = [
        'login' => 'Login', 'logout' => 'Logout', 'login_failed' => 'Login gagal', 'mfa_verified' => 'Verifikasi MFA',
        'mfa_failed' => 'MFA gagal', 'document.created' => 'Dokumen dibuat', 'document.updated' => 'Metadata diubah',
        'document.viewed' => 'Dokumen dilihat', 'document.previewed' => 'Preview file', 'document.downloaded' => 'File diunduh',
        'document.access_denied' => 'Akses ditolak', 'version.uploaded' => 'Versi diunggah', 'document.checked_out' => 'Check-out',
        'document.checked_in' => 'Check-in', 'workflow.submitted' => 'Diajukan', 'workflow.reviewed' => 'Direview',
        'workflow.approved' => 'Disetujui', 'workflow.revision' => 'Minta revisi', 'workflow.rejected' => 'Ditolak',
        'workflow.delegated' => 'Didelegasikan', 'workflow.escalated' => 'Eskalasi', 'document.published' => 'Dipublikasikan',
        'document.superseded' => 'Digantikan', 'document.archived' => 'Diarsipkan', 'acl.granted' => 'Hak akses diberikan',
        'acl.revoked' => 'Hak akses dicabut', 'access.requested' => 'Permintaan akses', 'access.decided' => 'Keputusan akses',
        'hold.placed' => 'Legal hold dipasang', 'hold.released' => 'Legal hold dilepas', 'disposal.requested' => 'Usulan pemusnahan',
        'disposal.approved' => 'Pemusnahan disetujui', 'disposal.rejected' => 'Pemusnahan ditolak', 'disposal.executed' => 'Pemusnahan dieksekusi',
        'disposal.blocked' => 'Pemusnahan diblokir', 'search' => 'Pencarian', 'export' => 'Ekspor data', 'acknowledged' => 'Acknowledgement',
        'reminder.sent' => 'Pengingat dikirim', 'admin.user_updated' => 'Admin: pengguna diubah', 'admin.master_updated' => 'Admin: master data',
        'api.access' => 'Akses API', 'action_item.updated' => 'Action item diperbarui',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new \RuntimeException('Audit trail bersifat append-only.'));
        static::deleting(fn () => throw new \RuntimeException('Audit trail bersifat append-only.'));
    }

    public function actor() { return $this->belongsTo(User::class, 'actor_id'); }

    public function eventLabel(): string { return self::EVENTS[$this->event] ?? $this->event; }

    public static function computeHash(?string $prev, array $payload): string
    {
        return hash('sha256', ($prev ?? 'GENESIS').'|'.json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public function payload(): array
    {
        return [
            'actor_id' => $this->actor_id, 'event' => $this->event, 'resource_type' => $this->resource_type,
            'resource_id' => $this->resource_id, 'description' => $this->description, 'ip' => $this->ip,
            'before_hash' => $this->before_hash, 'after_hash' => $this->after_hash,
            'correlation_id' => $this->correlation_id, 'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
