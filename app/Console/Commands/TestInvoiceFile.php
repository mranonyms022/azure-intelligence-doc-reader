<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Azure\AzureDocumentIntelligenceService;

/**
 * Quick single-file test command.
 * Use this to test Azure with one invoice before running full scan.
 *
 * Usage:
 *   php artisan invoices:test-file "D:/shared/S101/invoice.pdf"
 */
class TestInvoiceFile extends Command
{
    protected $signature = 'invoices:test-file {path : Full path to invoice file}';

    protected $description = 'Test Azure Document Intelligence on a single invoice file and show extracted JSON';

    public function __construct(private AzureDocumentIntelligenceService $azure)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $filePath = $this->argument('path');

        $this->newLine();
        $this->info("Testing file: {$filePath}");
        $this->newLine();

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return Command::FAILURE;
        }

        $this->line('  File size : ' . round(filesize($filePath) / 1024, 1) . ' KB');
        $this->line('  Sending to Azure Document Intelligence...');
        $this->newLine();

        try {
            $startTime = microtime(true);
            $data      = $this->azure->analyzeInvoice($filePath);
            $elapsed   = round(microtime(true) - $startTime, 2);

            $this->info("✓ Analysis complete in {$elapsed}s");
            $this->newLine();

            // ── Meta ──
            $this->line('<fg=cyan>── Document Info ──</>');
            $this->line("  Language  : " . ($data['meta']['document_language'] ?? 'unknown'));
            $this->line("  Pages     : " . ($data['meta']['page_count'] ?? 1));
            $this->newLine();

            // ── Fields ──
            $fields = $data['fields'] ?? [];
            if (!empty($fields)) {
                $this->line('<fg=cyan>── Extracted Fields ──</>');
                foreach ($fields as $key => $value) {
                    $conf = $data['confidences'][$key] ?? null;
                    $confStr = $conf ? " <fg=gray>(conf: {$conf})</>" : '';
                    $valueStr = is_numeric($value) ? number_format((float)$value, 2) : $value;
                    $this->line("  {$key}: <fg=green>{$valueStr}</>{$confStr}");
                }
                $this->newLine();
            } else {
                $this->warn('  No fields extracted. Check confidence threshold or document quality.');
                $this->newLine();
            }

            // ── Line items ──
            $lineItems = $data['line_items'] ?? [];
            if (!empty($lineItems)) {
                $this->line('<fg=cyan>── Line Items ──</>');
                $this->table(
                    array_keys($lineItems[0]),
                    array_map(fn($item) => array_map(
                        fn($v) => is_numeric($v) ? number_format((float)$v, 2) : (string)$v,
                        array_values($item)
                    ), $lineItems)
                );
                $this->newLine();
            }

            // ── Full JSON ──
            if ($this->confirm('Show full extracted JSON?', false)) {
                $output = [
                    'meta'       => $data['meta'],
                    'fields'     => $data['fields'],
                    'line_items' => $data['line_items'],
                    'confidences'=> $data['confidences'],
                ];
                $this->line(json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }

            return Command::SUCCESS;

        } catch (\Throwable $e) {
            $this->error('✗ Error: ' . $e->getMessage());
            $this->newLine();

            if (str_contains($e->getMessage(), 'AZURE_DI_KEY')) {
                $this->line('  → Add AZURE_DI_KEY to your .env file');
            } elseif (str_contains($e->getMessage(), 'AZURE_DI_ENDPOINT')) {
                $this->line('  → Add AZURE_DI_ENDPOINT to your .env file');
            } elseif (str_contains($e->getMessage(), '401')) {
                $this->line('  → Invalid Azure key. Check AZURE_DI_KEY in .env');
            } elseif (str_contains($e->getMessage(), '404')) {
                $this->line('  → Invalid endpoint. Check AZURE_DI_ENDPOINT in .env');
            }

            return Command::FAILURE;
        }
    }
}
