<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Console\Command;

class SetPassword extends Command
{
    protected $signature = 'archive:set-password {email? : Email pengguna} {--all-demo : Ganti password semua akun demo sekaligus}';
    protected $description = 'Ganti kata sandi pengguna (mis. setelah demo dipasang di server publik)';

    public function handle(): int
    {
        $users = $this->option('all-demo')
            ? User::where('role', '!=', 'service')->get()
            : User::where('email', $this->argument('email'))->get();
        if ($users->isEmpty()) { $this->error('Pengguna tidak ditemukan.'); return self::FAILURE; }
        $pwd = $this->secret('Kata sandi baru (min. 10 karakter)');
        if (strlen((string) $pwd) < 10 || $pwd !== $this->secret('Ulangi kata sandi')) { $this->error('Kata sandi terlalu pendek atau tidak sama.'); return self::FAILURE; }
        foreach ($users as $u) {
            $u->update(['password' => $pwd]);
            AuditLogger::log('admin.user_updated', $u, 'Kata sandi diganti melalui CLI');
        }
        $this->info($users->count().' akun diperbarui.');
        return self::SUCCESS;
    }
}
