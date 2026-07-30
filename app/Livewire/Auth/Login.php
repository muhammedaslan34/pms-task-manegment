<?php

namespace App\Livewire\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Login extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    protected function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function login(): void
    {
        $validated = $this->validate();

        $this->ensureIsNotRateLimited($validated['email']);

        if (! Auth::attempt(['email' => $validated['email'], 'password' => $validated['password']], $this->remember)) {
            RateLimiter::hit($this->throttleKey($validated['email']), 60);

            throw ValidationException::withMessages([
                'email' => __('The provided credentials do not match our records.'),
            ]);
        }

        RateLimiter::clear($this->throttleKey($validated['email']));
        request()->session()->regenerate();

        $this->redirectIntended(route('admin.tasks.index'), navigate: false);
    }

    protected function ensureIsNotRateLimited(string $email): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($email), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey($email));

        throw ValidationException::withMessages([
            'email' => __('Too many login attempts. Please try again in :seconds seconds.', ['seconds' => $seconds]),
        ]);
    }

    protected function throttleKey(string $email): string
    {
        return Str::transliterate(Str::lower($email) . '|' . request()->ip());
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.auth.login')->title(__('Support Login'));
    }
}
