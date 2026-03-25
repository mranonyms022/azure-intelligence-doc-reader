<?php
// app/Livewire/Dashboard.php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Invoice;
use App\Models\Store;

class Dashboard extends Component
{
    // ── Accessible store codes for current user ───────────────
    private function accessCodes(): array
    {
        return auth()->user()->accessibleStoreCodes();
    }

    private function baseQuery()
    {
        $codes = $this->accessCodes();
        return Invoice::when(!empty($codes), fn($q) =>
            $q->whereIn('store_code', $codes)
        );
    }

    public function getStatsProperty(): array
    {
        return [
            'total_invoices'   => $this->baseQuery()->count(),
            'needs_review'     => $this->baseQuery()->where('needs_review', true)->count(),
            'arabic_invoices'  => $this->baseQuery()->where('document_language', 'ar')->count(),
            'english_invoices' => $this->baseQuery()->where('document_language', 'en')->count(),
            'today_processed'  => $this->baseQuery()->whereDate('processed_at', today())->count(),
            'total_stores'     => auth()->user()->isAdmin()
                                    ? Store::count()
                                    : count($this->accessCodes()),
        ];
    }

    public function getRecentInvoicesProperty()
    {
        return $this->baseQuery()
            ->with('store')
            ->orderByDesc('processed_at')
            ->limit(8)
            ->get();
    }

    public function getByStoreProperty()
    {
        return $this->baseQuery()
            ->selectRaw('store_code, count(*) as total, sum(total_amount) as revenue')
            ->groupBy('store_code')
            ->orderByDesc('total')
            ->get();
    }

    public function getByCurrencyProperty()
    {
        return $this->baseQuery()
            ->selectRaw('currency, count(*) as total, sum(total_amount) as amount')
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
