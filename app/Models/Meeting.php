<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    protected $guarded = [];
    protected $casts = ['meeting_at' => 'datetime'];

    public const TYPES = [
        'rups' => 'RUPS', 'bod' => 'Rapat Direksi (BOD)', 'management' => 'Rapat Manajemen', 'department' => 'Rapat Departemen',
    ];

    public function entity() { return $this->belongsTo(Entity::class); }
    public function chair() { return $this->belongsTo(User::class, 'chair_id'); }
    public function secretary() { return $this->belongsTo(User::class, 'secretary_id'); }
    public function minutes() { return $this->belongsTo(Document::class, 'minutes_document_id'); }
    public function decisions() { return $this->hasMany(MeetingDecision::class)->orderBy('number'); }
    public function actionItems() { return $this->hasMany(ActionItem::class); }
}
