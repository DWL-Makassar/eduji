<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Controllers;

use App\Core\Auth\Http\Requests\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LoginController
{
    public function create(): Response
    {
        // Situs demo: kredensial admin demo ditampilkan agar calon pembeli bisa langsung mencoba.
        return Inertia::render('auth/Login', [
            'demoAccount' => config('platform.demo.enabled')
                ? ['email' => config('platform.admin.email'), 'password' => config('platform.admin.password') ?: 'password']
                : null,
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = $request->user();
        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        if ($user->locale) {
            $request->session()->put('locale', $user->locale);
        }

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login');
    }
}
