<?php
// database/migrations/2024_01_01_000002_create_invoices_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            // Store reference
            $table->foreignId('store_id')->constrained('stores')->onDelete('cascade');
            $table->string('store_code', 20);

            // File info
            $table->string('file_name', 191);
            $table->string('file_path', 500)->nullable();

            // Document metadata (from Azure)
            $table->string('document_language', 20)->nullable();
            $table->unsignedSmallInteger('page_count')->default(1);

            // ── Extracted invoice fields ──────────────────────────
            $table->string('invoice_number', 100)->nullable();
            $table->date('invoice_date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('po_number', 100)->nullable();

            // Vendor
            $table->string('vendor_name', 191)->nullable();
            $table->text('vendor_address')->nullable();
            $table->string('vendor_tax_id', 100)->nullable();

            // Customer
            $table->string('customer_name', 191)->nullable();
            $table->text('customer_address')->nullable();

            // Amounts
            $table->decimal('subtotal',     15, 2)->nullable();
            $table->decimal('vat_amount',   15, 2)->nullable();
            $table->decimal('total_amount', 15, 2)->nullable();
            $table->decimal('amount_due',   15, 2)->nullable();
            $table->string('currency', 10)->nullable();

            // ── JSON columns ─────────────────────────────────────
            $table->json('line_items')->nullable();
            $table->json('raw_fields')->nullable();
            $table->json('confidences')->nullable();
            $table->json('raw_azure_json')->nullable();

            // ── Timestamps ───────────────────────────────────────
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            // ── Indexes ──────────────────────────────────────────
            // Unique: store_code + file_name
            $table->unique(['store_code', 'file_name'], 'uq_store_file');

            // Simple indexes on short columns — no length issue
            $table->index('store_code');
            $table->index('invoice_number');
            $table->index('invoice_date');
            $table->index('document_language');
            $table->index('processed_at');
            $table->index('currency');

            // vendor_name and customer_name — prefix index (first 100 chars)
            // Cannot use $table->index() with prefix length directly in Laravel
            // So we skip these — not critical for basic queries
        });

        // Add prefix indexes separately using raw SQL
        // These are optional but help with vendor/customer search queries
        \DB::statement('ALTER TABLE invoices ADD INDEX idx_vendor_name (vendor_name(100))');
        \DB::statement('ALTER TABLE invoices ADD INDEX idx_customer_name (customer_name(100))');
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
