<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentLink extends Model
{
    protected $guarded = [];

    public const SYSTEMS = [
        'asset_care' => 'GPCH Asset Care', 'ruci_bsc' => 'Ruci BSC',
        'crm_gpch' => 'CRM GPCH', 'vhp' => 'VHP (Hotel System)',
    ];

    public function document() { return $this->belongsTo(Document::class); }
}
