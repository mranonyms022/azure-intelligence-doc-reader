<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\Azure\AzureDocumentIntelligenceService;

class AzureServiceTest extends TestCase
{
    /**
     * Test that the service correctly maps a mock Azure response
     * to our clean invoice structure.
     *
     * This test does NOT call the real Azure API.
     * It uses a mock response that matches Azure's actual JSON format.
     */
    public function test_extracts_fields_from_mock_azure_response(): void
    {
        // Use reflection to call private method directly
        $service = new class extends AzureDocumentIntelligenceService {
            public function __construct() {} // Skip config loading
            public function publicExtract(array $result): array {
                return $this->extractInvoiceFields($result);
            }
        };

        $mockAzureResponse = [
            'documents' => [[
                'docType' => 'invoice',
                'fields'  => [
                    'InvoiceId'    => ['valueString' => 'INV-2024-001', 'confidence' => 0.98],
                    'InvoiceDate'  => ['valueDate'   => '2024-03-15',   'confidence' => 0.97],
                    'VendorName'   => ['valueString' => 'شركة النور للتجارة', 'confidence' => 0.95],
                    'CustomerName' => ['valueString' => 'متجر S101',    'confidence' => 0.92],
                    'InvoiceTotal' => [
                        'valueCurrency' => ['amount' => 1500.00, 'currencyCode' => 'SAR'],
                        'confidence' => 0.99,
                    ],
                    'TotalTax'     => [
                        'valueCurrency' => ['amount' => 228.81],
                        'confidence' => 0.99,
                    ],
                    'SubTotal'     => [
                        'valueCurrency' => ['amount' => 1271.19],
                        'confidence' => 0.99,
                    ],
                    'CurrencyCode' => ['valueString' => 'SAR', 'confidence' => 0.99],
                    'Items'        => [
                        'valueArray' => [[
                            'valueObject' => [
                                'Description' => ['valueString' => 'منتج أ', 'confidence' => 0.94],
                                'Quantity'    => ['valueNumber' => 2,         'confidence' => 0.99],
                                'UnitPrice'   => ['valueCurrency' => ['amount' => 250.00], 'confidence' => 0.98],
                                'Amount'      => ['valueCurrency' => ['amount' => 500.00], 'confidence' => 0.99],
                            ],
                        ]],
                    ],
                ],
            ]],
            'pages'     => [['pageNumber' => 1]],
            'languages' => [['locale' => 'ar']],
        ];

        $result = $service->publicExtract($mockAzureResponse);

        // Fields extracted correctly
        $this->assertEquals('INV-2024-001',          $result['fields']['invoice_number']);
        $this->assertEquals('2024-03-15',             $result['fields']['invoice_date']);
        $this->assertEquals('شركة النور للتجارة',     $result['fields']['vendor_name']);
        $this->assertEquals('متجر S101',              $result['fields']['customer_name']);
        $this->assertEquals(1500.00,                  $result['fields']['total_amount']);
        $this->assertEquals(228.81,                   $result['fields']['vat_amount']);
        $this->assertEquals('SAR',                    $result['fields']['currency']);

        // Arabic text is correct — no BiDi fix needed
        $this->assertStringContainsString('النور',    $result['fields']['vendor_name']);

        // Line items extracted
        $this->assertCount(1, $result['line_items']);
        $this->assertEquals('منتج أ', $result['line_items'][0]['description']);
        $this->assertEquals(2,         $result['line_items'][0]['quantity']);
        $this->assertEquals(500.00,    $result['line_items'][0]['amount']);

        // Confidence scores present
        $this->assertArrayHasKey('vendor_name',   $result['confidences']);
        $this->assertArrayHasKey('total_amount',  $result['confidences']);

        // Meta
        $this->assertEquals('ar', $result['meta']['document_language']);
        $this->assertEquals(1,    $result['meta']['page_count']);
    }

    public function test_respects_minimum_confidence_threshold(): void
    {
        // Fields below 0.60 confidence should be excluded
        $service = new class extends AzureDocumentIntelligenceService {
            public function __construct() {}
            public function publicExtract(array $result): array {
                return $this->extractInvoiceFields($result);
            }
        };

        $mockResponse = [
            'documents' => [[
                'fields' => [
                    'InvoiceId'    => ['valueString' => 'INV-001', 'confidence' => 0.95],
                    'VendorName'   => ['valueString' => 'Low confidence vendor', 'confidence' => 0.30], // below threshold
                    'InvoiceTotal' => ['valueCurrency' => ['amount' => 100.0], 'confidence' => 0.98],
                ],
            ]],
            'pages'     => [[]],
            'languages' => [],
        ];

        $result = $service->publicExtract($mockResponse);

        $this->assertEquals('INV-001', $result['fields']['invoice_number']);
        $this->assertArrayNotHasKey('vendor_name', $result['fields']); // excluded
        $this->assertEquals(100.0, $result['fields']['total_amount']);
    }
}
