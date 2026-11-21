<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingDecision extends Model
{
    protected $guarded = [];

    public function meeting() { return $this->belongsTo(Meeting::class); }
    public function actionItems() { return $this->hasMany(ActionItem::class, 'decision_id'); }
}
