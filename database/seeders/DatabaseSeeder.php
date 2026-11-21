<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // bersihkan file arsip demo sebelumnya
        Storage::disk(config('archive.disk'))->deleteDirectory('gpch');
        Storage::disk(config('archive.disk'))->deleteDirectory('gbk');

        $this->call([
            MasterDataSeeder::class,
            DemoDocumentSeeder::class,
        ]);
    }
}
