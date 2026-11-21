<?php

namespace App\Services;

use App\Models\AuditEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuditLogger
{
    private static ?string $correlation = null;

    public static function correlation(): string
    {
        return self::$correlation ??= (string) Str::uuid();
    }

    public static function log(string $event, $resource = null, ?string $description = null, array $meta = [], ?int $actorId = null, $at = null, ?string $before = null, ?string $after = null): AuditEvent
    {
        return DB::transaction(function () use ($event, $resource, $description, $meta, $actorId, $at, $before, $after) {
            $prev = AuditEvent::query()->lockForUpdate()->orderByDesc('id')->value('hash');
            $req = app()->runningInConsole() ? null : request();

            $e = new AuditEvent([
                'actor_id' => $actorId ?? auth()->id(),
                'event' => $event,
                'resource_type' => $resource ? class_basename($resource) : null,
                'resource_id' => $resource?->id,
                'description' => $description ? Str::limit($description, 250) : null,
                'ip' => $req?->ip() ?? ($meta['_ip'] ?? '127.0.0.1'),
                'device' => $req ? Str::limit((string) $req->userAgent(), 250) : ($meta['_device'] ?? 'system/cli'),
                'before_hash' => $before,
                'after_hash' => $after,
                'meta' => array_diff_key($meta, ['_ip' => 1, '_device' => 1]) ?: null,
                'correlation_id' => self::correlation(),
                'created_at' => $at ?? now(),
            ]);
            $e->prev_hash = $prev;
            $e->hash = AuditEvent::computeHash($prev, $e->payload());
            $e->save();
            return $e;
        });
    }

    /** Verifikasi seluruh rantai hash. @return array{ok: bool, checked: int, broken_at: ?int} */
    public static function verify(): array
    {
        $prev = null; $n = 0;
        foreach (AuditEvent::orderBy('id')->cursor() as $e) {
            $n++;
            if ($e->prev_hash !== $prev || $e->hash !== AuditEvent::computeHash($prev, $e->payload())) {
                return ['ok' => false, 'checked' => $n, 'broken_at' => $e->id];
            }
            $prev = $e->hash;
        }
        return ['ok' => true, 'checked' => $n, 'broken_at' => null];
    }

    public static function snapshotHash($model): string
    {
        return hash('sha256', json_encode($model->getAttributes()));
    }
}
