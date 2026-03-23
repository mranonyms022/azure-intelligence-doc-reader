<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\InvoiceScannerService;
use App\Services\Azure\AzureDocumentIntelligenceService;

class ScanInvoices extends Command
{
    protected $signature = 'invoices:scan
                            {--store=   : Specific store code e.g. S101 (optional, default = all)}
                            {--dry-run  : Process without saving to database}
                            {--test     : Test Azure connection only, do not scan}';

    protected $description = 'Scan invoices from shared folder → Azure Document Intelligence → Database';

    public function __construct(
        private InvoiceScannerService            $scanner,
        private AzureDocumentIntelligenceService $azure,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        // ── Connection test mode ──
        if ($this->option('test')) {
            return $this->runConnectionTest();
        }

        // ── Scan mode ──
        $storeCode = $this->option('store') ?: null;
        $dryRun    = (bool) $this->option('dry-run');

        $this->printHeader($storeCode, $dryRun);

        $results = $this->scanner->scan(
            storeCode: $storeCode,
            dryRun:    $dryRun,
            logger:    fn(string $msg, string $level = 'info') => match($level) {
                'warn'  => $this->warn($msg),
                'error' => $this->error($msg),
                default => $this->line($msg),
            }
        );

        $this->printResults($results, $dryRun);

        return $results->where('status', 'failed')->isEmpty()
            ? Command::SUCCESS
            : Command::FAILURE;
    }

    // ──────────────────────────────────────────────────────────
    // Connection test
    // ──────────────────────────────────────────────────────────

    private function runConnectionTest(): int
    {
        $this->info('Testing Azure Document Intelligence connection...');
        $this->newLine();

        // Check .env values
        $endpoint = config('invoice.azure.endpoint');
        $key      = config('invoice.azure.key');

        $this->line('  Endpoint : ' . ($endpoint ?: '<NOT SET>'));
        $this->line('  Key      : ' . ($key ? substr($key, 0, 8) . '...' . substr($key, -4) : '<NOT SET>'));
        $this->line('  Version  : ' . config('invoice.azure.version'));
        $this->newLine();

        if (!$endpoint || !$key) {
            $this->error('AZURE_DI_KEY and AZURE_DI_ENDPOINT must be set in .env');
            return Command::FAILURE;
        }

        try {
            $result = $this->azure->testConnection();

            if ($result['success']) {
                $this->info('✓ ' . $result['message']);
                $this->newLine();
                $this->line('  Your Azure keys are working correctly.');
                $this->line('  Run: php artisan invoices:scan --dry-run');
                return Command::SUCCESS;
            }

            $this->error('✗ ' . $result['message']);
            $this->newLine();
            $this->line('  Check:');
            $this->line('  1. AZURE_DI_KEY is correct (copy from Azure portal → Keys and Endpoint)');
            $this->line('  2. AZURE_DI_ENDPOINT includes trailing slash');
            $this->line('  3. Your Azure resource is in "Running" state');
            return Command::FAILURE;

        } catch (\Throwable $e) {
            $this->error('✗ Exception: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    // ──────────────────────────────────────────────────────────
    // Display helpers
    // ──────────────────────────────────────────────────────────

    private function printHeader(?string $storeCode, bool $dryRun): void
    {
        $this->newLine();
        $this->line('╔══════════════════════════════════════════╗');
        $this->line('║       Invoice DMS — Azure Scanner        ║');
        $this->line('╚══════════════════════════════════════════╝');
        $this->newLine();
        $this->line('  Base path : ' . config('invoice.base_path'));
        $this->line('  Store     : ' . ($storeCode ?? 'ALL'));
        $this->line('  Mode      : ' . ($dryRun ? '🔍 DRY RUN (no DB save)' : '💾 LIVE'));
        $this->line('  Min conf  : ' . (config('invoice.min_confidence') * 100) . '%');
        $this->newLine();
    }

    private function printResults(\Illuminate\Support\Collection $results, bool $dryRun): void
    {
        if ($results->isEmpty()) {
            $this->warn('No files were processed.');
            return;
        }

        $this->newLine();
        $this->table(
            ['Store', 'File', 'Status', 'Language', 'Total', 'Message'],
            $results->map(fn($r) => [
                $r['store'],
                strlen($r['file']) > 35 ? substr($r['file'], 0, 32) . '...' : $r['file'],
                match($r['status']) {
                    'success' => '✓ success',
                    'skipped' => '- skipped',
                    'failed'  => '✗ failed',
                    default   => $r['status'],
                },
                $r['lang'],
                $r['total'],
                strlen($r['message']) > 45 ? substr($r['message'], 0, 42) . '...' : $r['message'],
            ])->toArray()
        );

        $this->newLine();
        $success = $results->where('status', 'success')->count();
        $skipped = $results->where('status', 'skipped')->count();
        $failed  = $results->where('status', 'failed')->count();

        $this->info("  ✓ Success : {$success}");
        if ($skipped) $this->warn("  - Skipped : {$skipped}");
        if ($failed)  $this->error("  ✗ Failed  : {$failed}");

        if ($dryRun) {
            $this->newLine();
            $this->warn('  DRY RUN — nothing was saved to the database.');
            $this->line('  Run without --dry-run to save.');
        }

        $this->newLine();
    }
}
