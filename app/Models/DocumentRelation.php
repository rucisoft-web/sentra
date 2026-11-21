<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentRelation extends Model
{
    protected $guarded = [];

    public const TYPES = [
        'renewal_of' => 'Perpanjangan dari', 'references' => 'Merujuk ke', 'amends' => 'Mengubah',
        'attachment_of' => 'Lampiran dari', 'evidence_for' => 'Bukti tindak lanjut untuk', 'supersedes' => 'Menggantikan',
    ];

    public function document() { return $this->belongsTo(Document::class); }
    public function related() { return $this->belongsTo(Document::class, 'related_document_id'); }
}
