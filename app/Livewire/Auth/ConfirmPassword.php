<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.auth')]
class ConfirmPassword extends Component
{
    public string $password = '';

    /**
     * Confirm the current user's password.
     *
     * Uses a direct hash check against the authenticated user instead of
     * Auth::validate(email, ...) so users without an email address
     * (e.g. committee accounts, Guest accounts) can still confirm.
     */
    public function confirmPassword(): void
    {
        $this->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Hash::check($this->password, Auth::user()->getAuthPassword())) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        session(['auth.password_confirmed_at' => time()]);

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}
