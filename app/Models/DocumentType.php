<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    protected $guarded = [];

    protected $casts = [
        'requires_expiry' => 'bool', 'requires_review' => 'bool',
        'is_material' => 'bool', 'requires_acknowledgement' => 'bool',
    ];

    public function category() { return $this->belongsTo(DocumentCategory::class, 'category_id'); }
}
