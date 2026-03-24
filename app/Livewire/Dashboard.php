<?php
// app/Livewire/Dashboard.php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Invoice;
use App\Models\Store;

class Dashboard extends Component
{
    public function getStatsProperty(): array
    {
        return [
            'total_invoices'   => Invoice::count(),
            'needs_review'     => Invoice::where('needs_review', true)->count(),
            'arabic_invoices'  => Invoice::where('document_language', 'ar')->count(),
            'english_invoices' => Invoice::where('document_language', 'en')->count(),
            'today_processed'  => Invoice::whereDate('processed_at', today())->count(),
            'total_stores'     => Store::count(),
        ];
    }

    public function getRecentInvoicesProperty()
    {
        return Invoice::with('store')
            ->orderByDesc('processed_at')
            ->limit(8)
            ->get();
    }

    public function getByStoreProperty()
    {
        return Invoice::selectRaw('store_code, count(*) as total, sum(total_amount) as revenue')
            ->groupBy('store_code')
            ->orderByDesc('total')
            ->get();
    }

    public function getByCurrencyProperty()
    {
        return Invoice::selectRaw('currency, count(*) as total, sum(total_amount) as amount')
            ->whereNotNull('currency')
            ->groupBy('currency')
            ->orderByDesc('total')
            ->get();
    }

    public function render()
    {
        return view('livewire.dashboard', [
            'stats'          => $this->stats,
            'recentInvoices' => $this->recentInvoices,
            'byStore'        => $this->byStore,
            'byCurrency'     => $this->byCurrency,
        ])->layout('layouts.app');
    }
}
