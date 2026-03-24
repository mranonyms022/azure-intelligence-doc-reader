{{-- resources/views/livewire/dashboard.blade.php --}}

<div>
    {{-- Page header --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-0" style="color:#1B3A5C;">Dashboard</h4>
            <p class="text-muted small mb-0">Invoice processing overview</p>
        </div>
        <div class="text-muted small">
            <i class="bi bi-clock me-1"></i>{{ now()->format('d M Y, H:i') }}
        </div>
    </div>

    {{-- ── Stats cards ── --}}
    <div class="row g-3 mb-4">
        {{-- Total Invoices --}}
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card stat-card p-3">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <div class="stat-value">{{ number_format($stats['total_invoices']) }}</div>
                        <div class="stat-label">Total Invoices</div>
                    </div>
                    <div class="stat-icon" style="background:#e8f4fd;color:#0d6efd;">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Needs Review --}}
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card stat-card p-3">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <div class="stat-value text-danger">{{ number_format($stats['needs_review']) }}</div>
                        <div class="stat-label">Need Review</div>
                    </div>
                    <div class="stat-icon" style="background:#fde8e8;color:#dc3545;">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Arabic --}}
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card stat-card p-3">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <div class="stat-value">{{ number_format($stats['arabic_invoices']) }}</div>
                        <div class="stat-label">Arabic</div>
                    </div>
                    <div class="stat-icon" style="background:#fff3cd;color:#856404;">
                        <i class="bi bi-translate"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- English --}}
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card stat-card p-3">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <div class="stat-value">{{ number_format($stats['english_invoices']) }}</div>
                        <div class="stat-label">English</div>
                    </div>
                    <div class="stat-icon" style="background:#d1e7dd;color:#0f5132;">
                        <i class="bi bi-translate"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Today --}}
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card stat-card p-3">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <div class="stat-value text-success">{{ number_format($stats['today_processed']) }}</div>
                        <div class="stat-label">Today</div>
                    </div>
                    <div class="stat-icon" style="background:#d1e7dd;color:#0f5132;">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Stores --}}
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card stat-card p-3">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <div class="stat-value">{{ number_format($stats['total_stores']) }}</div>
                        <div class="stat-label">Stores</div>
                    </div>
                    <div class="stat-icon" style="background:#e8d5f5;color:#6f42c1;">
                        <i class="bi bi-shop"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Recent Invoices --}}
        <div class="col-12 col-xl-8">
            <div class="card table-card">
                <div class="card-header bg-white border-0 d-flex align-items-center justify-content-between py-3">
                    <h6 class="fw-bold mb-0">Recent Invoices</h6>
                    <a href="{{ route('invoices.index') }}" class="btn btn-sm btn-outline-primary">
                        View All <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Store</th>
                                <th>Vendor</th>
                                <th class="d-none d-md-table-cell">Date</th>
                                <th>Total</th>
                                <th class="d-none d-sm-table-cell">Lang</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentInvoices as $invoice)
                            <tr>
                                <td>
                                    <span class="fw-semibold">{{ $invoice->invoice_number ?? '—' }}</span>
                                    @if($invoice->needs_review)
                                    <span class="badge badge-review ms-1" style="font-size:0.65rem;">Review</span>
                                    @endif
                                </td>
                                <td><span class="badge bg-secondary">{{ $invoice->store_code }}</span></td>
                                <td class="text-truncate" style="max-width:160px;" title="{{ $invoice->vendor_name }}">
                                    {{ $invoice->vendor_name ?? '—' }}
                                </td>
                                <td class="d-none d-md-table-cell">{{ $invoice->invoice_date?->format('d M Y') ?? '—' }}</td>
                                <td class="fw-semibold">
                                    {{ $invoice->total_amount ? number_format($invoice->total_amount, 2) . ' ' . $invoice->currency : '—' }}
                                </td>
                                <td class="d-none d-sm-table-cell">
                                    <span class="badge {{ $invoice->document_language === 'ar' ? 'lang-badge-ar' : 'lang-badge-en' }}">
                                        {{ strtoupper($invoice->document_language ?? '?') }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('invoices.show', $invoice->id) }}" class="btn btn-sm btn-light">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-4 d-block mb-2"></i>No invoices processed yet
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Right column --}}
        <div class="col-12 col-xl-4">
            {{-- By Store --}}
            <div class="card table-card mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold mb-0">Invoices by Store</h6>
                </div>
                <div class="card-body p-0">
                    @forelse($byStore as $row)
                    <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
                        <div>
                            <span class="badge bg-primary me-2">{{ $row->store_code }}</span>
                            <span class="text-muted small">{{ number_format($row->total) }} invoices</span>
                        </div>
                        <span class="fw-semibold small">{{ number_format($row->revenue, 0) }}</span>
                    </div>
                    @empty
                    <div class="text-center text-muted py-3 small">No data</div>
                    @endforelse
                </div>
            </div>

            {{-- By Currency --}}
            <div class="card table-card">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold mb-0">By Currency</h6>
                </div>
                <div class="card-body p-0">
                    @forelse($byCurrency as $row)
                    <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
                        <div>
                            <span class="fw-semibold me-2">{{ $row->currency }}</span>
                            <span class="text-muted small">{{ $row->total }} invoices</span>
                        </div>
                        <span class="text-success fw-semibold small">{{ number_format($row->amount, 2) }}</span>
                    </div>
                    @empty
                    <div class="text-center text-muted py-3 small">No data</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
