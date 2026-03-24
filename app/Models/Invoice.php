<?php
// app/Models/Invoice.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $fillable = [
        'store_id',
        'store_code',
        'file_name',
        'file_path',
        'document_language',
        'page_count',
        'invoice_number',
        'invoice_date',
        'due_date',
        'po_number',
        'vendor_name',
        'vendor_address',
        'vendor_tax_id',
        'customer_name',
        'customer_address',
        'subtotal',
        'vat_amount',
        'total_amount',
        'amount_due',
        'currency',
        'line_items',
        'raw_fields',
        'confidences',
        'raw_azure_json',
        'processed_at',
        'needs_review',
        'min_confidence_score',
    ];

    protected $casts = [
        'line_items'     => 'array',
        'raw_fields'     => 'array',
        'confidences'    => 'array',
        'raw_azure_json' => 'array',
        'subtotal'       => 'decimal:2',
        'vat_amount'     => 'decimal:2',
        'total_amount'   => 'decimal:2',
        'amount_due'     => 'decimal:2',
        'invoice_date'   => 'date',
        'due_date'       => 'date',
        'processed_at'   => 'datetime',
        'needs_review'         => 'boolean',
        'min_confidence_score' => 'decimal:2',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    // ── Scopes ──
    public function scopeByStore($query, string $code)
    {
        return $query->where('store_code', $code);
    }

    public function scopeArabic($query)
    {
        return $query->where('document_language', 'ar');
    }

    public function scopeEnglish($query)
    {
        return $query->where('document_language', 'en');
    }

    public function scopeProcessedToday($query)
    {
        return $query->whereDate('processed_at', today());
    }

    public function scopeHighConfidence($query, float $threshold = 0.90)
    {
        return $query->whereJsonContains('confidences->total_amount', $threshold);
    }
}
