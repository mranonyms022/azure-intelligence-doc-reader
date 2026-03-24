<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use App\Services\Azure\AzureDocumentIntelligenceService;
use App\Models\Store;
use App\Models\Invoice;

class InvoiceScannerService
{
    public function __construct(
        private AzureDocumentIntelligenceService $azure
    ) {}

    /**
     * Scan store folders under base_path.
     *
     * @param  string|null $storeCode  Filter to one store (e.g. S101) or null for all
     * @param  bool        $dryRun     If true: process but don't save to DB
     * @param  callable    $logger     fn(string $message, string $level = 'info')
     * @return Collection  Result rows for table display
     */
    public function scan(?string $storeCode, bool $dryRun, callable $logger): Collection
    {
        $basePath = config('invoice.base_path');
        $results  = collect();

        $storeFolders = $this->resolveStoreFolders($basePath, $storeCode);

        if ($storeFolders->isEmpty()) {
            $logger("No store folders found in: {$basePath}", 'warn');
            return $results;
        }

        $logger("Found " . $storeFolders->count() . " store(s) to process");

        foreach ($storeFolders as $folderPath => $code) {
            $logger("─── Store: {$code}");

            $store = $dryRun ? null : Store::firstOrCreate(
                ['code' => $code],
                ['name' => $code, 'folder_path' => $folderPath]
            );

            $files = $this->findInvoiceFiles($folderPath);

            if ($files->isEmpty()) {
                $logger("    No invoice files found", 'warn');
                $results->push($this->row($code, '(empty folder)', 'skipped', '-', '-', 'No files'));
                continue;
            }

            $logger("    Found {$files->count()} file(s)");

            foreach ($files as $filePath) {
                $result = $this->processOne($filePath, $store, $code, $dryRun, $logger);
                $results->push($result);
            }
        }

        return $results;
    }

    // ──────────────────────────────────────────────────────────
    // Process single file
    // ──────────────────────────────────────────────────────────

    private function processOne(
        string  $filePath,
        ?Store  $store,
        string  $storeCode,
        bool    $dryRun,
        callable $logger
    ): array {
        $fileName = basename($filePath);
        $logger("    → {$fileName}");

        try {
            // Skip already processed
            if (
                !$dryRun && Invoice::where('store_code', $storeCode)
                ->where('file_name', $fileName)
                ->exists()
            ) {
                $logger("      [SKIP] Already in database");
                return $this->row($storeCode, $fileName, 'skipped', '-', '-', 'Already processed');
            }

            // ── Call Azure Document Intelligence ──
            $data = $this->azure->analyzeInvoice($filePath);

            $lang        = $data['meta']['document_language'] ?? 'unknown';
            $fieldCount  = count($data['fields'] ?? []);
            $itemCount   = count($data['line_items'] ?? []);
            $totalAmount = $data['fields']['total_amount'] ?? '-';
            $currency    = $data['fields']['currency'] ?? '';

            $logger("      [OK] Lang: {$lang} | Fields: {$fieldCount} | Items: {$itemCount} | Total: {$totalAmount} {$currency}");

            // ── Save to DB ──
            if (!$dryRun) {
                $this->saveInvoice($store, $storeCode, $fileName, $filePath, $data);
            }

            $summary = "Fields: {$fieldCount} | Items: {$itemCount}" . ($dryRun ? ' (dry run)' : ' | Saved');
            return $this->row($storeCode, $fileName, 'success', $lang, $totalAmount . ' ' . $currency, $summary);
        } catch (\Throwable $e) {
            Log::error("InvoiceScanner: Failed [{$filePath}]", [
                'error' => $e->getMessage(),
                'file'  => $e->getFile() . ':' . $e->getLine(),
            ]);
            $logger("      [FAIL] " . $e->getMessage(), 'error');
            return $this->row($storeCode, $fileName, 'failed', '-', '-', $e->getMessage());
        }
    }

    // ──────────────────────────────────────────────────────────
    // DB save
    // ──────────────────────────────────────────────────────────

    private function saveInvoice(
        Store  $store,
        string $storeCode,
        string $fileName,
        string $filePath,
        array  $data
    ): Invoice {
        $fields = $data['fields'] ?? [];

        return Invoice::updateOrCreate(
            [
                'store_code' => $storeCode,
                'file_name'  => $fileName,
            ],
            [
                'store_id'          => $store->id,
                'store_code'        => $storeCode,
                'file_name'         => $fileName,
                'file_path'         => $filePath,
                'document_language' => $data['meta']['document_language'] ?? null,
                'page_count'        => $data['meta']['page_count'] ?? 1,

                // Extracted invoice fields
                'invoice_number'    => $fields['invoice_number']   ?? null,
                'invoice_date'      => $fields['invoice_date']     ?? null,
                'due_date'          => $fields['due_date']         ?? null,
                'po_number'         => $fields['po_number']        ?? null,
                'vendor_name'       => $fields['vendor_name']      ?? null,
                'vendor_address'    => $fields['vendor_address']   ?? null,
                'vendor_tax_id'     => $fields['vendor_tax_id']    ?? null,
                'customer_name'     => $fields['customer_name']    ?? null,
                'customer_address'  => $fields['customer_address'] ?? null,
                'subtotal'          => $fields['subtotal']         ?? null,
                'vat_amount'        => $fields['vat_amount']       ?? null,
                'total_amount'      => $fields['total_amount']     ?? null,
                'amount_due'        => $fields['amount_due']       ?? null,
                'currency'          => $fields['currency']         ?? null,

                // Structured JSON
                'line_items'        => $data['line_items'],
                'raw_fields'        => $fields,
                'confidences'       => $data['confidences'] ?? [],
                'raw_azure_json'    => $data['raw'],     // null unless INVOICE_STORE_RAW=true

                'processed_at'      => now(),
                'needs_review'         => $this->needsReview($data['confidences'] ?? []),
                'min_confidence_score' => $this->minConfidence($data['confidences'] ?? []),
            ]
        );
    }

    // ──────────────────────────────────────────────────────────
    // Folder helpers
    // ──────────────────────────────────────────────────────────

    private function resolveStoreFolders(string $basePath, ?string $storeCode): Collection
    {
        if (!is_dir($basePath)) {
            throw new \RuntimeException(
                "Base path not found: {$basePath}\n"
                    . "Set INVOICE_BASE_PATH in your .env file."
            );
        }

        $folders = collect();

        if ($storeCode) {
            $path = rtrim($basePath, '/\\') . DIRECTORY_SEPARATOR . $storeCode;
            if (is_dir($path)) {
                $folders->put($path, $storeCode);
            } else {
                throw new \RuntimeException("Store folder not found: {$path}");
            }
        } else {
            // Auto-detect all S### folders (S101, S102, etc.)
            $pattern = rtrim($basePath, '/\\') . DIRECTORY_SEPARATOR . 'S*';
            foreach (glob($pattern, GLOB_ONLYDIR) as $dir) {
                $folders->put($dir, basename($dir));
            }
        }

        return $folders;
    }

    private function findInvoiceFiles(string $folderPath): Collection
    {
        $extensions = config('invoice.extensions', ['pdf', 'jpg', 'jpeg', 'png', 'tiff', 'tif']);
        $pattern    = rtrim($folderPath, '/\\') . DIRECTORY_SEPARATOR
            . '*.{' . implode(',', $extensions) . '}';

        return collect(glob($pattern, GLOB_BRACE) ?: []);
    }

    private function row(
        string $store,
        string $file,
        string $status,
        string $lang,
        string $total,
        string $message
    ): array {
        return compact('store', 'file', 'status', 'lang', 'total', 'message');
    }

    // ──────────────────────────────────────────────────────────
    // Quality control helpers
    // ──────────────────────────────────────────────────────────

    /**
     * Flag invoice for manual review if any field confidence is below 0.70.
     */
    private function needsReview(array $confidences): bool
    {
        if (empty($confidences)) return false;

        return min(array_values($confidences)) < 0.70;
    }

    /**
     * Return the lowest confidence score across all extracted fields.
     * Saved to DB for easy sorting of review queue.
     */
    private function minConfidence(array $confidences): ?float
    {
        if (empty($confidences)) return null;

        return round(min(array_values($confidences)), 2);
    }
}
