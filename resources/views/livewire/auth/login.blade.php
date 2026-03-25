{{-- resources/views/livewire/auth/login.blade.php --}}

<div>
    <h6 class="fw-semibold mb-4 text-center text-muted">Sign in to your account</h6>

    @if ($error)
        <div class="alert alert-danger py-2 small">
            <i class="bi bi-exclamation-circle me-1"></i>{{ $error }}
        </div>
    @endif

    <form wire:submit="login">

        {{-- Email --}}
        <div class="mb-3">
            <label class="form-label fw-semibold small">Email Address</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope text-muted"></i></span>
                <input type="email" wire:model="email" class="form-control @error('email') is-invalid @enderror"
                    placeholder="admin@invoicedms.com" autofocus>
            </div>
            @error('email')
                <div class="invalid-feedback d-block small">{{ $message }}</div>
            @enderror
        </div>

        {{-- Password --}}
        <div class="mb-3">
            <label class="form-label fw-semibold small">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock text-muted"></i></span>
                <input type="password" wire:model="password"
                    class="form-control @error('password') is-invalid @enderror" placeholder="••••••••">
            </div>
            @error('password')
                <div class="invalid-feedback d-block small">{{ $message }}</div>
            @enderror
        </div>

        {{-- Remember me --}}
        <div class="mb-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" wire:model="remember" id="remember">
                <label class="form-check-label small text-muted" for="remember">
                    Keep me signed in
                </label>
            </div>
        </div>

        {{-- Submit --}}
        <button type="submit" class="btn btn-login w-100 rounded-3" wire:loading.attr="disabled">
            <span wire:loading.remove>
                <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
            </span>
            <span wire:loading>
                <span class="spinner-border spinner-border-sm me-2"></span>Signing in...
            </span>
        </button>

    </form>

    {{-- Demo credentials hint
    <div class="mt-4 p-3 bg-light rounded-3 small text-muted">
        <div class="fw-semibold mb-1"><i class="bi bi-info-circle me-1"></i>Demo credentials</div>
        <div>Admin: <code>admin@invoicedms.com</code> / <code>admin123</code></div>
        <div>User: <code>user@invoicedms.com</code> / <code>user123</code></div>
    </div> --}}
</div>
