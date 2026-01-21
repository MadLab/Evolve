<?php

namespace MadLab\Evolve\Console\Commands;

use Illuminate\Console\Command;
use MadLab\Evolve\Models\ConversionLog;

class PruneConversionLogs extends Command
{
    protected $signature = 'evolve:prune-logs';

    protected $description = 'Prune old conversion logs based on retention period';

    public function handle(): void
    {
        $days = config('evolve.conversion_log_retention_days', 30);
        $deleted = ConversionLog::where('created_at', '<', now()->subDays($days))->delete();
        $this->info("Pruned {$deleted} conversion logs older than {$days} days.");
    }
}