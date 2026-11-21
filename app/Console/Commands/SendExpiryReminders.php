<?php

namespace App\Console\Commands;

use App\Models\AppNotification;
use App\Models\Document;
use App\Models\DocumentReminder;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Console\Command;

/** M02 / AC-05: pengingat 90/60/30 hari sebelum izin/kontrak berakhir, plus pengingat review periodik. */
class SendExpiryReminders extends Command
{
    protected $signature = 'archive:reminders';
    protected $description = 'Kirim pengingat masa berlaku dokumen (90/60/30 hari) dan review yang jatuh tempo';

    public function handle(): int
    {
        $sent = 0;
        $docs = Document::with(['owner', 'department'])->where('status', 'published')->whereNotNull('expires_at')->get();
        $legal = User::where('role', 'legal')->pluck('id');
        foreach ($docs as $d) {
            $days = $d->daysToExpiry();
            foreach (array_merge(config('archive.reminder_days'), [0]) as $t) {
                if ($days > $t) continue;
                $exists = DocumentReminder::where('document_id', $d->id)->where('threshold_days', $t)->whereDate('expires_at', $d->expires_at)->exists();
                if ($exists) break;
                DocumentReminder::create(['document_id' => $d->id, 'threshold_days' => $t, 'expires_at' => $d->expires_at, 'sent_at' => now()]);
                $title = $days < 0 ? 'KEDALUWARSA: '.$d->title : "Berakhir dalam {$days} hari: ".$d->title;
                foreach (collect([$d->owner_id, $d->department?->head_user_id])->merge($legal)->filter()->unique() as $uid) {
                    AppNotification::send($uid, $title, $d->code.' — berakhir '.$d->expires_at->format('d/m/Y').'. Siapkan perpanjangan & unggah bukti.',
                        route('documents.show', $d), $days <= 30 ? 'danger' : 'warning');
                }
                AuditLogger::log('reminder.sent', $d, "Pengingat H-{$t} dikirim");
                $sent++;
                break;
            }
        }
        $this->info("{$sent} pengingat dikirim.");
        return self::SUCCESS;
    }
}
