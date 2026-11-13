<?php

declare(strict_types=1);

namespace App\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Urutan pilihan bahasa: pilihan di sesi (switch UI) → preferensi user → default aplikasi.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $available = array_keys(config('platform.locales'));

        $locale = collect([$request->session()->get('locale'), $request->user()?->locale])
            ->first(fn ($candidate) => in_array($candidate, $available, true), config('app.locale'));

        app()->setLocale($locale);

        return $next($request);
    }
}
