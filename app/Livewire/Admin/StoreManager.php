<?php
// app/Livewire/Admin/StoreManager.php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Store;
use App\Models\User;

class StoreManager extends Component
{
    // ── Store form ────────────────────────────────────────────
    public string  $code        = '';
    public string  $name        = '';
    public string  $folder_path = '';
    public bool    $is_active   = true;
    public bool    $showForm    = false;
    public ?int    $editingId   = null;
    public string  $search      = '';

    // ── User assignment ───────────────────────────────────────
    public ?int    $assignStoreId   = null;
    public string  $assignStoreName = '';
    public array   $assignedUsers   = [];   // user IDs currently assigned
    public bool    $showAssignModal = false;

    protected function rules(): array
    {
        return [
            'code'        => 'required|alpha_num|max:20|unique:stores,code,' . ($this->editingId ?? 'NULL'),
            'name'        => 'required|string|max:100',
            'folder_path' => 'nullable|string|max:500',
        ];
    }

    protected array $messages = [
        'code.alpha_num' => 'Store code must be letters and numbers only (e.g. S101).',
        'code.unique'    => 'This store code already exists.',
    ];

    // ── Store CRUD ────────────────────────────────────────────

    public function openCreate(): void
    {
        $this->reset(['code', 'name', 'folder_path', 'editingId']);
        $this->is_active = true;
        $this->showForm  = true;
        $this->resetValidation();
    }

    public function editStore(int $id): void
    {
        $store             = Store::findOrFail($id);
        $this->editingId   = $id;
        $this->code        = $store->code;
        $this->name        = $store->name;
        $this->folder_path = $store->folder_path ?? '';
        $this->is_active   = $store->is_active;
        $this->showForm    = true;
        $this->resetValidation();
    }

    public function saveStore(): void
    {
        $this->validate();

        $data = [
            'code'        => strtoupper($this->code),
            'name'        => $this->name,
            'folder_path' => $this->folder_path ?: null,
            'is_active'   => $this->is_active,
        ];

        if ($this->editingId) {
            Store::findOrFail($this->editingId)->update($data);
            session()->flash('success', "Store {$data['code']} updated successfully.");
        } else {
            Store::create($data);
            session()->flash('success', "Store {$data['code']} created successfully.");
        }

        $this->showForm = false;
        $this->reset(['code', 'name', 'folder_path', 'editingId']);
    }

    public function toggleStoreActive(int $id): void
    {
        $store = Store::findOrFail($id);
        $store->update(['is_active' => !$store->is_active]);
    }

    public function cancelForm(): void
    {
        $this->showForm = false;
        $this->reset(['code', 'name', 'folder_path', 'editingId']);
        $this->resetValidation();
    }

    // ── User Assignment ───────────────────────────────────────

    public function openAssign(int $storeId): void
    {
        $store                  = Store::findOrFail($storeId);
        $this->assignStoreId    = $storeId;
        $this->assignStoreName  = "{$store->code} — {$store->name}";
        $this->assignedUsers    = $store->users()->pluck('users.id')->toArray();
        $this->showAssignModal  = true;
    }

    public function toggleUserAssignment(int $userId): void
    {
        if (!$this->assignStoreId) return;

        $store = Store::findOrFail($this->assignStoreId);
        $user  = User::findOrFail($userId);

        // Admin users can't be restricted to stores
        if ($user->isAdmin()) return;

        if ($store->users()->where('users.id', $userId)->exists()) {
            $store->users()->detach($userId);
            $this->assignedUsers = array_diff($this->assignedUsers, [$userId]);
        } else {
            $store->users()->attach($userId);
            $this->assignedUsers[] = $userId;
        }
    }

    public function closeAssignModal(): void
    {
        $this->showAssignModal = false;
        $this->assignStoreId   = null;
        $this->assignedUsers   = [];
    }

    // ── Computed properties ───────────────────────────────────

    public function getStoresProperty()
    {
        return Store::when($this->search, fn($q) =>
            $q->where('code', 'like', "%{$this->search}%")
              ->orWhere('name', 'like', "%{$this->search}%")
        )->withCount('invoices', 'users')
         ->orderBy('code')
         ->get();
    }

    public function getNonAdminUsersProperty()
    {
        return User::where('role', 'user')
                   ->where('is_active', true)
                   ->orderBy('name')
                   ->get();
    }

    public function render()
    {
        return view('livewire.admin.store-manager', [
            'stores'         => $this->stores,
            'nonAdminUsers'  => $this->nonAdminUsers,
        ])->layout('layouts.app');
    }
}
