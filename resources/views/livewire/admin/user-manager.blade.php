{{-- resources/views/livewire/admin/user-manager.blade.php --}}

<div>
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-0" style="color:#1B3A5C;">User Management</h4>
            <p class="text-muted small mb-0">Manage system access and roles</p>
        </div>
        <button wire:click="openCreate" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Add User
        </button>
    </div>

    {{-- Create / Edit Form --}}
    @if($showForm)
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0">{{ $editingId ? 'Edit User' : 'Create New User' }}</h6>
            <button wire:click="$set('showForm', false)" class="btn-close"></button>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Full Name</label>
                    <input type="text" wire:model="name" class="form-control @error('name') is-invalid @enderror" placeholder="John Doe">
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Email Address</label>
                    <input type="email" wire:model="email" class="form-control @error('email') is-invalid @enderror" placeholder="user@company.com">
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">
                        Password {{ $editingId ? '(leave blank to keep)' : '' }}
                    </label>
                    <input type="password" wire:model="password" class="form-control @error('password') is-invalid @enderror" placeholder="••••••••">
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Role</label>
                    <select wire:model="role" class="form-select">
                        <option value="user">User</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" wire:model="isActive" id="isActive">
                        <label class="form-check-label small" for="isActive">Account Active</label>
                    </div>
                </div>
                <div class="col-12">
                    <button wire:click="save" class="btn btn-primary btn-sm me-2">
                        <span wire:loading.remove wire:target="save">
                            <i class="bi bi-check-lg me-1"></i>{{ $editingId ? 'Update' : 'Create' }} User
                        </span>
                        <span wire:loading wire:target="save">
                            <span class="spinner-border spinner-border-sm me-1"></span>Saving...
                        </span>
                    </button>
                    <button wire:click="$set('showForm', false)" class="btn btn-outline-secondary btn-sm">Cancel</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Users Table --}}
    <div class="card table-card">
        <div class="card-header bg-white border-0 pt-3 pb-2">
            <div class="input-group input-group-sm" style="max-width:300px;">
                <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                <input type="text" wire:model.live.debounce.300ms="search"
                       class="form-control" placeholder="Search users...">
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th class="pe-4"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle d-flex align-items-center justify-content-center text-white"
                                     style="width:32px;height:32px;font-size:0.75rem;font-weight:700;background:{{ $user->isAdmin() ? '#1B3A5C' : '#0D6E56' }};">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="fw-semibold small">{{ $user->name }}</div>
                                    @if($user->id === auth()->id())
                                    <div class="text-muted" style="font-size:0.68rem;">(You)</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="small text-muted">{{ $user->email }}</td>
                        <td>
                            <span class="badge {{ $user->isAdmin() ? 'bg-primary' : 'bg-secondary' }}">
                                {{ ucfirst($user->role) }}
                            </span>
                        </td>
                        <td>
                            @if($user->is_active)
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Inactive</span>
                            @endif
                        </td>
                        <td class="small text-muted">{{ $user->created_at->format('d M Y') }}</td>
                        <td class="pe-4">
                            <div class="d-flex gap-1 justify-content-end">
                                <button wire:click="editUser({{ $user->id }})" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                @if($user->id !== auth()->id())
                                <button wire:click="toggleActive({{ $user->id }})"
                                        class="btn btn-sm {{ $user->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                        title="{{ $user->is_active ? 'Deactivate' : 'Activate' }}">
                                    <i class="bi bi-{{ $user->is_active ? 'slash-circle' : 'check-circle' }}"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
