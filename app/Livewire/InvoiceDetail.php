<?php
// app/Livewire/InvoiceDetail.php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Invoice;

class InvoiceDetail extends Component
{
    public Invoice $invoice;
    public bool $showRawJson = false;

    public function mount(int $id): void
    {
        $this->invoice = Invoice::with('store')->findOrFail($id);
    }

    public function toggleRaw(): void
    {
        $this->showRawJson = !$this->showRawJson;
    }

    public function markReviewed(): void
    {
        $this->invoice->update(['needs_review' => false]);
        $this->invoice->refresh();
        session()->flash('success', 'Invoice marked as reviewed.');
    }

    public function render()
    {
        return view('livewire.invoice-detail')
            ->layout('layouts.app');
    }
}
