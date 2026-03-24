{{-- resources/views/livewire/invoice-list.blade.php --}}

<div>
    {{-- Page header --}}
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h5 class="fw-bold mb-0" style="color:#1B3A5C;">Invoices</h5>
            <p class="text-muted mb-0" style="font-size:.78rem;">
                {{ number_format($invoices->total()) }} records found
            </p>
        </div>
    </div>

    {{-- ── FILTER BAR ── --}}
    <div class="card filter-card mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">

                {{-- Search - full width on mobile --}}
                <div class="col-12 col-sm-6 col-lg-4">
                    <label class="form-label fw-semibold mb-1" style="font-size:.75rem;">Search</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search text-muted" style="font-size:.8rem;"></i>
                        </span>
                        <input type="text"
                               wire:model.live.debounce.400ms="search"
                               class="form-control border-start-0 ps-0"
                               placeholder="Invoice #, vendor, file...">
                        @if($search)
                        <button class="btn btn-outline-secondary btn-sm" wire:click="$set('search','')">
                            <i class="bi bi-x"></i>
                        </button>
                        @endif
                    </div>
                </div>

                {{-- Store --}}
                <div class="col-6 col-sm-3 col-lg-2">
                    <label class="form-label fw-semibold mb-1" style="font-size:.75rem;">Store</label>
                    <select wire:model.live="storeFilter" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach($stores as $store)
                        <option value="{{ $store->code }}">{{ $store->code }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Language --}}
                <div class="col-6 col-sm-3 col-lg-1">
                    <label class="form-label fw-semibold mb-1" style="font-size:.75rem;">Lang</label>
                    <select wire:model.live="langFilter" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="ar">AR</option>
                        <option value="en">EN</option>
                    </select>
                </div>

                {{-- Status --}}
                <div class="col-6 col-sm-4 col-lg-2">
                    <label class="form-label fw-semibold mb-1" style="font-size:.75rem;">Status</label>
                    <select wire:model.live="statusFilter" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="review">Needs Review</option>
                    </select>
                </div>

                {{-- Date From --}}
                <div class="col-6 col-sm-4 col-lg-2">
                    <label class="form-label fw-semibold mb-1" style="font-size:.75rem;">From</label>
                    <input type="date" wire:model.live="dateFrom" class="form-control form-control-sm">
                </div>

                {{-- Date To + Clear --}}
                <div class="col-12 col-sm-4 col-lg-1">
                    <label class="form-label fw-semibold mb-1" style="font-size:.75rem;">To</label>
                    <input type="date" wire:model.live="dateTo" class="form-control form-control-sm">
                </div>

                <div class="col-12 col-sm-auto">
                    <button wire:click="clearFilters"
                            class="btn btn-outline-secondary btn-sm w-100"
                            style="margin-top: 1.6rem;">
                        <i class="bi bi-x-circle me-1"></i>Clear
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── TABLE ── --}}
    <div class="card table-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        {{-- Always visible --}}
                        <th class="ps-3" wire:click="sortBy('invoice_number')" style="min-width:130px;">
                            Invoice #
                            @if($sortBy==='invoice_number')
                            <i class="bi bi-arrow-{{ $sortDir==='asc'?'up':'down' }}"></i>
                            @endif
                        </th>

                        <th style="min-width:60px;">Store</th>

                        <th wire:click="sortBy('vendor_name')" style="min-width:140px;">
                            Vendor
                            @if($sortBy==='vendor_name')
                            <i class="bi bi-arrow-{{ $sortDir==='asc'?'up':'down' }}"></i>
                            @endif
                        </th>

                        {{-- Hidden on mobile --}}
                        <th class="d-none d-md-table-cell" style="min-width:130px;">Customer</th>

                        <th class="d-none d-sm-table-cell" wire:click="sortBy('invoice_date')" style="min-width:100px;">
                            Date
                            @if($sortBy==='invoice_date')
                            <i class="bi bi-arrow-{{ $sortDir==='asc'?'up':'down' }}"></i>
                            @endif
                        </th>

                        <th wire:click="sortBy('total_amount')" style="min-width:100px;">
                            Total
                            @if($sortBy==='total_amount')
                            <i class="bi bi-arrow-{{ $sortDir==='asc'?'up':'down' }}"></i>
                            @endif
                        </th>

                        <th class="d-none d-sm-table-cell" style="min-width:55px;">Lang</th>

                        <th style="min-width:75px;">Status</th>

                        {{-- Processed date - tablet+ --}}
                        <th class="d-none d-lg-table-cell" wire:click="sortBy('processed_at')" style="min-width:115px;">
                            Processed
                            @if($sortBy==='processed_at')
                            <i class="bi bi-arrow-{{ $sortDir==='asc'?'up':'down' }}"></i>
                            @endif
                        </th>

                        <th class="pe-3" style="min-width:60px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                    <tr>
                        <td class="ps-3">
                            <div class="fw-semibold">{{ $invoice->invoice_number ?? '—' }}</div>
                            <div class="text-muted text-truncate" style="font-size:.7rem;max-width:120px;">
                                {{ $invoice->file_name }}
                            </div>
                        </td>

                        <td>
                            <span class="badge bg-secondary">{{ $invoice->store_code }}</span>
                        </td>

                        <td>
                            <div class="text-truncate" style="max-width:140px;" title="{{ $invoice->vendor_name }}">
                                {{ $invoice->vendor_name ?? '—' }}
                            </div>
                        </td>

                        <td class="d-none d-md-table-cell">
                            <div class="text-truncate" style="max-width:120px;" title="{{ $invoice->customer_name }}">
                                {{ $invoice->customer_name ?? '—' }}
                            </div>
                        </td>

                        <td class="d-none d-sm-table-cell">
                            {{ $invoice->invoice_date?->format('d M Y') ?? '—' }}
                        </td>

                        <td>
                            <span class="fw-semibold">
                                {{ $invoice->total_amount ? number_format($invoice->total_amount, 2) : '—' }}
                            </span>
                            @if($invoice->currency)
                            <span class="text-muted d-block d-sm-inline" style="font-size:.72rem;">
                                {{ $invoice->currency }}
                            </span>
                            @endif
                        </td>

                        <td class="d-none d-sm-table-cell">
                            <span class="badge {{ $invoice->document_language==='ar' ? 'lang-badge-ar' : 'lang-badge-en' }}">
                                {{ strtoupper($invoice->document_language ?? '?') }}
                            </span>
                        </td>

                        <td>
                            @if($invoice->needs_review)
                            <span class="badge badge-review" style="font-size:.68rem;">
                                <i class="bi bi-exclamation-triangle"></i>
                                <span class="d-none d-md-inline ms-1">Review</span>
                            </span>
                            @else
                            <span class="badge bg-light text-success border" style="font-size:.68rem;">
                                <i class="bi bi-check-circle"></i>
                                <span class="d-none d-md-inline ms-1">OK</span>
                            </span>
                            @endif
                        </td>

                        <td class="d-none d-lg-table-cell text-muted" style="font-size:.76rem;">
                            {{ $invoice->processed_at?->format('d M, H:i') ?? '—' }}
                        </td>

                        <td class="pe-3">
                            <a href="{{ route('invoices.show', $invoice->id) }}"
                               class="btn btn-sm btn-outline-primary px-2">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox" style="font-size:2.5rem;opacity:.2;display:block;margin-bottom:.5rem;"></i>
                            <div class="fw-semibold">No invoices found</div>
                            <div style="font-size:.8rem;">Try adjusting your search or filters</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ── PAGINATION ── --}}
        @if($invoices->hasPages())
        <div class="card-footer bg-white d-flex flex-column flex-sm-row
                    align-items-start align-items-sm-center
                    justify-content-between gap-2 py-2 px-3">
            <div class="text-muted" style="font-size:.78rem;">
                Showing {{ $invoices->firstItem() }}–{{ $invoices->lastItem() }}
                of {{ number_format($invoices->total()) }}
            </div>
            {{ $invoices->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>

    {{-- Loading toast --}}
    <div wire:loading.delay class="position-fixed bottom-0 end-0 p-3" style="z-index:999;">
        <div class="toast show align-items-center text-bg-dark border-0" style="font-size:.82rem;">
            <div class="d-flex">
                <div class="toast-body py-2 px-3">
                    <span class="spinner-border spinner-border-sm me-2"></span>Loading...
                </div>
            </div>
        </div>
    </div>
</div>
