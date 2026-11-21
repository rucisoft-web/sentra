<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Penyimpanan objek privat. File dienkripsi (AES-256 via APP_KEY) sebelum ditulis,
 * disimpan di luar direktori publik dengan object key per entitas, dan diverifikasi SHA-256.
 */
class DocumentStorage
{
    public static function disk() { return Storage::disk(config('archive.disk')); }

    /** @return array{object_key: string, checksum: string, size: int} */
    public static function put(Document $doc, string $contents): array
    {
        $key = strtolower($doc->entity->code).'/'.date('Y/m').'/'.Str::uuid().'.bin';
        self::disk()->put($key, Crypt::encryptString(base64_encode($contents)));
        return ['object_key' => $key, 'checksum' => hash('sha256', $contents), 'size' => strlen($contents)];
    }

    public static function get(string $key): string
    {
        return base64_decode(Crypt::decryptString(self::disk()->get($key)));
    }

    public static function delete(?string $key): void
    {
        if ($key && self::disk()->exists($key)) self::disk()->delete($key);
    }
}
