{{-- resources/views/livewire/admin/store-manager.blade.php --}}

<div>
    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div>
            <h5 class="fw-bold mb-0" style="color:#1B3A5C;">Store Management</h5>
            <p class="text-muted mb-0" style="font-size:.78rem;">
                Manage stores and assign user access
            </p>
        </div>
        <button wire:click="openCreate" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Add Store
        </button>
    </div>

    {{-- ── CREATE / EDIT FORM ── --}}
    @if($showForm)
    <div class="card border-0 shadow-sm rounded-3 mb-3"
         style="border-left: 4px solid #0D6E56 !important;">
        <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0" style="color:#1B3A5C;">
                {{ $editingId ? 'Edit Store' : 'Add New Store' }}
            </h6>
            <button wire:click="cancelForm" class="btn-close"></button>
        </div>
        <div class="card-body">
            <div class="row g-3">
                {{-- Code --}}
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label fw-semibold small">
                        Store Code <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                           wire:model="code"
                           class="form-control form-control-sm @error('code') is-invalid @enderror"
                           placeholder="S101"
                           {{ $editingId ? 'readonly' : '' }}
                           style="text-transform:uppercase;">
                    @error('code')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text" style="font-size:.7rem;">
                        Letters and numbers only (e.g. S101, ST02)
                    </div>
                </div>

                {{-- Name --}}
                <div class="col-12 col-sm-6 col-md-4">
                    <label class="form-label fw-semibold small">
                        Store Name <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                           wire:model="name"
                           class="form-control form-control-sm @error('name') is-invalid @enderror"
                           placeholder="Main Branch - Riyadh">
                    @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Folder path --}}
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold small">Folder Path</label>
                    <input type="text"
                           wire:model="folder_path"
                           class="form-control form-control-sm"
                           placeholder="D:/shared/S101">
                    <div class="form-text" style="font-size:.7rem;">
                        Leave blank — auto-detected from INVOICE_BASE_PATH
                    </div>
                </div>

                {{-- Active --}}
                <div class="col-12 col-md-1 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox"
                               wire:model="is_active" id="storeActive">
                        <label class="form-check-label small" for="storeActive">Active</label>
                    </div>
                </div>

                {{-- Buttons --}}
                <div class="col-12 d-flex gap-2">
                    <button wire:click="saveStore"
                            class="btn btn-primary btn-sm"
                            wire:loading.attr="disabled"
                            wire:target="saveStore">
                        <span wire:loading.remove wire:target="saveStore">
                            <i class="bi bi-check-lg me-1"></i>
                            {{ $editingId ? 'Update Store' : 'Create Store' }}
                        </span>
                        <span wire:loading wire:target="saveStore">
                            <span class="spinner-border spinner-border-sm me-1"></span>Saving...
                        </span>
                    </button>
                    <button wire:click="cancelForm" class="btn btn-outline-secondary btn-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ── STORES TABLE ── --}}
    <div class="card table-card">
        {{-- Search --}}
        <div class="card-header bg-white border-0 pt-3 pb-2">
            <div class="input-group input-group-sm" style="max-width:280px;">
                <span class="input-group-text bg-white border-end-0">
                    <i class="bi bi-search text-muted" style="font-size:.8rem;"></i>
                </span>
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       class="form-control border-start-0 ps-0"
                       placeholder="Search stores...">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Code</th>
                        <th>Store Name</th>
                        <th class="d-none d-md-table-cell">Folder Path</th>
                        <th class="text-center">Invoices</th>
                        <th class="text-center">Users</th>
                        <th class="text-center">Status</th>
                        <th class="pe-3 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stores as $store)
                    <tr>
                        <td class="ps-3">
                            <span class="badge bg-primary fs-6 fw-bold px-2">
                                {{ $store->code }}
                            </span>
                        </td>
                        <td>
                            <div class="fw-semibold" style="font-size:.85rem;">{{ $store->name }}</div>
                        </td>
                        <td class="d-none d-md-table-cell">
                            <code style="font-size:.72rem;color:#6c757d;">
                                {{ $store->folder_path ?? '—' }}
                            </code>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border">
                                {{ number_format($store->invoices_count) }}
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge {{ $store->users_count > 0 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-light text-muted border' }}">
                                {{ $store->users_count }}
                                {{ $store->users_count === 1 ? 'user' : 'users' }}
                            </span>
                        </td>
                        <td class="text-center">
                            @if($store->is_active)
                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                Active
                            </span>
                            @else
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                Inactive
                            </span>
                            @endif
                        </td>
                        <td class="pe-3">
                            <div class="d-flex gap-1 justify-content-end">
                                {{-- Assign Users --}}
                                <button wire:click="openAssign({{ $store->id }})"
                                        class="btn btn-sm btn-outline-success"
                                        title="Assign Users">
                                    <i class="bi bi-people"></i>
                                </button>
                                {{-- Edit --}}
                                <button wire:click="editStore({{ $store->id }})"
                                        class="btn btn-sm btn-outline-primary"
                                        title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                {{-- Toggle Active --}}
                                <button wire:click="toggleStoreActive({{ $store->id }})"
                                        class="btn btn-sm {{ $store->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                        title="{{ $store->is_active ? 'Deactivate' : 'Activate' }}">
                                    <i class="bi bi-{{ $store->is_active ? 'slash-circle' : 'check-circle' }}"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-shop" style="font-size:2.5rem;opacity:.2;display:block;margin-bottom:.5rem;"></i>
                            <div class="fw-semibold">No stores found</div>
                            <div style="font-size:.8rem;">
                                Stores are created automatically when invoices are scanned,<br>
                                or you can add them manually above.
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ══ USER ASSIGNMENT MODAL ══ --}}
    @if($showAssignModal)
    <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.45);">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content border-0 shadow-lg rounded-3">

                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-bold" style="color:#1B3A5C;">
                            <i class="bi bi-people me-2"></i>Assign Users
                        </h5>
                        <p class="text-muted mb-0" style="font-size:.78rem;">
                            {{ $assignStoreName }}
                        </p>
                    </div>
                    <button wire:click="closeAssignModal" class="btn-close ms-auto"></button>
                </div>

                <div class="modal-body py-3">

                    @if($nonAdminUsers->isEmpty())
                    <div class="text-center text-muted py-4" style="font-size:.84rem;">
                        <i class="bi bi-person-x fs-3 d-block mb-2 opacity-50"></i>
                        No regular users found.<br>
                        <a href="{{ route('admin.users') }}" class="text-decoration-none">
                            Create users first →
                        </a>
                    </div>
                    @else

                    <p class="small text-muted mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        Toggle access for each user. Admins always have access to all stores.
                    </p>

                    <div style="max-height:340px;overflow-y:auto;">
                        @foreach($nonAdminUsers as $user)
                        @php $isAssigned = in_array($user->id, $assignedUsers); @endphp
                        <div class="d-flex align-items-center justify-content-between p-2 rounded-3 mb-1"
                             style="background:{{ $isAssigned ? '#f0fff4' : '#f8f9fa' }};border:1px solid {{ $isAssigned ? '#86efac' : '#e9ecef' }};">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle d-flex align-items-center justify-content-center text-white"
                                     style="width:34px;height:34px;font-size:.78rem;font-weight:700;background:#0D6E56;flex-shrink:0;">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="fw-semibold" style="font-size:.84rem;">{{ $user->name }}</div>
                                    <div class="text-muted" style="font-size:.72rem;">{{ $user->email }}</div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                @if($isAssigned)
                                <span class="badge bg-success-subtle text-success border border-success-subtle"
                                      style="font-size:.68rem;">
                                    <i class="bi bi-check-circle me-1"></i>Assigned
                                </span>
                                @endif
                                <button wire:click="toggleUserAssignment({{ $user->id }})"
                                        class="btn btn-sm {{ $isAssigned ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                        style="font-size:.75rem;padding:.25rem .6rem;"
                                        wire:loading.attr="disabled"
                                        wire:target="toggleUserAssignment({{ $user->id }})">
                                    <span wire:loading.remove wire:target="toggleUserAssignment({{ $user->id }})">
                                        @if($isAssigned)
                                            <i class="bi bi-x-lg me-1"></i>Remove
                                        @else
                                            <i class="bi bi-plus-lg me-1"></i>Assign
                                        @endif
                                    </span>
                                    <span wire:loading wire:target="toggleUserAssignment({{ $user->id }})">
                                        <span class="spinner-border spinner-border-sm"></span>
                                    </span>
                                </button>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button wire:click="closeAssignModal" class="btn btn-secondary btn-sm">
                        <i class="bi bi-check-lg me-1"></i>Done
                    </button>
                </div>

            </div>
        </div>
    </div>
    @endif

</div>
