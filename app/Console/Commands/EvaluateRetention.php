<?php

namespace App\Console\Commands;

use App\Models\Document;
use Illuminate\Console\Command;

class EvaluateRetention extends Command
{
    protected $signature = 'archive:retention';
    protected $description = 'Hitung ulang tanggal retensi & tandai arsip yang layak dimusnahkan (kecuali legal hold / permanen)';

    public function handle(): int
    {
        $n = 0;
        foreach (Document::with(['retentionPolicy', 'holds'])->whereNotIn('status', ['disposed'])->get() as $d) {
            $until = $d->computeRetentionUntil();
            $d->retention_until = $until;
            if ($until && $until->isPast() && in_array($d->status, ['archived', 'superseded'])
                && $d->retentionPolicy?->disposal_action === 'destroy' && ! $d->hasActiveHold()) {
                $d->status = 'eligible_disposal';
                $n++;
            }
            $d->saveQuietly();
        }
        $this->info("{$n} dokumen ditandai layak musnah.");
        return self::SUCCESS;
    }
}
