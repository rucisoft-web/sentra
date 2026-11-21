<?php

namespace App\Console\Commands;

use App\Services\WorkflowService;
use Illuminate\Console\Command;

class EscalateWorkflow extends Command
{
    protected $signature = 'archive:escalate';
    protected $description = 'Eskalasi langkah review/persetujuan yang melewati batas waktu';

    public function handle(WorkflowService $wf): int
    {
        $this->info($wf->escalateOverdue().' langkah dieskalasi.');
        return self::SUCCESS;
    }
}
