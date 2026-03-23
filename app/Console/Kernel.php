<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        Commands\ScanInvoices::class,
        Commands\TestInvoiceFile::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // Optional: auto-scan every day at midnight
        // $schedule->command('invoices:scan')->dailyAt('00:00');
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
