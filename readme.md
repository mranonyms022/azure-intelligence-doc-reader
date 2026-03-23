# 🧾 Invoice DMS — Azure Document Intelligence Scanner

> Arabic + English invoice scanner with **native BiDi support**.
> Scanned invoices → Azure AI → Structured JSON → MySQL — fully automated.

---

## ✨ Features

- ✅ **Arabic + English** invoices — both natively supported
- ✅ **No BiDi post-processing** — Azure handles RTL direction automatically
- ✅ **Mixed language invoices** — Arabic + English on same page
- ✅ **Auto folder scan** — reads `D:/shared/S101/`, `S102/`, `S103/` etc.
- ✅ **Structured JSON** saved to MySQL — invoice number, date, vendor, line items
- ✅ **Confidence scores** per field from Azure
- ✅ **Dry run mode** — test without saving to DB
- ✅ **REST API** — query invoices via HTTP
- ✅ Laravel 11 + PHP 8.2

---

## 🏗️ How It Works

```
D:/shared/
├── S101/
│   ├── invoice_001.pdf   ← Arabic invoice
│   └── invoice_002.jpg   ← English invoice
├── S102/
│   └── scan_march.pdf    ← Mixed Arabic+English
└── S103/
    └── ...
         │
         ▼
  [InvoiceScannerService]
  Walks store folders
         │
         ▼
  [Azure Document Intelligence]
  prebuilt-invoice model
  Native Arabic OCR + BiDi
         │
         ▼
  [Structured JSON]
  invoice_number, vendor_name,
  total_amount, line_items ...
         │
         ▼
  [MySQL Database]
  stores + invoices tables
```

---

## ⚡ Quick Start

### 1. Clone & Install

```bash
git clone https://github.com/your-username/azure-invoice-dms.git
cd azure-invoice-dms
composer install
cp .env.example .env
php artisan key:generate
```

### 2. Azure Resource Banao

1. [portal.azure.com](https://portal.azure.com) → **Create a resource** → `Document Intelligence`
2. Pricing tier: `F0` (free — 500 pages/month)
3. Deploy → **Go to Resource** → **Keys and Endpoint**
4. `KEY 1` aur `ENDPOINT` copy karo

### 3. `.env` Configure Karo

```env
# Database
DB_DATABASE=invoice_dms
DB_USERNAME=root
DB_PASSWORD=your_password

# Azure — portal se copy karo
AZURE_DI_KEY=your-key-here
AZURE_DI_ENDPOINT=https://your-resource.cognitiveservices.azure.com/

# Shared folder path
INVOICE_BASE_PATH="D:/shared"
```

### 4. Database Setup

```bash
mysql -u root -p -e "CREATE DATABASE invoice_dms;"
php artisan migrate
```

### 5. Test Karo

```bash
# Azure connection check
php artisan invoices:scan --test

# Single file test (JSON dekho)
php artisan invoices:test-file "D:/shared/S101/invoice.pdf"

# Dry run — kuch save nahi hoga
php artisan invoices:scan --dry-run

# Live scan — DB mein save hoga
php artisan invoices:scan
```

---

## 📟 Commands

| Command                                  | Description                         |
| ---------------------------------------- | ----------------------------------- |
| `php artisan invoices:scan --test`       | Azure connection verify karo        |
| `php artisan invoices:scan --dry-run`    | Sab scan karo, DB save mat karo     |
| `php artisan invoices:scan --store=S101` | Sirf ek store scan karo             |
| `php artisan invoices:scan`              | Sab stores scan + DB save           |
| `php artisan invoices:test-file "path"`  | Single file test, JSON output dekho |

---

## 📤 Sample Output

```
╔══════════════════════════════════════════╗
║       Invoice DMS — Azure Scanner        ║
╚══════════════════════════════════════════╝

  Base path : D:/shared
  Store     : ALL
  Mode      : 💾 LIVE

─── Store: S101
    Found 2 file(s)
    → invoice_001.pdf
      [OK] Lang: ar | Fields: 8 | Items: 5 | Total: 1500.00 SAR
    → invoice_002.pdf
      [OK] Lang: en | Fields: 7 | Items: 3 | Total: 850.00 USD

  ✓ Success : 2
```

---

## 🗃️ Database Schema

### `stores`

| Column      | Type         | Description             |
| ----------- | ------------ | ----------------------- |
| id          | bigint       | Primary key             |
| code        | varchar(20)  | Store code — S101, S102 |
| name        | varchar(100) | Store name              |
| folder_path | varchar(500) | Full folder path        |

### `invoices`

| Column            | Type          | Description             |
| ----------------- | ------------- | ----------------------- |
| id                | bigint        | Primary key             |
| store_id          | FK            | → stores table          |
| store_code        | varchar(20)   | S101, S102 ...          |
| file_name         | varchar(191)  | Original filename       |
| document_language | varchar(20)   | `ar`, `en`, `mixed`     |
| invoice_number    | varchar(100)  | Extracted invoice ID    |
| invoice_date      | date          | Invoice date            |
| vendor_name       | varchar(191)  | Arabic or English       |
| customer_name     | varchar(191)  | Arabic or English       |
| total_amount      | decimal(15,2) | Total invoice amount    |
| vat_amount        | decimal(15,2) | VAT / tax amount        |
| subtotal          | decimal(15,2) | Subtotal before tax     |
| currency          | varchar(10)   | SAR, AED, USD ...       |
| line_items        | json          | Array of line items     |
| raw_fields        | json          | All extracted fields    |
| confidences       | json          | Azure confidence scores |
| processed_at      | timestamp     | When processed          |

---

## 📦 JSON Output Sample

```json
{
    "store_code": "S101",
    "file_name": "invoice_001.pdf",
    "document_language": "ar",
    "invoice_number": "INV-2024-001",
    "invoice_date": "2024-03-15",
    "vendor_name": "شركة النور للتجارة",
    "customer_name": "متجر S101",
    "subtotal": 1271.19,
    "vat_amount": 228.81,
    "total_amount": 1500.0,
    "currency": "SAR",
    "line_items": [
        {
            "description": "منتج أ - وصف المنتج",
            "quantity": 2,
            "unit_price": 250.0,
            "amount": 500.0
        }
    ],
    "confidences": {
        "vendor_name": 0.95,
        "invoice_number": 0.98,
        "total_amount": 0.99
    }
}
```

---

## 🌐 REST API

```bash
# Sab invoices
GET /api/invoices

# Store filter
GET /api/invoices?store=S101

# Language filter
GET /api/invoices?lang=ar

# Date range
GET /api/invoices?from=2024-01-01&to=2024-03-31

# Single invoice
GET /api/invoices/{id}

# Sab stores
GET /api/stores

# Store ki invoices
GET /api/stores/S101/invoices

# Stats
GET /api/stats
```

---

## 📁 Project Structure

```
app/
├── Console/Commands/
│   ├── ScanInvoices.php              ← php artisan invoices:scan
│   └── TestInvoiceFile.php           ← php artisan invoices:test-file
├── Http/Controllers/Api/
│   └── InvoiceController.php         ← REST API endpoints
├── Models/
│   ├── Invoice.php
│   └── Store.php
├── Services/
│   ├── Azure/
│   │   └── AzureDocumentIntelligenceService.php   ← Core Azure service
│   └── InvoiceScannerService.php                  ← Scanner orchestrator
config/
└── invoice.php                       ← All settings
database/migrations/
├── ..._create_stores_table.php
└── ..._create_invoices_table.php
```

---

## ⚙️ Configuration (`config/invoice.php`)

| Key              | Default             | Description                |
| ---------------- | ------------------- | -------------------------- |
| `azure.key`      | `AZURE_DI_KEY`      | Azure API key              |
| `azure.endpoint` | `AZURE_DI_ENDPOINT` | Azure resource endpoint    |
| `azure.version`  | `2024-11-30`        | API version                |
| `base_path`      | `INVOICE_BASE_PATH` | Shared folder path         |
| `min_confidence` | `0.60`              | Minimum field confidence   |
| `store_raw`      | `false`             | Save full Azure JSON in DB |
| `extensions`     | pdf,jpg,png,tiff    | Supported file types       |

---

## 🔧 Troubleshooting

| Error                        | Fix                                                          |
| ---------------------------- | ------------------------------------------------------------ |
| `AZURE_DI_KEY is not set`    | `.env` mein `AZURE_DI_KEY` add karo                          |
| `401 Unauthorized`           | Azure portal se key dobara copy karo                         |
| `404 Not Found`              | `AZURE_DI_ENDPOINT` URL check karo                           |
| `SSL certificate error`      | `.env` mein local dev ke liye — yeh normal hai Windows pe    |
| `Specified key was too long` | `php artisan migrate:fresh` dobara run karo                  |
| `No store folders found`     | `INVOICE_BASE_PATH` check karo, `S101` folder exist karta ho |
| Fields kam extract ho rahe   | `INVOICE_MIN_CONFIDENCE=0.40` try karo `.env` mein           |

---

## 💰 Azure Pricing

| Tier          | Pages/month | Cost                 |
| ------------- | ----------- | -------------------- |
| Free (F0)     | 500         | Free                 |
| Standard (S0) | 0 – 1M      | ~$10 per 1,000 pages |

> Testing ke liye F0 (free tier) kaafi hai.
> Production ke liye Azure portal mein S0 pe upgrade karo.

---

## 🛠️ Tech Stack

- **PHP** 8.2
- **Laravel** 11
- **Azure Document Intelligence** — prebuilt-invoice model
- **MySQL** 5.7+
- **Guzzle** HTTP client

---

## 📄 License

MIT License — free to use and modify.

---

## 🤝 Contributing

Pull requests welcome! Please open an issue first for major changes.
