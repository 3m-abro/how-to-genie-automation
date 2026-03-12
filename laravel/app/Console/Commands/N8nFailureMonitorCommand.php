<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class N8nFailureMonitorCommand extends Command
{
    protected $signature = 'n8n:check-failures';

    protected $description = 'Check n8n executions for failures and send Telegram alert';

    public function handle(): int
    {
        return self::SUCCESS;
    }
}
