<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentCategory extends Model
{
    protected $guarded = [];

    public const MODULES = [
        'legal' => 'Legal & Compliance',
        'policy' => 'Kebijakan, SOP & Organisasi',
        'board' => 'Board & Management Records',
        'finance' => 'Finance & Tax',
        'marketing' => 'Marketing & Customer',
        'hr' => 'Sumber Daya Manusia',
        'operations' => 'Operasional & Aset',
    ];

    public function retentionPolicy() { return $this->belongsTo(RetentionPolicy::class); }
    public function types() { return $this->hasMany(DocumentType::class, 'category_id'); }
}
