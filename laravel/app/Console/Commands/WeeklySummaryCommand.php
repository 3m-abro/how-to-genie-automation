<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class WeeklySummaryCommand extends Command
{
    protected $signature = 'weekly:summary';

    protected $description = 'Generate and send weekly summary email';

    public function handle(): int
    {
        return self::SUCCESS;
    }
}
