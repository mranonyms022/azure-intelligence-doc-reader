{{-- resources/views/livewire/invoice-detail.blade.php --}}

<div>
    {{-- Page header --}}
    <div class="d-flex align-items-start justify-content-between mb-3 flex-wrap gap-2">
        <div>
            <a href="{{ route('invoices.index') }}"
               class="text-muted text-decoration-none mb-1 d-inline-block"
               style="font-size:.8rem;">
                <i class="bi bi-arrow-left me-1"></i>Back to Invoices
            </a>
            <h5 class="fw-bold mb-0" style="color:#1B3A5C;">
                {{ $invoice->invoice_number ?? 'Invoice Detail' }}
            </h5>
            <p class="text-muted mb-0" style="font-size:.78rem;">
                {{ $invoice->file_name }} &nbsp;•&nbsp;
                <span class="badge {{ $invoice->document_language === 'ar' ? 'lang-badge-ar' : 'lang-badge-en' }}">
                    {{ strtoupper($invoice->document_language ?? '?') }}
                </span>
                &nbsp;•&nbsp;
                <span class="badge bg-secondary">{{ $invoice->store_code }}</span>
            </p>
        </div>

        {{-- Action buttons --}}
        <div class="d-flex gap-2 flex-wrap">
            @if(!$editMode)
                {{-- Edit button --}}
                <button wire:click="startEdit"
                        class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-pencil me-1"></i>Edit
                </button>

                {{-- Mark reviewed --}}
                @if($invoice->needs_review)
                <button wire:click="markReviewed"
                        class="btn btn-sm btn-success">
                    <i class="bi bi-check-lg me-1"></i>Mark Reviewed
                </button>
                @endif
            @else
                {{-- Save button --}}
                <button wire:click="saveEdit"
                        class="btn btn-sm btn-primary"
                        wire:loading.attr="disabled"
                        wire:target="saveEdit,saveAndReview">
                    <span wire:loading.remove wire:target="saveEdit">
                        <i class="bi bi-floppy me-1"></i>Save
                    </span>
                    <span wire:loading wire:target="saveEdit">
                        <span class="spinner-border spinner-border-sm me-1"></span>Saving...
                    </span>
                </button>

                {{-- Save + Review --}}
                @if($invoice->needs_review)
                <button wire:click="saveAndReview"
                        class="btn btn-sm btn-success"
                        wire:loading.attr="disabled"
                        wire:target="saveEdit,saveAndReview">
                    <span wire:loading.remove wire:target="saveAndReview">
                        <i class="bi bi-check-lg me-1"></i>Save & Mark Reviewed
                    </span>
                    <span wire:loading wire:target="saveAndReview">
                        <span class="spinner-border spinner-border-sm me-1"></span>Saving...
                    </span>
                </button>
                @endif

                {{-- Cancel --}}
                <button wire:click="cancelEdit"
                        class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x me-1"></i>Cancel
                </button>
            @endif
        </div>
    </div>

    {{-- Edit mode banner --}}
    @if($editMode)
    <div class="alert alert-info d-flex align-items-center mb-3 py-2" style="font-size:.82rem;">
        <i class="bi bi-pencil-square me-2"></i>
        <div>You are in <strong>edit mode</strong> — make changes and click Save or Save & Mark Reviewed.</div>
    </div>
    @endif

    {{-- Needs review warning --}}
    @if($invoice->needs_review && !$editMode)
    <div class="alert alert-warning d-flex align-items-center mb-3 py-2" style="font-size:.82rem;">
        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
        <div>
            <strong>Manual Review Required</strong> —
            One or more fields have low confidence.
            Lowest: <strong>{{ $invoice->min_confidence_score }}</strong>
            &nbsp;
            <a href="#" wire:click.prevent="startEdit" class="alert-link">Click Edit to correct values</a>
        </div>
    </div>
    @endif

    <div class="row g-3">

        {{-- ══ LEFT COLUMN ══ --}}
        <div class="col-12 col-xl-8">

            {{-- Invoice Header Card --}}
            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="card-header bg-white border-0 pt-3 pb-0 d-flex align-items-center justify-content-between">
                    <h6 class="fw-bold mb-0" style="color:#1B3A5C;">
                        <i class="bi bi-file-earmark-text me-2"></i>Invoice Header
                    </h6>
                    @if($editMode)
                    <span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size:.68rem;">
                        Editing
                    </span>
                    @endif
                </div>
                <div class="card-body">
                    <div class="row g-3">

                        {{-- Invoice Number --}}
                        <div class="col-6 col-md-3">
                            <div class="detail-label">Invoice Number</div>
                            @if($editMode)
                                <input type="text" wire:model="invoice_number"
                                       class="form-control form-control-sm @error('invoice_number') is-invalid @enderror"
                                       placeholder="INV-001">
                                @error('invoice_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            @else
                                <div class="detail-value">{{ $invoice->invoice_number ?? '—' }}</div>
                            @endif
                        </div>

                        {{-- Invoice Date --}}
                        <div class="col-6 col-md-3">
                            <div class="detail-label">Invoice Date</div>
                            @if($editMode)
                                <input type="date" wire:model="invoice_date"
                                       class="form-control form-control-sm @error('invoice_date') is-invalid @enderror">
                                @error('invoice_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            @else
                                <div class="detail-value">{{ $invoice->invoice_date?->format('d M Y') ?? '—' }}</div>
                            @endif
                        </div>

                        {{-- Due Date --}}
                        <div class="col-6 col-md-3">
                            <div class="detail-label">Due Date</div>
                            @if($editMode)
                                <input type="date" wire:model="due_date"
                                       class="form-control form-control-sm @error('due_date') is-invalid @enderror">
                                @error('due_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            @else
                                <div class="detail-value">{{ $invoice->due_date?->format('d M Y') ?? '—' }}</div>
                            @endif
                        </div>

                        {{-- PO Number --}}
                        <div class="col-6 col-md-3">
                            <div class="detail-label">PO Number</div>
                            @if($editMode)
                                <input type="text" wire:model="po_number"
                                       class="form-control form-control-sm"
                                       placeholder="PO-001">
                            @else
                                <div class="detail-value">{{ $invoice->po_number ?? '—' }}</div>
                            @endif
                        </div>

                        <div class="col-12"><hr class="my-1"></div>

                        {{-- Vendor --}}
                        <div class="col-12 col-md-6">
                            <div class="p-3 rounded-3" style="background:#f8f9fa;">
                                <div class="detail-label mb-2">
                                    <i class="bi bi-building me-1"></i>Vendor / Supplier
                                </div>
                                @if($editMode)
                                    <input type="text" wire:model="vendor_name"
                                           class="form-control form-control-sm mb-2 @error('vendor_name') is-invalid @enderror"
                                           placeholder="Vendor name">
                                    @error('vendor_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    <textarea wire:model="vendor_address"
                                              class="form-control form-control-sm mb-2"
                                              rows="2"
                                              placeholder="Vendor address"></textarea>
                                    <input type="text" wire:model="vendor_tax_id"
                                           class="form-control form-control-sm"
                                           placeholder="VAT / Tax ID">
                                @else
                                    <div class="detail-value">{{ $invoice->vendor_name ?? '—' }}</div>
                                    @if($invoice->vendor_address)
                                    <div class="text-muted mt-1" style="font-size:.78rem;">{{ $invoice->vendor_address }}</div>
                                    @endif
                                    @if($invoice->vendor_tax_id)
                                    <div class="text-muted mt-1" style="font-size:.78rem;">
                                        <span class="fw-semibold">VAT/Tax ID:</span> {{ $invoice->vendor_tax_id }}
                                    </div>
                                    @endif
                                @endif
                            </div>
                        </div>

                        {{-- Customer --}}
                        <div class="col-12 col-md-6">
                            <div class="p-3 rounded-3" style="background:#f8f9fa;">
                                <div class="detail-label mb-2">
                                    <i class="bi bi-person me-1"></i>Customer / Bill To
                                </div>
                                @if($editMode)
                                    <input type="text" wire:model="customer_name"
                                           class="form-control form-control-sm mb-2 @error('customer_name') is-invalid @enderror"
                                           placeholder="Customer name">
                                    @error('customer_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    <textarea wire:model="customer_address"
                                              class="form-control form-control-sm"
                                              rows="2"
                                              placeholder="Customer address"></textarea>
                                @else
                                    <div class="detail-value">{{ $invoice->customer_name ?? '—' }}</div>
                                    @if($invoice->customer_address)
                                    <div class="text-muted mt-1" style="font-size:.78rem;">{{ $invoice->customer_address }}</div>
                                    @endif
                                    <div class="text-muted mt-1" style="font-size:.78rem;">
                                        <span class="fw-semibold">Store:</span>
                                        <span class="badge bg-secondary ms-1">{{ $invoice->store_code }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- Line Items --}}
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white border-0 pt-3 pb-0">
                    <h6 class="fw-bold mb-0" style="color:#1B3A5C;">
                        <i class="bi bi-list-ul me-2"></i>Line Items
                        <span class="badge bg-light text-muted ms-2" style="font-size:.7rem;">
                            {{ count($invoice->line_items ?? []) }}
                        </span>
                    </h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3" style="width:40px;">#</th>
                                <th>Description</th>
                                <th class="d-none d-md-table-cell">Code</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end d-none d-sm-table-cell">Unit Price</th>
                                <th class="text-end pe-3">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoice->line_items ?? [] as $item)
                            <tr>
                                <td class="ps-3 text-muted">{{ $item['index'] ?? $loop->iteration }}</td>
                                <td>{{ $item['description'] ?? '—' }}</td>
                                <td class="text-muted d-none d-md-table-cell" style="font-size:.78rem;">
                                    {{ $item['product_code'] ?? '—' }}
                                </td>
                                <td class="text-end">{{ $item['quantity'] ?? '—' }}</td>
                                <td class="text-end d-none d-sm-table-cell">
                                    {{ isset($item['unit_price']) ? $item['unit_price'] : '—' }}
                                </td>
                                <td class="text-end pe-3 fw-semibold">
                                    {{ isset($item['amount']) ? $item['amount'] : '—' }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4" style="font-size:.82rem;">
                                    No line items extracted
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Totals --}}
                <div class="card-footer bg-white py-3">
                    <div class="row justify-content-end">
                        <div class="col-12 col-sm-7 col-md-5">
                            @if($editMode)
                            {{-- Editable totals --}}
                            <table class="table table-sm mb-0">
                                <tr>
                                    <td class="text-muted border-0 py-1" style="font-size:.82rem;">Currency</td>
                                    <td class="border-0 py-1">
                                        <input type="text" wire:model="currency"
                                               class="form-control form-control-sm text-end"
                                               placeholder="SAR" style="max-width:80px;float:right;">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted border-0 py-1" style="font-size:.82rem;">Subtotal</td>
                                    <td class="border-0 py-1">
                                        <input type="number" wire:model="subtotal" step="0.01"
                                               class="form-control form-control-sm text-end"
                                               placeholder="0.00">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted border-0 py-1" style="font-size:.82rem;">VAT / Tax</td>
                                    <td class="border-0 py-1">
                                        <input type="number" wire:model="vat_amount" step="0.01"
                                               class="form-control form-control-sm text-end"
                                               placeholder="0.00">
                                    </td>
                                </tr>
                                <tr class="border-top">
                                    <td class="fw-bold border-0 pt-2" style="font-size:.9rem;">Total</td>
                                    <td class="border-0 pt-2">
                                        <input type="number" wire:model="total_amount" step="0.01"
                                               class="form-control form-control-sm text-end fw-bold"
                                               placeholder="0.00">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-danger border-0 py-1" style="font-size:.82rem;">Amount Due</td>
                                    <td class="border-0 py-1">
                                        <input type="number" wire:model="amount_due" step="0.01"
                                               class="form-control form-control-sm text-end"
                                               placeholder="0.00">
                                    </td>
                                </tr>
                            </table>
                            @else
                            {{-- View mode totals --}}
                            <table class="table table-sm mb-0">
                                <tr>
                                    <td class="text-muted border-0 py-1" style="font-size:.82rem;">Subtotal</td>
                                    <td class="text-end fw-semibold border-0 py-1">
                                        {{ $invoice->subtotal ? $invoice->subtotal : '—' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted border-0 py-1" style="font-size:.82rem;">VAT / Tax</td>
                                    <td class="text-end fw-semibold border-0 py-1">
                                        {{ $invoice->vat_amount ? $invoice->vat_amount : '—' }}
                                    </td>
                                </tr>
                                <tr class="border-top">
                                    <td class="fw-bold border-0 pt-2">Total</td>
                                    <td class="text-end fw-bold border-0 pt-2" style="font-size:1.1rem;color:#0D6E56;">
                                        {{ $invoice->total_amount ? $invoice->total_amount : '—' }}
                                        <span style="font-size:.8rem;font-weight:400;color:#6c757d;">
                                            {{ $invoice->currency }}
                                        </span>
                                    </td>
                                </tr>
                                @if($invoice->amount_due && $invoice->amount_due != $invoice->total_amount)
                                <tr>
                                    <td class="text-danger border-0 py-1" style="font-size:.82rem;">Amount Due</td>
                                    <td class="text-end fw-semibold text-danger border-0 py-1">
                                        {{ $invoice->amount_due }} {{ $invoice->currency }}
                                    </td>
                                </tr>
                                @endif
                            </table>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- ══ RIGHT COLUMN ══ --}}
        <div class="col-12 col-xl-4">

            {{-- Document Info --}}
            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="card-header bg-white border-0 pt-3 pb-0">
                    <h6 class="fw-bold mb-0" style="color:#1B3A5C;">
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
                            <div class="detail-value">{{ $invoice->page_count ?? 1 }}</div>
                        </div>
                        <div class="col-12">
                            <div class="detail-label">File</div>
                            <div class="detail-value small text-break">{{ $invoice->file_name }}</div>
                        </div>
                        <div class="col-12">
                            <div class="detail-label">Processed</div>
                            <div class="detail-value small">
                                {{ $invoice->processed_at?->format('d M Y, H:i') ?? '—' }}
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="detail-label">Review Status</div>
                            @if($invoice->needs_review)
                            <span class="badge badge-review">
                                <i class="bi bi-exclamation-triangle me-1"></i>Needs Review
                            </span>
                            @else
                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                <i class="bi bi-check-circle me-1"></i>Reviewed
                            </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Confidence Scores --}}
            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="card-header bg-white border-0 pt-3 pb-0">
                    <h6 class="fw-bold mb-0" style="color:#1B3A5C;">
                        <i class="bi bi-bar-chart me-2"></i>Confidence Scores
                    </h6>
                </div>
                <div class="card-body">
                    @if(!empty($invoice->confidences))
                        @foreach($invoice->confidences as $field => $score)
                        @php
                            $pct   = round($score * 100);
                            $color = $pct >= 90 ? '#198754'
                                   : ($pct >= 70 ? '#0d6efd'
                                   : ($pct >= 60 ? '#fd7e14' : '#dc3545'));
                        @endphp
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span style="font-size:.76rem;color:#6c757d;">
                                    {{ ucfirst(str_replace('_', ' ', $field)) }}
                                </span>
                                <span class="fw-semibold" style="font-size:.76rem;color:{{ $color }};">
                                    {{ $pct }}%
                                </span>
                            </div>
                            <div class="conf-bar">
                                <div class="conf-fill" style="width:{{ $pct }}%;background:{{ $color }};"></div>
                            </div>
                        </div>
                        @endforeach
                    @else
                        <p class="text-muted mb-0" style="font-size:.82rem;">No confidence data</p>
                    @endif
                </div>
            </div>

            {{-- Raw JSON — admin only --}}
            @if(auth()->user()?->isAdmin())
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white border-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0" style="color:#1B3A5C;font-size:.88rem;">
                        <i class="bi bi-code-slash me-2"></i>Raw Fields
                    </h6>
                    <button wire:click="toggleRaw"
                            class="btn btn-sm btn-outline-secondary"
                            style="font-size:.75rem;padding:.2rem .5rem;">
                        {{ $showRawJson ? 'Hide' : 'Show' }}
                    </button>
                </div>
                @if($showRawJson)
                <div class="card-body p-0">
                    <pre class="p-3 mb-0 rounded-bottom-3"
                         style="background:#1e2939;color:#93c5fd;font-size:.7rem;max-height:380px;overflow-y:auto;white-space:pre-wrap;">{{ json_encode($invoice->raw_fields, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
                @endif
            </div>
            @endif

        </div>
    </div>
</div>
