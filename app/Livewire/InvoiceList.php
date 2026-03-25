<?php
// app/Livewire/InvoiceList.php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Invoice;
use App\Models\Store;

class InvoiceList extends Component
{
    use WithPagination;

    public string $search       = '';
    public string $storeFilter  = '';
    public string $langFilter   = '';
    public string $statusFilter = '';
    public string $dateFrom     = '';
    public string $dateTo       = '';
    public string $sortBy       = 'processed_at';
    public string $sortDir      = 'desc';
    public int    $perPage      = 15;

    protected $queryString = [
        'search'       => ['except' => ''],
        'storeFilter'  => ['except' => ''],
        'langFilter'   => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'dateFrom'     => ['except' => ''],
        'dateTo'       => ['except' => ''],
        'sortBy'       => ['except' => 'processed_at'],
        'sortDir'      => ['except' => 'desc'],
    ];

    public function updatedSearch():       void { $this->resetPage(); }
    public function updatedStoreFilter():  void { $this->resetPage(); }
    public function updatedLangFilter():   void { $this->resetPage(); }
    public function updatedStatusFilter(): void { $this->resetPage(); }
    public function updatedDateFrom():     void { $this->resetPage(); }
    public function updatedDateTo():       void { $this->resetPage(); }

    public function sortBy(string $column): void
    {
        $this->sortBy  = $this->sortBy === $column
            ? $this->sortBy
            : $column;
        $this->sortDir = ($this->sortBy === $column && $this->sortDir === 'asc')
            ? 'desc' : 'asc';
        $this->sortBy  = $column;
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'storeFilter', 'langFilter', 'statusFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    // ── Store access codes for current user ───────────────────
    private function accessibleStoreCodes(): array
    {
        return auth()->user()->accessibleStoreCodes();
    }

    public function getInvoicesProperty()
    {
        $accessCodes = $this->accessibleStoreCodes();

        return Invoice::query()
            ->with('store')

            // ── Store access filter ──
            // Admin: no restriction (accessCodes = [])
            // User:  only their assigned stores
            ->when(!empty($accessCodes), fn($q) =>
                $q->whereIn('store_code', $accessCodes)
            )

            // ── User applied filters ──
            ->when($this->search, fn($q) =>
                $q->where(fn($inner) =>
                    $inner->where('invoice_number', 'like', "%{$this->search}%")
                          ->orWhere('vendor_name',   'like', "%{$this->search}%")
                          ->orWhere('customer_name', 'like', "%{$this->search}%")
                          ->orWhere('file_name',     'like', "%{$this->search}%")
                )
            )
            ->when($this->storeFilter, fn($q) =>
                $q->where('store_code', $this->storeFilter)
            )
            ->when($this->langFilter, fn($q) =>
                $q->where('document_language', $this->langFilter)
            )
            ->when($this->statusFilter === 'review', fn($q) =>
                $q->where('needs_review', true)
            )
            ->when($this->dateFrom, fn($q) =>
                $q->whereDate('invoice_date', '>=', $this->dateFrom)
            )
            ->when($this->dateTo, fn($q) =>
                $q->whereDate('invoice_date', '<=', $this->dateTo)
            )
            ->orderBy($this->sortBy, $this->sortDir)
            ->paginate($this->perPage);
    }

    // ── Only show accessible stores in store filter dropdown ──
    public function getStoresProperty()
    {
        $accessCodes = $this->accessibleStoreCodes();

        return Store::when(!empty($accessCodes), fn($q) =>
            $q->whereIn('code', $accessCodes)
        )->orderBy('code')->get();
    }

    public function getStatsProperty(): array
    {
        $accessCodes = $this->accessibleStoreCodes();

        $query = fn() => Invoice::when(!empty($accessCodes), fn($q) =>
            $q->whereIn('store_code', $accessCodes)
        );

        return [
            'total'        => $query()->count(),
            'needs_review' => $query()->where('needs_review', true)->count(),
            'arabic'       => $query()->where('document_language', 'ar')->count(),
            'today'        => $query()->whereDate('processed_at', today())->count(),
        ];
    }

    public function render()
    {
        return view('livewire.invoice-list', [
            'invoices' => $this->invoices,
            'stores'   => $this->stores,
            'stats'    => $this->stats,
        ])->layout('layouts.app');
    }
}
