<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetentionPolicy extends Model
{
    protected $guarded = [];

    public const TRIGGERS = [
        'effective' => 'Sejak tanggal efektif',
        'expiry' => 'Sejak tanggal berakhir',
        'superseded' => 'Sejak digantikan/dicabut',
        'created' => 'Sejak dibuat',
    ];

    public const ACTIONS = [
        'destroy' => 'Musnahkan (dengan persetujuan ganda)',
        'review' => 'Tinjau ulang',
        'permanent' => 'Simpan permanen',
    ];

    public function documents() { return $this->hasMany(Document::class); }

    public function periodLabel(): string
    {
        return $this->period_years ? $this->period_years.' tahun' : 'Permanen';
    }
}
