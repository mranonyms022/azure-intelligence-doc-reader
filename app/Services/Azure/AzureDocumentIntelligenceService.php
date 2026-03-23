<?php

namespace App\Services\Azure;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;

/**
 * Azure Document Intelligence — prebuilt-invoice model
 *
 * Supports Arabic, English, and mixed-language invoices natively.
 * No BiDi fix needed. No field mapping needed.
 * Arabic text comes out in correct logical order automatically.
 */
class AzureDocumentIntelligenceService
{
    private string $endpoint;
    private string $key;
    private string $version;
    private int    $pollMaxAttempts;
    private int    $pollSleepSeconds;

    public function __construct()
    {
        $this->endpoint         = rtrim(config('invoice.azure.endpoint', ''), '/');
        $this->key              = config('invoice.azure.key', '');
        $this->version          = config('invoice.azure.version', '2024-11-30');
        $this->pollMaxAttempts  = config('invoice.azure.poll_max_attempts', 30);
        $this->pollSleepSeconds = config('invoice.azure.poll_sleep_seconds', 2);
    }

    // ──────────────────────────────────────────────────────────
    // PUBLIC API
    // ──────────────────────────────────────────────────────────

    /**
     * Analyze a local invoice file.
     * Returns structured invoice data — Arabic and English both correct.
     *
     * @param  string $localFilePath  Full path to PDF/JPG/PNG/TIFF
     * @return array  Structured invoice data (see extractInvoiceFields)
     *
     * @throws \RuntimeException on API error or timeout
     */
    public function analyzeInvoice(string $localFilePath): array
    {
        $this->validateConfig();
        $this->validateFile($localFilePath);

        Log::info("AzureDI: Starting analysis → " . basename($localFilePath));

        $operationUrl = $this->submitDocument($localFilePath);
        $rawResult    = $this->pollForResult($operationUrl);

        return $this->extractInvoiceFields($rawResult);
    }

    /**
     * Quick connectivity test — validates key and endpoint are correct.
     * Call this from the test command before scanning.
     */
    public function testConnection(): array
    {
        $this->validateConfig();

        $url = "{$this->endpoint}/documentintelligence/documentModels"
            . "?api-version={$this->version}";

        $response = Http::withHeaders([
            'Ocp-Apim-Subscription-Key' => $this->key,
        ])->withoutVerifying()->get($url);

        if ($response->successful()) {
            return ['success' => true, 'message' => 'Azure DI connection successful'];
        }

        return [
            'success' => false,
            'message' => "Connection failed [{$response->status()}]: " . $response->body(),
        ];
    }

    // ──────────────────────────────────────────────────────────
    // STEP 1: Submit document to Azure
    // ──────────────────────────────────────────────────────────

    private function submitDocument(string $filePath): string
    {
        $url = "{$this->endpoint}/documentintelligence/documentModels/prebuilt-invoice:analyze"
            . "?api-version={$this->version}";

        $fileBytes = file_get_contents($filePath);
        $mimeType  = $this->mimeType($filePath);

        Log::debug("AzureDI: Submitting to {$url} | MIME: {$mimeType} | Size: " . strlen($fileBytes) . " bytes");

        $response = Http::withHeaders([
            'Ocp-Apim-Subscription-Key' => $this->key,
            'Content-Type'              => $mimeType,
        ])->withoutVerifying()->withBody($fileBytes, $mimeType)->post($url);

        if ($response->status() !== 202) {
            $this->throwApiError('Submit', $response->status(), $response->body());
        }

        $operationUrl = $response->header('Operation-Location');

        if (!$operationUrl) {
            throw new \RuntimeException(
                "AzureDI: No Operation-Location header in response. "
                    . "Check your endpoint URL format."
            );
        }

        Log::debug("AzureDI: Job submitted → {$operationUrl}");
        return $operationUrl;
    }

    // ──────────────────────────────────────────────────────────
    // STEP 2: Poll until analysis complete
    // ──────────────────────────────────────────────────────────

    private function pollForResult(string $operationUrl): array
    {
        Log::debug("AzureDI: Polling (max {$this->pollMaxAttempts} attempts, {$this->pollSleepSeconds}s interval)");

        for ($attempt = 1; $attempt <= $this->pollMaxAttempts; $attempt++) {
            sleep($this->pollSleepSeconds);

            $response = Http::withHeaders([
                'Ocp-Apim-Subscription-Key' => $this->key,
            ])->withoutVerifying()->get($operationUrl);

            if ($response->failed()) {
                throw new \RuntimeException(
                    "AzureDI: Poll request failed [{$response->status()}]: " . $response->body()
                );
            }

            $body   = $response->json();
            $status = $body['status'] ?? 'unknown';

            Log::debug("AzureDI: Poll #{$attempt} — status = {$status}");

            switch ($status) {
                case 'succeeded':
                    Log::info("AzureDI: Analysis succeeded after {$attempt} polls");
                    return $body['analyzeResult'] ?? [];

                case 'failed':
                    $errorMsg = $body['error']['message']
                        ?? $body['error']['innererror']['message']
                        ?? 'Unknown Azure error';
                    throw new \RuntimeException("AzureDI: Analysis failed → {$errorMsg}");

                case 'running':
                case 'notStarted':
                    // continue polling
                    break;

                default:
                    Log::warning("AzureDI: Unknown status [{$status}]");
            }
        }

        $totalSeconds = $this->pollMaxAttempts * $this->pollSleepSeconds;
        throw new \RuntimeException("AzureDI: Timed out after {$totalSeconds}s");
    }

    // ──────────────────────────────────────────────────────────
    // STEP 3: Extract clean structured fields from Azure response
    // ──────────────────────────────────────────────────────────

    /**
     * Converts raw Azure response → clean structured array.
     *
     * Azure prebuilt-invoice returns these standard fields:
     *   InvoiceId, InvoiceDate, DueDate, PurchaseOrder,
     *   VendorName, VendorAddress, VendorTaxId,
     *   CustomerName, CustomerAddress, CustomerTaxId,
     *   SubTotal, TotalTax, InvoiceTotal, AmountDue,
     *   CurrencyCode, Items[]
     *
     * Arabic field values are already in correct logical order.
     * No BiDi fix required.
     */
    private function extractInvoiceFields(array $analyzeResult): array
    {
        $documents = $analyzeResult['documents'] ?? [];
        $doc       = $documents[0] ?? [];
        $fields    = $doc['fields'] ?? [];
        $minConf   = config('invoice.min_confidence', 0.60);

        return [
            'meta' => [
                'document_language' => $this->detectLanguage($analyzeResult),
                'page_count'        => count($analyzeResult['pages'] ?? []),
                'model_id'          => $doc['docType'] ?? 'prebuilt-invoice',
                'api_version'       => $this->version,
            ],

            'fields' => array_filter([
                'invoice_number'  => $this->getString($fields, 'InvoiceId',       $minConf),
                'invoice_date'    => $this->getDate($fields,   'InvoiceDate',      $minConf),
                'due_date'        => $this->getDate($fields,   'DueDate',          $minConf),
                'po_number'       => $this->getString($fields, 'PurchaseOrder',    $minConf),
                'vendor_name'     => $this->getString($fields, 'VendorName',       $minConf),
                'vendor_address'  => $this->getString($fields, 'VendorAddress',    $minConf),
                'vendor_tax_id'   => $this->getString($fields, 'VendorTaxId',      $minConf),
                'customer_name'   => $this->getString($fields, 'CustomerName',     $minConf),
                'customer_address' => $this->getString($fields, 'CustomerAddress',  $minConf),
                'customer_tax_id' => $this->getString($fields, 'CustomerTaxId',    $minConf),
                'subtotal'        => $this->getAmount($fields, 'SubTotal',         $minConf),
                'vat_amount'      => $this->getAmount($fields, 'TotalTax',         $minConf),
                'total_amount'    => $this->getAmount($fields, 'InvoiceTotal',     $minConf),
                'amount_due'      => $this->getAmount($fields, 'AmountDue',        $minConf),
                'currency'        => $this->getString($fields, 'CurrencyCode',     $minConf),
            ], fn($v) => $v !== null && $v !== ''),

            'line_items' => $this->extractLineItems($fields, $minConf),

            'confidences' => $this->extractConfidences($fields),

            // Full raw response (stored only if INVOICE_STORE_RAW=true)
            'raw' => config('invoice.store_raw') ? $analyzeResult : null,
        ];
    }

    // ──────────────────────────────────────────────────────────
    // Line items
    // ──────────────────────────────────────────────────────────

    private function extractLineItems(array $fields, float $minConf): array
    {
        $items     = $fields['Items']['valueArray'] ?? [];
        $lineItems = [];

        foreach ($items as $index => $item) {
            $f = $item['valueObject'] ?? [];

            $lineItem = array_filter([
                'index'        => $index + 1,
                'description'  => $this->getString($f, 'Description',  $minConf),
                'product_code' => $this->getString($f, 'ProductCode',  $minConf),
                'quantity'     => $this->getNumber($f, 'Quantity',      $minConf),
                'unit'         => $this->getString($f, 'Unit',          $minConf),
                'unit_price'   => $this->getAmount($f, 'UnitPrice',     $minConf),
                'tax'          => $this->getAmount($f, 'Tax',           $minConf),
                'amount'       => $this->getAmount($f, 'Amount',        $minConf),
                'date'         => $this->getDate($f,   'Date',          $minConf),
            ], fn($v) => $v !== null && $v !== '');

            if (!empty($lineItem)) {
                $lineItems[] = $lineItem;
            }
        }

        return $lineItems;
    }

    private function extractConfidences(array $fields): array
    {
        $keys = [
            'invoice_number'  => 'InvoiceId',
            'invoice_date'    => 'InvoiceDate',
            'vendor_name'     => 'VendorName',
            'customer_name'   => 'CustomerName',
            'total_amount'    => 'InvoiceTotal',
            'vat_amount'      => 'TotalTax',
            'currency'        => 'CurrencyCode',
        ];

        $confidences = [];
        foreach ($keys as $ourKey => $azureKey) {
            if (isset($fields[$azureKey]['confidence'])) {
                $confidences[$ourKey] = round((float) $fields[$azureKey]['confidence'], 2);
            }
        }
        return $confidences;
    }

    // ──────────────────────────────────────────────────────────
    // Field value extractors
    // ──────────────────────────────────────────────────────────

    private function getString(array $fields, string $key, float $minConf): ?string
    {
        if (!isset($fields[$key])) return null;
        $f = $fields[$key];

        if (($f['confidence'] ?? 1.0) < $minConf) return null;

        // valueString → content → fallback
        $val = $f['valueString']
            ?? $f['content']
            ?? null;

        return $val ? trim((string) $val) : null;
    }

    private function getAmount(array $fields, string $key, float $minConf): ?float
    {
        if (!isset($fields[$key])) return null;
        $f = $fields[$key];

        if (($f['confidence'] ?? 1.0) < $minConf) return null;

        // valueCurrency.amount → valueNumber → parse from content
        $val = $f['valueCurrency']['amount']
            ?? $f['valueNumber']
            ?? null;

        if ($val !== null) return (float) $val;

        // Fallback: parse from content string e.g. "SAR 1,500.00"
        if (isset($f['content'])) {
            $cleaned = preg_replace('/[^\d\.,]/', '', $f['content']);
            $cleaned = str_replace(',', '', $cleaned);
            return is_numeric($cleaned) ? (float) $cleaned : null;
        }

        return null;
    }

    private function getNumber(array $fields, string $key, float $minConf): ?float
    {
        if (!isset($fields[$key])) return null;
        $f = $fields[$key];

        if (($f['confidence'] ?? 1.0) < $minConf) return null;

        $val = $f['valueNumber']
            ?? $f['content']
            ?? null;

        return $val !== null ? (float) $val : null;
    }

    private function getDate(array $fields, string $key, float $minConf): ?string
    {
        if (!isset($fields[$key])) return null;
        $f = $fields[$key];

        if (($f['confidence'] ?? 1.0) < $minConf) return null;

        // valueDate is already ISO format (YYYY-MM-DD) if parsed
        return $f['valueDate']
            ?? $f['content']
            ?? null;
    }

    // ──────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────

    private function detectLanguage(array $analyzeResult): string
    {
        $languages = $analyzeResult['languages'] ?? [];
        if (!empty($languages)) {
            return $languages[0]['locale'] ?? 'unknown';
        }
        // Fallback: check content for Arabic chars
        $allText = collect($analyzeResult['pages'] ?? [])
            ->flatMap(fn($p) => $p['lines'] ?? [])
            ->pluck('content')
            ->implode(' ');

        return preg_match('/[\x{0600}-\x{06FF}]/u', $allText) ? 'ar' : 'en';
    }

    private function mimeType(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'pdf'         => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            'tiff', 'tif' => 'image/tiff',
            default       => 'application/octet-stream',
        };
    }

    private function validateConfig(): void
    {
        if (empty($this->key)) {
            throw new \RuntimeException(
                "AZURE_DI_KEY is not set. Add it to your .env file."
            );
        }
        if (empty($this->endpoint)) {
            throw new \RuntimeException(
                "AZURE_DI_ENDPOINT is not set. Add it to your .env file."
            );
        }
    }

    private function validateFile(string $path): void
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("File not found: {$path}");
        }

        $sizeMb = filesize($path) / 1024 / 1024;
        if ($sizeMb > 50) {
            throw new \RuntimeException(
                "File too large ({$sizeMb}MB). Azure DI limit is 50MB."
            );
        }
    }

    private function throwApiError(string $step, int $status, string $body): never
    {
        $decoded = json_decode($body, true);
        $message = $decoded['error']['message']
            ?? $decoded['error']['innererror']['message']
            ?? $body;

        throw new \RuntimeException(
            "AzureDI {$step} failed [{$status}]: {$message}"
        );
    }
}
