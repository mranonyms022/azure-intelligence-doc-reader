<?php
// app/Livewire/Auth/Login.php

namespace App\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Login extends Component
{
    public string $email    = '';
    public string $password = '';
    public bool   $remember = false;
    public string $error    = '';

    protected array $rules = [
        'email'    => 'required|email',
        'password' => 'required|min:6',
    ];

    protected array $messages = [
        'email.required'    => 'Email address is required.',
        'email.email'       => 'Please enter a valid email address.',
        'password.required' => 'Password is required.',
        'password.min'      => 'Password must be at least 6 characters.',
    ];

    public function login(): void
    {
        $this->validate();
        $this->error = '';

        $credentials = [
            'email'    => $this->email,
            'password' => $this->password,
        ];

        if (!Auth::attempt($credentials, $this->remember)) {
            $this->error = 'Invalid email or password. Please try again.';
            $this->password = '';
            return;
        }

        // Check if account is active
        if (!Auth::user()->is_active) {
            Auth::logout();
            $this->error = 'Your account has been deactivated. Contact administrator.';
            return;
        }

        session()->regenerate();

        $this->redirect(route('dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.login')
            ->layout('layouts.guest');
    }
}
