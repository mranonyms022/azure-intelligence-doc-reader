# Invoice DMS — Azure Document Intelligence Scanner

> An automated invoice processing system built on **Azure Document Intelligence**, designed to extract structured data from scanned Arabic and English invoices with native bidirectional text support.

![Laravel](https://img.shields.io/badge/Laravel-11-red?logo=laravel)
![PHP](https://img.shields.io/badge/PHP-8.2-blue?logo=php)
![Azure](https://img.shields.io/badge/Azure-Document%20Intelligence-0078D4?logo=microsoft-azure)
![License](https://img.shields.io/badge/License-MIT-green)

---

## Table of Contents

- [Overview](#overview)
- [Features](#features)
- [System Requirements](#system-requirements)
- [Architecture](#architecture)
- [Installation](#installation)
- [Usage](#usage)
- [Database Schema](#database-schema)
- [Extracted Data Sample](#extracted-data-sample)
- [Confidence Score & Quality Control](#confidence-score--quality-control)
- [REST API](#rest-api)
- [Project Structure](#project-structure)
- [Configuration Reference](#configuration-reference)
- [Scheduling Automated Scans](#scheduling-automated-scans)
- [Troubleshooting](#troubleshooting)
- [Azure Pricing](#azure-pricing)
- [Security Considerations](#security-considerations)
- [Running Tests](#running-tests)
- [Publishing to GitHub](#publishing-to-github)
- [Tech Stack](#tech-stack)
- [License](#license)
- [Contributing](#contributing)

---

## Overview

Invoice DMS eliminates manual data entry by automatically scanning invoice files from a shared network directory, submitting them to Azure Document Intelligence's prebuilt invoice model, and persisting structured JSON output to a relational database. The system natively handles Arabic, English, and mixed-language invoices without any post-processing or custom field mapping.

**Key distinction from similar tools:** Unlike solutions built on AWS Textract — which lacks official Arabic support and returns words in incorrect visual order for RTL scripts — this system leverages Azure Document Intelligence's prebuilt invoice model, which natively understands right-to-left script, bidirectional text rendering, and Arabic invoice field semantics out of the box. No BiDi post-processing layer is required.

---

## Features

- **Native Arabic & English support** — Azure handles bidirectional text, RTL layout, and Arabic field detection without any post-processing layer
- **Mixed-language invoice processing** — Accurately extracts data from invoices containing both Arabic and English content on the same page
- **Automated store folder scanning** — Discovers and processes invoice files across multiple store directories (`S101/`, `S102/`, `S103/`, etc.) with a single command
- **Structured JSON persistence** — Saves extracted invoice fields, line items, and confidence scores to MySQL with full schema validation
- **Confidence-based quality control** — Fields below a configurable confidence threshold are automatically excluded; invoices with low-confidence extractions are flagged for manual review via the `needs_review` column
- **Idempotent processing** — Re-running a scan never duplicates records; already-processed files are automatically detected and skipped
- **Dry-run mode** — Preview extraction output without writing anything to the database
- **Single-file testing** — Validate extraction quality on individual invoices before running a full batch scan
- **REST API** — Query processed invoices programmatically with store, language, and date range filtering
- **Comprehensive Artisan commands** — Full CLI interface for connection testing, single-file validation, and batch processing
- **Automated scheduling support** — Native Laravel scheduler integration for unattended nightly scans

---

## System Requirements

| Dependency | Minimum Version |
| ---------- | --------------- |
| PHP        | 8.2             |
| Laravel    | 11.x            |
| MySQL      | 5.7+            |
| Composer   | 2.x             |

**External services required:**

- Microsoft Azure subscription (free tier available — no credit card required for F0)
- Azure Document Intelligence resource (F0 free tier: 500 pages/month at no cost)

---

## Architecture

```
Shared Network Drive (D:/shared/)
├── S101/
│   ├── invoice_001.pdf
│   └── invoice_002.jpg
├── S102/
│   └── scan_march.pdf
└── S103/
    └── ...
         │
         ▼
┌──────────────────────────────┐
│     InvoiceScannerService    │  ← Orchestrates folder traversal and pipeline
│                              │
│  • Discovers store folders   │
│  • Detects duplicate files   │
│  • Calls Azure per file      │
│  • Persists results to DB    │
└──────────────┬───────────────┘
               │
               ▼
┌──────────────────────────────────────────┐
│   AzureDocumentIntelligenceService       │
│                                          │
│  Step 1: Submit document  (POST → 202)   │
│  Step 2: Poll Operation-Location URL     │
│  Step 3: Extract & normalize fields      │
│  Step 4: Apply confidence threshold      │
└──────────────┬───────────────────────────┘
               │  Clean structured JSON
               ▼
┌──────────────────────────────┐
│        MySQL Database        │
│   stores table               │
│   invoices table             │
└──────────────────────────────┘
```

---

## Installation

### Step 1 — Provision Azure Resource

1. Sign in to [portal.azure.com](https://portal.azure.com)
2. Navigate to **Create a resource** → search for `Document Intelligence`
3. Configure the resource:
    - **Resource Group:** `invoice-dms-rg` (or any existing group)
    - **Region:** `East US` or `UAE North` for Middle East deployments
    - **Pricing Tier:** `F0` (free — 500 pages/month) for development; `S0` for production
4. After deployment completes, click **Go to Resource**
5. In the left panel, select **Keys and Endpoint**
6. Copy **KEY 1** and the **Endpoint URL** — you will need both in Step 3

### Step 2 — Clone and Install Dependencies

```bash
git clone https://github.com/your-username/azure-invoice-dms.git
cd azure-invoice-dms

composer install

cp .env.example .env
php artisan key:generate
```

### Step 3 — Configure Environment Variables

Open `.env` and populate the following values:

```env
# ── Database ──────────────────────────────────
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=invoice_dms
DB_USERNAME=root
DB_PASSWORD=your_database_password

# ── Azure Document Intelligence ───────────────
# Copy from: Azure Portal → Your Resource → Keys and Endpoint
AZURE_DI_KEY=your-azure-key-1-here
AZURE_DI_ENDPOINT=https://your-resource-name.cognitiveservices.azure.com/

# ── Invoice Scanner ───────────────────────────
# Root directory containing S101/, S102/, S103/ subfolders
INVOICE_BASE_PATH="D:/shared"

# Confidence threshold (0.0–1.0)
# Fields extracted below this score are excluded from output
# Recommended: 0.60 for production, 0.40 for debugging
INVOICE_MIN_CONFIDENCE=0.60

# Set to true to persist the full raw Azure JSON in the database
# Use only for debugging — increases storage significantly
INVOICE_STORE_RAW=false
```

### Step 4 — Provision Database

```bash
# Create the database (run once)
mysql -u root -p -e "CREATE DATABASE invoice_dms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Execute all migrations
php artisan migrate
```

### Step 5 — Verify Azure Connectivity

```bash
php artisan invoices:scan --test
```

Expected output:

```
  Endpoint : https://your-resource.cognitiveservices.azure.com/
  Key      : abcd1234...xyz9

  ✓ Azure DI connection successful
  Ready! Run: php artisan invoices:scan --dry-run
```

If the connection fails, refer to the [Troubleshooting](#troubleshooting) section.

---

## Usage

### Command Reference

| Command                                                | Description                                                   |
| ------------------------------------------------------ | ------------------------------------------------------------- |
| `php artisan invoices:scan --test`                     | Verify Azure credentials and API connectivity                 |
| `php artisan invoices:scan --dry-run`                  | Process all stores without persisting to the database         |
| `php artisan invoices:scan --store=S101 --dry-run`     | Dry-run for a specific store only                             |
| `php artisan invoices:scan --store=S101`               | Process a specific store and save results to DB               |
| `php artisan invoices:scan`                            | Process all stores and save results to DB                     |
| `php artisan invoices:test-file "D:/path/invoice.pdf"` | Analyze a single invoice file and display full extracted JSON |

### Recommended First-Run Workflow

Follow this sequence when setting up the system for the first time:

```bash
# Step 1: Confirm Azure connection is working
php artisan invoices:scan --test

# Step 2: Test extraction on one invoice to inspect output quality
php artisan invoices:test-file "D:/shared/S101/invoice_001.pdf"

# Step 3: Dry-run across one store to review batch behavior
php artisan invoices:scan --store=S101 --dry-run

# Step 4: Execute a live scan for one store
php artisan invoices:scan --store=S101

# Step 5: Scale to all stores once satisfied with output
php artisan invoices:scan
```

### Sample Output — `invoices:test-file`

```
Testing file: D:/shared/S101/invoice_001.pdf
  File size : 284.5 KB
  Sending to Azure Document Intelligence...

✓ Analysis complete in 4.2s

── Document Info ──
  Language  : ar
  Pages     : 1

── Extracted Fields ──
  invoice_number : INV-2024-001     (conf: 0.98)
  invoice_date   : 2024-03-15       (conf: 0.97)
  vendor_name    : شركة النور للتجارة  (conf: 0.95)
  customer_name  : متجر S101        (conf: 0.92)
  subtotal       : 1,271.19         (conf: 0.99)
  vat_amount     : 228.81           (conf: 0.99)
  total_amount   : 1,500.00         (conf: 0.99)
  currency       : SAR              (conf: 0.99)

── Line Items ──
+-------+----------------------+----------+------------+---------+
| index | description          | quantity | unit_price | amount  |
+-------+----------------------+----------+------------+---------+
| 1     | منتج أ - وصف المنتج  | 2.00     | 250.00     | 500.00  |
| 2     | منتج ب               | 1.00     | 771.19     | 771.19  |
+-------+----------------------+----------+------------+---------+

 Show full extracted JSON? (yes/no) [no]:
```

### Sample Output — `invoices:scan`

```
╔══════════════════════════════════════════╗
║       Invoice DMS — Azure Scanner        ║
╚══════════════════════════════════════════╝

  Base path : D:/shared
  Store     : ALL
  Mode      : 💾 LIVE
  Min conf  : 60%

─── Store: S101
    Found 3 file(s)
    → invoice_001.pdf
      [OK] lang:ar | fields:9 | items:2 | total:1500.00 SAR
    → invoice_002.pdf
      [OK] lang:en | fields:8 | items:3 | total:850.00 USD
    → invoice_003.pdf
      [SKIP] Already processed

─── Store: S102
    Found 1 file(s)
    → scan_march.pdf
      [FAIL] File too large (54MB). Azure DI limit is 50MB.

┌───────┬──────────────────┬───────────┬──────┬───────────┬───────────────────────────┐
│ Store │ File             │ Status    │ Lang │ Total     │ Note                      │
├───────┼──────────────────┼───────────┼──────┼───────────┼───────────────────────────┤
│ S101  │ invoice_001.pdf  │ ✓ OK      │ ar   │ 1500.00   │ fields:9 items:2 saved    │
│ S101  │ invoice_002.pdf  │ ✓ OK      │ en   │ 850.00    │ fields:8 items:3 saved    │
│ S101  │ invoice_003.pdf  │ ~ skip    │ -    │ -         │ Already in DB             │
│ S102  │ scan_march.pdf   │ ✗ FAIL    │ -    │ -         │ File too large (54MB)...  │
└───────┴──────────────────┴───────────┴──────┴───────────┴───────────────────────────┘

  ✓ Success : 2
  ~ Skipped : 1
  ✗ Failed  : 1
```

---

## Database Schema

### `stores`

| Column        | Type               | Description                            |
| ------------- | ------------------ | -------------------------------------- |
| `id`          | bigint PK          | Auto-incrementing primary key          |
| `code`        | varchar(20) UNIQUE | Store identifier — e.g. `S101`, `S202` |
| `name`        | varchar(100)       | Human-readable store name              |
| `folder_path` | varchar(500)       | Absolute path to the store directory   |
| `created_at`  | timestamp          | Record creation timestamp              |
| `updated_at`  | timestamp          | Last modification timestamp            |

### `invoices`

| Column                 | Type          | Description                                                            |
| ---------------------- | ------------- | ---------------------------------------------------------------------- |
| `id`                   | bigint PK     | Auto-incrementing primary key                                          |
| `store_id`             | FK → stores   | Parent store reference (cascades on delete)                            |
| `store_code`           | varchar(20)   | Denormalized store code for query performance                          |
| `file_name`            | varchar(191)  | Original filename of the scanned invoice                               |
| `file_path`            | varchar(500)  | Absolute path to the source file on disk                               |
| `document_language`    | varchar(20)   | Detected language code — `ar`, `en`                                    |
| `page_count`           | smallint      | Number of pages in the document                                        |
| `invoice_number`       | varchar(100)  | Extracted invoice identifier                                           |
| `invoice_date`         | date          | Invoice issuance date                                                  |
| `due_date`             | date          | Payment due date                                                       |
| `po_number`            | varchar(100)  | Purchase order number                                                  |
| `vendor_name`          | varchar(191)  | Supplier name — Arabic or English                                      |
| `vendor_address`       | text          | Vendor postal address                                                  |
| `vendor_tax_id`        | varchar(100)  | Vendor VAT or tax registration number                                  |
| `customer_name`        | varchar(191)  | Customer or buyer name                                                 |
| `customer_address`     | text          | Customer postal address                                                |
| `subtotal`             | decimal(15,2) | Pre-tax subtotal amount                                                |
| `vat_amount`           | decimal(15,2) | VAT or tax amount                                                      |
| `total_amount`         | decimal(15,2) | Total invoice amount                                                   |
| `amount_due`           | decimal(15,2) | Outstanding amount due                                                 |
| `currency`             | varchar(10)   | Currency code — e.g. `SAR`, `AED`, `USD`                               |
| `line_items`           | json          | Array of extracted line item objects                                   |
| `raw_fields`           | json          | All extracted fields as key-value pairs                                |
| `confidences`          | json          | Azure confidence score (0.0–1.0) per field                             |
| `needs_review`         | boolean       | `true` when any field confidence falls below 0.70                      |
| `min_confidence_score` | decimal(5,2)  | Lowest confidence score across all extracted fields                    |
| `raw_azure_json`       | json          | Full Azure API response — populated only when `INVOICE_STORE_RAW=true` |
| `processed_at`         | timestamp     | Timestamp when the invoice was processed                               |
| `created_at`           | timestamp     | Record creation timestamp                                              |
| `updated_at`           | timestamp     | Last modification timestamp                                            |

---

## Extracted Data Sample

The following is an example of the structured JSON produced for an Arabic invoice and saved to the database:

```json
{
    "meta": {
        "document_language": "ar",
        "page_count": 1,
        "model_id": "prebuilt-invoice"
    },
    "fields": {
        "invoice_number": "INV-2024-001",
        "invoice_date": "2024-03-15",
        "due_date": "2024-04-15",
        "po_number": "PO-9981",
        "vendor_name": "شركة النور للتجارة",
        "vendor_address": "الرياض، المملكة العربية السعودية",
        "vendor_tax_id": "300012345600003",
        "customer_name": "متجر S101",
        "customer_address": "جدة، المملكة العربية السعودية",
        "subtotal": 1271.19,
        "vat_amount": 228.81,
        "total_amount": 1500.0,
        "amount_due": 1500.0,
        "currency": "SAR"
    },
    "line_items": [
        {
            "index": 1,
            "description": "منتج أ - وصف المنتج",
            "quantity": 2,
            "unit_price": 250.0,
            "amount": 500.0
        },
        {
            "index": 2,
            "description": "منتج ب - وصف آخر",
            "quantity": 1,
            "unit_price": 771.19,
            "amount": 771.19
        }
    ],
    "confidences": {
        "invoice_number": 0.98,
        "invoice_date": 0.97,
        "vendor_name": 0.95,
        "customer_name": 0.92,
        "total_amount": 0.99,
        "vat_amount": 0.99,
        "currency": 0.99
    }
}
```

---

## Confidence Score & Quality Control

### What Confidence Scores Mean

Azure Document Intelligence returns a confidence score between 0.0 and 1.0 for every extracted field. This score represents the model's certainty in its extraction — not a measure of the data's correctness per se, but a reliable indicator of extraction quality.

| Score Range   | Interpretation                                                         |
| ------------- | ---------------------------------------------------------------------- |
| `0.90 – 1.00` | Very high confidence — essentially certain                             |
| `0.70 – 0.89` | High confidence — production-grade                                     |
| `0.60 – 0.69` | Acceptable — above threshold but may warrant spot-checking             |
| `0.40 – 0.59` | Low confidence — excluded by default threshold                         |
| `< 0.40`      | Very low — typically indicates poor scan quality or unsupported format |

### Automated Flagging

Invoices are automatically flagged for manual review (`needs_review = true`) when any extracted field has a confidence score below **0.70**, regardless of whether that field passed the exclusion threshold. The `min_confidence_score` column records the lowest score across all fields for easy sorting.

### Querying the Review Queue

```php
// Retrieve all invoices requiring manual review
Invoice::where('needs_review', true)
       ->orderBy('min_confidence_score')
       ->get();

// Review queue for a specific store, sorted by worst confidence first
Invoice::where('needs_review', true)
       ->where('store_code', 'S101')
       ->orderBy('min_confidence_score', 'asc')
       ->get();

// Invoices processed today that need review
Invoice::where('needs_review', true)
       ->whereDate('processed_at', today())
       ->get();
```

### Tuning the Confidence Threshold

Adjust `INVOICE_MIN_CONFIDENCE` in `.env` based on your accuracy requirements:

| Setting | Use Case                                                      |
| ------- | ------------------------------------------------------------- |
| `0.90`  | Maximum precision — only very high-confidence fields retained |
| `0.60`  | Default — balanced accuracy for well-scanned invoices         |
| `0.40`  | Permissive — useful for diagnosing extraction issues          |
| `0.00`  | Include all fields regardless of confidence — debugging only  |

---

## REST API

The optional REST API exposes processed invoice data for integration with external systems.

```bash
# Retrieve all invoices (paginated, 20 per page by default)
GET /api/invoices

# Filter by store code
GET /api/invoices?store=S101

# Filter by document language
GET /api/invoices?lang=ar

# Filter by invoice date range
GET /api/invoices?from=2024-01-01&to=2024-03-31

# Combine filters
GET /api/invoices?store=S101&lang=ar&from=2024-01-01

# Retrieve a specific invoice by ID
GET /api/invoices/{id}

# List all stores with invoice counts
GET /api/stores

# Retrieve all invoices for a specific store
GET /api/stores/S101/invoices

# Aggregate statistics (totals by store, language, currency)
GET /api/stats
```

---

## Project Structure

```
app/
├── Console/
│   ├── Commands/
│   │   ├── ScanInvoices.php                         ← php artisan invoices:scan
│   │   └── TestInvoiceFile.php                      ← php artisan invoices:test-file
│   └── Kernel.php                                   ← Scheduler registration
├── Http/
│   └── Controllers/
│       └── Api/
│           └── InvoiceController.php                ← REST API endpoints
├── Models/
│   ├── Invoice.php                                  ← Invoice Eloquent model + scopes
│   └── Store.php                                    ← Store Eloquent model
├── Providers/
│   └── AppServiceProvider.php                       ← Service container bindings
└── Services/
    ├── Azure/
    │   └── AzureDocumentIntelligenceService.php     ← Azure API client (submit/poll/extract)
    └── InvoiceScannerService.php                    ← Scan pipeline orchestrator

config/
└── invoice.php                    ← All invoice scanner configuration

database/
└── migrations/
    ├── ..._create_stores_table.php
    ├── ..._create_invoices_table.php
    └── ..._add_needs_review_to_invoices.php

routes/
└── api.php                        ← REST API route definitions

tests/
└── Unit/
    └── AzureServiceTest.php       ← Unit tests for field extraction logic
```

---

## Configuration Reference

All configuration is centralized in `config/invoice.php` and sourced from environment variables.

| ENV Variable             | Default     | Description                                                                                                 |
| ------------------------ | ----------- | ----------------------------------------------------------------------------------------------------------- |
| `AZURE_DI_KEY`           | —           | Azure Document Intelligence API key. Obtain from Azure Portal → Keys and Endpoint.                          |
| `AZURE_DI_ENDPOINT`      | —           | Azure resource endpoint URL. Must include trailing slash.                                                   |
| `INVOICE_BASE_PATH`      | `D:/shared` | Root directory containing store subdirectories (`S101/`, `S102/`, etc.).                                    |
| `INVOICE_MIN_CONFIDENCE` | `0.60`      | Minimum confidence score for field acceptance. Fields below this value are excluded from the output.        |
| `INVOICE_STORE_RAW`      | `false`     | When `true`, persists the complete Azure JSON response to `raw_azure_json`. Recommended for debugging only. |

**Azure polling configuration** (set in `config/invoice.php` directly):

| Config Key                 | Default      | Description                                       |
| -------------------------- | ------------ | ------------------------------------------------- |
| `azure.version`            | `2024-11-30` | Azure Document Intelligence API version           |
| `azure.poll_max_attempts`  | `30`         | Maximum number of polling attempts before timeout |
| `azure.poll_sleep_seconds` | `2`          | Delay in seconds between each polling attempt     |

---

## Scheduling Automated Scans

To run the scanner automatically on a recurring schedule, uncomment the scheduling entry in `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule): void
{
    // Run a full scan every day at midnight
    $schedule->command('invoices:scan')->dailyAt('00:00');

    // Or run every hour
    $schedule->command('invoices:scan')->hourly();

    // Or scan a specific store every weekday morning
    $schedule->command('invoices:scan --store=S101')->weekdays()->at('08:00');
}
```

Then add the Laravel scheduler to your system's cron table:

```bash
# Open crontab
crontab -e

# Add this single entry — Laravel handles all scheduling internally
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

On Windows, use **Task Scheduler** to run `php artisan schedule:run` every minute.

---

## Troubleshooting

| Symptom                                                           | Resolution                                                                                                                                        |
| ----------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------- |
| `AZURE_DI_KEY is not set`                                         | Populate `AZURE_DI_KEY` in your `.env` file                                                                                                       |
| `AZURE_DI_ENDPOINT is not set`                                    | Populate `AZURE_DI_ENDPOINT` — ensure it ends with `/`                                                                                            |
| `401 Unauthorized`                                                | Re-copy KEY 1 from Azure Portal → Your Resource → Keys and Endpoint                                                                               |
| `404 Not Found`                                                   | Verify the endpoint URL — it should match the format `https://resource-name.cognitiveservices.azure.com/`                                         |
| `SSL certificate problem: unable to get local issuer certificate` | Expected on local Windows environments (XAMPP/Wamp). The service uses `withoutVerifying()` in development. Do not use this in production.         |
| `SQLSTATE: Specified key was too long`                            | Run `php artisan migrate:fresh` using the corrected migration files provided in this repository                                                   |
| `No store folders found`                                          | Confirm `INVOICE_BASE_PATH` points to the correct directory and that `S###` subfolders exist within it                                            |
| Fewer fields than expected                                        | Lower `INVOICE_MIN_CONFIDENCE` to `0.40` in `.env` and re-process                                                                                 |
| `needs_review` flagged on many invoices                           | Scan quality may be low — rescan at 150 DPI or higher. Review individual invoices using `invoices:test-file`                                      |
| `File too large` error                                            | Azure Document Intelligence enforces a 50MB per-file limit. Compress or split large PDFs before scanning.                                         |
| `AzureDI: Timed out after 60s`                                    | Azure processing is slow for large or complex documents. Increase `poll_max_attempts` in `config/invoice.php`.                                    |
| Arabic text appears reversed in terminal                          | This is a terminal rendering issue, not a data issue. The data stored in the database is correct. Verify using a database client or the REST API. |

---

## Azure Pricing

| Tier          | Monthly Volume              | Cost                 |
| ------------- | --------------------------- | -------------------- |
| Free (F0)     | Up to 500 pages             | No charge            |
| Standard (S0) | 0 – 1,000,000 pages         | ~$10 per 1,000 pages |
| Standard (S0) | 1,000,001 – 5,000,000 pages | ~$5 per 1,000 pages  |

The free tier (F0) is sufficient for development, testing, and low-volume deployments. Upgrade to Standard (S0) in the Azure Portal for production workloads. Pricing applies per page regardless of document language.

> **Note:** Azure Document Intelligence pricing may change. Verify current rates at [azure.microsoft.com/pricing/details/ai-document-intelligence](https://azure.microsoft.com/en-us/pricing/details/ai-document-intelligence/).

---

## Security Considerations

- **Never commit `.env` to version control.** The `.env` file is excluded by `.gitignore` by default — verify this before your first push.
- **Commit only `.env.example`** with placeholder values and no real credentials.
- **Production deployments:** Store `AZURE_DI_KEY` in a dedicated secrets manager such as Azure Key Vault or HashiCorp Vault rather than directly in `.env`.
- **SSL verification:** The `withoutVerifying()` flag on HTTP requests is intentional for local Windows development environments where root CA certificates are commonly missing. Remove this flag and configure proper SSL certificates before deploying to production.
- **Database credentials:** Use a dedicated MySQL user with permissions restricted to the `invoice_dms` database — avoid using the root account in production.
- **API authentication:** The REST API endpoints are currently unauthenticated. Implement Laravel Sanctum or API key middleware before exposing the API externally.

---

## Running Tests

```bash
# Run the full unit test suite
php artisan test tests/Unit/AzureServiceTest.php

# Run with verbose output
php artisan test tests/Unit/AzureServiceTest.php --verbose
```

The unit test suite covers:

- Correct field extraction from a mock Azure response
- Arabic text is returned without modification (Azure handles BiDi natively)
- Confidence threshold filtering — fields below the minimum score are excluded
- Correct line item extraction including Arabic descriptions
- Confidence score mapping per field

All tests execute against a mock Azure response and make no live API calls, making them safe to run in any environment without Azure credentials.

---

## Publishing to GitHub

```bash
# Initialize repository
git init
git add .

# Verify .env is not staged (critical)
git status

# Create initial commit
git commit -m "Initial commit — Invoice DMS Azure scanner"

# Connect to your GitHub repository
git remote add origin https://github.com/your-username/azure-invoice-dms.git

# Push to main branch
git push -u origin main
```

> **Before pushing:** confirm that `.env` appears in `.gitignore` and does not appear in `git status`. Only `.env.example` should be committed.

---

## Tech Stack

| Component        | Technology                                             |
| ---------------- | ------------------------------------------------------ |
| Framework        | Laravel 11                                             |
| Language         | PHP 8.2                                                |
| AI / OCR Service | Azure Document Intelligence — `prebuilt-invoice` model |
| Database         | MySQL 5.7+                                             |
| HTTP Client      | Laravel HTTP Client (Guzzle)                           |
| Authentication   | Azure Subscription Key (`Ocp-Apim-Subscription-Key`)   |

---

## License

This project is licensed under the [MIT License](LICENSE). You are free to use, modify, and distribute this software for both commercial and non-commercial purposes.

---

## Contributing

Contributions are welcome. To propose a change:

1. Open an issue describing the problem or feature request
2. Fork the repository and create a feature branch
3. Implement your changes with appropriate unit test coverage
4. Submit a pull request referencing the original issue

Please ensure all existing tests pass before submitting a pull request.
