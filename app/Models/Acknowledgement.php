<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Acknowledgement extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['acknowledged_at' => 'datetime'];

    public function user() { return $this->belongsTo(User::class); }
    public function document() { return $this->belongsTo(Document::class); }
    public function version() { return $this->belongsTo(DocumentVersion::class, 'document_version_id'); }
}
