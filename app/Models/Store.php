<?php
// app/Models/Store.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    protected $fillable = ['code', 'name', 'folder_path'];

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function getInvoiceCountAttribute(): int
    {
        return $this->invoices()->count();
    }
}
