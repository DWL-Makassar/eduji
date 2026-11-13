<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Requests;

use App\Core\Licensing\LicenseManager;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    private const MAX_ATTEMPTS = 5;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ];
    }

    /** @throws ValidationException */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if ($this->usesPublicPasswordOnline()) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages(['email' => __('auth.public_password')]);
        }

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /** Sandi demo publik di luar localhost: siapa pun bisa menebaknya, jadi ditolak (kecuali situs demo). */
    private function usesPublicPasswordOnline(): bool
    {
        return ! config('platform.demo.enabled')
            && hash_equals((string) config('platform.admin.public_password'), (string) $this->input('password'))
            && ! LicenseManager::isLocal($this->getHost(), $this->ip());
    }

    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout($this));

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($this->throttleKey()), 'minutes' => ceil(RateLimiter::availableIn($this->throttleKey()) / 60)]),
        ]);
    }

    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
