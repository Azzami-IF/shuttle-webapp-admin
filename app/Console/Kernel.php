<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Define scheduled commands here when needed
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        // register single commands explicitly if needed
        $this->commands([
            \App\Console\Commands\ApiPing::class,
            \App\Console\Commands\DbDiagCommand::class,
        ]);
    }
}
