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

    // ── Filters ──────────────────────────────────────────────
    public string  $search       = '';
    public string  $storeFilter  = '';
    public string  $langFilter   = '';
    public string  $statusFilter = '';  // needs_review
    public string  $dateFrom     = '';
    public string  $dateTo       = '';
    public string  $sortBy       = 'processed_at';
    public string  $sortDir      = 'desc';
    public int     $perPage      = 15;

    // ── URL query string params (bookmarkable filters) ────────
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

    // Reset pagination on filter change
    public function updatedSearch():       void { $this->resetPage(); }
    public function updatedStoreFilter():  void { $this->resetPage(); }
    public function updatedLangFilter():   void { $this->resetPage(); }
    public function updatedStatusFilter(): void { $this->resetPage(); }
    public function updatedDateFrom():     void { $this->resetPage(); }
    public function updatedDateTo():       void { $this->resetPage(); }

    public function sortBy(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy  = $column;
            $this->sortDir = 'asc';
        }
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset([
            'search', 'storeFilter', 'langFilter',
            'statusFilter', 'dateFrom', 'dateTo',
        ]);
        $this->resetPage();
    }

    public function getInvoicesProperty()
    {
        return Invoice::query()
            ->with('store')
            ->when($this->search, function ($q) {
                $q->where(function ($inner) {
                    $inner->where('invoice_number', 'like', "%{$this->search}%")
                          ->orWhere('vendor_name',   'like', "%{$this->search}%")
                          ->orWhere('customer_name', 'like', "%{$this->search}%")
                          ->orWhere('file_name',     'like', "%{$this->search}%");
                });
            })
            ->when($this->storeFilter,  fn($q) => $q->where('store_code', $this->storeFilter))
            ->when($this->langFilter,   fn($q) => $q->where('document_language', $this->langFilter))
            ->when($this->statusFilter === 'review', fn($q) => $q->where('needs_review', true))
            ->when($this->dateFrom,     fn($q) => $q->whereDate('invoice_date', '>=', $this->dateFrom))
            ->when($this->dateTo,       fn($q) => $q->whereDate('invoice_date', '<=', $this->dateTo))
            ->orderBy($this->sortBy, $this->sortDir)
            ->paginate($this->perPage);
    }

    public function getStoresProperty()
    {
        return Store::orderBy('code')->get();
    }

    public function getStatsProperty(): array
    {
        return [
            'total'         => Invoice::count(),
            'needs_review'  => Invoice::where('needs_review', true)->count(),
            'arabic'        => Invoice::where('document_language', 'ar')->count(),
            'today'         => Invoice::whereDate('processed_at', today())->count(),
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
