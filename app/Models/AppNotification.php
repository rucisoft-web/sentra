<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppNotification extends Model
{
    protected $guarded = [];
    protected $casts = ['read_at' => 'datetime'];

    public static function send(int|User $user, string $title, ?string $body = null, ?string $url = null, string $level = 'info'): self
    {
        return static::create([
            'user_id' => $user instanceof User ? $user->id : $user,
            'title' => $title, 'body' => $body, 'url' => $url, 'level' => $level,
        ]);
    }
}
