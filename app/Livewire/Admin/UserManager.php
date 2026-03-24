<?php
// app/Livewire/Admin/UserManager.php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserManager extends Component
{
    // Form fields
    public string $name     = '';
    public string $email    = '';
    public string $password = '';
    public string $role     = 'user';
    public bool   $isActive = true;

    public bool  $showForm  = false;
    public ?int  $editingId = null;
    public string $search   = '';

    protected function rules(): array
    {
        return [
            'name'     => 'required|min:2',
            'email'    => 'required|email|unique:users,email,' . ($this->editingId ?? 'NULL'),
            'password' => $this->editingId ? 'nullable|min:6' : 'required|min:6',
            'role'     => 'required|in:admin,user',
        ];
    }

    public function openCreate(): void
    {
        $this->reset(['name', 'email', 'password', 'role', 'editingId']);
        $this->isActive  = true;
        $this->role      = 'user';
        $this->showForm  = true;
    }

    public function editUser(int $id): void
    {
        $user            = User::findOrFail($id);
        $this->editingId = $id;
        $this->name      = $user->name;
        $this->email     = $user->email;
        $this->role      = $user->role;
        $this->isActive  = $user->is_active;
        $this->password  = '';
        $this->showForm  = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name'      => $this->name,
            'email'     => $this->email,
            'role'      => $this->role,
            'is_active' => $this->isActive,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->editingId) {
            User::findOrFail($this->editingId)->update($data);
            session()->flash('success', 'User updated successfully.');
        } else {
            User::create($data);
            session()->flash('success', 'User created successfully.');
        }

        $this->showForm = false;
        $this->reset(['name', 'email', 'password', 'editingId']);
    }

    public function toggleActive(int $id): void
    {
        $user            = User::findOrFail($id);
        // Prevent deactivating self
        if ($user->id === auth()->id()) return;
        $user->update(['is_active' => !$user->is_active]);
    }

    public function getUsersProperty()
    {
        return User::when($this->search, fn($q) =>
            $q->where('name',  'like', "%{$this->search}%")
              ->orWhere('email', 'like', "%{$this->search}%")
        )->orderBy('role')->orderBy('name')->get();
    }

    public function render()
    {
        return view('livewire.admin.user-manager', [
            'users' => $this->users,
        ])->layout('layouts.app');
    }
}
