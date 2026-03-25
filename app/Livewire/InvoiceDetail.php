<?php
// app/Livewire/InvoiceDetail.php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Invoice;

class InvoiceDetail extends Component
{
    public Invoice $invoice;
    public bool    $showRawJson  = false;
    public bool    $editMode     = false;

    // ── Editable fields ──────────────────────────────────────
    public ?string $invoice_number   = null;
    public ?string $invoice_date     = null;
    public ?string $due_date         = null;
    public ?string $po_number        = null;
    public ?string $vendor_name      = null;
    public ?string $vendor_address   = null;
    public ?string $vendor_tax_id    = null;
    public ?string $customer_name    = null;
    public ?string $customer_address = null;
    public ?string $subtotal         = null;
    public ?string $vat_amount       = null;
    public ?string $total_amount     = null;
    public ?string $amount_due       = null;
    public ?string $currency         = null;

    protected array $rules = [
        'invoice_number'   => 'nullable|string|max:100',
        'invoice_date'     => 'nullable|date',
        'due_date'         => 'nullable|date',
        'po_number'        => 'nullable|string|max:100',
        'vendor_name'      => 'nullable|string|max:191',
        'vendor_address'   => 'nullable|string',
        'vendor_tax_id'    => 'nullable|string|max:100',
        'customer_name'    => 'nullable|string|max:191',
        'customer_address' => 'nullable|string',
        'subtotal'         => 'nullable|numeric|min:0',
        'vat_amount'       => 'nullable|numeric|min:0',
        'total_amount'     => 'nullable|numeric|min:0',
        'amount_due'       => 'nullable|numeric|min:0',
        'currency'         => 'nullable|string|max:10',
    ];

    public function mount(int $id): void
    {
        $this->invoice = Invoice::with('store')->findOrFail($id);

        // Store access guard — user can only view assigned stores
        if (!auth()->user()->hasStoreAccess($this->invoice->store_code)) {
            abort(403, 'You do not have access to this store.');
        }

        $this->loadFields();
    }

    // ── Load invoice values into editable properties ──────────
    private function loadFields(): void
    {
        $this->invoice_number   = $this->invoice->invoice_number;
        $this->invoice_date     = $this->invoice->invoice_date?->format('Y-m-d');
        $this->due_date         = $this->invoice->due_date?->format('Y-m-d');
        $this->po_number        = $this->invoice->po_number;
        $this->vendor_name      = $this->invoice->vendor_name;
        $this->vendor_address   = $this->invoice->vendor_address;
        $this->vendor_tax_id    = $this->invoice->vendor_tax_id;
        $this->customer_name    = $this->invoice->customer_name;
        $this->customer_address = $this->invoice->customer_address;
        $this->subtotal         = $this->invoice->subtotal;
        $this->vat_amount       = $this->invoice->vat_amount;
        $this->total_amount     = $this->invoice->total_amount;
        $this->amount_due       = $this->invoice->amount_due;
        $this->currency         = $this->invoice->currency;
    }

    // ── Toggle edit mode ──────────────────────────────────────
    public function startEdit(): void
    {
        $this->loadFields();
        $this->editMode = true;
    }

    public function cancelEdit(): void
    {
        $this->loadFields();
        $this->editMode = false;
        $this->resetValidation();
    }

    // ── Save edited fields ────────────────────────────────────
    public function saveEdit(): void
    {
        $this->validate();

        $this->invoice->update([
            'invoice_number'   => $this->invoice_number,
            'invoice_date'     => $this->invoice_date,
            'due_date'         => $this->due_date,
            'po_number'        => $this->po_number,
            'vendor_name'      => $this->vendor_name,
            'vendor_address'   => $this->vendor_address,
            'vendor_tax_id'    => $this->vendor_tax_id,
            'customer_name'    => $this->customer_name,
            'customer_address' => $this->customer_address,
            'subtotal'         => $this->subtotal,
            'vat_amount'       => $this->vat_amount,
            'total_amount'     => $this->total_amount,
            'amount_due'       => $this->amount_due,
            'currency'         => $this->currency,
        ]);

        $this->invoice->refresh();
        $this->editMode = false;
        session()->flash('success', 'Invoice updated successfully.');
    }

    // ── Save + Mark as reviewed in one click ─────────────────
    public function saveAndReview(): void
    {
        $this->validate();

        $this->invoice->update([
            'invoice_number'   => $this->invoice_number,
            'invoice_date'     => $this->invoice_date,
            'due_date'         => $this->due_date,
            'po_number'        => $this->po_number,
            'vendor_name'      => $this->vendor_name,
            'vendor_address'   => $this->vendor_address,
            'vendor_tax_id'    => $this->vendor_tax_id,
            'customer_name'    => $this->customer_name,
            'customer_address' => $this->customer_address,
            'subtotal'         => $this->subtotal,
            'vat_amount'       => $this->vat_amount,
            'total_amount'     => $this->total_amount,
            'amount_due'       => $this->amount_due,
            'currency'         => $this->currency,
            'needs_review'     => false,
        ]);

        $this->invoice->refresh();
        $this->editMode = false;
        session()->flash('success', 'Invoice updated and marked as reviewed.');
    }

    // ── Mark reviewed without edit ────────────────────────────
    public function markReviewed(): void
    {
        $this->invoice->update(['needs_review' => false]);
        $this->invoice->refresh();
        session()->flash('success', 'Invoice marked as reviewed.');
    }

    public function toggleRaw(): void
    {
        $this->showRawJson = !$this->showRawJson;
    }

    public function render()
    {
        return view('livewire.invoice-detail')
            ->layout('layouts.app');
    }
}
