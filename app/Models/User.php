<?php
// app/Models/User.php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'is_active'];

    protected $hidden   = ['password', 'remember_token'];

    protected $casts = [
        'password'  => 'hashed',
        'is_active' => 'boolean',
    ];

    // ── Role helpers ──────────────────────────────────────────
    public function isAdmin(): bool { return $this->role === 'admin'; }
    public function isUser():  bool { return $this->role === 'user';  }

    // ── Store relationship (many-to-many) ─────────────────────
    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'user_store')
                    ->withTimestamps();
    }

    /**
     * Returns store codes this user can access.
     * Admin → empty array (means no restriction — see all)
     * User  → array of assigned store codes
     */
    public function accessibleStoreCodes(): array
    {
        if ($this->isAdmin()) return [];
        return $this->stores()->pluck('code')->toArray();
    }

    /**
     * Check access to a specific store code.
     */
    public function hasStoreAccess(string $storeCode): bool
    {
        if ($this->isAdmin()) return true;
        return in_array($storeCode, $this->accessibleStoreCodes());
    }
}
