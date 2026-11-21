<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentVersion extends Model
{
    protected $guarded = [];

    protected $casts = ['approved_at' => 'datetime', 'locked' => 'bool'];

    public const STATUSES = [
        'draft' => 'Draft', 'in_review' => 'Dalam Review', 'approved' => 'Disetujui',
        'final' => 'Final (Berlaku)', 'superseded' => 'Digantikan', 'rejected' => 'Ditolak',
        'destroyed' => 'Dimusnahkan',
    ];

    protected static function booted(): void
    {
        // M08: versi final tidak boleh ditimpa (file/checksum terkunci)
        static::updating(function (DocumentVersion $v) {
            if ($v->getOriginal('locked') && $v->isDirty(['object_key', 'checksum', 'size', 'mime', 'major', 'minor', 'content_text'])
                && $v->status !== 'destroyed') {
                throw new \RuntimeException('Versi final terkunci dan tidak dapat ditimpa. Buat versi baru.');
            }
        });
    }

    public function document() { return $this->belongsTo(Document::class); }
    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }

    public function label(): string { return 'v'.$this->major.'.'.$this->minor; }

    public function sizeLabel(): string
    {
        $s = $this->size;
        return $s >= 1048576 ? round($s / 1048576, 1).' MB' : max(1, round($s / 1024)).' KB';
    }

    public function isPreviewable(): bool
    {
        return in_array($this->mime, ['application/pdf', 'image/png', 'image/jpeg', 'text/plain']);
    }
}
