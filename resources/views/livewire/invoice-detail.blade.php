{{-- resources/views/livewire/invoice-detail.blade.php --}}

<div>
    {{-- Page header --}}
    <div class="d-flex align-items-start justify-content-between mb-4">
        <div>
            <a href="{{ route('invoices.index') }}" class="text-muted small text-decoration-none mb-2 d-inline-block">
                <i class="bi bi-arrow-left me-1"></i>Back to Invoices
            </a>
            <h4 class="fw-bold mb-0" style="color:#1B3A5C;">
                {{ $invoice->invoice_number ?? 'Invoice Detail' }}
            </h4>
            <p class="text-muted small mb-0">
                {{ $invoice->file_name }} &nbsp;•&nbsp;
                <span class="badge {{ $invoice->document_language === 'ar' ? 'lang-badge-ar' : 'lang-badge-en' }}">
                    {{ strtoupper($invoice->document_language ?? '?') }}
                </span>
            </p>
        </div>
        <div class="d-flex gap-2">
            @if($invoice->needs_review)
            <button wire:click="markReviewed" class="btn btn-sm btn-success">
                <i class="bi bi-check-lg me-1"></i>Mark Reviewed
            </button>
            @endif
        </div>
    </div>

    @if($invoice->needs_review)
    <div class="alert alert-warning d-flex align-items-center mb-4 py-2">
        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
        <div class="small">
            <strong>Manual Review Required</strong> — One or more fields have low confidence scores.
            Lowest confidence: <strong>{{ $invoice->min_confidence_score }}</strong>
        </div>
    </div>
    @endif

    <div class="row g-4">

        {{-- ── LEFT COLUMN ── --}}
        <div class="col-12 col-xl-8">

            {{-- Invoice Header --}}
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white border-0 pt-3 pb-0">
                    <h6 class="fw-bold" style="color:#1B3A5C;">
                        <i class="bi bi-file-earmark-text me-2"></i>Invoice Header
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">

                        <div class="col-6 col-md-3">
                            <div class="detail-label">Invoice Number</div>
                            <div class="detail-value">{{ $invoice->invoice_number ?? '—' }}</div>
                        </div>

                        <div class="col-6 col-md-3">
                            <div class="detail-label">Invoice Date</div>
                            <div class="detail-value">{{ $invoice->invoice_date?->format('d M Y') ?? '—' }}</div>
                        </div>

                        <div class="col-6 col-md-3">
                            <div class="detail-label">Due Date</div>
                            <div class="detail-value">{{ $invoice->due_date?->format('d M Y') ?? '—' }}</div>
                        </div>

                        <div class="col-6 col-md-3">
                            <div class="detail-label">PO Number</div>
                            <div class="detail-value">{{ $invoice->po_number ?? '—' }}</div>
                        </div>

                        <div class="col-12"><hr class="my-1"></div>

                        {{-- Vendor --}}
                        <div class="col-12 col-md-6">
                            <div class="p-3 bg-light rounded-3">
                                <div class="detail-label mb-2">
                                    <i class="bi bi-building me-1"></i>Vendor
                                </div>
                                <div class="detail-value fs-6">{{ $invoice->vendor_name ?? '—' }}</div>
                                @if($invoice->vendor_address)
                                <div class="text-muted small mt-1">{{ $invoice->vendor_address }}</div>
                                @endif
                                @if($invoice->vendor_tax_id)
                                <div class="text-muted small mt-1">
                                    <span class="fw-semibold">Tax ID:</span> {{ $invoice->vendor_tax_id }}
                                </div>
                                @endif
                            </div>
                        </div>

                        {{-- Customer --}}
                        <div class="col-12 col-md-6">
                            <div class="p-3 bg-light rounded-3">
                                <div class="detail-label mb-2">
                                    <i class="bi bi-person me-1"></i>Customer
                                </div>
                                <div class="detail-value fs-6">{{ $invoice->customer_name ?? '—' }}</div>
                                @if($invoice->customer_address)
                                <div class="text-muted small mt-1">{{ $invoice->customer_address }}</div>
                                @endif
                                <div class="text-muted small mt-1">
                                    <span class="fw-semibold">Store:</span>
                                    <span class="badge bg-secondary ms-1">{{ $invoice->store_code }}</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- Line Items --}}
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white border-0 pt-3 pb-0">
                    <h6 class="fw-bold" style="color:#1B3A5C;">
                        <i class="bi bi-list-ul me-2"></i>Line Items
                        <span class="badge bg-light text-muted ms-2">{{ count($invoice->line_items ?? []) }}</span>
                    </h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">#</th>
                                <th>Description</th>
                                <th>Product Code</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-end pe-4">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoice->line_items ?? [] as $item)
                            <tr>
                                <td class="ps-4 text-muted">{{ $item['index'] ?? ($loop->iteration) }}</td>
                                <td>
                                    <span @if(app()->getLocale() !== 'ar' && str_contains($item['description'] ?? '', 'ا')) dir="rtl" @endif>
                                        {{ $item['description'] ?? '—' }}
                                    </span>
                                </td>
                                <td class="text-muted">{{ $item['product_code'] ?? '—' }}</td>
                                <td class="text-end">{{ $item['quantity'] ?? '—' }}</td>
                                <td class="text-end">
                                    {{ isset($item['unit_price']) ? number_format($item['unit_price'], 2) : '—' }}
                                </td>
                                <td class="text-end pe-4 fw-semibold">
                                    {{ isset($item['amount']) ? number_format($item['amount'], 2) : '—' }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4 small">
                                    No line items extracted
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Totals footer --}}
                <div class="card-footer bg-white">
                    <div class="row justify-content-end">
                        <div class="col-12 col-md-5">
                            <table class="table table-sm mb-0">
                                <tr>
                                    <td class="text-muted border-0">Subtotal</td>
                                    <td class="text-end fw-semibold border-0">
                                        {{ $invoice->subtotal ? number_format($invoice->subtotal, 2) : '—' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted border-0">VAT / Tax</td>
                                    <td class="text-end fw-semibold border-0">
                                        {{ $invoice->vat_amount ? number_format($invoice->vat_amount, 2) : '—' }}
                                    </td>
                                </tr>
                                <tr class="border-top">
                                    <td class="fw-bold pt-2 border-0">Total</td>
                                    <td class="text-end fw-bold fs-5 pt-2 border-0" style="color:#0D6E56;">
                                        {{ $invoice->total_amount ? number_format($invoice->total_amount, 2) : '—' }}
                                        <span class="fs-6 fw-normal text-muted">{{ $invoice->currency }}</span>
                                    </td>
                                </tr>
                                @if($invoice->amount_due && $invoice->amount_due != $invoice->total_amount)
                                <tr>
                                    <td class="text-danger border-0">Amount Due</td>
                                    <td class="text-end fw-semibold text-danger border-0">
                                        {{ number_format($invoice->amount_due, 2) }} {{ $invoice->currency }}
                                    </td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- ── RIGHT COLUMN ── --}}
        <div class="col-12 col-xl-4">

            {{-- File Info --}}
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white border-0 pt-3 pb-0">
                    <h6 class="fw-bold" style="color:#1B3A5C;">
                        <i class="bi bi-info-circle me-2"></i>Document Info
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="detail-label">Language</div>
                            <div class="detail-value">
                                <span class="badge {{ $invoice->document_language === 'ar' ? 'lang-badge-ar' : 'lang-badge-en' }}">
                                    {{ $invoice->document_language === 'ar' ? 'Arabic' : 'English' }}
                                </span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="detail-label">Pages</div>
                            <div class="detail-value">{{ $invoice->page_count }}</div>
                        </div>
                        <div class="col-12">
                            <div class="detail-label">File Name</div>
                            <div class="detail-value small text-break">{{ $invoice->file_name }}</div>
                        </div>
                        <div class="col-12">
                            <div class="detail-label">Processed At</div>
                            <div class="detail-value small">{{ $invoice->processed_at?->format('d M Y, H:i') ?? '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Confidence Scores --}}
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white border-0 pt-3 pb-0">
                    <h6 class="fw-bold" style="color:#1B3A5C;">
                        <i class="bi bi-bar-chart me-2"></i>Confidence Scores
                    </h6>
                </div>
                <div class="card-body">
                    @if(!empty($invoice->confidences))
                        @foreach($invoice->confidences as $field => $score)
                        @php
                            $pct   = round($score * 100);
                            $color = $pct >= 90 ? '#198754' : ($pct >= 70 ? '#0d6efd' : ($pct >= 60 ? '#fd7e14' : '#dc3545'));
                        @endphp
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="small text-muted">{{ str_replace('_', ' ', ucfirst($field)) }}</span>
                                <span class="small fw-semibold" style="color:{{ $color }};">{{ $pct }}%</span>
                            </div>
                            <div class="conf-bar">
                                <div class="conf-fill" style="width:{{ $pct }}%;background:{{ $color }};"></div>
                            </div>
                        </div>
                        @endforeach
                    @else
                        <p class="text-muted small mb-0">No confidence data available</p>
                    @endif
                </div>
            </div>

            {{-- Raw JSON toggle (admin only) --}}
            @if(auth()->user()?->isAdmin())
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white border-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0" style="color:#1B3A5C;">
                        <i class="bi bi-code-slash me-2"></i>Raw Fields
                    </h6>
                    <button wire:click="toggleRaw" class="btn btn-sm btn-outline-secondary">
                        {{ $showRawJson ? 'Hide' : 'Show' }}
                    </button>
                </div>
                @if($showRawJson)
                <div class="card-body p-0">
                    <pre class="p-3 mb-0 rounded-bottom-3"
                         style="background:#1e2939;color:#93c5fd;font-size:0.72rem;max-height:400px;overflow-y:auto;">{{ json_encode($invoice->raw_fields, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
                @endif
            </div>
            @endif

        </div>
    </div>
</div>
